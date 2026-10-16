<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogContext;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private CatalogContext $catalog) {}

    public function missing(): never
    {
        abort(404);
    }

    public function index(Request $request): View
    {
        $search = trim($request->query('search', ''));

        $products = Product::query()
            ->where('active', true)
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->select('id', 'sku', 'name', 'sale_price', 'photo_path', 'photo_disk')
            ->orderBy('name')
            ->get();

        $cartCount = collect($request->session()->get($this->catalog->cartSessionKey(), []))
            ->sum(fn (mixed $quantity): int => max((int) $quantity, 0));

        return view('products.index', compact('products', 'search', 'cartCount'));
    }
}
