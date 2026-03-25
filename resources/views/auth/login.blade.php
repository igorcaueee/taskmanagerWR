
    <title>@yield('title', 'WR Assessoria - Contabilidade Profissional')</title>
    @include('partials.head')
    
    <!-- Styles / Scripts (Vite centralized) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    
    <div class="min-h-screen flex items-center justify-center py-16">
        <div class="w-full max-w-md p-8 bg-white rounded-xl shadow-lg">
            <div class="flex justify-center mb-6">
                <img src="/images/logo2.png" alt="WR Assessoria" class="w-24 h-24">
            </div>

            @if ($errors->any())
                <div class="mb-4 text-sm text-red-700">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus class="mt-1 block w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Senha</label>
                    <input type="password" name="password" required class="mt-1 block w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-600">
                </div>
                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="remember" class="mr-2"> Lembrar-me
                    </label>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-bold hover:from-blue-700 hover:to-blue-800 transition">Entrar</button>
            </form>
        </div>
    </div>
