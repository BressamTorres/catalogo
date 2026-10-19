@extends('layouts.auth')

@section('title', 'Entrar')

@push('head')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
@endpush

@section('content')
    <div class="mx-auto max-w-sm rounded-2xl border border-slate-200 bg-white p-5 font-figtree shadow-sm sm:p-7">
        <div class="mb-7 text-center">
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                <span class="text-violet-600">Infinit</span> Catálogo
            </h1>
            <p class="mt-2 text-sm text-slate-600">Acesse sua conta</p>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-800"
                role="status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" x-data="{ showPassword: false, submitting: false }"
            x-on:submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }">
            @csrf

            <div>
                <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">E-mail</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    autocomplete="email" placeholder="seu@email.com"
                    class="h-12 w-full rounded-xl border bg-white px-4 py-2 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                @error('email')
                    <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-5">
                <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Senha</label>
                <div class="relative">
                    <input id="password" name="password" x-bind:type="showPassword ? 'text' : 'password'" required
                        autocomplete="current-password" placeholder="Digite sua senha"
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

            <div class="mt-5">
                <label for="remember_me" class="inline-flex cursor-pointer items-center">
                    <input id="remember_me" type="checkbox"
                        class="form-checkbox rounded border-slate-300 text-violet-600 shadow-sm focus:ring-violet-500"
                        name="remember" value="1">
                    <span class="ml-2 text-sm text-slate-600">Lembrar de mim</span>
                </label>
            </div>

            <button type="submit" x-bind:disabled="submitting"
                class="mt-7 inline-flex min-h-12 w-full items-center justify-center rounded-full bg-violet-700 px-7 py-3 text-sm font-semibold text-white transition hover:bg-violet-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 disabled:cursor-wait disabled:bg-violet-400"
                x-text="submitting ? 'Entrando...' : 'Entrar'">
                Entrar
            </button>

            <p class="mt-5 text-center text-sm text-slate-600">
                Ainda não possui conta?
                <a href="{{ route('register') }}"
                    class="font-semibold text-violet-700 hover:text-violet-800 focus:outline-none focus:underline">Criar conta</a>
            </p>
        </form>
    </div>
@endsection
