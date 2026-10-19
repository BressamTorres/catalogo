@extends('layouts.auth')

@section('title', 'Criar conta')

@push('head')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
@endpush

@section('content')
    <div class="mx-auto max-w-lg rounded-2xl border border-slate-200 bg-white p-5 font-figtree shadow-sm sm:p-7">
        <div class="mb-7 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                <span class="text-violet-600">Infinit</span> Catálogo
            </h1>
            <p class="mt-2 text-sm text-slate-600">Crie sua conta</p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                Revise os campos destacados antes de continuar.
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}"
            class="space-y-5"
            x-data="consumerRegistrationForm({ document: @js(old('document', '')), phone: @js(old('phone', '')) })"
            x-on:submit="submit($event)">
            @csrf

            <div>
                <label for="name" class="mb-2 block text-sm font-semibold text-slate-800">Nome completo</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                    autocomplete="name" placeholder="Seu nome completo"
                    class="h-12 w-full rounded-xl border bg-white px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has('name') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                @error('name')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="document" class="mb-2 block text-sm font-semibold text-slate-800">CPF/CNPJ</label>
                <input id="document" name="document" type="text" x-ref="document" x-model="document"
                    x-on:input="maskDocument($event)" x-on:blur="validateDocument()" required maxlength="18"
                    inputmode="text" autocomplete="off" placeholder="000.000.000-00"
                    x-bind:class="documentError ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : '{{ $errors->has('document') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}'"
                    class="h-12 w-full rounded-xl border bg-white px-4 text-sm uppercase text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2">
                <p x-cloak x-show="documentError" x-text="documentError" class="mt-2 text-sm text-red-700"></p>
                @error('document')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="phone" class="mb-2 block text-sm font-semibold text-slate-800">Telefone/WhatsApp</label>
                <input id="phone" name="phone" type="tel" x-model="phone" x-on:input="maskPhone($event)" required
                    maxlength="15" inputmode="tel" autocomplete="tel" placeholder="(44) 99999-9999"
                    class="h-12 w-full rounded-xl border bg-white px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has('phone') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                @error('phone')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required
                    autocomplete="email" placeholder="seu@email.com"
                    class="h-12 w-full rounded-xl border bg-white px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                @error('email')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Senha</label>
                <div class="relative">
                    <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" required
                        autocomplete="new-password" placeholder="Mínimo de 8 caracteres"
                        class="h-12 w-full rounded-xl border bg-white py-3 pl-4 pr-11 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                    <button type="button"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-violet-600"
                        x-on:click="showPassword = ! showPassword"
                        x-bind:aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                        x-bind:aria-pressed="showPassword" aria-controls="password">
                        <svg x-show="! showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                            <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                        </svg>
                        <svg x-cloak x-show="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 4.2A10.4 10.4 0 0 1 12 4c6 0 9.5 6 9.5 6a17 17 0 0 1-2.1 2.8M6.2 6.2C3.8 8 2.5 10 2.5 10s3.5 6 9.5 6a10.4 10.4 0 0 0 3-.4" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-800">Confirmar senha</label>
                <div class="relative">
                    <input id="password_confirmation" name="password_confirmation"
                        x-bind:type="showPasswordConfirmation ? 'text' : 'password'" required
                        autocomplete="new-password" placeholder="Repita sua senha"
                        class="h-12 w-full rounded-xl border bg-white py-3 pl-4 pr-11 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                    <button type="button"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-slate-400 transition hover:text-violet-600"
                        x-on:click="showPasswordConfirmation = ! showPasswordConfirmation"
                        x-bind:aria-label="showPasswordConfirmation ? 'Ocultar confirmação da senha' : 'Mostrar confirmação da senha'"
                        x-bind:aria-pressed="showPasswordConfirmation" aria-controls="password_confirmation">
                        <svg x-show="! showPasswordConfirmation" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                            <circle cx="12" cy="12" r="2.5" stroke-width="1.8" />
                        </svg>
                        <svg x-cloak x-show="showPasswordConfirmation" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M3 3l18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 4.2A10.4 10.4 0 0 1 12 4c6 0 9.5 6 9.5 6a17 17 0 0 1-2.1 2.8M6.2 6.2C3.8 8 2.5 10 2.5 10s3.5 6 9.5 6a10.4 10.4 0 0 0 3-.4" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" x-bind:disabled="submitting"
                class="inline-flex min-h-12 w-full items-center justify-center rounded-full bg-violet-700 px-7 py-3 text-sm font-semibold text-white transition hover:bg-violet-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 disabled:cursor-wait disabled:bg-violet-400"
                x-text="submitting ? 'Criando conta...' : 'Criar conta'">
                Criar conta
            </button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-600">
            Já possui conta?
            <a href="{{ route('login') }}"
                class="font-semibold text-violet-700 hover:text-violet-800 focus:outline-none focus:underline">Entrar</a>
        </p>
    </div>
@endsection
