<?php

namespace Tests\Feature;

use App\Services\CatalogCredentialCipher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MultiBusinessCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config()->set('services.mini_erp.url', 'http://erp.test');
        config()->set('services.mini_erp.product_photo_urls.r2_products', 'https://images.example.test');

        DB::table('businesses')->where('id', 1)->update([
            'trade_name' => 'Empresa Alfa',
            'catalog_slug' => 'empresa-alfa',
        ]);
        DB::table('businesses')->insert([
            'id' => 2,
            'trade_name' => 'Empresa Beta',
            'catalog_slug' => 'empresa-beta',
            'catalog_api_token' => app(CatalogCredentialCipher::class)->encryptToken('token-beta'),
            'catalog_api_token_expires_at' => now()->addDay(),
        ]);

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('sku');
            $table->string('name');
            $table->decimal('sale_price', 15, 2);
            $table->boolean('active')->default(true);
            $table->string('photo_path')->nullable();
            $table->string('photo_disk')->nullable();
        });
        Schema::create('persons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('name');
            $table->string('type');
            $table->boolean('active')->default(true);
        });

        $this->insertProduct(10, 1, 'Produto Alfa', '10.00');
        $this->insertProduct(20, 2, 'Produto Beta', '20.00');
        DB::table('persons')->insert([
            ['id' => 101, 'business_id' => 1, 'name' => 'Cliente Alfa', 'type' => 'customer', 'active' => true],
            ['id' => 102, 'business_id' => 1, 'name' => 'Vendedor Alfa', 'type' => 'seller', 'active' => true],
            ['id' => 201, 'business_id' => 2, 'name' => 'Cliente Beta', 'type' => 'customer', 'active' => true],
            ['id' => 202, 'business_id' => 2, 'name' => 'Vendedor Beta', 'type' => 'seller', 'active' => true],
        ]);
    }

    public function test_unknown_catalog_urls_return_404(): void
    {
        $this->get('/')->assertNotFound();
        $this->get('/empresa-inexistente')->assertNotFound();
        $this->get('/empresa-inexistente/produtos')->assertNotFound();
    }

    public function test_each_catalog_displays_only_its_products_prices_and_photos(): void
    {
        $this->get(route('products.index', ['catalog' => 'empresa-alfa']))
            ->assertOk()
            ->assertSee('Empresa Alfa')
            ->assertSee('Produto Alfa')
            ->assertSee('R$ 10,00')
            ->assertSee('https://images.example.test/businesses/1/products/produto.jpg')
            ->assertDontSee('Empresa Beta')
            ->assertDontSee('Produto Beta')
            ->assertDontSee('R$ 20,00');

        $this->get(route('products.index', ['catalog' => 'empresa-beta']))
            ->assertOk()
            ->assertSee('Empresa Beta')
            ->assertSee('Produto Beta')
            ->assertSee('R$ 20,00')
            ->assertSee('https://images.example.test/businesses/2/products/produto.jpg')
            ->assertDontSee('Empresa Alfa')
            ->assertDontSee('Produto Alfa')
            ->assertDontSee('R$ 10,00');
    }

    public function test_carts_are_separated_and_reject_products_from_another_business(): void
    {
        $this->post(route('cart.add', ['catalog' => 'empresa-alfa']), ['product_id' => 10])
            ->assertRedirect(route('products.index', ['catalog' => 'empresa-alfa']));
        $this->post(route('cart.add', ['catalog' => 'empresa-beta']), ['product_id' => 20])
            ->assertRedirect(route('products.index', ['catalog' => 'empresa-beta']));

        $this->get(route('cart.index', ['catalog' => 'empresa-alfa']))
            ->assertOk()
            ->assertSee('Produto Alfa')
            ->assertDontSee('Produto Beta');
        $this->get(route('cart.index', ['catalog' => 'empresa-beta']))
            ->assertOk()
            ->assertSee('Produto Beta')
            ->assertDontSee('Produto Alfa');

        $this->from(route('products.index', ['catalog' => 'empresa-beta']))
            ->post(route('cart.add', ['catalog' => 'empresa-beta']), ['product_id' => 10])
            ->assertSessionHasErrors('product_id');

        $this->assertSame([10 => 1], session('catalog.1.cart'));
        $this->assertSame([20 => 1], session('catalog.2.cart'));
    }

    public function test_guest_orders_use_the_catalog_business_token_and_ignore_manipulated_business_id(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::sequence()
                ->push(['id' => 101, 'number' => 'VEN-000101', 'total' => '10.00', 'created' => true], 201)
                ->push(['id' => 202, 'number' => 'VEN-000202', 'total' => '20.00', 'created' => true], 201),
        ]);
        $this->post(route('cart.add', ['catalog' => 'empresa-alfa']), ['product_id' => 10]);
        $this->post(route('cart.add', ['catalog' => 'empresa-beta']), ['product_id' => 20]);

        $this->post(route('checkout.store', ['catalog' => 'empresa-alfa']), ['business_id' => 2])
            ->assertRedirect(route('checkout.completed', ['catalog' => 'empresa-alfa']));
        $this->post(route('checkout.store', ['catalog' => 'empresa-beta']), ['business_id' => 1])
            ->assertRedirect(route('checkout.completed', ['catalog' => 'empresa-beta']));

        $requests = Http::recorded()->pluck(0)->values();
        $this->assertCount(2, $requests);
        $this->assertRequestUsesBusiness($requests[0], 'catalog-token', 1, 10);
        $this->assertRequestUsesBusiness($requests[1], 'token-beta', 2, 20);
    }

    public function test_checkout_rejects_a_cart_with_products_from_different_businesses(): void
    {
        Http::preventStrayRequests();

        $this->withSession(['catalog.1.cart' => [10 => 1, 20 => 1]])
            ->post(route('checkout.store', ['catalog' => 'empresa-alfa']))
            ->assertSessionHas('error', 'Um ou mais produtos do carrinho não estão disponíveis.');

        Http::assertNothingSent();
        $this->assertSame([10 => 1, 20 => 1], session('catalog.1.cart'));
    }

    public function test_checkout_rejects_people_from_another_business_before_calling_the_api(): void
    {
        Http::preventStrayRequests();
        $this->post(route('cart.add', ['catalog' => 'empresa-alfa']), ['product_id' => 10]);

        $this->post(route('checkout.store', ['catalog' => 'empresa-alfa']), [
            'customer_id' => 201,
            'seller_id' => 202,
        ])->assertSessionHasErrors(['customer_id', 'seller_id']);

        Http::assertNothingSent();
        $this->assertSame([10 => 1], session('catalog.1.cart'));
    }

    public function test_checkout_does_not_send_an_order_with_an_expired_business_token(): void
    {
        DB::table('businesses')->where('id', 1)->update([
            'catalog_api_token_expires_at' => now()->subMinute(),
        ]);
        Http::preventStrayRequests();
        $this->post(route('cart.add', ['catalog' => 'empresa-alfa']), ['product_id' => 10]);

        $this->post(route('checkout.store', ['catalog' => 'empresa-alfa']))
            ->assertSessionHas('error', 'A integração desta empresa com o Mini ERP não está configurada.');

        Http::assertNothingSent();
        $this->assertSame([10 => 1], session('catalog.1.cart'));
    }

    private function insertProduct(int $id, int $businessId, string $name, string $price): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'business_id' => $businessId,
            'sku' => "SKU-{$id}",
            'name' => $name,
            'sale_price' => $price,
            'active' => true,
            'photo_path' => "businesses/{$businessId}/products/produto.jpg",
            'photo_disk' => 'r2_products',
        ]);
    }

    private function assertRequestUsesBusiness(Request $request, string $token, int $businessId, int $productId): void
    {
        $this->assertTrue($request->hasHeader('Authorization', "Bearer {$token}"));
        $this->assertTrue($request->hasHeader('X-Business-ID', (string) $businessId));
        $this->assertSame(
            [['product_id' => $productId, 'quantity' => 1]],
            $request->data()['items'],
        );
        $this->assertArrayNotHasKey('business_id', $request->data());
    }
}
