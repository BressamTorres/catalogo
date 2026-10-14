@extends('layouts.catalog')

@section('title', 'Catálogo de Produtos')

@section('content')
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
            role="status">
            {{ session('success') }}
        </div>
    @endif

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

    <div class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Seleção atual</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-950 sm:text-3xl">
                {{ $search !== '' ? 'Resultados da pesquisa' : 'Todos os produtos' }}
            </h1>
        </div>

        <p class="text-sm text-zinc-600" aria-live="polite">
            {{ $products->count() }} {{ $products->count() === 1 ? 'produto encontrado' : 'produtos encontrados' }}
        </p>
    </div>

    @if ($products->isNotEmpty())
        <div
            class="grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 sm:gap-5 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
            @foreach ($products as $product)
                <article
                    class="group flex h-full flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                    <x-product-image :product="$product" wrapper-class="aspect-[4/5]"
                        image-class="h-full w-full object-contain p-4 transition duration-300 group-hover:scale-[1.02]" />

                    <div class="flex flex-1 flex-col p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">Código {{ $product->sku }}</p>
                        <h2 class="mt-2 min-h-12 text-base font-medium leading-6 text-zinc-900">
                            {{ $product->name }}
                        </h2>
                        <p class="mt-3 text-xl font-bold tracking-tight text-zinc-950">
                            R$ {{ number_format((float) $product->sale_price, 2, ',', '.') }}
                        </p>

                        <form method="POST" action="{{ route('cart.add') }}" x-data="{ submitting: false }"
                            x-on:submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }"
                            class="mt-auto pt-5">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            <button type="submit" x-bind:disabled="submitting"
                                class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-zinc-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-700 focus-visible:ring-offset-2 disabled:cursor-wait disabled:bg-zinc-500"
                                aria-label="Comprar {{ $product->name }}">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="1.8" aria-hidden="true">
                                    <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L20.5 7H6"></path>
                                    <circle cx="10" cy="20" r="1"></circle>
                                    <circle cx="17" cy="20" r="1"></circle>
                                </svg>
                                <span x-text="submitting ? 'Adicionando...' : 'Comprar'">Comprar</span>
                            </button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <section class="rounded-2xl border border-dashed border-zinc-300 bg-white px-6 py-16 text-center"
            aria-labelledby="empty-catalog-title">
            <svg class="mx-auto h-12 w-12 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.4" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="m20 20-3.7-3.7"></path>
            </svg>
            <h2 id="empty-catalog-title" class="mt-4 text-lg font-semibold text-zinc-900">
                {{ $search !== '' ? 'Nenhum produto encontrado' : 'Catálogo sem produtos disponíveis' }}
            </h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-zinc-600">
                {{ $search !== '' ? 'Tente pesquisar por outro nome ou código.' : 'Assim que houver produtos ativos, eles aparecerão aqui.' }}
            </p>

            @if ($search !== '')
                <a href="{{ route('products.index') }}"
                    class="mt-6 inline-flex min-h-11 items-center justify-center rounded-full bg-zinc-950 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-700 focus-visible:ring-offset-2">
                    Limpar pesquisa
                </a>
            @endif
        </section>
    @endif
@endsection
