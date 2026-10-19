<?php

namespace Tests\Feature\Http\Controllers;

use App\Http\Middleware\EnsureCatalogConsumerBelongsToBusiness;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Schema::connection('erp_read')->create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(1);
            $table->string('sku');
            $table->string('name');
            $table->decimal('sale_price', 15, 2);
            $table->boolean('active')->default(true);
            $table->string('photo_path')->nullable();
            $table->string('photo_disk')->nullable();
        });
    }

    public function test_cart_uses_catalog_identity_and_displays_complete_item_summary(): void
    {
        $this->actingAs(User::factory()->create(['business_id' => 1]));
        config()->set('services.mini_erp.product_photo_urls.r2_products', 'https://images.example.test');
        DB::connection('erp_read')->table('products')->insert([
            'id' => 10,
            'sku' => 'CAM-001',
            'name' => 'Camisa Essencial',
            'sale_price' => '49.90',
            'active' => true,
            'photo_path' => 'businesses/1/products/camisa.jpg',
            'photo_disk' => 'r2_products',
        ]);

        $response = $this->withSession(['catalog.1.cart' => [10 => 2]])->get(route('cart.index'));

        $response->assertOk()
            ->assertSee('Catálogo Teste')
            ->assertSee('Meu carrinho')
            ->assertSee('Camisa Essencial')
            ->assertSee('CAM-001')
            ->assertSee('R$ 49,90')
            ->assertSee('R$ 99,80')
            ->assertSee('Continuar comprando')
            ->assertSee('Finalizar pedido')
            ->assertSee('src="https://images.example.test/businesses/1/products/camisa.jpg"', false)
            ->assertSee('aria-label="Carrinho com 2 itens"', false)
            ->assertSee('action="'.route('cart.update').'"', false);
    }

    public function test_empty_cart_displays_empty_state_and_catalog_action(): void
    {
        $this->actingAs(User::factory()->create(['business_id' => 1]));

        $response = $this->get(route('cart.index'));

        $response->assertOk()
            ->assertSee('Seu carrinho está vazio')
            ->assertSee('Explorar produtos')
            ->assertSee('Ver produtos')
            ->assertDontSee('Finalizar pedido');
    }

    public function test_product_from_another_business_cannot_be_added_to_cart(): void
    {
        $this->actingAs(User::factory()->create(['business_id' => 1]));
        DB::connection('erp_read')->table('products')->insert([
            'id' => 20,
            'business_id' => 2,
            'sku' => 'OUTRA-EMPRESA',
            'name' => 'Produto protegido',
            'sale_price' => '99.90',
            'active' => true,
        ]);

        $this->from(route('products.index'))
            ->post(route('cart.add'), ['product_id' => 20])
            ->assertRedirectToRoute('products.index')
            ->assertSessionHasErrors('product_id')
            ->assertSessionMissing('catalog.1.cart');
    }

    public function test_guest_cart_link_and_direct_access_redirect_to_login_with_intended_destination(): void
    {
        $cartUrl = route('cart.index');

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('href="'.$cartUrl.'"', false);

        $response = $this->get($cartUrl);

        $response->assertRedirectToRoute('login')
            ->assertSessionHas('status', EnsureCatalogConsumerBelongsToBusiness::LOGIN_MESSAGE)
            ->assertSessionHas('url.intended', $cartUrl);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(EnsureCatalogConsumerBelongsToBusiness::LOGIN_MESSAGE);
    }

    #[DataProvider('guestCartMutationProvider')]
    public function test_guest_cart_mutations_redirect_to_login_without_changing_cart(
        string $routeName,
        array $payload,
    ): void {
        $response = $this->post(route($routeName), $payload);

        $response->assertRedirectToRoute('login')
            ->assertSessionHas('status', EnsureCatalogConsumerBelongsToBusiness::LOGIN_MESSAGE)
            ->assertSessionMissing('catalog.1.cart');
    }

    public function test_consumer_from_another_business_cannot_access_the_cart(): void
    {
        DB::connection('erp_read')->table('businesses')->insert([
            'id' => 2,
            'trade_name' => 'Segunda Loja',
            'catalog_slug' => 'segunda-loja',
            'catalog_api_token' => null,
            'catalog_api_token_expires_at' => null,
        ]);
        $this->actingAs(User::factory()->create(['business_id' => 1]));
        $cartUrl = route('cart.index', ['catalog' => 'segunda-loja']);

        $response = $this->get($cartUrl);

        $response->assertRedirect(route('login', ['catalog' => 'segunda-loja']))
            ->assertSessionHas('status', EnsureCatalogConsumerBelongsToBusiness::LOGIN_MESSAGE)
            ->assertSessionHas('url.intended', $cartUrl);
    }

    public function test_cart_does_not_leak_between_consumer_sessions(): void
    {
        DB::connection('erp_read')->table('products')->insert([
            'id' => 10,
            'sku' => 'PRIVATE-001',
            'name' => 'Produto do primeiro cliente',
            'sale_price' => '49.90',
            'active' => true,
        ]);
        $firstConsumer = User::factory()->create(['business_id' => 1]);
        $secondConsumer = User::factory()->create(['business_id' => 1]);

        $this->actingAs($firstConsumer)
            ->withSession(['catalog.1.cart' => [10 => 1]])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Produto do primeiro cliente');

        session()->invalidate();
        Auth::forgetGuards();

        $this->actingAs($secondConsumer)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Seu carrinho está vazio')
            ->assertDontSee('Produto do primeiro cliente');
    }

    public static function guestCartMutationProvider(): array
    {
        return [
            'add product' => ['cart.add', ['product_id' => 10]],
            'change quantity' => ['cart.update', ['product_id' => 10, 'action' => 'increase']],
            'remove product' => ['cart.update', ['product_id' => 10, 'action' => 'remove']],
        ];
    }
}
