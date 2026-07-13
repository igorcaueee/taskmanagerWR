<div class="p-4">
    <nav class="space-y-2">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a href="{{ route('tarefas.list')}}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-list-check"></i> Tarefas</a>
        <a href="{{ route('agenda') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-calendar-days"></i> Agenda</a>
        <a href="{{ route('chat.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-comments"></i> Chat</a>
        <a href="{{ route('arquivos') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-regular fa-folder-open"></i> Arquivos</a>
        <a href="{{ route('tarefas.uploads-portal') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-file-arrow-up"></i> Uploads Portal</a>
        <a href="{{ route('nfse.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-file-invoice"></i> NFS-e Portal Nacional</a>
        <a href="{{ route('nfe.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-truck-ramp-box"></i> NF-e / CT-e Nacional</a>
        <a href="{{ route('notas-emitidas.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-hashtag"></i> Contador de Notas</a>
        <a href="{{ route('ideias.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-lightbulb"></i> Ideias &amp; Correções</a>
        @if (auth()->user()?->canGerenciarBlog())
            <a href="{{ route('blog.admin.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-newspaper"></i> Blog</a>
            <a href="{{ route('email-campanhas.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-envelope-open-text"></i> News Letter EMAIL</a>
        @endif
        @if (auth()->user()?->canGerenciarAcessoExterno())
            <a href="{{ route('acesso-externo.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-shield-halved"></i> Acesso Externo</a>
        @endif
        @if (auth()->user()?->canVerFunil())
        <div>
            <button type="button" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline flex items-center justify-between submenu-toggle focus:outline-none focus:ring-0 bg-transparent border-0 appearance-none" aria-expanded="false" data-target="submenu-comercial">
                <span class="text-sm text-gray-700 dark:text-gray-300"><i class="fa-solid fa-briefcase"></i> Comercial</span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div id="submenu-comercial" class="hidden pl-3 mt-1 space-y-1">
                <a href="{{ route('funil') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-filter"></i> Funil de Vendas</a>
                <a href="{{ route('leads.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-user-plus"></i> Leads</a>
            </div>
        </div>
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
                @if (auth()->user()?->canVerProdutosPossibilidades())
                <a href="/produtos" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-box-open"></i> Produtos</a>
                <a href="/possibilidades" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-regular fa-lightbulb"></i> Possibilidades</a>
                @endif
                <a href="/tarefas" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-list-check"></i> Tarefas</a>
                <a href="{{ route('tipos-tarefa.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-tags"></i> Tipos de Tarefa</a>
                <a href="{{ route('notas-emitidas.emitentes.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-regular fa-address-card"></i> Clientes de Nota</a>
                @if (auth()->user()?->canVerColaboradores())
                    <a href="/colaboradores" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-regular fa-user"></i> Colaboradores</a>
                @endif
            </div>
        </div>
        @if(auth()->user()?->canVerClassificacao())
        <div>
            <button type="button" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline flex items-center justify-between submenu-toggle focus:outline-none focus:ring-0 bg-transparent border-0 appearance-none" aria-expanded="false" data-target="submenu-classificacao">
                <span class="text-sm text-gray-700 dark:text-gray-300"><i class="fa-solid fa-star-half-stroke"></i> Classificação</span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div id="submenu-classificacao" class="hidden pl-3 mt-1 space-y-1">
                <a href="{{ route('questionarios.index') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-clipboard-question"></i> Questionário</a>
            </div>
        </div>
        @endif
        <div>
            <button type="button" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline flex items-center justify-between submenu-toggle focus:outline-none focus:ring-0 bg-transparent border-0 appearance-none" aria-expanded="false" data-target="submenu-apps">
                <span class="text-sm text-gray-700 dark:text-gray-300"><i class="fa-solid fa-table-cells-large"></i> Apps</span>
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
            <div id="submenu-apps" class="hidden pl-3 mt-1 space-y-1">
                <a href="https://charming-fiscal-flow-hub.base44.app" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-chart-pie"></i> WR Dashboard Fiscal</a>
                <a href="https://fiery-smart-ledger-sync.base44.app" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-scale-balanced"></i> ConciliaAI</a>
                <a href="https://wr-payroll-flow.base44.app" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-coins"></i> WR Custos</a>
                <a href="https://conferencia-fiscal.base44.app" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-file-invoice"></i> Conferência Fiscal</a>
                <a href="https://time-trace-pro.base44.app" target="_blank" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-clock"></i> TimeTrace AI</a>
            </div>
        </div>
        @if (auth()->user()?->canVerRelatorios())
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
                <a href="{{ route('relatorios.notas') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-file-invoice"></i> Notas Emitidas</a>
                <a href="{{ route('relatorios.geolocalizacao') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 no-underline"><i class="fa-solid fa-map-location-dot"></i> Geolocalização</a>
            </div>
        </div>
        @endif
    </nav>
</div>
