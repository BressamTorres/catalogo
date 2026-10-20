@extends('layouts.catalog')

@section('title', 'Pedido concluído')

@section('description', 'Confirmação do pedido registrado no Mini ERP.')

@section('content')
    <section class="mx-auto max-w-xl py-4 text-center sm:py-8" aria-labelledby="completed-order-title">
        <div class="rounded-3xl border border-violet-100 bg-white px-5 py-8 shadow-sm sm:px-10 sm:py-10">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 shadow-sm"
                aria-hidden="true">
                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="m5 12 4 4L19 6"></path>
                </svg>
            </div>

            <p class="mt-6 text-xs font-semibold uppercase tracking-[0.22em] text-violet-600">Pedido concluído</p>
            <h1 id="completed-order-title" class="mt-2 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
                Compra confirmada com sucesso
            </h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500">
                Seu pedido foi recebido e registrado no Mini ERP.
            </p>

            <dl class="mx-auto mt-7 grid max-w-md grid-cols-1 overflow-hidden rounded-2xl border border-violet-100 bg-violet-50/60 sm:grid-cols-2">
                <div class="px-5 py-4 text-center sm:py-5">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Número do pedido</dt>
                    <dd class="mt-1 break-words text-base font-semibold text-slate-950">{{ $order['number'] }}</dd>
                </div>
                <div class="border-t border-violet-100 px-5 py-4 text-center sm:border-l sm:border-t-0 sm:py-5">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Valor total</dt>
                    <dd class="mt-1 text-2xl font-bold tracking-tight text-violet-700">
                        R$ {{ number_format((float) $order['total'], 2, ',', '.') }}
                    </dd>
                </div>
            </dl>

            <a href="{{ route('products.index') }}"
                class="mt-7 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-violet-700 to-purple-600 px-7 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-violet-800 hover:to-purple-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 sm:w-auto">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    aria-hidden="true">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Voltar ao catálogo
            </a>
        </div>
    </section>
@endsection
