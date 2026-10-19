<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\CatalogContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsumerRegistrationTest extends TestCase
{
    public function test_registration_page_displays_complete_consumer_form(): void
    {
        $response = $this->get('/catalogo-teste/criar-conta');

        $response->assertOk()
            ->assertSee('Nome completo')
            ->assertSee('CPF/CNPJ')
            ->assertSee('Telefone/WhatsApp')
            ->assertSee('E-mail')
            ->assertSee('Confirmar senha')
            ->assertDontSee('name="photo"', false);
    }

    public function test_registration_creates_authenticated_consumer_with_normalized_data(): void
    {
        $cartKey = app(CatalogContext::class)->cartSessionKey();

        $response = $this->withSession([$cartKey => [10 => 2]])
            ->post('/catalogo-teste/criar-conta', $this->validPayload([
                'name' => '  Maria da Silva  ',
                'document' => '529.982.247-25',
                'phone' => '(44) 99999-9999',
                'email' => '  MARIA@EXAMPLE.COM  ',
            ]));

        $response->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Conta criada com sucesso.')
            ->assertSessionHas($cartKey, [10 => 2]);

        $user = User::query()->where('email', 'maria@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, $user->business_id);
        $this->assertSame('Maria da Silva', $user->name);
        $this->assertSame('52998224725', $user->document);
        $this->assertSame('44999999999', $user->phone);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotSame('password123', $user->password);
    }

    public function test_registration_accepts_valid_alphanumeric_cnpj(): void
    {
        $response = $this->post('/catalogo-teste/criar-conta', $this->validPayload([
            'document' => '12.ABC.345/01DE-35',
        ]));

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('users', [
            'business_id' => 1,
            'document' => '12ABC34501DE35',
        ], 'mysql');
    }

    public function test_registration_rejects_invalid_document_and_password_confirmation(): void
    {
        $response = $this->from('/catalogo-teste/criar-conta')
            ->post('/catalogo-teste/criar-conta', $this->validPayload([
                'document' => '529.982.247-24',
                'password_confirmation' => 'different-password',
            ]));

        $response->assertRedirect('/catalogo-teste/criar-conta')
            ->assertSessionHasErrors([
                'document' => 'CPF inválido. Verifique o número informado.',
                'password' => 'A confirmação da senha não corresponde.',
            ]);
        $this->assertDatabaseCount('users', 0, 'mysql');
    }

    public function test_registration_rejects_cnpj_with_invalid_check_digits(): void
    {
        $response = $this->post('/catalogo-teste/criar-conta', $this->validPayload([
            'document' => '12.ABC.345/01DE-34',
        ]));

        $response->assertSessionHasErrors([
            'document' => 'CNPJ inválido. Verifique o número informado.',
        ]);
        $this->assertDatabaseCount('users', 0, 'mysql');
    }

    public function test_registration_requires_consumer_identification_and_credentials(): void
    {
        $response = $this->post('/catalogo-teste/criar-conta');

        $response->assertSessionHasErrors([
            'name' => 'Informe seu nome completo.',
            'document' => 'Informe seu CPF ou CNPJ.',
            'phone' => 'Informe seu telefone ou WhatsApp.',
            'email' => 'Informe seu e-mail.',
            'password' => 'Informe uma senha.',
        ]);
        $this->assertDatabaseCount('users', 0, 'mysql');
    }

    public function test_email_is_unique_only_inside_the_active_business(): void
    {
        User::factory()->create([
            'business_id' => 1,
            'email' => 'cliente@example.com',
            'document' => '11144477735',
        ]);

        $duplicate = $this->post('/catalogo-teste/criar-conta', $this->validPayload([
            'document' => '529.982.247-25',
        ]));

        $duplicate->assertSessionHasErrors([
            'email' => 'Este e-mail já está cadastrado neste catálogo.',
        ]);

        $this->createSecondBusiness();

        $otherBusiness = $this->post('/segunda-loja/criar-conta', $this->validPayload());

        $otherBusiness->assertRedirect('/segunda-loja/produtos');
        $this->assertDatabaseHas('users', [
            'business_id' => 2,
            'email' => 'cliente@example.com',
            'document' => '52998224725',
        ], 'mysql');
    }

    public function test_same_cpf_with_and_without_formatting_is_rejected_inside_the_same_business(): void
    {
        User::factory()->create([
            'business_id' => 1,
            'email' => 'existing@example.com',
            'document' => '52998224725',
        ]);

        $response = $this->post('/catalogo-teste/criar-conta', $this->validPayload([
            'email' => 'new@example.com',
            'document' => '529.982.247-25',
        ]));

        $response->assertSessionHasErrors([
            'document' => 'Este CPF já está cadastrado. Faça login para continuar.',
        ]);
        $this->assertDatabaseCount('users', 1, 'mysql');
    }

    public function test_same_cpf_can_be_registered_in_different_businesses(): void
    {
        User::factory()->create([
            'business_id' => 1,
            'email' => 'first@example.com',
            'document' => '52998224725',
        ]);
        $this->createSecondBusiness();

        $response = $this->post('/segunda-loja/criar-conta', $this->validPayload([
            'email' => 'second@example.com',
            'document' => '529.982.247-25',
        ]));

        $response->assertRedirect('/segunda-loja/produtos');
        $this->assertDatabaseHas('users', [
            'business_id' => 2,
            'email' => 'second@example.com',
            'document' => '52998224725',
        ], 'mysql');
        $this->assertDatabaseCount('users', 2, 'mysql');
    }

    public function test_different_cpf_can_be_registered_inside_the_same_business(): void
    {
        User::factory()->create([
            'business_id' => 1,
            'email' => 'existing@example.com',
            'document' => '52998224725',
        ]);

        $response = $this->post('/catalogo-teste/criar-conta', $this->validPayload([
            'email' => 'new@example.com',
            'document' => '111.444.777-35',
        ]));

        $response->assertRedirect('/catalogo-teste/produtos');
        $this->assertDatabaseHas('users', [
            'business_id' => 1,
            'email' => 'new@example.com',
            'document' => '11144477735',
        ], 'mysql');
        $this->assertDatabaseCount('users', 2, 'mysql');
    }

    /** @param array<string, mixed> $overrides */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Cliente Catálogo',
            'document' => '529.982.247-25',
            'phone' => '(44) 3333-4444',
            'email' => 'cliente@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    private function createSecondBusiness(): void
    {
        DB::connection('erp_read')->table('businesses')->insert([
            'id' => 2,
            'trade_name' => 'Segunda Loja',
            'catalog_slug' => 'segunda-loja',
            'catalog_api_token' => null,
            'catalog_api_token_expires_at' => null,
        ]);
    }
}
