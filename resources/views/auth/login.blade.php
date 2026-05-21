<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso — WR Assessoria</title>
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
    <div class="w-full max-w-md">

        <div class="flex justify-center mb-8 text-center">
            <div>
                <img src="/images/torresemfundo.png" alt="WR Assessoria" class="h-16 w-16 mx-auto object-contain mb-3">
                <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">WR Assessoria</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400">Área restrita — Colaboradores</p>
            </div>
        </div>

        <div class="bg-white dark:bg-[#1e293b] rounded-xl shadow-sm border border-gray-200 dark:border-[#334155] p-8">
            <h2 class="text-lg font-semibold text-gray-700 dark:text-slate-200 mb-6">Acesse sua conta</h2>

            @if ($errors->any())
                <div class="mb-5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3 text-sm text-red-700 dark:text-red-400">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Email</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="block w-full px-4 py-3 border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]/20 focus:border-[#0084AA]"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Senha</label>
                    <input
                        type="password"
                        name="password"
                        required
                        class="block w-full px-4 py-3 border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]/20 focus:border-[#0084AA]"
                    >
                </div>
                <button type="submit" class="w-full bg-[#0084AA] hover:bg-[#006e8e] text-white font-semibold py-3 rounded-lg transition text-sm">
                    Entrar
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 dark:text-slate-500 mt-6">
            Problemas no acesso? Fale com o administrador do sistema.
        </p>
        
        <p class="text-center mt-4">
            <a href="/" class="text-xs text-[#0084AA] hover:text-[#006e8e] transition">
                ← Voltar para o site
            </a>
        </p>
    </div>
</div>

</body>
</html>
