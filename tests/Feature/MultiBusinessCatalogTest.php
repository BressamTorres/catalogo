<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CatalogCredentialCipher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MultiBusinessCatalogTest extends TestCase
{
    private User $alphaUser;

    private User $betaUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config()->set('services.mini_erp.url', 'http://erp.test');
        config()->set('services.mini_erp.product_photo_urls.r2_products', 'https://images.example.test');

        DB::connection('erp_read')->table('businesses')->where('id', 1)->update([
            'trade_name' => 'Empresa Alfa',
            'catalog_slug' => 'empresa-alfa',
        ]);
        DB::connection('erp_read')->table('businesses')->insert([
            'id' => 2,
            'trade_name' => 'Empresa Beta',
            'catalog_slug' => 'empresa-beta',
            'catalog_api_token' => app(CatalogCredentialCipher::class)->encryptToken('token-beta'),
            'catalog_api_token_expires_at' => now()->addDay(),
        ]);

        Schema::connection('erp_read')->create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('sku');
            $table->string('name');
            $table->decimal('sale_price', 15, 2);
            $table->boolean('active')->default(true);
            $table->string('photo_path')->nullable();
            $table->string('photo_disk')->nullable();
        });
        $this->insertProduct(10, 1, 'Produto Alfa', '10.00');
        $this->insertProduct(20, 2, 'Produto Beta', '20.00');

        $this->alphaUser = User::factory()->create([
            'business_id' => 1,
            'name' => 'Cliente Alfa',
            'document' => '52998224725',
            'phone' => '11999999999',
            'email' => 'cliente@alfa.test',
        ]);
        $this->betaUser = User::factory()->create([
            'business_id' => 2,
            'name' => 'Cliente Beta',
            'document' => '11144477735',
            'phone' => '44999999999',
            'email' => 'cliente@beta.test',
        ]);
        $this->actingAs($this->alphaUser);
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
        $this->actingAs($this->alphaUser)
            ->post(route('cart.add', ['catalog' => 'empresa-alfa']), ['product_id' => 10])
            ->assertRedirect(route('products.index', ['catalog' => 'empresa-alfa']));
        $this->actingAs($this->betaUser)
            ->post(route('cart.add', ['catalog' => 'empresa-beta']), ['product_id' => 20])
            ->assertRedirect(route('products.index', ['catalog' => 'empresa-beta']));

        $this->actingAs($this->alphaUser)
            ->get(route('cart.index', ['catalog' => 'empresa-alfa']))
            ->assertOk()
            ->assertSee('Produto Alfa')
            ->assertDontSee('Produto Beta');
        $this->actingAs($this->betaUser)
            ->get(route('cart.index', ['catalog' => 'empresa-beta']))
            ->assertOk()
            ->assertSee('Produto Beta')
            ->assertDontSee('Produto Alfa');

        $this->from(route('products.index', ['catalog' => 'empresa-beta']))
            ->post(route('cart.add', ['catalog' => 'empresa-beta']), ['product_id' => 10])
            ->assertSessionHasErrors('product_id');

        $this->assertSame([10 => 1], session('catalog.1.cart'));
        $this->assertSame([20 => 1], session('catalog.2.cart'));
    }

    public function test_orders_use_the_catalog_business_token_and_ignore_manipulated_business_id(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::sequence()
                ->push(['id' => 101, 'number' => 'VEN-000101', 'total' => '10.00', 'created' => true], 201)
                ->push(['id' => 202, 'number' => 'VEN-000202', 'total' => '20.00', 'created' => true], 201),
        ]);
        $this->actingAs($this->alphaUser)
            ->post(route('cart.add', ['catalog' => 'empresa-alfa']), ['product_id' => 10]);
        $this->actingAs($this->betaUser)
            ->post(route('cart.add', ['catalog' => 'empresa-beta']), ['product_id' => 20]);

        $this->actingAs($this->alphaUser)
            ->post(route('checkout.store', ['catalog' => 'empresa-alfa']), [
                'business_id' => 2,
                'customer_id' => 201,
                'seller_id' => 202,
            ])
            ->assertRedirect(route('checkout.completed', ['catalog' => 'empresa-alfa']));
        $this->actingAs($this->betaUser)
            ->post(route('checkout.store', ['catalog' => 'empresa-beta']), [
                'business_id' => 1,
                'customer_id' => 101,
                'seller_id' => 102,
            ])
            ->assertRedirect(route('checkout.completed', ['catalog' => 'empresa-beta']));

        $requests = Http::recorded()->pluck(0)->values();
        $this->assertCount(2, $requests);
        $this->assertRequestUsesBusiness($requests[0], 'catalog-token', 1, 10, $this->alphaUser);
        $this->assertRequestUsesBusiness($requests[1], 'token-beta', 2, 20, $this->betaUser);
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

    public function test_checkout_ignores_people_ids_from_the_browser(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::response([
                'id' => 303,
                'number' => 'VEN-000303',
                'total' => '10.00',
                'created' => true,
            ], 201),
        ]);
        $this->post(route('cart.add', ['catalog' => 'empresa-alfa']), ['product_id' => 10]);

        $this->post(route('checkout.store', ['catalog' => 'empresa-alfa']), [
            'customer_id' => 201,
            'seller_id' => 202,
        ])->assertRedirect(route('checkout.completed', ['catalog' => 'empresa-alfa']));

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return ! array_key_exists('customer_id', $payload)
                && ! array_key_exists('seller_id', $payload)
                && $payload['customer']['document'] === $this->alphaUser->document;
        });
    }

    public function test_checkout_does_not_send_an_order_with_an_expired_business_token(): void
    {
        DB::connection('erp_read')->table('businesses')->where('id', 1)->update([
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
        DB::connection('erp_read')->table('products')->insert([
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

    private function assertRequestUsesBusiness(
        Request $request,
        string $token,
        int $businessId,
        int $productId,
        User $user,
    ): void {
        $this->assertTrue($request->hasHeader('Authorization', "Bearer {$token}"));
        $this->assertTrue($request->hasHeader('X-Business-ID', (string) $businessId));
        $this->assertSame(
            [['product_id' => $productId, 'quantity' => 1]],
            $request->data()['items'],
        );
        $this->assertArrayNotHasKey('business_id', $request->data());
        $this->assertArrayNotHasKey('customer_id', $request->data());
        $this->assertArrayNotHasKey('seller_id', $request->data());
        $this->assertSame([
            'name' => $user->name,
            'document' => $user->document,
            'phone' => $user->phone,
            'email' => $user->email,
        ], $request->data()['customer']);
    }
}
