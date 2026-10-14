@extends('layouts.catalog')

@section('title', 'Pedido concluído')

@section('description', 'Confirmação do pedido registrado no Mini ERP.')

@section('content')
    <section class="mx-auto max-w-2xl py-4 text-center sm:py-8" aria-labelledby="completed-order-title">
        <div class="rounded-2xl border border-zinc-200 bg-white px-5 py-10 shadow-sm sm:px-10 sm:py-12">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"
                aria-hidden="true">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m5 12 4 4L19 6"></path>
                </svg>
            </div>

            <p class="mt-6 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Pedido concluído</p>
            <h1 id="completed-order-title" class="mt-2 text-2xl font-semibold tracking-tight text-zinc-950 sm:text-3xl">
                Compra confirmada com sucesso
            </h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-zinc-600">
                Seu pedido foi recebido e registrado no Mini ERP.
            </p>

            <dl class="mx-auto mt-8 max-w-md overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 text-left">
                <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-5 py-4">
                    <dt class="text-sm text-zinc-600">Número do pedido</dt>
                    <dd class="text-sm font-semibold text-zinc-950">{{ $order['number'] }}</dd>
                </div>
                <div class="flex items-end justify-between gap-4 px-5 py-5">
                    <dt class="text-sm font-semibold text-zinc-700">Valor total</dt>
                    <dd class="text-2xl font-bold tracking-tight text-zinc-950">
                        R$ {{ number_format((float) $order['total'], 2, ',', '.') }}
                    </dd>
                </div>
            </dl>

            <a href="{{ route('products.index') }}"
                class="mt-8 inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-zinc-950 px-7 py-3 text-sm font-semibold text-white transition hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-700 focus-visible:ring-offset-2">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Voltar ao catálogo
            </a>
        </div>
    </section>
@endsection
