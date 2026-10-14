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
    $headerCartCount = isset($cartCount)
        ? (int) $cartCount
        : (int) collect(session('cart', []))->sum(fn (mixed $quantity): int => max((int) $quantity, 0));
    $headerSearch = request()->routeIs('home', 'products.index') ? (string) request('search', '') : '';
@endphp

<body class="min-h-screen bg-stone-100 text-zinc-950 antialiased">
    <a href="#main-content"
        class="sr-only z-50 rounded-md bg-white px-4 py-2 text-zinc-950 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">
        Ir para o conteúdo
    </a>

    <header class="bg-zinc-950 text-white">
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center gap-4 px-4 py-4 sm:px-6 lg:flex-nowrap lg:px-8">
            <a href="{{ route('home') }}"
                class="order-1 inline-flex shrink-0 items-center gap-3 rounded-md focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950"
                aria-label="Página inicial do catálogo">
                <span class="flex h-10 w-10 items-center justify-center rounded-full border border-white/40 text-sm font-bold"
                    aria-hidden="true">C</span>
                <span class="text-base font-semibold tracking-[0.22em] sm:text-lg">CATÁLOGO</span>
            </a>

            <form method="GET" action="{{ route('products.index') }}" x-data="{ submitting: false }"
                x-on:submit="submitting = true"
                class="order-3 w-full lg:order-2 lg:mx-auto lg:max-w-3xl lg:flex-1" role="search">
                <label for="catalog-search" class="sr-only">Pesquisar produtos por nome ou código</label>

                <div class="relative">
                    <svg class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-500"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                        aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.7-3.7"></path>
                    </svg>

                    <input id="catalog-search" type="search" name="search" value="{{ $headerSearch }}"
                        placeholder="O que você procura?"
                        class="h-12 w-full rounded-full border-0 bg-white py-3 pl-12 pr-28 text-sm text-zinc-950 shadow-sm outline-none placeholder:text-zinc-500 focus:ring-2 focus:ring-zinc-400 sm:h-14 sm:text-base">

                    <button type="submit" x-bind:disabled="submitting"
                        class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-full bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-400 disabled:cursor-wait disabled:bg-zinc-500"
                        x-text="submitting ? 'Buscando...' : 'Buscar'">
                        Buscar
                    </button>
                </div>
            </form>

            <a href="{{ route('cart.index') }}"
                class="order-2 ml-auto inline-flex min-h-11 items-center gap-3 rounded-full px-3 py-2 transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white lg:order-3 lg:ml-0"
                aria-label="Carrinho com {{ $headerCartCount }} {{ $headerCartCount === 1 ? 'item' : 'itens' }}">
                <span class="relative" aria-hidden="true">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.7">
                        <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20.5 7H6"></path>
                        <circle cx="10" cy="20" r="1"></circle>
                        <circle cx="17" cy="20" r="1"></circle>
                    </svg>
                    <span
                        class="absolute -right-2 -top-2 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-white px-1 text-[11px] font-bold text-zinc-950">
                        {{ $headerCartCount }}
                    </span>
                </span>
                <span class="hidden text-sm font-medium sm:inline">Carrinho</span>
            </a>
        </div>
    </header>

    <div class="h-[61px] border-b border-zinc-200 bg-white" data-catalog-spacer aria-hidden="true"></div>

    <main id="main-content" class="mx-auto max-w-[1600px] px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        @yield('content')
    </main>
</body>

</html>
