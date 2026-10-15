<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config()->set('services.mini_erp', [
            'url' => 'http://erp.test',
            'token' => 'catalog-token',
            'business_id' => 1,
            'timeout' => 10,
            'connect_timeout' => 3,
        ]);

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(1);
            $table->string('sku');
            $table->string('name');
            $table->decimal('sale_price', 15, 2);
            $table->boolean('active')->default(true);
        });
        Schema::create('persons', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(1);
            $table->string('name');
            $table->string('type');
            $table->boolean('active')->default(true);
        });
    }

    public function test_checkout_displays_enabled_confirmation_and_keeps_external_order_id(): void
    {
        $this->insertProduct();

        $response = $this->withSession(['cart' => [10 => 2]])
            ->get(route('checkout.index'));

        $response->assertOk()
            ->assertSee('CATÁLOGO')
            ->assertSee('Dados do pedido')
            ->assertSee('Resumo do pedido')
            ->assertSee('Confirmar pedido')
            ->assertSee('type="submit"', false)
            ->assertSee('x-bind:disabled="submitting"', false)
            ->assertSessionHas('checkout.external_order_id', fn (string $id): bool => str($id)->isUuid());
    }

    public function test_checkout_only_displays_people_from_configured_business(): void
    {
        $this->insertProduct();
        $this->insertPeople();
        DB::table('persons')->insert([
            'id' => 40,
            'business_id' => 2,
            'name' => 'Pessoa de outra empresa',
            'type' => 'customer',
            'active' => true,
        ]);

        $this->withSession(['cart' => [10 => 1]])
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Cliente de teste')
            ->assertSee('Vendedor de teste')
            ->assertDontSee('Pessoa de outra empresa');
    }

    public function test_confirming_order_sends_only_ids_and_quantities_then_clears_cart(): void
    {
        $this->insertProduct();
        $this->insertPeople();
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::response([
                'id' => 91,
                'number' => 'VEN-000091',
                'total' => '99.80',
                'created' => true,
            ], 201),
        ]);
        $externalOrderId = 'cf936d6b-471d-4ef5-981e-154b825daf45';

        $response = $this->withSession([
            'cart' => [10 => 2],
            'checkout.external_order_id' => $externalOrderId,
        ])->post(route('checkout.store'), [
            'customer_id' => 20,
            'seller_id' => 30,
        ]);

        $response->assertRedirectToRoute('checkout.completed')
            ->assertSessionMissing('cart')
            ->assertSessionMissing('checkout.external_order_id')
            ->assertSessionHas('checkout.completed_order', fn (array $order): bool => $order === [
                'id' => 91,
                'number' => 'VEN-000091',
                'total' => '99.80',
                'created' => true,
            ]);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://erp.test/api/catalog/sales'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer catalog-token')
            && $request->hasHeader('X-Business-ID', '1')
            && $request->data() === [
                'external_order_id' => $externalOrderId,
                'items' => [['product_id' => 10, 'quantity' => 2]],
                'customer_id' => 20,
                'seller_id' => 30,
            ]);

        $this->get(route('checkout.completed'))
            ->assertOk()
            ->assertSee('Compra confirmada com sucesso')
            ->assertSee('VEN-000091')
            ->assertSee('R$ 99,80')
            ->assertSee('Voltar ao catálogo');
    }

    public function test_confirming_without_people_omits_optional_ids(): void
    {
        $this->insertProduct();
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::response([
                'id' => 92,
                'number' => 'VEN-000092',
                'total' => '49.90',
                'created' => true,
            ], 201),
        ]);
        $externalOrderId = '0771dd34-27df-44d5-931e-5f25fffc5664';

        $this->withSession([
            'cart' => [10 => 1],
            'checkout.external_order_id' => $externalOrderId,
        ])->post(route('checkout.store'))->assertRedirectToRoute('checkout.completed');

        Http::assertSent(fn (Request $request): bool => $request->data() === [
            'external_order_id' => $externalOrderId,
            'items' => [['product_id' => 10, 'quantity' => 1]],
        ]);
    }

    public function test_price_change_after_cart_addition_never_sends_stale_price(): void
    {
        $this->insertProduct();
        $checkoutResponse = $this->withSession(['cart' => [10 => 1]])
            ->get(route('checkout.index'));
        $externalOrderId = session('checkout.external_order_id');
        DB::table('products')->where('id', 10)->update(['sale_price' => '75.00']);
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::response([
                'id' => 94,
                'number' => 'VEN-000094',
                'total' => '75.00',
                'created' => true,
            ], 201),
        ]);

        $checkoutResponse->assertOk()->assertSee('R$ 49,90');
        $this->post(route('checkout.store'))->assertRedirectToRoute('checkout.completed');

        Http::assertSent(function (Request $request) use ($externalOrderId): bool {
            $payload = $request->data();

            return $payload === [
                'external_order_id' => $externalOrderId,
                'items' => [['product_id' => 10, 'quantity' => 1]],
            ] && ! array_key_exists('total', $payload)
                && ! array_key_exists('unit_price', $payload['items'][0]);
        });
    }

    public function test_timeout_after_remote_acceptance_preserves_cart_and_reuses_uuid_on_retry(): void
    {
        $this->insertProduct();
        Http::preventStrayRequests();
        $acceptedPayload = null;
        $attempt = 0;
        Http::fake([
            'http://erp.test/api/catalog/sales' => function (Request $request) use (&$acceptedPayload, &$attempt) {
                $attempt++;

                if ($attempt === 1) {
                    $acceptedPayload = $request->data();

                    throw new ConnectionException('timeout after sale creation');
                }

                $this->assertSame($acceptedPayload, $request->data());

                return Http::response([
                    'id' => 93,
                    'number' => 'VEN-000093',
                    'total' => '49.90',
                    'created' => false,
                ]);
            },
        ]);
        $externalOrderId = 'a6ea69bf-1e21-4c7a-bce2-dcba20da478f';

        $failedResponse = $this->withSession([
            'cart' => [10 => 1],
            'checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'));

        $failedResponse->assertRedirectToRoute('checkout.index')
            ->assertSessionHas('cart', [10 => 1])
            ->assertSessionHas('checkout.external_order_id', $externalOrderId)
            ->assertSessionHas('error', 'Não foi possível confirmar o pedido no Mini ERP. Tente novamente.');

        $successfulResponse = $this->post(route('checkout.store'));

        $successfulResponse->assertRedirectToRoute('checkout.completed')
            ->assertSessionMissing('cart')
            ->assertSessionMissing('checkout.external_order_id');
        $sentRequests = Http::recorded();
        $this->assertSame(2, $attempt);
        $this->assertSame($externalOrderId, $acceptedPayload['external_order_id']);
        $this->assertCount(1, $sentRequests);
        $this->assertSame($externalOrderId, $sentRequests[0][0]->data()['external_order_id']);
    }

    public function test_duplicate_confirmation_requests_send_the_same_uuid_safely(): void
    {
        $this->insertProduct();
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::sequence()
                ->push([
                    'id' => 95,
                    'number' => 'VEN-000095',
                    'total' => '99.80',
                    'created' => true,
                ], 201)
                ->push([
                    'id' => 95,
                    'number' => 'VEN-000095',
                    'total' => '99.80',
                    'created' => false,
                ]),
        ]);
        $externalOrderId = '3aef3693-bb35-4d5c-8d86-2c38a20bcf47';
        $requestSession = [
            'cart' => [10 => 2],
            'checkout.external_order_id' => $externalOrderId,
        ];

        $firstResponse = $this->withSession($requestSession)->post(route('checkout.store'));
        $secondResponse = $this->withSession($requestSession)->post(route('checkout.store'));

        $firstResponse->assertRedirectToRoute('checkout.completed');
        $secondResponse->assertRedirectToRoute('checkout.completed');
        $sentRequests = Http::recorded();
        $this->assertCount(2, $sentRequests);
        $this->assertSame($sentRequests[0][0]->data(), $sentRequests[1][0]->data());
        $this->assertSame($externalOrderId, $sentRequests[0][0]->data()['external_order_id']);
    }

    #[DataProvider('erpErrorResponses')]
    public function test_erp_error_preserves_cart_and_uuid(
        int $status,
        array $body,
        string $expectedMessage,
    ): void {
        $this->insertProduct();
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::response($body, $status),
        ]);
        $externalOrderId = 'fd33744d-87f7-49b3-b893-cc0f9e4fa3dc';

        $response = $this->withSession([
            'cart' => [10 => 1],
            'checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'));

        $response->assertRedirectToRoute('checkout.index')
            ->assertSessionHas('cart', [10 => 1])
            ->assertSessionHas('checkout.external_order_id', $externalOrderId)
            ->assertSessionHas('error', $expectedMessage);
        Http::assertSentCount(1);
    }

    public function test_invalid_selection_does_not_call_erp_and_preserves_order(): void
    {
        $this->insertProduct();
        Http::preventStrayRequests();
        $externalOrderId = '3f269194-884d-4c85-be2c-151d92832684';

        $response = $this->withSession([
            'cart' => [10 => 1],
            'checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'), [
            'customer_id' => 'invalid',
        ]);

        $response->assertRedirectToRoute('checkout.index')
            ->assertSessionHasErrors(['customer_id'])
            ->assertSessionHas('cart', [10 => 1])
            ->assertSessionHas('checkout.external_order_id', $externalOrderId);
        Http::assertNothingSent();
    }

    public function test_unavailable_product_does_not_call_erp_or_clear_cart(): void
    {
        $this->insertProduct(active: false);
        Http::preventStrayRequests();
        $externalOrderId = 'c6d89fa5-abfa-4370-a7d2-94acfccb199d';

        $response = $this->withSession([
            'cart' => [10 => 1],
            'checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'));

        $response->assertRedirectToRoute('checkout.index')
            ->assertSessionHas('cart', [10 => 1])
            ->assertSessionHas('checkout.external_order_id', $externalOrderId)
            ->assertSessionHas('error', 'Um ou mais produtos do carrinho não estão disponíveis.');
        Http::assertNothingSent();
    }

    public function test_nonexistent_product_does_not_call_erp_or_clear_cart(): void
    {
        Http::preventStrayRequests();
        $externalOrderId = 'b46fcf20-1bc1-4dc4-bafd-230882a11044';

        $response = $this->withSession([
            'cart' => [999999 => 1],
            'checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'));

        $response->assertRedirectToRoute('checkout.index')
            ->assertSessionHas('cart', [999999 => 1])
            ->assertSessionHas('checkout.external_order_id', $externalOrderId)
            ->assertSessionHas('error', 'Um ou mais produtos do carrinho não estão disponíveis.');
        Http::assertNothingSent();
    }

    public function test_completed_page_requires_a_confirmed_order(): void
    {
        $this->get(route('checkout.completed'))
            ->assertRedirect('/produtos');
    }

    public static function erpErrorResponses(): array
    {
        return [
            '401 unauthorized' => [
                401,
                ['message' => 'Unauthenticated.'],
                'O catálogo não conseguiu se autenticar no Mini ERP.',
            ],
            '403 forbidden' => [
                403,
                ['message' => 'Invalid ability provided.'],
                'O catálogo não possui permissão para registrar vendas no Mini ERP.',
            ],
            '409 conflict' => [
                409,
                ['message' => 'O identificador externo já está vinculado a um pedido diferente.'],
                'O identificador externo já está vinculado a um pedido diferente.',
            ],
            '422 validation' => [
                422,
                ['message' => 'O produto informado é inválido.'],
                'O produto informado é inválido.',
            ],
        ];
    }

    private function insertProduct(bool $active = true): void
    {
        DB::table('products')->insert([
            'id' => 10,
            'sku' => 'SKU-10',
            'name' => 'Produto de teste',
            'sale_price' => '49.90',
            'active' => $active,
        ]);
    }

    private function insertPeople(): void
    {
        DB::table('persons')->insert([
            [
                'id' => 20,
                'name' => 'Cliente de teste',
                'type' => 'customer',
                'active' => true,
            ],
            [
                'id' => 30,
                'name' => 'Vendedor de teste',
                'type' => 'seller',
                'active' => true,
            ],
        ]);
    }
}
