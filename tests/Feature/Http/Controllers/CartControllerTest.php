<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        Schema::create('products', function (Blueprint $table): void {
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
        config()->set('services.mini_erp.product_photo_urls.r2_products', 'https://images.example.test');
        DB::table('products')->insert([
            'id' => 10,
            'sku' => 'CAM-001',
            'name' => 'Camisa Essencial',
            'sale_price' => '49.90',
            'active' => true,
            'photo_path' => 'products/camisa.jpg',
            'photo_disk' => 'r2_products',
        ]);

        $response = $this->withSession(['cart' => [10 => 2]])->get(route('cart.index'));

        $response->assertOk()
            ->assertSee('CATÁLOGO')
            ->assertSee('Meu carrinho')
            ->assertSee('Camisa Essencial')
            ->assertSee('CAM-001')
            ->assertSee('R$ 49,90')
            ->assertSee('R$ 99,80')
            ->assertSee('Continuar comprando')
            ->assertSee('Finalizar pedido')
            ->assertSee('src="https://images.example.test/products/camisa.jpg"', false)
            ->assertSee('aria-label="Carrinho com 2 itens"', false)
            ->assertSee('action="'.route('cart.update').'"', false);
    }

    public function test_empty_cart_displays_empty_state_and_catalog_action(): void
    {
        $response = $this->get(route('cart.index'));

        $response->assertOk()
            ->assertSee('Seu carrinho está vazio')
            ->assertSee('Ver produtos')
            ->assertDontSee('Finalizar pedido');
    }

    public function test_product_from_another_business_cannot_be_added_to_cart(): void
    {
        DB::table('products')->insert([
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
            ->assertSessionMissing('cart');
    }
}
