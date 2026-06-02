<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Erro interno | WR Assessoria</title>
    @include('partials.head')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script>
        if (localStorage.getItem('theme') === 'dark' ||
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-[#0f172a] antialiased">

<div class="min-h-screen flex items-center justify-center py-16">
    <div class="w-full max-w-md text-center">

        <div class="flex justify-center mb-8">
            <div>
                <img src="/images/torresemfundo.png" alt="WR Assessoria" class="h-16 w-16 mx-auto object-contain mb-3">
                <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">WR Assessoria</h1>
            </div>
        </div>

        <div class="bg-white dark:bg-[#1e293b] rounded-xl shadow-sm border border-gray-200 dark:border-[#334155] p-10">

            <div class="text-7xl font-extrabold text-red-500 dark:text-red-400 mb-2">500</div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-slate-100 mb-2">Erro interno do servidor</h2>
            <p class="text-sm text-gray-500 dark:text-slate-400 mb-8">
                Algo deu errado no nosso lado. Tente novamente em alguns instantes.
            </p>

            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 no-underline bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-6 py-2.5 rounded-lg transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Ir para o início
            </a>
        </div>

    </div>
</div>

</body>
</html>
