@extends('layouts.internal')

@section('title', 'Painel — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Painel</h1>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Usuários ativos</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalUsuariosAtivos }}</p>
            </div>
        </div>
    </div>
@endsection
