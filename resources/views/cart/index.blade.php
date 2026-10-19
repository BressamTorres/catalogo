@extends('layouts.catalog')

@section('title', 'Meu Carrinho')

@section('content')
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Meu carrinho</h1>
            <p class="mt-2 text-sm text-slate-600">Revise os produtos antes de finalizar o pedido.</p>
        </div>

        <a href="{{ route('products.index') }}"
            class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-full border border-violet-200 bg-white px-5 py-2.5 text-sm font-semibold text-violet-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 sm:self-auto">
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

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
        <section class="space-y-4" aria-label="Produtos no carrinho">
            @forelse ($items as $item)
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md sm:p-5">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <x-product-image :product="$item['product']"
                            wrapper-class="h-36 w-full shrink-0 rounded-xl border border-slate-200 sm:h-32 sm:w-28"
                            image-class="h-full w-full object-contain p-3" />

                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Código {{ $item['product']->sku }}
                            </p>
                            <h2 class="mt-1 text-lg font-semibold text-slate-950">{{ $item['product']->name }}</h2>
                            <p class="mt-2 text-sm text-slate-600">
                                Unitário:
                                <span class="font-semibold text-slate-900">
                                    R$ {{ number_format((float) $item['product']->sale_price, 2, ',', '.') }}
                                </span>
                            </p>

                            <div class="mt-4 flex flex-wrap items-center gap-4">
                                <div class="inline-flex items-center overflow-hidden rounded-full border border-slate-200 bg-white shadow-sm"
                                    aria-label="Controle de quantidade">
                                    <form method="POST" action="{{ route('cart.update') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                        <input type="hidden" name="action" value="decrease">

                                        <button type="submit" aria-label="Diminuir quantidade de {{ $item['product']->name }}"
                                            class="flex h-11 w-11 items-center justify-center text-lg font-medium text-slate-600 transition hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-violet-600">
                                            &minus;
                                        </button>
                                    </form>

                                    <span class="flex h-11 min-w-12 items-center justify-center border-x border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-950"
                                        aria-label="Quantidade {{ $item['quantity'] }}">
                                        {{ $item['quantity'] }}
                                    </span>

                                    <form method="POST" action="{{ route('cart.update') }}">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                        <input type="hidden" name="action" value="increase">

                                        <button type="submit" aria-label="Aumentar quantidade de {{ $item['product']->name }}"
                                            class="flex h-11 w-11 items-center justify-center text-lg font-medium text-slate-600 transition hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-violet-600">
                                            +
                                        </button>
                                    </form>
                                </div>

                                <form method="POST" action="{{ route('cart.update') }}">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item['product']->id }}">
                                    <input type="hidden" name="action" value="remove">

                                    <button type="submit"
                                        class="inline-flex min-h-11 items-center gap-2 rounded-full px-3 text-sm font-medium text-slate-500 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600"
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

                        <div class="border-t border-slate-100 pt-4 sm:border-l sm:border-t-0 sm:pl-6 sm:pt-0 sm:text-right">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Subtotal</p>
                            <p class="mt-1 whitespace-nowrap text-xl font-bold tracking-tight text-slate-950">
                                R$ {{ number_format((float) $item['subtotal'], 2, ',', '.') }}
                            </p>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-8 text-center shadow-sm sm:px-10 sm:py-10">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 text-violet-700"
                        aria-hidden="true">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7">
                            <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20.5 7H6"></path>
                            <circle cx="10" cy="20" r="1"></circle>
                            <circle cx="17" cy="20" r="1"></circle>
                        </svg>
                    </span>
                    <h2 class="mt-5 text-lg font-semibold text-slate-900">Seu carrinho está vazio</h2>
                    <p class="mt-2 text-sm text-slate-600">Escolha um produto para iniciar seu pedido.</p>
                    <a href="{{ route('products.index') }}"
                        class="mt-6 inline-flex min-h-12 items-center justify-center rounded-full bg-violet-700 px-7 py-3 text-sm font-semibold text-white transition hover:bg-violet-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2">
                        Explorar produtos
                    </a>
                </div>
            @endforelse
        </section>

        <aside aria-label="Resumo do pedido">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 lg:sticky lg:top-6">
                <h2 class="text-lg font-semibold text-slate-950">Resumo do pedido</h2>

                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-600">Subtotal</dt>
                        <dd class="font-semibold text-slate-900">R$ {{ number_format((float) $total, 2, ',', '.') }}</dd>
                    </div>
                    <div class="flex items-end justify-between gap-4 border-t border-slate-200 pt-5">
                        <dt class="font-semibold text-slate-900">Total</dt>
                        <dd class="text-2xl font-bold tracking-tight text-slate-950">
                            R$ {{ number_format((float) $total, 2, ',', '.') }}
                        </dd>
                    </div>
                </dl>

                @if ($items->isNotEmpty())
                    <a href="{{ route('checkout.index') }}"
                        class="mt-6 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-violet-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-violet-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2">
                        Finalizar pedido
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" aria-hidden="true">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('products.index') }}"
                        class="mt-6 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-violet-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-violet-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2">
                        Ver produtos
                    </a>
                @endif
            </div>
        </aside>
    </div>
@endsection
