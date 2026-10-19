@extends('layouts.catalog')

@section('title', 'Catálogo de Produtos')

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            role="status">
            {{ session('success') }}
        </div>
    @endif

    <div x-cloak x-show="cartStatusMessage" x-text="cartStatusMessage"
        class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
        role="status" aria-live="polite"></div>

    <div x-cloak x-show="cartErrorMessage" x-text="cartErrorMessage"
        class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"></div>

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            Não foi possível concluir a ação. Revise os dados e tente novamente.
        </div>
    @endif

    <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Todos os produtos</h1>
            @if ($search !== '')
                <p class="mt-2 text-sm text-slate-600">Resultados para “{{ $search }}”</p>
            @endif
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between lg:justify-end">
            <p class="text-sm text-slate-600" aria-live="polite">
                {{ $products->count() }} {{ $products->count() === 1 ? 'produto encontrado' : 'produtos encontrados' }}
            </p>

            <form method="GET" action="{{ route('products.index') }}" class="flex items-center gap-2">
                @if ($search !== '')
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif

                <label for="catalog-sort" class="text-sm font-medium text-slate-700">Ordenar por</label>
                <select id="catalog-sort" name="sort" x-on:change="$el.form.requestSubmit()"
                    class="h-11 rounded-xl border border-slate-300 bg-white py-2 pl-3 pr-9 text-sm text-slate-800 shadow-sm outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-200">
                    <option value="relevance" @selected($sort === 'relevance')>Relevância</option>
                    <option value="name_asc" @selected($sort === 'name_asc')>Nome: A–Z</option>
                    <option value="name_desc" @selected($sort === 'name_desc')>Nome: Z–A</option>
                    <option value="price_asc" @selected($sort === 'price_asc')>Preço: menor primeiro</option>
                    <option value="price_desc" @selected($sort === 'price_desc')>Preço: maior primeiro</option>
                </select>
                <button type="submit" class="sr-only">Aplicar ordenação</button>
            </form>
        </div>
    </div>

    @if ($products->isNotEmpty())
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                <article
                    class="group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <x-product-image :product="$product" wrapper-class="aspect-square border-b border-slate-100"
                        image-class="h-full w-full object-contain p-4 transition duration-300 group-hover:scale-[1.02]" />

                    <div class="flex flex-1 flex-col p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Código {{ $product->sku }}</p>
                        <h2 class="mt-2 min-h-12 text-base font-medium leading-6 text-slate-900">
                            {{ $product->name }}
                        </h2>
                        <p class="mt-3 text-xl font-bold tracking-tight text-slate-950">
                            R$ {{ number_format((float) $product->sale_price, 2, ',', '.') }}
                        </p>

                        <form method="POST" action="{{ route('cart.add') }}" x-data="{ submitting: false }"
                            x-on:submit.prevent="if (!submitting) { submitting = true; addToCart($event).finally(() => submitting = false); }"
                            class="mt-auto pt-5">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            <button type="submit" x-bind:disabled="submitting"
                                class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-violet-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-violet-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 disabled:cursor-wait disabled:bg-violet-400"
                                aria-label="Adicionar {{ $product->name }} ao carrinho">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8" aria-hidden="true">
                                    <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20.5 7H6"></path>
                                    <circle cx="10" cy="20" r="1"></circle>
                                    <circle cx="17" cy="20" r="1"></circle>
                                </svg>
                                <span x-text="submitting ? 'Adicionando...' : 'Adicionar ao carrinho'">Adicionar ao carrinho</span>
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"
            aria-labelledby="empty-catalog-title">
            <svg class="mx-auto h-12 w-12 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.4" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.7-3.7"></path>
            </svg>
            <h2 id="empty-catalog-title" class="mt-4 text-lg font-semibold text-slate-900">
                {{ $search !== '' ? 'Nenhum produto encontrado' : 'Catálogo sem produtos disponíveis' }}
            </h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">
                {{ $search !== '' ? 'Tente pesquisar por outro nome ou código.' : 'Assim que houver produtos ativos, eles aparecerão aqui.' }}
            </p>

            @if ($search !== '')
                <a href="{{ route('products.index') }}"
                    class="mt-6 inline-flex min-h-11 items-center justify-center rounded-full bg-violet-700 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2">
                    Limpar pesquisa
                </a>
            @endif
        </section>
    @endif
@endsection
