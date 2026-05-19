<div class="p-4">
    <nav class="space-y-2">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a href="{{ route('tarefas.list')}}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-list-check"></i> Tarefas</a>
        @if (auth()->user()?->canVerFunil())
            <a href="{{ route('funil') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-filter"></i> Funil de Vendas</a>
        @endif
        <a href="{{ route('agenda') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-calendar-days"></i> Agenda</a>
        <a href="{{ route('arquivos') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-regular fa-folder-open"></i> Arquivos</a>
        <a href="{{ route('ideias.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-lightbulb"></i> Ideias &amp; Correções</a>
        @if (auth()->user()?->canGerenciarBlog())
            <a href="{{ route('blog.admin.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-newspaper"></i> Blog</a>
            <a href="{{ route('email-campanhas.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-envelope-open-text"></i> E-mail Marketing</a>
        @endif
        <div>
            <button type="button" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline flex items-center justify-between submenu-toggle focus:outline-none focus:ring-0 bg-transparent border-0 appearance-none" aria-expanded="false" data-target="submenu-cadastros">
                <span class="text-sm text-gray-700 dark:text-gray-300"><i class="fa-regular fa-square-plus"></i> Cadastros</span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div id="submenu-cadastros" class="hidden pl-3 mt-1 space-y-1">
                <a href="/clientes" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-regular fa-building"></i> Clientes</a>
                <a href="/produtos" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-box-open"></i> Produtos</a>
                <a href="/tarefas" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-list-check"></i> Tarefas</a>
                @if (auth()->user()?->canVerColaboradores())
                    <a href="/colaboradores" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-regular fa-user"></i> Colaboradores</a>
                @endif
            </div>
        </div>
        <div>
            <button type="button" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline flex items-center justify-between submenu-toggle focus:outline-none focus:ring-0 bg-transparent border-0 appearance-none" aria-expanded="false" data-target="submenu-relatorios">
                <span class="text-sm text-gray-700 dark:text-gray-300"><i class="fa-solid fa-chart-bar"></i> Relatórios</span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div id="submenu-relatorios" class="hidden pl-3 mt-1 space-y-1">
                <a href="{{ route('relatorios') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-list-check"></i> Tarefas</a>
                <a href="{{ route('relatorios.clientes') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-regular fa-building"></i> Clientes</a>
                @if (auth()->user()?->canVerColaboradores())
                    <a href="{{ route('relatorios.colaboradores') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-regular fa-user"></i> Colaboradores</a>
                @endif
                <a href="{{ route('relatorios.produtos') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-box-open"></i> Produtos</a>
                <a href="{{ route('relatorios.geolocalizacao') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-map-location-dot"></i> Geolocalização</a>
            </div>
        </div>
    </nav>
</div>
