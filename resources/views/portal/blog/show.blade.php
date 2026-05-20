@extends('layouts.portal')

@section('title', $artigo->titulo)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <a href="{{ route('portal.blog') }}" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline">
        ← Voltar ao Blog
    </a>

    <article class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm overflow-hidden">
        @if ($artigo->imagem_capa)
            <img src="{{ $artigo->imagem_capa }}" alt="{{ $artigo->titulo }}" class="w-full max-h-72 object-cover">
        @endif

        <div class="p-8">
            <p class="text-xs text-gray-400 dark:text-slate-500 mb-2">{{ $artigo->publicado_em?->format('d \d\e F \d\e Y') }}</p>
            <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-100 mb-6">{{ $artigo->titulo }}</h1>

            @if ($artigo->resumo)
                <p class="text-lg text-gray-500 dark:text-slate-400 mb-6 italic border-l-4 border-[#0084AA] pl-4">{{ $artigo->resumo }}</p>
            @endif

            <div class="prose prose-gray dark:prose-invert max-w-none">
                {!! nl2br(e($artigo->conteudo)) !!}
            </div>
        </div>
    </article>

</div>
@endsection
