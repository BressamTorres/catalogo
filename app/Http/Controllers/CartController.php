<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CatalogContext $catalog) {}

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('business_id', $this->catalog->id())
                    ->where('active', true),
            ],
        ]);

        $product = Product::query()
            ->where('active', true)
            ->findOrFail($data['product_id']);

        $cartKey = $this->catalog->cartSessionKey();
        $cart = $request->session()->get($cartKey, []);

        $id = $product->id;

        $cart[$id] = ($cart[$id] ?? 0) + 1;

        $request->session()->put($cartKey, $cart);

        return to_route('products.index')
            ->with('success', 'Produto adicionado ao carrinho!');
    }

    public function index(Request $request): View
    {
        $cart = $request->session()->get($this->catalog->cartSessionKey(), []);

        $products = Product::query()
            ->where('active', true)
            ->whereIn('id', array_keys($cart))
            ->get();

        $items = $products->map(function ($product) use ($cart) {
            $quantity = $cart[$product->id];

            return [
                'product' => $product,
                'quantity' => $quantity,
                'subtotal' => $product->sale_price * $quantity,
            ];
        });

        $total = $items->sum('subtotal');

        return view('cart.index', compact('items', 'total'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'action' => ['required', 'in:increase,decrease,remove'],
        ]);

        $cartKey = $this->catalog->cartSessionKey();
        $cart = $request->session()->get($cartKey, []);

        $id = $data['product_id'];

        if (! isset($cart[$id])) {
            return redirect()->route('cart.index');
        }

        if (
            $data['action'] !== 'remove'
            && ! Product::query()->where('active', true)->whereKey($id)->exists()
        ) {
            unset($cart[$id]);
            $request->session()->put($cartKey, $cart);

            return to_route('cart.index')->with('error', 'O produto não está disponível neste catálogo.');
        }

        switch ($data['action']) {
            case 'increase':
                $cart[$id]++;
                break;

            case 'decrease':
                $cart[$id]--;

                if ($cart[$id] <= 0) {
                    unset($cart[$id]);
                }
                break;

            case 'remove':
                unset($cart[$id]);
                break;
        }

        $request->session()->put($cartKey, $cart);

        return redirect()->route('cart.index');
    }
}
