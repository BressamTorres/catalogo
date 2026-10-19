<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterConsumerRequest;
use App\Services\CatalogContext;
use App\Services\ConsumerRegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredConsumerController extends Controller
{
    public function __construct(
        private CatalogContext $catalog,
        private ConsumerRegistrationService $registrations,
    ) {}

    public function create(): View|RedirectResponse
    {
        if ((int) Auth::user()?->business_id === $this->catalog->id()) {
            return to_route('products.index');
        }

        return view('auth.register');
    }

    public function store(RegisterConsumerRequest $request): RedirectResponse
    {
        $validated = $request->safe()->except(['password_confirmation']);
        $user = $this->registrations->create(
            $this->catalog->id(),
            $validated,
        );

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return to_route('products.index')->with('success', 'Conta criada com sucesso.');
    }
}
