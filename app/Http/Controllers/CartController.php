<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where('business_id', max((int) config('services.mini_erp.business_id'), 0)),
            ],
        ]);

        $product = Product::query()
            ->where('active', true)
            ->findOrFail($data['product_id']);

        $cart = $request->session()->get('cart', []);

        $id = $product->id;

        $cart[$id] = ($cart[$id] ?? 0) + 1;

        $request->session()->put('cart', $cart);

        return redirect('/produtos')
            ->with('success', 'Produto adicionado ao carrinho!');
    }

    public function index(Request $request)
    {
        $cart = $request->session()->get('cart', []);

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

    public function update(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'action' => ['required', 'in:increase,decrease,remove'],
        ]);

        $cart = $request->session()->get('cart', []);

        $id = $data['product_id'];

        if (! isset($cart[$id])) {
            return redirect()->route('cart.index');
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

        $request->session()->put('cart', $cart);

        return redirect()->route('cart.index');
    }
}
