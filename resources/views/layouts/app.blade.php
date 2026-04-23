<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="WR Assessoria - Expertise em contabilidade e gestão empresarial">

        <title>@yield('title', 'WR Assessoria - Contabilidade Profissional')</title>
        @include('partials.head')

        <!-- Styles / Scripts (Vite centralized) -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        @stack('styles')
    </head>
    <body class="bg-black text-gray-100 antialiased">
        @include('partials.nav')

        <main>
            @yield('content')
        </main>

        @include('partials.footer')
    </body>
</html>
