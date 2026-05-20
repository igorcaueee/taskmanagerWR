@extends('layouts.portal')

@section('title', 'Início')

@section('content')
<div class="space-y-8">

    {{-- Boas-vindas --}}
    <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Olá, {{ $cliente->nome }}!</h1>
        <p class="text-gray-500 dark:text-slate-400 mt-1 text-sm">
            Bem-vindo ao seu portal. Aqui você acessa o blog e seus documentos.
        </p>
    </div>

    {{-- Atalhos --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('portal.blog') }}" class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm hover:border-[#0084AA] hover:shadow-md transition group">
            <div class="flex items-center gap-4">
                <div class="bg-blue-50 dark:bg-[#0084AA]/20 text-[#0084AA] rounded-lg p-3 text-2xl group-hover:bg-[#0084AA]/20 transition">📰</div>
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-slate-100">Blog</h2>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Artigos e novidades exclusivos</p>
                </div>
            </div>
        </a>

        <a href="{{ route('portal.arquivos') }}" class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm hover:border-[#0084AA] hover:shadow-md transition group">
            <div class="flex items-center gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-lg p-3 text-2xl group-hover:bg-green-100 dark:group-hover:bg-green-900/30 transition">📁</div>
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-slate-100">Meus Arquivos</h2>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Guias e documentos enviados</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Últimos artigos --}}
    @if ($artigos->count())
    <div>
        <h2 class="text-lg font-semibold text-gray-700 dark:text-slate-200 mb-4">Últimas publicações</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach ($artigos as $artigo)
            <a href="{{ route('portal.blog.show', $artigo->slug) }}" class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl overflow-hidden shadow-sm hover:shadow-md transition group">
                @if ($artigo->imagem_capa)
                    <img src="{{ $artigo->imagem_capa }}" alt="{{ $artigo->titulo }}" class="w-full h-36 object-cover">
                @endif
                <div class="p-4">
                    <p class="text-xs text-gray-400 dark:text-slate-500 mb-1">{{ $artigo->publicado_em?->format('d/m/Y') }}</p>
                    <h3 class="font-semibold text-gray-800 dark:text-slate-100 text-sm group-hover:text-[#0084AA] transition line-clamp-2">{{ $artigo->titulo }}</h3>
                    @if ($artigo->resumo)
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1 line-clamp-2">{{ $artigo->resumo }}</p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
