@extends('layouts.catalog')

@section('title', 'Meu Carrinho')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Sua seleção</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 sm:text-3xl">Meu carrinho</h1>
            <p class="mt-2 text-sm text-zinc-600">Revise os produtos antes de finalizar o pedido.</p>
        </div>

        <a href="{{ route('products.index') }}"
            class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-full border border-zinc-300 bg-white px-5 py-2.5 text-sm font-semibold text-zinc-800 transition hover:border-zinc-400 hover:bg-zinc-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-700 sm:self-auto">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                aria-hidden="true">
                <path d="m15 18-6-6 6-6"></path>
            </svg>
            Continuar comprando
        </a>
    </div>

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
        <section class="space-y-4" aria-label="Produtos no carrinho">
            @forelse ($items as $item)
                <article class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm sm:p-5">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <x-product-image :product="$item['product']"
                            wrapper-class="h-36 w-full shrink-0 rounded-xl border border-zinc-200 sm:h-32 sm:w-28"
                            image-class="h-full w-full object-contain p-3" />

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">
                                Código {{ $item['product']->sku }}
                            </p>
                            <h2 class="mt-1 text-lg font-semibold text-zinc-950">{{ $item['product']->name }}</h2>
                            <p class="mt-2 text-sm text-zinc-600">
                                Unitário:
                                <span class="font-semibold text-zinc-900">
                                    R$ {{ number_format((float) $item['product']->sale_price, 2, ',', '.') }}
                                </span>
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-4">
                                <div class="inline-flex items-center overflow-hidden rounded-full border border-zinc-200 bg-white"
                                    aria-label="Controle de quantidade">
                                    <form method="POST" action="{{ route('cart.update') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                        <input type="hidden" name="action" value="decrease">

                                        <button type="submit" aria-label="Diminuir quantidade de {{ $item['product']->name }}"
                                            class="flex h-11 w-11 items-center justify-center text-lg font-medium text-zinc-600 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-zinc-700">
                                            &minus;
                                        </button>
                                    </form>

                                    <span class="flex h-11 min-w-12 items-center justify-center border-x border-zinc-200 px-3 text-sm font-semibold text-zinc-950"
                                        aria-label="Quantidade {{ $item['quantity'] }}">
                                        {{ $item['quantity'] }}
                                    </span>

                                    <form method="POST" action="{{ route('cart.update') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                        <input type="hidden" name="action" value="increase">

                                        <button type="submit" aria-label="Aumentar quantidade de {{ $item['product']->name }}"
                                            class="flex h-11 w-11 items-center justify-center text-lg font-medium text-zinc-600 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-zinc-700">
                                            +
                                        </button>
                                    </form>
                                </div>

                                <form method="POST" action="{{ route('cart.update') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                    <input type="hidden" name="action" value="remove">

                                    <button type="submit"
                                        class="inline-flex min-h-11 items-center gap-2 rounded-full px-3 text-sm font-medium text-zinc-500 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600"
                                        aria-label="Remover {{ $item['product']->name }} do carrinho">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="1.8" aria-hidden="true">
                                            <path d="M4 7h16"></path>
                                            <path d="M10 11v6M14 11v6"></path>
                                            <path d="m6 7 1 14h10l1-14M9 7V4h6v3"></path>
                                        </svg>
                                        Remover
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="border-t border-zinc-100 pt-4 sm:border-l sm:border-t-0 sm:pl-6 sm:pt-0 sm:text-right">
                            <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Subtotal</p>
                            <p class="mt-1 whitespace-nowrap text-xl font-bold tracking-tight text-zinc-950">
                                R$ {{ number_format((float) $item['subtotal'], 2, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-14 text-center">
                    <svg class="mx-auto h-11 w-11 text-zinc-400" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20.5 7H6"></path>
                        <circle cx="10" cy="20" r="1"></circle>
                        <circle cx="17" cy="20" r="1"></circle>
                    </svg>
                    <h2 class="mt-4 text-lg font-semibold text-zinc-900">Seu carrinho está vazio</h2>
                    <p class="mt-2 text-sm text-zinc-600">Escolha um produto para iniciar seu pedido.</p>
                </div>
            @endforelse
        </section>

        <aside aria-label="Resumo do pedido">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm lg:sticky lg:top-6">
                <h2 class="text-lg font-semibold text-zinc-950">Resumo do pedido</h2>

                <dl class="mt-6 space-y-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-zinc-600">Subtotal</dt>
                        <dd class="font-semibold text-zinc-900">R$ {{ number_format((float) $total, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex items-end justify-between gap-4 border-t border-zinc-200 pt-5">
                        <dt class="font-semibold text-zinc-900">Total</dt>
                        <dd class="text-2xl font-bold tracking-tight text-zinc-950">
                            R$ {{ number_format((float) $total, 2, ',', '.') }}
                        </dd>
                    </div>
                </dl>

                @if ($items->isNotEmpty())
                    <a href="{{ route('checkout.index') }}"
                        class="mt-6 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-zinc-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-700 focus-visible:ring-offset-2">
                        Finalizar pedido
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" aria-hidden="true">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('products.index') }}"
                        class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-zinc-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-700 focus-visible:ring-offset-2">
                        Ver produtos
                    </a>
                @endif
            </div>
        </aside>
    </div>
@endsection
