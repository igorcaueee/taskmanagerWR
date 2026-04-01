<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="WR Assessoria - Área Interna">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Painel — WR Assessoria')</title>
        @include('partials.head')

        <style>
            /* Sidebar always visible */
            .sidebar { width: 16rem; }

            /* Mobile: sidebar slides over content; use .open to show */
            @media (max-width: 767px) {
                .sidebar { transform: translateX(-100%); position: fixed; z-index: 40; top: 0; left: 0; height: 100vh; transition: transform .2s ease-in-out; }
                .sidebar.open { transform: translateX(0); }
            }
        </style>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-gray-50 text-gray-900 min-h-screen">
        @include('partials.internal.header')

        <div class="flex">
            <aside id="main-sidebar" class="sidebar w-64 bg-white border-r border-gray-200 min-h-[calc(100vh-64px)] md:static md:block">
                @include('partials.internal.sidebar')
            </aside>

            <div class="flex-1 p-6 main-content">
                @yield('content')
            </div>
        </div>

        {{-- Global modal --}}
        <div id="globalModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div id="modalContent" class="p-6"></div>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>
