<?php

namespace App\Http\Middleware;

use App\Services\CatalogContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCatalogConsumerBelongsToBusiness
{
    public const LOGIN_MESSAGE = 'Faça login para acessar seu carrinho.';

    public function __construct(private CatalogContext $catalog) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ((int) $request->user()?->business_id !== $this->catalog->id()) {
            return redirect()
                ->guest(route('login', ['catalog' => $this->catalog->slug()]))
                ->with('status', self::LOGIN_MESSAGE);
        }

        return $next($request);
    }
}
