<header class="bg-white border-b border-gray-200 shadow-sm">
    <div class="w-full px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-3">
                <img src="/images/torresemfundo.png" alt="WR" class="w-16 h-16">
            </div>
        </div>

        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600">{{ auth()->user()?->nome }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-red-600 bg-transparent border-0 p-0 m-0 appearance-none focus:outline-none focus:ring-0 inline-flex items-center hover:bg-gray-200 px-2 py-1 rounded">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </button>
            </form>
        </div>
    </div>
</header>