<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductControllerTest extends TestCase
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

    public function test_catalog_displays_active_products_cart_count_and_available_controls(): void
    {
        $this->insertProduct(10, 'CAM-001', 'Camisa Essencial', '129.90');
        $this->insertProduct(20, 'CAL-001', 'Calça Indisponível', '199.90', false);

        $response = $this->withSession(['catalog.1.cart' => [10 => 2]])->get(route('home'));

        $response->assertOk()
            ->assertSee('Camisa Essencial')
            ->assertSee('R$ 129,90')
            ->assertSee('Comprar')
            ->assertSee('Imagem indisponível')
            ->assertSee('aria-label="Carrinho com 2 itens"', false)
            ->assertSee('action="'.route('cart.add').'"', false)
            ->assertSee("submitting ? 'Buscando...' : 'Buscar'", false)
            ->assertSee("submitting ? 'Adicionando...' : 'Comprar'", false)
            ->assertSee('data-catalog-spacer', false)
            ->assertDontSee('Todas categorias')
            ->assertDontSee('Calça Indisponível');
    }

    public function test_search_filters_existing_products_by_name_or_sku(): void
    {
        $this->insertProduct(10, 'CAM-001', 'Camisa Essencial', '129.90');
        $this->insertProduct(20, 'CAL-002', 'Calça Reta', '199.90');

        $nameResponse = $this->get(route('products.index', ['search' => 'camisa']));
        $skuResponse = $this->get(route('products.index', ['search' => 'CAL-002']));

        $nameResponse->assertOk()
            ->assertSee('Camisa Essencial')
            ->assertDontSee('Calça Reta')
            ->assertSee('value="camisa"', false);
        $skuResponse->assertOk()
            ->assertSee('Calça Reta')
            ->assertDontSee('Camisa Essencial');
    }

    public function test_catalog_only_displays_products_from_configured_business(): void
    {
        $this->insertProduct(10, 'EMP-1', 'Produto da empresa configurada', '10.00');
        $this->insertProduct(20, 'EMP-2', 'Produto de outra empresa', '20.00', businessId: 2);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Produto da empresa configurada')
            ->assertDontSee('Produto de outra empresa');
    }

    public function test_catalog_uses_public_url_configured_for_product_photo_disk(): void
    {
        config()->set('services.mini_erp.product_photo_urls.r2_products', 'https://images.example.test');
        $this->insertProduct(
            10,
            'CAM-001',
            'Camisa Essencial',
            '129.90',
            photoPath: 'businesses/1/products/camisa.jpg',
            photoDisk: 'r2_products',
        );

        $response = $this->get(route('products.index'));

        $response->assertOk()
            ->assertSee('src="https://images.example.test/businesses/1/products/camisa.jpg"', false)
            ->assertSee('alt="Camisa Essencial"', false);
    }

    public function test_catalog_does_not_expose_photo_path_when_disk_has_no_public_url(): void
    {
        $this->insertProduct(
            10,
            'CAM-001',
            'Camisa Essencial',
            '129.90',
            photoPath: 'http://internal.example.test/private/products/camisa.jpg',
            photoDisk: 'private',
        );

        $response = $this->get(route('products.index'));

        $response->assertOk()
            ->assertSee('Imagem indisponível')
            ->assertDontSee('internal.example.test');
    }

    public function test_catalog_shows_specific_empty_state_for_search_without_results(): void
    {
        $this->insertProduct(10, 'CAM-001', 'Camisa Essencial', '129.90');

        $response = $this->get(route('products.index', ['search' => 'vestido']));

        $response->assertOk()
            ->assertSee('Nenhum produto encontrado')
            ->assertSee('Limpar pesquisa')
            ->assertDontSee('Camisa Essencial');
    }

    public function test_catalog_displays_error_feedback_without_hiding_the_catalog(): void
    {
        $response = $this->withSession(['error' => 'Não foi possível carregar a solicitação.'])
            ->get(route('products.index'));

        $response->assertOk()
            ->assertSee('Não foi possível carregar a solicitação.')
            ->assertSee('role="alert"', false)
            ->assertSee('Catálogo sem produtos disponíveis');
    }

    private function insertProduct(
        int $id,
        string $sku,
        string $name,
        string $salePrice,
        bool $active = true,
        ?string $photoPath = null,
        ?string $photoDisk = null,
        int $businessId = 1,
    ): void {
        DB::table('products')->insert([
            'id' => $id,
            'business_id' => $businessId,
            'sku' => $sku,
            'name' => $name,
            'sale_price' => $salePrice,
            'active' => $active,
            'photo_path' => $photoPath,
            'photo_disk' => $photoDisk,
        ]);
    }
}
