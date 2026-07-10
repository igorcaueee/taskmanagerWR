<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="WR Assessoria - Portal do Cliente">
    <title>@yield('title', 'Portal do Cliente') — WR Assessoria</title>
    @include('partials.head')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @stack('styles')
    <script>
        // Aplica dark mode antes do render para evitar flash
        if (localStorage.getItem('portal-theme') === 'dark' ||
            (!localStorage.getItem('portal-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 antialiased transition-colors duration-200">

    {{-- Topbar --}}
    <header class="bg-white dark:bg-[#1e293b] border-b border-gray-200 dark:border-[#334155] shadow-sm">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img src="/images/torresemfundo.png" alt="WR Assessoria" class="h-10 w-10 object-contain">
                <div>
                    <span class="font-bold text-gray-800 dark:text-slate-100 text-sm">WR Assessoria</span>
                    <p class="text-xs text-gray-400 dark:text-slate-500">Portal do Cliente</p>
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-6 text-sm font-medium text-gray-600 dark:text-slate-300">
                <a href="{{ route('portal.dashboard') }}" class="no-underline hover:text-[#0084AA] transition {{ request()->routeIs('portal.dashboard') ? 'text-[#0084AA]' : '' }}">Início</a>
                <a href="{{ route('portal.blog') }}" class="no-underline hover:text-[#0084AA] transition {{ request()->routeIs('portal.blog*') ? 'text-[#0084AA]' : '' }}">Blog</a>
                <a href="{{ route('portal.arquivos') }}" class="no-underline hover:text-[#0084AA] transition {{ request()->routeIs('portal.arquivos*') ? 'text-[#0084AA]' : '' }}">Meus Arquivos</a>
                <a href="{{ route('portal.agenda') }}" class="no-underline hover:text-[#0084AA] transition {{ request()->routeIs('portal.agenda') ? 'text-[#0084AA]' : '' }}">Agenda</a>
                <a href="{{ route('portal.chamados.index') }}" class="no-underline hover:text-[#0084AA] transition {{ request()->routeIs('portal.chamados*') ? 'text-[#0084AA]' : '' }}">Chamados</a>
            </nav>

            <div class="flex items-center gap-3">
                <span class="hidden sm:block text-sm text-gray-500 dark:text-slate-400">
                    {{ Auth::guard('portal')->user()?->nome }}
                </span>

                {{-- Botão dark mode --}}
                <button id="portal-theme-toggle" title="Alternar tema"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-150 border-0 bg-transparent cursor-pointer">
                    <i id="icon-moon" class="fa-solid fa-moon text-sm"></i>
                    <i id="icon-sun" class="fa-solid fa-sun text-sm hidden"></i>
                </button>

                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit" title="Sair"
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors duration-150 border-0 bg-transparent cursor-pointer">
                        <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                    </button>
                </form>
            </div>
        </div>

        {{-- Mobile nav --}}
        <div class="md:hidden border-t border-gray-100 dark:border-[#334155] bg-gray-50 dark:bg-[#1e293b] px-4 py-2 flex gap-4 text-sm font-medium text-gray-600 dark:text-slate-300">
            <a href="{{ route('portal.dashboard') }}" class="no-underline hover:text-[#0084AA] {{ request()->routeIs('portal.dashboard') ? 'text-[#0084AA]' : '' }}">Início</a>
            <a href="{{ route('portal.blog') }}" class="no-underline hover:text-[#0084AA] {{ request()->routeIs('portal.blog*') ? 'text-[#0084AA]' : '' }}">Blog</a>
            <a href="{{ route('portal.arquivos') }}" class="no-underline hover:text-[#0084AA] {{ request()->routeIs('portal.arquivos*') ? 'text-[#0084AA]' : '' }}">Arquivos</a>
            <a href="{{ route('portal.agenda') }}" class="no-underline hover:text-[#0084AA] {{ request()->routeIs('portal.agenda') ? 'text-[#0084AA]' : '' }}">Agenda</a>
            <a href="{{ route('portal.chamados.index') }}" class="no-underline hover:text-[#0084AA] {{ request()->routeIs('portal.chamados*') ? 'text-[#0084AA]' : '' }}">Chamados</a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @yield('content')
    </main>

    <footer class="mt-16 border-t border-gray-200 dark:border-[#334155] py-6 text-center text-xs text-gray-400 dark:text-slate-500">
        © {{ date('Y') }} WR Assessoria. Portal exclusivo para clientes.
    </footer>

    @stack('scripts')

    <script>
        const btn = document.getElementById('portal-theme-toggle');
        const sun = document.getElementById('icon-sun');
        const moon = document.getElementById('icon-moon');

        function applyTheme(dark) {
            document.documentElement.classList.toggle('dark', dark);
            sun.classList.toggle('hidden', !dark);
            moon.classList.toggle('hidden', dark);
        }

        applyTheme(document.documentElement.classList.contains('dark'));

        btn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('portal-theme', isDark ? 'dark' : 'light');
            sun.classList.toggle('hidden', !isDark);
            moon.classList.toggle('hidden', isDark);
        });
    </script>
</body>
</html>
