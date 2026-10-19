<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\CatalogContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConsumerAuthenticationTest extends TestCase
{
    public function test_consumer_logs_in_only_at_own_business_and_keeps_cart(): void
    {
        $user = User::factory()->create([
            'business_id' => 1,
            'email' => 'cliente@example.com',
            'password' => Hash::make('correct-password'),
        ]);
        $cartKey = app(CatalogContext::class)->cartSessionKey();

        $response = $this->withSession([$cartKey => [10 => 3]])
            ->post('/catalogo-teste/entrar', [
                'email' => ' CLIENTE@EXAMPLE.COM ',
                'password' => 'correct-password',
            ]);

        $response->assertRedirect(route('products.index'))
            ->assertSessionHas($cartKey, [10 => 3]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_credentials_from_another_business(): void
    {
        $this->createSecondBusiness();
        User::factory()->create([
            'business_id' => 1,
            'email' => 'cliente@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->from('/segunda-loja/entrar')->post('/segunda-loja/entrar', [
            'email' => 'cliente@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/segunda-loja/entrar')
            ->assertSessionHasErrors(['email' => 'E-mail ou senha inválidos.']);
        $this->assertGuest();
    }

    public function test_consumer_can_log_in_without_persistent_authentication(): void
    {
        User::factory()->create([
            'business_id' => 1,
            'email' => 'cliente@example.com',
            'password' => Hash::make('correct-password'),
        ]);
        $recallerName = Auth::guard()->getRecallerName();

        $response = $this->post('/catalogo-teste/entrar', [
            'email' => 'cliente@example.com',
            'password' => 'correct-password',
        ]);

        $response->assertRedirect('/catalogo-teste/produtos')
            ->assertCookieMissing($recallerName);

        $this->endBrowserSession();

        $this->get('/catalogo-teste/entrar')->assertOk();
        $this->assertGuest();
    }

    public function test_consumer_can_resume_authentication_with_remember_cookie(): void
    {
        $user = User::factory()->create([
            'business_id' => 1,
            'email' => 'cliente@example.com',
            'password' => Hash::make('correct-password'),
        ]);
        $recallerName = Auth::guard()->getRecallerName();

        $loginResponse = $this->post('/catalogo-teste/entrar', [
            'email' => 'cliente@example.com',
            'password' => 'correct-password',
            'remember' => '1',
        ]);
        $rememberCookie = $loginResponse->getCookie($recallerName, false);

        $loginResponse->assertRedirect('/catalogo-teste/produtos')
            ->assertCookieNotExpired($recallerName);
        $this->assertNotNull($rememberCookie);

        $this->endBrowserSession();

        $response = $this->withUnencryptedCookies([
            $recallerName => $rememberCookie->getValue(),
        ])->get('/catalogo-teste/entrar');

        $response->assertRedirect('/catalogo-teste/produtos');
        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_cookie_does_not_authenticate_consumer_in_another_business(): void
    {
        $this->createSecondBusiness();
        User::factory()->create([
            'business_id' => 1,
            'name' => 'Cliente da Primeira Empresa',
            'email' => 'cliente@example.com',
            'password' => Hash::make('correct-password'),
        ]);
        $recallerName = Auth::guard()->getRecallerName();
        $loginResponse = $this->post('/catalogo-teste/entrar', [
            'email' => 'cliente@example.com',
            'password' => 'correct-password',
            'remember' => '1',
        ]);
        $rememberCookie = $loginResponse->getCookie($recallerName, false);
        $this->assertNotNull($rememberCookie);

        $this->endBrowserSession();

        $response = $this->withUnencryptedCookies([
            $recallerName => $rememberCookie->getValue(),
        ])->get('/segunda-loja/entrar');

        $response->assertOk()
            ->assertSeeText('Acesse sua conta')
            ->assertDontSee('Cliente da Primeira Empresa');
    }

    public function test_same_email_authenticates_the_consumer_from_the_active_business(): void
    {
        $this->createSecondBusiness();
        User::factory()->create([
            'business_id' => 1,
            'email' => 'compartilhado@example.com',
            'password' => Hash::make('first-password'),
        ]);
        $secondUser = User::factory()->create([
            'business_id' => 2,
            'email' => 'compartilhado@example.com',
            'password' => Hash::make('second-password'),
        ]);

        $response = $this->post('/segunda-loja/entrar', [
            'email' => 'compartilhado@example.com',
            'password' => 'second-password',
        ]);

        $response->assertRedirect('/segunda-loja/produtos');
        $this->assertAuthenticatedAs($secondUser);
    }

    public function test_header_does_not_expose_consumer_from_another_business(): void
    {
        $this->createSecondBusiness();
        $user = User::factory()->create([
            'business_id' => 1,
            'name' => 'Cliente da Primeira Empresa',
        ]);

        $response = $this->actingAs($user)->get('/segunda-loja/entrar');

        $response->assertOk()
            ->assertDontSee('Cliente da Primeira Empresa')
            ->assertSee('Entrar');
    }

    public function test_auth_pages_do_not_render_the_store_header(): void
    {
        $this->get('/catalogo-teste/entrar')
            ->assertOk()
            ->assertSee('Ainda não possui conta?')
            ->assertSee('id="remember_me"', false)
            ->assertSeeText('Lembrar de mim')
            ->assertDontSee('id="catalog-search"', false)
            ->assertDontSee('aria-label="Carrinho com', false);

        $this->get('/catalogo-teste/criar-conta')
            ->assertOk()
            ->assertSee('Já possui conta?')
            ->assertDontSee('id="catalog-search"', false)
            ->assertDontSee('aria-label="Carrinho com', false);
    }

    public function test_consumer_logout_clears_session_and_persistent_authentication(): void
    {
        $user = User::factory()->create([
            'business_id' => 1,
            'email' => 'cliente@example.com',
            'password' => Hash::make('correct-password'),
            'remember_token' => 'original-remember-token',
        ]);
        $recallerName = Auth::guard()->getRecallerName();
        $loginResponse = $this->post('/catalogo-teste/entrar', [
            'email' => 'cliente@example.com',
            'password' => 'correct-password',
            'remember' => '1',
        ]);
        $rememberCookie = $loginResponse->getCookie($recallerName, false);
        $this->assertNotNull($rememberCookie);

        $this->endBrowserSession();

        $response = $this->withUnencryptedCookies([
            $recallerName => $rememberCookie->getValue(),
        ])->post('/catalogo-teste/sair');

        $response->assertRedirect(route('products.index'))
            ->assertCookieExpired($recallerName);
        $this->assertGuest();
        $this->assertNotSame('original-remember-token', $user->fresh()->remember_token);
    }

    private function endBrowserSession(): void
    {
        session()->invalidate();
        Auth::forgetGuards();
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
