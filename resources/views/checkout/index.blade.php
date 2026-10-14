@extends('layouts.catalog')

@section('title', 'Finalizar Pedido')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Última etapa</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 sm:text-3xl">Finalizar pedido</h1>
            <p class="mt-2 text-sm text-zinc-600">Confira os dados e confirme sua compra.</p>
        </div>

        <a href="{{ route('cart.index') }}"
            class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-full border border-zinc-300 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-800 transition hover:border-zinc-400 hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-700 sm:self-auto">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                aria-hidden="true">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Voltar ao carrinho
        </a>
    </div>

    @if (session('error'))
        <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            role="alert">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M12 7v6M12 17h.01"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <p class="font-semibold">Revise os campos destacados antes de continuar.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('checkout.store') }}" x-data="{ submitting: false }"
        x-on:submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
        @csrf

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(320px,1fr)]">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-7"
                aria-labelledby="order-data-title">
                <div class="flex items-start gap-4 border-b border-zinc-100 pb-6">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-700"
                        aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M19 8v6M22 11h-6"></path>
                        </svg>
                    </span>
                    <div>
                        <h2 id="order-data-title" class="text-lg font-semibold text-zinc-950">Dados do pedido</h2>
                        <p class="mt-1 text-sm text-zinc-600">
                            Cliente e vendedor são opcionais. Os padrões do Mini ERP serão usados quando estiverem vazios.
                        </p>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label for="customer_id" class="mb-2 block text-sm font-semibold text-zinc-800">
                            Cliente <span class="font-normal text-zinc-500">(opcional)</span>
                        </label>

                        <div x-data="{ customerId: @js((string) old('customer_id', '')) }">
                            <x-form-combobox id="customer_id" name="customer_id" model="customerId"
                                :options="Js::from($customers)" placeholder="Consumidor Final" :error="$errors->has('customer_id')" />
                        </div>

                        @error('customer_id')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="seller_id" class="mb-2 block text-sm font-semibold text-zinc-800">
                            Vendedor <span class="font-normal text-zinc-500">(opcional)</span>
                        </label>

                        <div x-data="{ sellerId: @js((string) old('seller_id', '')) }">
                            <x-form-combobox id="seller_id" name="seller_id" model="sellerId"
                                :options="Js::from($sellers)" placeholder="Vendas Online" :error="$errors->has('seller_id')" />
                        </div>

                        @error('seller_id')
                            <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <aside aria-label="Resumo do pedido">
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm sm:p-6 lg:sticky lg:top-6">
                    <h2 class="text-lg font-semibold text-zinc-950">Resumo do pedido</h2>

                    <div class="mt-5 divide-y divide-zinc-100">
                        @foreach ($items as $item)
                            <div class="flex gap-3 py-4 first:pt-0">
                                <x-product-image :product="$item['product']"
                                    wrapper-class="h-16 w-14 shrink-0 rounded-lg border border-zinc-200"
                                    image-class="h-full w-full object-contain p-1.5" />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-zinc-900">{{ $item['product']->name }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-500">
                                        {{ $item['product']->sku }} · Quantidade: {{ $item['quantity'] }}
                                    </p>
                                </div>

                                <span class="whitespace-nowrap text-sm font-semibold text-zinc-900">
                                    R$ {{ number_format((float) $item['subtotal'], 2, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-2 flex items-end justify-between gap-4 border-t border-zinc-200 pt-5">
                        <span class="font-semibold text-zinc-900">Total</span>
                        <span class="text-2xl font-bold tracking-tight text-zinc-950">
                            R$ {{ number_format((float) $total, 2, ',', '.') }}
                        </span>
                    </div>

                    <button type="submit" x-bind:disabled="submitting"
                        class="mt-6 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-zinc-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-700 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-zinc-400"
                        x-text="submitting ? 'Enviando pedido...' : 'Confirmar pedido'">
                        Confirmar pedido
                    </button>

                    <p class="mt-3 text-center text-xs leading-5 text-zinc-500">
                        O valor final será calculado e confirmado pelo Mini ERP.
                    </p>
                </div>
            </aside>
        </div>
    </form>
@endsection
