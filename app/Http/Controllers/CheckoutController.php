<?php

namespace App\Http\Controllers;

use App\Exceptions\MiniErpApiException;
use App\Http\Requests\ConfirmCheckoutRequest;
use App\Models\Product;
use App\Services\MiniErpSalesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private MiniErpSalesService $miniErpSales) {}

    public function index(Request $request): View|RedirectResponse
    {
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('error', 'Seu carrinho está vazio.');
        }

        $items = $this->checkoutItems($cart);

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Não há produtos disponíveis no carrinho.');
        }

        $total = $items->sum('subtotal');

        $sellers = DB::table('persons')
            ->where('type', 'seller')
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($seller) => [
                'value' => (string) $seller->id,
                'label' => $seller->name,
            ])
            ->values();
        $customers = DB::table('persons')
            ->where('type', 'customer')
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($customer) => [
                'value' => (string) $customer->id,
                'label' => $customer->name,
            ])
            ->values();

        $this->externalOrderId($request);

        return view('checkout.index', compact('items', 'total', 'sellers', 'customers'));
    }

    public function store(ConfirmCheckoutRequest $request): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index')
                ->with('error', 'Seu carrinho está vazio.');
        }

        $items = $this->checkoutItems($cart);

        if ($items->count() !== count($cart)) {
            return back()->withInput()
                ->with('error', 'Um ou mais produtos do carrinho não estão disponíveis.');
        }

        $payload = [
            'external_order_id' => $this->externalOrderId($request),
            'items' => $items
                ->map(fn (array $item): array => [
                    'product_id' => (int) $item['product']->id,
                    'quantity' => $item['quantity'],
                ])
                ->values()
                ->all(),
        ];
        $validated = $request->validated();

        if (isset($validated['customer_id'])) {
            $payload['customer_id'] = (int) $validated['customer_id'];
        }

        if (isset($validated['seller_id'])) {
            $payload['seller_id'] = (int) $validated['seller_id'];
        }

        try {
            $order = $this->miniErpSales->createSale($payload);
        } catch (MiniErpApiException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $request->session()->forget(['cart', 'checkout.external_order_id']);
        $request->session()->flash('checkout.completed_order', $order);

        return redirect()->route('checkout.completed');
    }

    public function completed(Request $request): View|RedirectResponse
    {
        $order = $request->session()->get('checkout.completed_order');

        if (! is_array($order)) {
            return redirect('/produtos');
        }

        return view('checkout.completed', compact('order'));
    }

    /**
     * @param  array<int|string, int|string>  $cart
     */
    private function checkoutItems(array $cart): Collection
    {
        return Product::query()
            ->where('active', true)
            ->whereIn('id', array_keys($cart))
            ->orderBy('id')
            ->get()
            ->map(function (Product $product) use ($cart): array {
                $quantity = (int) $cart[$product->id];

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product->sale_price * $quantity,
                ];
            });
    }

    private function externalOrderId(Request $request): string
    {
        $externalOrderId = $request->session()->get('checkout.external_order_id');

        if (! is_string($externalOrderId) || ! Str::isUuid($externalOrderId)) {
            $externalOrderId = (string) Str::uuid();
            $request->session()->put('checkout.external_order_id', $externalOrderId);
        }

        return $externalOrderId;
    }
}
