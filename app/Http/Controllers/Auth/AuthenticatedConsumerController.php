<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginConsumerRequest;
use App\Services\CatalogAuthenticationRedirect;
use App\Services\CatalogContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedConsumerController extends Controller
{
    public function __construct(
        private CatalogContext $catalog,
        private CatalogAuthenticationRedirect $redirects,
    ) {}

    public function create(): View|RedirectResponse
    {
        if ((int) Auth::user()?->business_id === $this->catalog->id()) {
            return to_route('products.index');
        }

        return view('auth.login');
    }

    public function store(LoginConsumerRequest $request): RedirectResponse
    {
        if (! Auth::attempt([
            'business_id' => $this->catalog->id(),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
        ], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->redirects->destination($request));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('products.index');
    }
}
