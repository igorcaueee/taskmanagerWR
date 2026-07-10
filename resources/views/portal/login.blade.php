<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acesso ao Portal — WR Assessoria</title>
    @include('partials.head')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-[#0f172a] antialiased">

<div class="min-h-screen flex items-center justify-center py-16">
    <div class="w-full max-w-md">

        <div class="flex justify-center mb-8">
            <div class="text-center">
                <img src="/images/torresemfundo.png" alt="WR Assessoria" class="h-16 w-16 mx-auto object-contain mb-3">
                <h1 class="text-xl font-bold text-gray-800 dark:text-slate-100">WR Assessoria</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400">Portal Exclusivo para Clientes</p>
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

            <form method="POST" action="{{ route('portal.login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Usuário</label>
                    <input
                        type="text"
                        name="username"
                        value="{{ old('username') }}"
                        required
                        autofocus
                        placeholder="seu.usuario"
                        class="block w-full px-4 py-3 border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]/20 focus:border-[#0084AA]"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Senha</label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            class="block w-full px-4 py-3 pr-11 border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]/20 focus:border-[#0084AA]"
                        >
                        <button
                            type="button"
                            onclick="const i=document.getElementById('password'); const hidden=i.type==='password'; i.type=hidden?'text':'password'; this.querySelector('.icon-eye').classList.toggle('hidden', hidden); this.querySelector('.icon-eye-off').classList.toggle('hidden', !hidden);"
                            class="absolute inset-y-0 right-0 flex items-center px-3 bg-transparent border-0 appearance-none text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-200 focus:outline-none"
                            tabindex="-1"
                        >
                            <svg class="icon-eye w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-off hidden w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a19.68 19.68 0 0 1 5.06-5.94M9.9 4.24A10.4 10.4 0 0 1 12 4c7 0 11 8 11 8a19.69 19.69 0 0 1-2.68 3.9M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#0084AA] hover:bg-[#006e8e] text-white font-semibold py-3 rounded-lg transition text-sm">
                    Entrar no Portal
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-gray-400 dark:text-slate-500 mt-6">
            Problemas no acesso? Entre em contato com a WR Assessoria.
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
