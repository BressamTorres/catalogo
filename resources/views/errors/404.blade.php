<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Catálogo não encontrado</title>
    @include('partials.favicon')

    @vite('resources/css/app.css')
</head>

<body class="font-sans text-slate-950 antialiased">
    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-8 sm:px-6">
        <section
            class="w-full max-w-lg rounded-3xl border border-slate-200/80 bg-white px-6 py-10 text-center shadow-[0_20px_55px_-28px_rgba(76,29,149,0.28)] sm:px-12 sm:py-12"
            aria-labelledby="catalog-not-found-title">
            <svg class="mx-auto h-auto w-full max-w-[230px] sm:max-w-[250px]" viewBox="0 0 280 210" fill="none"
                xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="23" y="23" width="196" height="137" rx="18" fill="#F5F3FF" />
                <rect x="31" y="31" width="180" height="121" rx="13" fill="white" stroke="#8B5CF6"
                    stroke-width="4" />
                <path d="M33 63H209" stroke="#DDD6FE" stroke-width="4" />
                <circle cx="51" cy="47" r="4" fill="#A78BFA" />
                <circle cx="65" cy="47" r="4" fill="#C4B5FD" />
                <circle cx="79" cy="47" r="4" fill="#DDD6FE" />
                <path d="M80 97H126" stroke="#EDE9FE" stroke-width="9" stroke-linecap="round" />
                <path d="M80 116H111" stroke="#EDE9FE" stroke-width="9" stroke-linecap="round" />
                <circle cx="185" cy="137" r="43" fill="#EDE9FE" />
                <circle cx="174" cy="126" r="30" fill="white" stroke="#7C3AED" stroke-width="6" />
                <path d="M196 148L228 180" stroke="#6D28D9" stroke-width="10" stroke-linecap="round" />
                <path d="M164 116L184 136M184 116L164 136" stroke="#A78BFA" stroke-width="6"
                    stroke-linecap="round" />
                <path d="M49 179H151" stroke="#DDD6FE" stroke-width="7" stroke-linecap="round" />
            </svg>

            <h1 id="catalog-not-found-title" class="mt-7 text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">
                Catálogo não encontrado
            </h1>
            <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-slate-500 sm:text-base sm:leading-7">
                Não encontramos um catálogo disponível neste endereço. Verifique o link informado e tente novamente.
            </p>
        </section>
    </main>
</body>

</html>
