<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogNotFoundPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config()->set('app.debug', false);

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
    }

    public function test_root_returns_custom_catalog_not_found_page(): void
    {
        $response = $this->get('/');

        $response->assertNotFound()
            ->assertSee('Catálogo não encontrado')
            ->assertSee('Não encontramos um catálogo disponível neste endereço. Verifique o link informado e tente novamente.')
            ->assertSee('name="viewport"', false)
            ->assertSee(asset('favicon-32x32.png'))
            ->assertSee('max-w-lg', false)
            ->assertSee('sm:px-12', false)
            ->assertDontSee('Laravel')
            ->assertDontSee('Stack trace')
            ->assertDontSee('APP_DEBUG')
            ->assertDontSee('<header', false)
            ->assertDontSee('<footer', false)
            ->assertDontSee('>404<', false)
            ->assertDontSee('Código 404')
            ->assertDontSee('Voltar para a página inicial')
            ->assertDontSee('Precisa de ajuda?');
    }

    public function test_unknown_catalog_returns_the_same_custom_404_page(): void
    {
        $response = $this->get('/loja-inexistente');

        $response->assertNotFound()
            ->assertSee('Catálogo não encontrado')
            ->assertDontSee('Laravel');
    }

    public function test_valid_catalog_continues_to_render_normally(): void
    {
        $response = $this->get('/catalogo-teste');

        $response->assertOk()
            ->assertSee('Catálogo Teste')
            ->assertDontSee('Catálogo não encontrado');
    }

    public function test_internal_database_failure_is_not_rendered_as_missing_catalog(): void
    {
        Schema::connection('erp_read')->drop('products');

        $response = $this->get('/catalogo-teste');

        $response->assertServerError()
            ->assertDontSee('Catálogo não encontrado');
    }

    public function test_authentication_cart_and_checkout_routes_remain_available(): void
    {
        $this->get('/catalogo-teste/entrar')->assertOk();
        $this->get('/catalogo-teste/criar-conta')->assertOk();
        $this->get('/catalogo-teste/carrinho')->assertRedirect('/catalogo-teste/entrar');
        $this->get('/catalogo-teste/checkout')->assertRedirect('/catalogo-teste/entrar');
    }
}
