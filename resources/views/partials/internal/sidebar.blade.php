<div class="p-4">
    <nav class="space-y-2">
        <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline">Dashboard</a>
        <a href="{{ route('agenda') }}" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline">Agenda</a>
        <!-- Example collapsible submenu: Cadastros -->
        <div>
            <button type="button" class="w-full text-left px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline flex items-center justify-between submenu-toggle focus:outline-none focus:ring-0 bg-transparent border-0 appearance-none" aria-expanded="false" data-target="submenu-cadastros">
                <span class="text-sm text-gray-700">Cadastros</span>
                <svg class="w-4 h-4 text-gray-500 transition-transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>

            <div id="submenu-cadastros" class="hidden pl-3 mt-1 space-y-1">
                <a href="/clientes" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline">Cadastro de Clientes</a>
                <a href="/tarefas" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline">Cadastro de Tarefas</a>
                <a href="/colaboradores" class="block px-3 py-2 rounded hover:bg-gray-100 text-sm text-gray-700 no-underline">Cadastro de Colaboradores</a>
            </div>
        </div>
    </nav>
</div>
