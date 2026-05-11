<header id="main-header-internal" class="bg-white dark:bg-[#1e293b] border-b border-gray-200 dark:border-[#334155] shadow-sm transition-colors duration-200 fixed top-0 left-0 right-0 z-30">
    <div class="w-full px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">

        <!-- Esquerda: hamburguer + logo -->
        <div class="flex items-center gap-3">
            <!-- Botão hamburguer (mobile E desktop para recolher) -->
            <button id="nav-toggle" aria-label="Abrir menu" aria-expanded="false"
                class="flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-150 border-0 bg-transparent cursor-pointer">
                <i class="fa-solid fa-bars text-base"></i>
            </button>

            <img src="/images/torresemfundo.png" alt="WR" class="w-10 h-10 object-contain">
            <span class="hidden sm:block text-sm font-semibold text-gray-700 dark:text-slate-200">WR Assessoria</span>
        </div>

        <!-- Direita: nome, toggle dark, logout -->
        <div class="flex items-center gap-2">
            <span class="hidden sm:block text-sm text-gray-600 dark:text-slate-400">{{ auth()->user()?->nome }}</span>

            {{-- Dark mode toggle --}}
            <button id="dark-mode-toggle" title="Alternar modo escuro"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-150 border-0 bg-transparent cursor-pointer">
                <i id="dark-mode-icon" class="fa-solid fa-moon text-sm"></i>
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Sair"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors duration-150 border-0 bg-transparent cursor-pointer">
                    <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                </button>
            </form>
        </div>
    </div>
</header>

{{-- Espaçador para compensar o header fixo --}}
<div class="h-16"></div>

{{-- Overlay mobile --}}
<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/40 z-20 md:hidden"></div>

<script>
(function () {
    const html = document.getElementById('html-root');
    const btn = document.getElementById('dark-mode-toggle');
    const icon = document.getElementById('dark-mode-icon');
    const overlay = document.getElementById('sidebar-overlay');
    const sidebar = document.getElementById('main-sidebar');

    /* ── Dark mode toggle ── */
    function syncIcon() {
        if (html.classList.contains('dark')) {
            icon.className = 'fa-solid fa-sun text-sm';
        } else {
            icon.className = 'fa-solid fa-moon text-sm';
        }
    }
    syncIcon();

    btn.addEventListener('click', function () {
        if (html.classList.contains('dark')) {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
        syncIcon();
    });

    /* ── Overlay mobile fecha sidebar ── */
    if (overlay && sidebar) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        });
    }
}());
</script>
