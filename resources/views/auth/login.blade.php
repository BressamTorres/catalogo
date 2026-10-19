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

        <form method="POST" action="{{ route('login.store') }}" x-data="{ submitting: false }"
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
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    placeholder="Digite sua senha"
                    class="h-12 w-full rounded-xl border bg-white py-3 pl-4 pr-11 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:ring-2 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
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
