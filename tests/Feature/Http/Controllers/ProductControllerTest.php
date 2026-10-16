<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->assertSee('Catálogo Teste')
            ->assertSee('Todos os produtos')
            ->assertSee('O que você procura?')
            ->assertSee('Camisa Essencial')
            ->assertSee('R$ 129,90')
            ->assertSee('Adicionar ao carrinho')
            ->assertSee('Imagem indisponível')
            ->assertSee('aria-label="Carrinho com 2 itens"', false)
            ->assertSee('action="'.route('cart.add').'"', false)
            ->assertSee("submitting ? 'Adicionando...' : 'Adicionar ao carrinho'", false)
            ->assertDontSee('>Buscar</button>', false)
            ->assertDontSee('data-catalog-spacer', false)
            ->assertDontSee('Todas categorias')
            ->assertDontSee('Calça Indisponível');
    }

    public static function productSortOrders(): array
    {
        return [
            'name ascending' => ['name_asc', ['Produto A', 'Produto B', 'Produto C']],
            'name descending' => ['name_desc', ['Produto C', 'Produto B', 'Produto A']],
            'price ascending' => ['price_asc', ['Produto C', 'Produto A', 'Produto B']],
            'price descending' => ['price_desc', ['Produto B', 'Produto A', 'Produto C']],
            'unknown sort falls back safely' => ['name; DROP TABLE products', ['Produto A', 'Produto B', 'Produto C']],
        ];
    }

    #[DataProvider('productSortOrders')]
    public function test_catalog_sorts_products_using_only_supported_options(string $sort, array $expectedOrder): void
    {
        $this->insertProduct(10, 'A-001', 'Produto A', '20.00');
        $this->insertProduct(20, 'B-001', 'Produto B', '30.00');
        $this->insertProduct(30, 'C-001', 'Produto C', '10.00');

        $response = $this->get(route('products.index', ['sort' => $sort]));

        $response->assertOk()
            ->assertSeeInOrder($expectedOrder);
    }

    public function test_relevance_prioritizes_exact_sku_name_and_prefix_matches(): void
    {
        $this->insertProduct(10, 'CAM', 'Produto por código', '10.00');
        $this->insertProduct(20, 'EXATO', 'Cam', '20.00');
        $this->insertProduct(30, 'PREFIXO', 'Camisa básica', '30.00');
        $this->insertProduct(40, 'CONTEM', 'Minha camisa', '40.00');

        $response = $this->get(route('products.index', ['search' => 'cam', 'sort' => 'relevance']));

        $response->assertOk()
            ->assertSeeInOrder(['Produto por código', 'Cam', 'Camisa básica', 'Minha camisa']);
    }

    public function test_catalog_escapes_business_name_in_header(): void
    {
        DB::table('businesses')->where('id', 1)->update([
            'trade_name' => '<script>alert("catalog")</script>',
        ]);

        $response = $this->get(route('products.index'));

        $response->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("catalog")</script>', false);
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
