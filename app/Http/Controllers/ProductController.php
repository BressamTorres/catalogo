<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private const ALLOWED_SORTS = [
        'relevance',
        'name_asc',
        'name_desc',
        'price_asc',
        'price_desc',
    ];

    public function __construct(private CatalogContext $catalog) {}

    public function missing(): never
    {
        abort(404);
    }

    public function index(Request $request): View
    {
        $requestedSearch = $request->query('search', '');
        $search = is_string($requestedSearch) ? trim($requestedSearch) : '';
        $requestedSort = $request->query('sort', 'relevance');
        $sort = is_string($requestedSort) && in_array($requestedSort, self::ALLOWED_SORTS, true)
            ? $requestedSort
            : 'relevance';

        $productsQuery = Product::query()
            ->where('active', true)
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->select('id', 'sku', 'name', 'sale_price', 'photo_path', 'photo_disk');

        $this->applySort($productsQuery, $sort, $search);

        $products = $productsQuery->get();

        $cartCount = collect($request->session()->get($this->catalog->cartSessionKey(), []))
            ->sum(fn (mixed $quantity): int => max((int) $quantity, 0));

        return view('products.index', compact('products', 'search', 'sort', 'cartCount'));
    }

    private function applySort(Builder $query, string $sort, string $search): void
    {
        if ($sort === 'relevance' && $search !== '') {
            $normalizedSearch = mb_strtolower($search);

            $query->orderByRaw(
                'CASE WHEN LOWER(sku) = ? THEN 0 WHEN LOWER(name) = ? THEN 1 WHEN LOWER(name) LIKE ? THEN 2 ELSE 3 END',
                [$normalizedSearch, $normalizedSearch, "{$normalizedSearch}%"],
            )->orderBy('name')->orderBy('id');

            return;
        }

        match ($sort) {
            'name_desc' => $query->orderByDesc('name')->orderBy('id'),
            'price_asc' => $query->orderBy('sale_price')->orderBy('name')->orderBy('id'),
            'price_desc' => $query->orderByDesc('sale_price')->orderBy('name')->orderBy('id'),
            default => $query->orderBy('name')->orderBy('id'),
        };
    }
}
