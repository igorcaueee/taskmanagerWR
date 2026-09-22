<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trocar senha — Portal WR Assessoria</title>
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
            <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-4 py-3 mb-6 text-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Por segurança, defina uma nova senha antes de continuar.</span>
            </div>

            @if ($errors->any())
                <div class="mb-5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg px-4 py-3 text-sm text-red-700 dark:text-red-400">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('portal.trocar-senha.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Nova senha</label>
                    <input type="password" name="password" required autofocus minlength="6"
                        class="block w-full px-4 py-3 border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]/20 focus:border-[#0084AA]">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Confirme a nova senha</label>
                    <input type="password" name="password_confirmation" required minlength="6"
                        class="block w-full px-4 py-3 border border-gray-300 dark:border-[#475569] dark:bg-[#334155] dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]/20 focus:border-[#0084AA]">
                </div>

                <button type="submit" class="w-full bg-[#0084AA] hover:bg-[#006e8e] text-white font-semibold py-3 rounded-lg transition text-sm">
                    Salvar nova senha
                </button>
            </form>
        </div>

        <p class="text-center mt-4">
            <a href="{{ route('portal.logout') }}"
               onclick="event.preventDefault(); document.getElementById('form-logout-trocar-senha').submit();"
               class="text-xs text-[#0084AA] hover:text-[#006e8e] transition">
                Sair
            </a>
            <form id="form-logout-trocar-senha" method="POST" action="{{ route('portal.logout') }}" class="hidden">@csrf</form>
        </p>
    </div>
</div>

</body>
</html>
