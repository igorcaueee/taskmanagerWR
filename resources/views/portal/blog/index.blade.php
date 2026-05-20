@extends('layouts.portal')

@section('title', 'Blog')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-2xl font-bold text-gray-800">Blog</h1>

        <form method="GET" action="{{ route('portal.blog') }}" class="flex gap-2">
            <input
                type="text"
                name="busca"
                value="{{ request('busca') }}"
                placeholder="Buscar artigos..."
                class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-500"
            >
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition">Buscar</button>
        </form>
    </div>

    @if ($artigos->isEmpty())
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-10 text-center text-gray-400 dark:text-slate-500 shadow-sm">
            <p class="text-4xl mb-3">📭</p>
            <p class="font-medium">Nenhum artigo encontrado.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($artigos as $artigo)
            <a href="{{ route('portal.blog.show', $artigo->slug) }}" class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl overflow-hidden shadow-sm hover:shadow-md transition group flex flex-col">
                @if ($artigo->imagem_capa)
                    <img src="{{ $artigo->imagem_capa }}" alt="{{ $artigo->titulo }}" class="w-full h-40 object-cover">
                @else
                    <div class="w-full h-40 bg-gradient-to-br from-blue-50 dark:from-[#0084AA]/10 to-gray-100 dark:to-[#334155] flex items-center justify-center text-5xl">📄</div>
                @endif
                <div class="p-5 flex flex-col flex-1">
                    <p class="text-xs text-gray-400 dark:text-slate-500 mb-1">{{ $artigo->publicado_em?->format('d/m/Y') }}</p>
                    <h2 class="font-semibold text-gray-800 dark:text-slate-100 group-hover:text-[#0084AA] transition line-clamp-2 flex-1">{{ $artigo->titulo }}</h2>
                    @if ($artigo->resumo)
                        <p class="text-sm text-gray-500 dark:text-slate-400 mt-2 line-clamp-3">{{ $artigo->resumo }}</p>
                    @endif
                    <span class="mt-4 text-xs text-[#0084AA] font-medium">Ler mais →</span>
                </div>
            </a>
            @endforeach
        </div>

        <div>
            {{ $artigos->links() }}
        </div>
    @endif

</div>
@endsection
