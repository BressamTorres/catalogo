<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="@yield('description', 'Catálogo de produtos disponíveis para compra.')">
    <title>@yield('title', 'Catálogo de Produtos')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@php
    $catalogBusinessName = (string) app(\App\Services\CatalogContext::class)->business()->trade_name;
    $headerCartCount = isset($cartCount)
        ? (int) $cartCount
        : (int) collect(session(app(\App\Services\CatalogContext::class)->cartSessionKey(), []))
            ->sum(fn (mixed $quantity): int => max((int) $quantity, 0));
    $requestedHeaderSearch = request('search');
    $headerSearch = request()->routeIs('home', 'products.index') && is_string($requestedHeaderSearch)
        ? $requestedHeaderSearch
        : '';
    $requestedHeaderSort = request('sort');
    $headerSort = request()->routeIs('home', 'products.index') && is_string($requestedHeaderSort)
        ? $requestedHeaderSort
        : '';
@endphp

<body class="min-h-screen bg-slate-50 text-slate-950 antialiased">
    <a href="#main-content"
        class="sr-only z-50 rounded-md bg-white px-4 py-2 text-slate-950 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Ir para o conteúdo
    </a>

    <header class="border-b border-slate-200 bg-white">
        <div
            class="mx-auto grid max-w-7xl grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-4 py-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,42rem)_minmax(0,1fr)] lg:px-8">
            <a href="{{ route('home') }}"
                class="inline-flex min-w-0 items-center gap-3 rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2"
                aria-label="Página inicial do catálogo">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-100 text-violet-700"
                    aria-hidden="true">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.8">
                        <path d="M4 10v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-9"></path>
                        <path d="M3 10 5.5 4h13L21 10"></path>
                        <path d="M3 10a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"></path>
                        <path d="M9 20v-6h6v6"></path>
                    </svg>
                </span>
                <span class="truncate text-base font-semibold text-slate-900 sm:text-lg" title="{{ $catalogBusinessName }}">
                    {{ $catalogBusinessName }}
                </span>
            </a>

            <form method="GET" action="{{ route('products.index') }}"
                class="col-span-2 row-start-2 w-full lg:col-span-1 lg:col-start-2 lg:row-start-1" role="search">
                <label for="catalog-search" class="sr-only">Pesquisar produtos por nome ou código</label>

                @if ($headerSort !== '')
                    <input type="hidden" name="sort" value="{{ $headerSort }}">
                @endif

                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.7-3.7"></path>
                    </svg>

                    <input id="catalog-search" type="search" name="search" value="{{ $headerSearch }}"
                        placeholder="O que você procura?"
                        class="h-12 w-full rounded-full border border-slate-300 bg-slate-50 py-3 pl-12 pr-4 text-sm text-slate-950 shadow-sm outline-none transition placeholder:text-slate-500 hover:border-slate-400 focus:border-violet-500 focus:bg-white focus:ring-2 focus:ring-violet-200 sm:text-base">
                </div>
            </form>

            <a href="{{ route('cart.index') }}"
                class="justify-self-end inline-flex min-h-11 items-center gap-3 rounded-full px-3 py-2 text-slate-700 transition hover:bg-violet-50 hover:text-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 lg:col-start-3"
                aria-label="Carrinho com {{ $headerCartCount }} {{ $headerCartCount === 1 ? 'item' : 'itens' }}">
                <span class="relative" aria-hidden="true">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.7">
                        <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20.5 7H6"></path>
                        <circle cx="10" cy="20" r="1"></circle>
                        <circle cx="17" cy="20" r="1"></circle>
                    </svg>
                    <span
                        class="absolute -right-2 -top-2 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-violet-700 px-1 text-[11px] font-bold text-white">
                        {{ $headerCartCount }}
                    </span>
                </span>
                <span class="hidden text-sm font-medium sm:inline">Carrinho</span>
            </a>
        </div>
    </header>

    <main id="main-content" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        @yield('content')
    </main>
</body>

</html>
