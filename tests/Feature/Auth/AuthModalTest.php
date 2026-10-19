<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EnsureCatalogConsumerBelongsToBusiness;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthModalTest extends TestCase
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

    public function test_catalog_header_opens_reusable_login_and_registration_modals(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertOk()
            ->assertSee('x-data="authModals(', false)
            ->assertSee('x-show="activeModal === \'login\'"', false)
            ->assertSee('x-show="activeModal === \'register\'"', false)
            ->assertSee('x-on:click.prevent="open(\'login\'', false)
            ->assertSee('x-on:click.prevent="open(\'register\'', false)
            ->assertSee('x-on:click.prevent="switchTo(\'register\')"', false)
            ->assertSee('x-on:click.prevent="switchTo(\'login\')"', false)
            ->assertSee('aria-label="Fechar login"', false)
            ->assertSee('aria-label="Fechar cadastro"', false)
            ->assertSee('x-on:click.self="close()"', false)
            ->assertSee('x-on:keydown.escape.window="close()"', false)
            ->assertSee('x-on:keydown.tab="trapFocus($event)"', false)
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('action="'.route('register.store').'"', false);
    }

    public function test_guest_cart_control_opens_login_modal_with_cart_destination(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertOk()
            ->assertSee('href="'.route('cart.index').'"', false)
            ->assertSee(EnsureCatalogConsumerBelongsToBusiness::LOGIN_MESSAGE)
            ->assertSee('name="return_to"', false);
    }

    public function test_invalid_modal_login_returns_to_current_page_and_reopens_login(): void
    {
        $currentUrl = '/catalogo-teste/produtos?search=camisa';

        $response = $this->from($currentUrl)->post(route('login.store'), [
            'auth_form' => 'login',
            'return_to' => $currentUrl,
            'email' => 'cliente@example.com',
            'password' => 'incorrect-password',
        ]);

        $response->assertRedirect($currentUrl);

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee("initialModal: 'login'", false)
            ->assertSee('E-mail ou senha inválidos.');
    }

    public function test_invalid_modal_registration_returns_to_current_page_and_reopens_registration(): void
    {
        $currentUrl = '/catalogo-teste/produtos?sort=name_asc';

        $response = $this->from($currentUrl)->post(route('register.store'), [
            'auth_form' => 'register',
            'return_to' => $currentUrl,
        ]);

        $response->assertRedirect($currentUrl);

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee("initialModal: 'register'", false)
            ->assertSee('Revise os campos destacados antes de continuar.');
    }

    public function test_modal_login_returns_to_current_page_after_success(): void
    {
        $user = User::factory()->create([
            'business_id' => 1,
            'email' => 'cliente@example.com',
            'password' => Hash::make('correct-password'),
        ]);
        $currentUrl = '/catalogo-teste/produtos?search=camisa';

        $response = $this->post(route('login.store'), [
            'auth_form' => 'login',
            'return_to' => $currentUrl,
            'email' => 'cliente@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect($currentUrl);
        $this->assertAuthenticatedAs($user);
    }

    public function test_modal_login_opens_cart_after_success(): void
    {
        $user = User::factory()->create([
            'business_id' => 1,
            'email' => 'cliente@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post(route('login.store'), [
            'auth_form' => 'login',
            'return_to' => route('cart.index', absolute: false),
            'email' => 'cliente@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('cart.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_modal_registration_returns_to_current_page_after_success(): void
    {
        $currentUrl = '/catalogo-teste/produtos?sort=price_asc';

        $response = $this->post(route('register.store'), [
            'auth_form' => 'register',
            'return_to' => $currentUrl,
            'name' => 'Cliente Modal',
            'document' => '529.982.247-25',
            'phone' => '(44) 99999-9999',
            'email' => 'modal@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect($currentUrl)
            ->assertSessionHas('success', 'Conta criada com sucesso.');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'business_id' => 1,
            'email' => 'modal@example.com',
        ], 'mysql');
    }

    public function test_modal_registration_opens_cart_after_success(): void
    {
        $response = $this->post(route('register.store'), [
            'auth_form' => 'register',
            'return_to' => route('cart.index', absolute: false),
            'name' => 'Cliente do Carrinho',
            'document' => '111.444.777-35',
            'phone' => '(44) 3333-4444',
            'email' => 'carrinho@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('cart.index'));
        $this->assertAuthenticated();
    }

    public function test_authentication_rejects_external_return_destination(): void
    {
        $user = User::factory()->create([
            'business_id' => 1,
            'email' => 'cliente@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->post(route('login.store'), [
            'auth_form' => 'login',
            'return_to' => 'https://attacker.example/steal',
            'email' => 'cliente@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertAuthenticatedAs($user);
    }
}
