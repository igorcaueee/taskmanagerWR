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

        <!-- Centro: busca de clientes -->
        <div class="flex-1 flex justify-center px-4 max-w-xl mx-auto relative" id="header-search-wrapper">
            <div class="w-full relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" id="header-search-input"
                       placeholder="Buscar cliente..."
                       autocomplete="off"
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 text-gray-700 dark:text-slate-200 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-brand">
                <!-- Dropdown de resultados -->
                <div id="header-search-results"
                     class="hidden absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 rounded-lg shadow-xl z-50 overflow-hidden">
                    <ul id="header-search-list" class="max-h-72 overflow-y-auto"></ul>
                    <div id="header-search-empty" class="hidden px-4 py-3 text-xs text-gray-400 dark:text-slate-500 italic text-center">Nenhum cliente encontrado.</div>
                    <div id="header-search-loading" class="hidden px-4 py-3 text-xs text-gray-400 dark:text-slate-500 text-center">
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Buscando...
                    </div>
                </div>
            </div>
        </div>

        <!-- Direita: nome, toggle dark, logout -->
        <div class="flex items-center gap-2">

<script>
(function () {
    const input = document.getElementById('header-search-input');
    const dropdown = document.getElementById('header-search-results');
    const list = document.getElementById('header-search-list');
    const empty = document.getElementById('header-search-empty');
    const loading = document.getElementById('header-search-loading');
    let timer = null;

    function showDropdown() { dropdown.classList.remove('hidden'); }
    function hideDropdown() { dropdown.classList.add('hidden'); }

    function setLoading(on) {
        loading.classList.toggle('hidden', !on);
        empty.classList.add('hidden');
        if (on) { list.innerHTML = ''; }
    }

    input.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(timer);

        if (q.length < 2) { hideDropdown(); return; }

        setLoading(true);
        showDropdown();

        timer = setTimeout(function () {
            fetch('{{ route('clientes.busca') }}?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(function (data) {
                setLoading(false);
                list.innerHTML = '';

                if (!data.length) {
                    empty.classList.remove('hidden');
                    return;
                }

                data.forEach(function (c) {
                    const li = document.createElement('li');
                    li.className = 'flex items-center gap-2 px-2 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer';
                    li.innerHTML =
                        '<span class="text-gray-400 text-xs flex-shrink-0">' +
                            (c.tipo == 1 ? '<i class="fa-solid fa-building-user"></i>' : '<i class="fa-solid fa-user"></i>') +
                        '</span>' +
                        '<div class="min-w-0 flex-1">' +
                            '<p class="text-xs font-medium text-gray-800 dark:text-slate-100 truncate">' + c.nome + '</p>' +
                            (c.cpfcnpj ? '<p class="text-xs text-gray-400 dark:text-slate-500">' + c.cpfcnpj + '</p>' : '') +
                        '</div>' +
                        (c.status === 'ativo'
                            ? '<span class="text-xs px-1.5 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 flex-shrink-0">Ativo</span>'
                            : '<span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 flex-shrink-0">Inativo</span>');
                    li.addEventListener('click', function () {
                        window.location.href = c.url;
                    });
                    list.appendChild(li);
                });
            })
            .catch(function () { setLoading(false); });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('header-search-wrapper').contains(e.target)) {
            hideDropdown();
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { hideDropdown(); input.blur(); }
    });
}());
</script>
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
