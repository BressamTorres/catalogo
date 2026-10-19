@extends('layouts.catalog')

@section('title', 'Criar conta')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="mb-7">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-700">Minha conta</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Criar conta</h1>
            <p class="mt-2 text-sm text-slate-600">Cadastre seus dados para comprar neste catálogo.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                Revise os campos destacados antes de continuar.
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" enctype="multipart/form-data"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"
            x-data="consumerRegistrationForm({ document: @js(old('document', '')), phone: @js(old('phone', '')) })"
            x-on:submit="submit($event)">
            @csrf

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="name" class="mb-2 block text-sm font-semibold text-slate-800">Nome completo</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                        autocomplete="name"
                        class="h-12 w-full rounded-xl border bg-white px-4 text-sm text-slate-950 outline-none transition focus:ring-2 {{ $errors->has('name') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                    @error('name')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="document" class="mb-2 block text-sm font-semibold text-slate-800">CPF/CNPJ</label>
                    <input id="document" name="document" type="text" x-ref="document" x-model="document"
                        x-on:input="maskDocument($event)" x-on:blur="validateDocument()" required maxlength="18"
                        inputmode="text" autocomplete="off" placeholder="000.000.000-00"
                        class="h-12 w-full rounded-xl border bg-white px-4 text-sm uppercase text-slate-950 outline-none transition focus:ring-2 {{ $errors->has('document') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                    <p x-cloak x-show="documentError" x-text="documentError" class="mt-2 text-sm text-red-700"></p>
                    @error('document')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="mb-2 block text-sm font-semibold text-slate-800">Telefone/WhatsApp</label>
                    <input id="phone" name="phone" type="tel" x-model="phone" x-on:input="maskPhone($event)" required
                        maxlength="15" inputmode="tel" autocomplete="tel" placeholder="(44) 99999-9999"
                        class="h-12 w-full rounded-xl border bg-white px-4 text-sm text-slate-950 outline-none transition focus:ring-2 {{ $errors->has('phone') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                    @error('phone')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        autocomplete="email"
                        class="h-12 w-full rounded-xl border bg-white px-4 text-sm text-slate-950 outline-none transition focus:ring-2 {{ $errors->has('email') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                    @error('email')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-slate-800">Senha</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password"
                        class="h-12 w-full rounded-xl border bg-white px-4 text-sm text-slate-950 outline-none transition focus:ring-2 {{ $errors->has('password') ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-violet-500 focus:ring-violet-200' }}">
                    @error('password')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-800">Confirmar senha</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        autocomplete="new-password"
                        class="h-12 w-full rounded-xl border border-slate-300 bg-white px-4 text-sm text-slate-950 outline-none transition focus:border-violet-500 focus:ring-2 focus:ring-violet-200">
                </div>

                <div class="md:col-span-2">
                    <label for="photo" class="mb-2 block text-sm font-semibold text-slate-800">
                        Foto <span class="font-normal text-slate-500">(opcional)</span>
                    </label>
                    <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        class="block w-full rounded-xl border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-violet-50 file:px-4 file:py-3 file:font-semibold file:text-violet-700 hover:file:bg-violet-100">
                    <p class="mt-2 text-xs text-slate-500">PNG, JPG ou WEBP, com no máximo 5 MB.</p>
                    @error('photo')
                        <p class="mt-2 text-sm text-red-700">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-slate-600">
                    Já possui conta?
                    <a href="{{ route('login') }}" class="font-semibold text-violet-700 hover:text-violet-800">Entrar</a>
                </p>

                <button type="submit" x-bind:disabled="submitting"
                    class="inline-flex min-h-12 items-center justify-center rounded-full bg-violet-700 px-7 py-3 text-sm font-semibold text-white transition hover:bg-violet-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-600 focus-visible:ring-offset-2 disabled:cursor-wait disabled:bg-violet-400"
                    x-text="submitting ? 'Criando conta...' : 'Criar conta'">
                    Criar conta
                </button>
            </div>
        </form>
    </div>
@endsection
