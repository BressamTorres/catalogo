@extends('layouts.catalog')

@section('title', 'Finalizar Pedido')

@section('content')
    <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-violet-600">Última etapa</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Finalizar pedido</h1>
            <p class="mt-2 text-sm text-slate-500">Confira os dados e confirme sua compra.</p>
        </div>

        <a href="{{ route('cart.index') }}"
            class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-full border border-violet-200 bg-white px-5 py-2.5 text-sm font-semibold text-violet-700 transition hover:border-violet-300 hover:bg-violet-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 sm:self-auto">
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

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[minmax(0,1.45fr)_minmax(340px,0.85fr)]">
            <section class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-7"
                aria-labelledby="order-data-title">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700"
                        aria-hidden="true">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.7">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <div>
                        <h2 id="order-data-title" class="text-lg font-semibold text-slate-950">Dados do cliente</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            O pedido será vinculado automaticamente à sua conta.
                        </p>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="relative rounded-2xl border border-violet-100 bg-violet-50/60 px-4 py-3.5 pr-12">
                        <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-violet-500">Nome</dt>
                        <dd class="mt-1 break-words text-sm font-medium text-slate-900">{{ $customer->name }}</dd>
                        <svg class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-violet-300" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div class="relative rounded-2xl border border-violet-100 bg-violet-50/60 px-4 py-3.5 pr-12">
                        <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-violet-500">CPF</dt>
                        <dd class="mt-1 break-words text-sm font-medium text-slate-900">{{ $customer->document }}</dd>
                        <svg class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-violet-300" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <circle cx="8" cy="11" r="2"></circle>
                            <path d="M5.5 16a3 3 0 0 1 5 0M13 10h5M13 14h5"></path>
                        </svg>
                    </div>
                    <div class="relative rounded-2xl border border-violet-100 bg-violet-50/60 px-4 py-3.5 pr-12">
                        <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-violet-500">Telefone/WhatsApp</dt>
                        <dd class="mt-1 break-words text-sm font-medium text-slate-900">{{ $customer->phone }}</dd>
                        <svg class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-violet-300" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </div>
                    <div class="relative rounded-2xl border border-violet-100 bg-violet-50/60 px-4 py-3.5 pr-12">
                        <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-violet-500">E-mail</dt>
                        <dd class="mt-1 break-words text-sm font-medium text-slate-900">{{ $customer->email }}</dd>
                        <svg class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-violet-300" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                            <path d="m3 7 9 6 9-6"></path>
                        </svg>
                    </div>
                </dl>
            </section>

            <aside aria-label="Resumo do pedido">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm sm:p-6 lg:sticky lg:top-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700"
                            aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="1.7">
                                <path d="M6 8h12l1 13H5L6 8Z"></path>
                                <path d="M9 9V6a3 3 0 0 1 6 0v3"></path>
                            </svg>
                        </span>
                        <h2 class="text-lg font-semibold text-slate-950">Resumo do pedido</h2>
                    </div>

                    <div class="mt-5 divide-y divide-slate-100">
                        @foreach ($items as $item)
                            <div class="flex gap-3 py-4 first:pt-0">
                                <x-product-image :product="$item['product']"
                                    wrapper-class="h-16 w-14 shrink-0 rounded-xl border border-violet-100 bg-white"
                                    image-class="h-full w-full object-contain p-1.5" />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item['product']->name }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ $item['product']->sku }} · Quantidade: {{ $item['quantity'] }}
                                    </p>
                                </div>

                                <span class="whitespace-nowrap text-sm font-semibold text-slate-900">
                                    R$ {{ number_format((float) $item['subtotal'], 2, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-2 flex items-end justify-between gap-4 border-t border-violet-100 pt-5">
                        <span class="font-semibold text-slate-900">Total</span>
                        <span class="text-2xl font-bold tracking-tight text-violet-700">
                            R$ {{ number_format((float) $total, 2, ',', '.') }}
                        </span>
                    </div>

                    <button type="submit" x-bind:disabled="submitting"
                        class="mt-6 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-gradient-to-r from-violet-700 to-purple-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:from-violet-800 hover:to-purple-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:from-violet-300 disabled:to-purple-300">
                        <svg class="h-5 w-5" x-show="!submitting" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="m8 12 2.5 2.5L16 9"></path>
                        </svg>
                        <svg class="h-5 w-5 animate-spin" x-cloak x-show="submitting" viewBox="0 0 24 24" fill="none"
                            aria-hidden="true">
                            <circle class="opacity-30" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"></circle>
                            <path class="opacity-90" fill="currentColor" d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z"></path>
                        </svg>
                        <span x-text="submitting ? 'Enviando pedido...' : 'Confirmar pedido'">Confirmar pedido</span>
                    </button>

                    <p class="mt-3 text-center text-xs leading-5 text-slate-500">
                        O valor final será calculado e confirmado pelo Mini ERP.
                    </p>
                </div>
            </aside>
        </div>
    </form>
@endsection
