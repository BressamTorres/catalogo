<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
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
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->user = User::factory()->create([
            'business_id' => 1,
            'name' => 'Cliente do Catálogo',
            'document' => '52998224725',
            'phone' => '11999999999',
            'email' => 'cliente@example.com',
        ]);
        $this->actingAs($this->user);
        config()->set('services.mini_erp', [
            'url' => 'http://erp.test',
            'credentials_key' => 'base64:dGVzdC1jYXRhbG9nLWNyZWRlbnRpYWwta2V5LTEyMzQ=',
            'timeout' => 10,
            'connect_timeout' => 3,
        ]);

        Schema::connection('erp_read')->create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('business_id')->default(1);
            $table->string('sku');
            $table->string('name');
            $table->decimal('sale_price', 15, 2);
            $table->boolean('active')->default(true);
        });
    }

    public function test_checkout_displays_enabled_confirmation_and_keeps_external_order_id(): void
    {
        $this->insertProduct();

        $response = $this->withSession(['catalog.1.cart' => [10 => 2]])
            ->get(route('checkout.index'));

        $response->assertOk()
            ->assertSee('Catálogo Teste')
            ->assertSee('Dados do cliente')
            ->assertSee('Cliente do Catálogo')
            ->assertSee('52998224725')
            ->assertSee('11999999999')
            ->assertSee('cliente@example.com')
            ->assertSee('Resumo do pedido')
            ->assertSee('Confirmar pedido')
            ->assertSee(asset('favicon-32x32.png'))
            ->assertSee('type="submit"', false)
            ->assertDontSee('name="customer_id"', false)
            ->assertDontSee('name="seller_id"', false)
            ->assertSee('x-bind:disabled="submitting"', false)
            ->assertSessionHas('catalog.1.checkout.external_order_id', fn (string $id): bool => str($id)->isUuid());
    }

    public function test_checkout_does_not_query_or_display_erp_people_selectors(): void
    {
        $this->insertProduct();

        $this->withSession(['catalog.1.cart' => [10 => 1]])
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('O pedido será vinculado automaticamente à sua conta.')
            ->assertDontSee('Consumidor Final')
            ->assertDontSee('Vendedor Padrão');
    }

    public function test_confirming_order_sends_authenticated_customer_and_ignores_browser_ids(): void
    {
        $this->insertProduct();
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
            'catalog.1.cart' => [10 => 2],
            'catalog.1.checkout.external_order_id' => $externalOrderId,
        ])->post(route('checkout.store'), [
            'customer_id' => 999,
            'seller_id' => 999,
            'customer' => [
                'name' => 'Cliente adulterado',
                'document' => '11144477735',
                'phone' => '44999999999',
                'email' => 'adulterado@example.com',
            ],
        ]);

        $response->assertRedirectToRoute('checkout.completed')
            ->assertSessionMissing('catalog.1.cart')
            ->assertSessionMissing('catalog.1.checkout.external_order_id')
            ->assertSessionHas('catalog.1.checkout.completed_order', fn (array $order): bool => $order === [
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
                'customer' => $this->customerPayload(),
                'items' => [['product_id' => 10, 'quantity' => 2]],
            ]);

        $this->get(route('checkout.completed'))
            ->assertOk()
            ->assertSee('Compra confirmada com sucesso')
            ->assertSee('VEN-000091')
            ->assertSee('R$ 99,80')
            ->assertSee('Voltar ao catálogo');
    }

    public function test_existing_customer_response_completes_without_sending_people_ids(): void
    {
        $this->insertProduct();
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::response([
                'id' => 92,
                'number' => 'VEN-000092',
                'total' => '49.90',
                'created' => false,
            ]),
        ]);
        $externalOrderId = '0771dd34-27df-44d5-931e-5f25fffc5664';

        $this->withSession([
            'catalog.1.cart' => [10 => 1],
            'catalog.1.checkout.external_order_id' => $externalOrderId,
        ])->post(route('checkout.store'))->assertRedirectToRoute('checkout.completed');

        Http::assertSent(fn (Request $request): bool => $request->data() === [
            'external_order_id' => $externalOrderId,
            'customer' => $this->customerPayload(),
            'items' => [['product_id' => 10, 'quantity' => 1]],
        ] && ! array_key_exists('customer_id', $request->data())
            && ! array_key_exists('seller_id', $request->data()));
    }

    public function test_price_change_after_cart_addition_never_sends_stale_price(): void
    {
        $this->insertProduct();
        $checkoutResponse = $this->withSession(['catalog.1.cart' => [10 => 1]])
            ->get(route('checkout.index'));
        $externalOrderId = session('catalog.1.checkout.external_order_id');
        DB::connection('erp_read')->table('products')->where('id', 10)->update(['sale_price' => '75.00']);
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
                'customer' => $this->customerPayload(),
                'items' => [['product_id' => 10, 'quantity' => 1]],
            ] && ! array_key_exists('total', $payload)
                && ! array_key_exists('unit_price', $payload['items'][0]);
        });
    }

    public function test_confirming_order_preserves_multiple_products_and_quantities(): void
    {
        $this->insertProduct();
        $this->insertProduct(id: 11, price: '15.00');
        Http::preventStrayRequests();
        Http::fake([
            'http://erp.test/api/catalog/sales' => Http::response([
                'id' => 96,
                'number' => 'VEN-000096',
                'total' => '144.80',
                'created' => true,
            ], 201),
        ]);

        $this->withSession(['catalog.1.cart' => [10 => 2, 11 => 3]])
            ->post(route('checkout.store'))
            ->assertRedirectToRoute('checkout.completed');

        Http::assertSent(fn (Request $request): bool => $request->data()['items'] === [
            ['product_id' => 10, 'quantity' => 2],
            ['product_id' => 11, 'quantity' => 3],
        ]);
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
            'catalog.1.cart' => [10 => 1],
            'catalog.1.checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'));

        $failedResponse->assertRedirectToRoute('checkout.index')
            ->assertSessionHas('catalog.1.cart', [10 => 1])
            ->assertSessionHas('catalog.1.checkout.external_order_id', $externalOrderId)
            ->assertSessionHas('error', 'Não foi possível confirmar o pedido no Mini ERP. Tente novamente.');

        $successfulResponse = $this->post(route('checkout.store'));

        $successfulResponse->assertRedirectToRoute('checkout.completed')
            ->assertSessionMissing('catalog.1.cart')
            ->assertSessionMissing('catalog.1.checkout.external_order_id');
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
            'catalog.1.cart' => [10 => 2],
            'catalog.1.checkout.external_order_id' => $externalOrderId,
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
            'catalog.1.cart' => [10 => 1],
            'catalog.1.checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'));

        $response->assertRedirectToRoute('checkout.index')
            ->assertSessionHas('catalog.1.cart', [10 => 1])
            ->assertSessionHas('catalog.1.checkout.external_order_id', $externalOrderId)
            ->assertSessionHas('error', $expectedMessage);
        Http::assertSentCount(1);
    }

    public function test_invalid_authenticated_cpf_does_not_call_erp_and_preserves_order(): void
    {
        $this->insertProduct();
        Http::preventStrayRequests();
        $externalOrderId = '3f269194-884d-4c85-be2c-151d92832684';
        $this->user->update(['document' => '11111111111']);

        $response = $this->withSession([
            'catalog.1.cart' => [10 => 1],
            'catalog.1.checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'));

        $response->assertRedirectToRoute('checkout.index')
            ->assertSessionHasErrors(['customer.document'])
            ->assertSessionHas('catalog.1.cart', [10 => 1])
            ->assertSessionHas('catalog.1.checkout.external_order_id', $externalOrderId);
        Http::assertNothingSent();
    }

    public function test_unavailable_product_does_not_call_erp_or_clear_cart(): void
    {
        $this->insertProduct(active: false);
        Http::preventStrayRequests();
        $externalOrderId = 'c6d89fa5-abfa-4370-a7d2-94acfccb199d';

        $response = $this->withSession([
            'catalog.1.cart' => [10 => 1],
            'catalog.1.checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'));

        $response->assertRedirectToRoute('checkout.index')
            ->assertSessionHas('catalog.1.cart', [10 => 1])
            ->assertSessionHas('catalog.1.checkout.external_order_id', $externalOrderId)
            ->assertSessionHas('error', 'Um ou mais produtos do carrinho não estão disponíveis.');
        Http::assertNothingSent();
    }

    public function test_nonexistent_product_does_not_call_erp_or_clear_cart(): void
    {
        Http::preventStrayRequests();
        $externalOrderId = 'b46fcf20-1bc1-4dc4-bafd-230882a11044';

        $response = $this->withSession([
            'catalog.1.cart' => [999999 => 1],
            'catalog.1.checkout.external_order_id' => $externalOrderId,
        ])->from(route('checkout.index'))->post(route('checkout.store'));

        $response->assertRedirectToRoute('checkout.index')
            ->assertSessionHas('catalog.1.cart', [999999 => 1])
            ->assertSessionHas('catalog.1.checkout.external_order_id', $externalOrderId)
            ->assertSessionHas('error', 'Um ou mais produtos do carrinho não estão disponíveis.');
        Http::assertNothingSent();
    }

    public function test_completed_page_requires_a_confirmed_order(): void
    {
        $this->get(route('checkout.completed'))
            ->assertRedirectToRoute('products.index');
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
            '422 invalid cpf' => [
                422,
                [
                    'message' => 'Os dados informados são inválidos.',
                    'errors' => ['customer.document' => ['CPF inválido. Verifique o número informado.']],
                ],
                'CPF inválido. Verifique o número informado.',
            ],
            '422 duplicate cpf' => [
                422,
                [
                    'message' => 'Os dados informados são inválidos.',
                    'errors' => ['customer.document' => [
                        'Existe mais de um cliente com este CPF. Corrija a duplicidade no Mini ERP antes de continuar.',
                    ]],
                ],
                'Existe mais de um cliente com este CPF. Corrija a duplicidade no Mini ERP antes de continuar.',
            ],
            '422 inactive customer' => [
                422,
                [
                    'message' => 'Os dados informados são inválidos.',
                    'errors' => ['customer.document' => [
                        'O cliente cadastrado com este CPF está inativo no Mini ERP.',
                    ]],
                ],
                'O cliente cadastrado com este CPF está inativo no Mini ERP.',
            ],
        ];
    }

    private function insertProduct(bool $active = true, int $id = 10, string $price = '49.90'): void
    {
        DB::connection('erp_read')->table('products')->insert([
            'id' => $id,
            'sku' => "SKU-{$id}",
            'name' => "Produto de teste {$id}",
            'sale_price' => $price,
            'active' => $active,
        ]);
    }

    /**
     * @return array{name: string, document: string, phone: string, email: string}
     */
    private function customerPayload(): array
    {
        return [
            'name' => 'Cliente do Catálogo',
            'document' => '52998224725',
            'phone' => '11999999999',
            'email' => 'cliente@example.com',
        ];
    }
}
