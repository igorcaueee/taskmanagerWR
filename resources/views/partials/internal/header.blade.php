<header class="bg-white border-b border-gray-200 shadow-sm">
    <div class="w-full px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <button id="nav-toggle" aria-controls="main-sidebar" class="p-2 rounded hover:bg-gray-100 bg-transparent border-0 appearance-none focus:outline-none focus:ring-0">
                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div class="flex items-center gap-3">
                <img src="/images/logo7.png" alt="WR" class="w-8 h-8">
            </div>
        </div>

        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">{{ auth()->user()?->nome }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-red-600 hover:underline">Sair</button>
            </form>
        </div>
    </div>
</header>