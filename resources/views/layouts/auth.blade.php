<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Acesse sua conta no catálogo online.">
    <title>@yield('title') | {{ app(\App\Services\CatalogContext::class)->business()->trade_name }}</title>

    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans text-slate-900 antialiased">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-50 px-4 py-10 sm:px-6">
        <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-violet-200/40 blur-3xl" aria-hidden="true"></div>
        <div class="absolute -right-20 bottom-0 h-80 w-80 rounded-full bg-indigo-200/40 blur-3xl" aria-hidden="true"></div>

        <div class="relative w-full">
            @yield('content')
        </div>
    </main>
</body>

</html>
