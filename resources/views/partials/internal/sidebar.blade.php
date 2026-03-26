<div class="p-4">
    <nav class="space-y-2">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
        <a href="{{ route('agenda') }}" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline"><i class="fa-solid fa-calendar-days"></i> Agenda</a>
        <!-- Example collapsible submenu: Cadastros -->
        <div>
            <button type="button" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline flex items-center justify-between submenu-toggle focus:outline-none focus:ring-0 bg-transparent border-0 appearance-none" aria-expanded="false" data-target="submenu-cadastros">
                <span class="text-sm text-gray-700"><i class="fa-regular fa-square-plus"></i> Cadastros</span>
                <svg class="w-4 h-4 text-gray-500 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div id="submenu-cadastros" class="hidden pl-3 mt-1 space-y-1">
                <a href="/clientes" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline"><i class="fa-regular fa-building"></i> Clientes</a>
                <a href="/tarefas" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline"><i class="fa-solid fa-list-check"></i> Tarefas</a>
                @if (auth()->user()?->cargo === 'diretor')
                    <a href="/colaboradores" class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline"><i class="fa-regular fa-user"></i> Colaboradores</a>
                @endif
            </div>
        </div>
    </nav>
</div>
