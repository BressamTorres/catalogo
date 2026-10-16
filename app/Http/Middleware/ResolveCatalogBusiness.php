<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Services\CatalogContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class ResolveCatalogBusiness
{
    public function __construct(private CatalogContext $catalog) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = (string) $request->route('catalog');
        $business = Business::query()
            ->where('catalog_slug', $slug)
            ->firstOrFail();

        $this->catalog->set($business);
        URL::defaults(['catalog' => $business->catalog_slug]);

        try {
            return $next($request);
        } finally {
            $this->catalog->clear();
        }
    }
}
