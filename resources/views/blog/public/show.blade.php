@extends('layouts.app')

@section('title', $artigo->titulo . ' — WR Assessoria')

@push('styles')
<style>
    .artigo-conteudo h2 { font-size: 1.4rem; font-weight: 700; margin: 2rem 0 .75rem; color: #f1f5f9; }
    .artigo-conteudo h3 { font-size: 1.1rem; font-weight: 600; margin: 1.5rem 0 .5rem; color: #e2e8f0; }
    .artigo-conteudo p  { margin-bottom: 1rem; color: #cbd5e1; line-height: 1.75; }
    .artigo-conteudo ul, .artigo-conteudo ol { padding-left: 1.5rem; margin-bottom: 1rem; color: #cbd5e1; }
    .artigo-conteudo li { margin-bottom: .4rem; }
    .artigo-conteudo strong { color: #f1f5f9; font-weight: 600; }
    .artigo-conteudo a { color: #0084aa; text-decoration: underline; }
    .artigo-conteudo a:hover { color: #00a3d4; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-3 { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
</style>
@endpush

@section('content')
<div class="pt-28 pb-20 px-4 min-h-screen" style="background:#0a0a0a;">
    <div class="max-w-3xl mx-auto">

        {{-- Breadcrumb --}}
        <nav class="text-xs text-gray-500 mb-8 flex items-center gap-2">
            <a href="/" class="hover:text-white no-underline" style="color:inherit;">Início</a>
            <span>/</span>
            <a href="{{ route('blog.index') }}" class="hover:text-white no-underline" style="color:inherit;">Blog</a>
            <span>/</span>
            <span class="text-gray-400 truncate max-w-xs">{{ $artigo->titulo }}</span>
        </nav>

        {{-- Imagem de capa --}}
        @if ($artigo->imagem_capa)
            <div class="rounded-xl overflow-hidden mb-8 h-64 md:h-80">
                <img src="{{ $artigo->imagem_capa }}" alt="{{ $artigo->titulo }}"
                     class="w-full h-full object-cover">
            </div>
        @endif

        {{-- Meta --}}
        <div class="flex items-center gap-3 text-xs text-gray-500 mb-4">
            <span>{{ $artigo->publicado_em->format('d \d\e F \d\e Y') }}</span>
            @if ($artigo->autor)
                <span>·</span>
                <span>{{ $artigo->autor->nome }}</span>
            @endif
        </div>

        {{-- Título --}}
        <h1 class="text-3xl md:text-4xl font-bold text-white leading-tight mb-6">
            {{ $artigo->titulo }}
        </h1>

        @if ($artigo->resumo)
            <p class="text-lg text-gray-400 border-l-4 pl-4 mb-8 italic" style="border-color:#0084aa;">
                {{ $artigo->resumo }}
            </p>
        @endif

        {{-- Conteúdo --}}
        <div class="artigo-conteudo text-base">
            {!! $artigo->conteudo !!}
        </div>

        {{-- CTA --}}
        <div class="mt-12 rounded-xl p-6 text-center" style="background:rgba(0,132,170,.08); border:1px solid rgba(0,132,170,.2);">
            <p class="text-white font-semibold text-lg mb-2">Precisa de assessoria contábil?</p>
            <p class="text-gray-400 text-sm mb-4">Nossa equipe está pronta para ajudar sua empresa a crescer com segurança.</p>
            <a href="{{ route('funil.captura') }}"
               class="inline-flex items-center gap-2 px-6 py-3 text-white font-semibold rounded-xl no-underline transition-all hover:shadow-[0_0_25px_rgba(0,132,170,.5)] hover:scale-[1.02]"
               style="background:#0084aa;">
                <i class="far fa-calendar-check"></i> Fale com um especialista
            </a>
        </div>

        {{-- Artigos relacionados --}}
        @if ($relacionados->isNotEmpty())
            <div class="mt-14">
                <h2 class="text-white font-semibold text-lg mb-5">Leia também</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach ($relacionados as $rel)
                        <a href="{{ route('blog.show', $rel->slug) }}"
                           class="group block rounded-xl p-4 no-underline transition-all duration-200 hover:-translate-y-1"
                           style="background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07);">
                            @if ($rel->imagem_capa)
                                <div class="h-28 rounded-lg overflow-hidden mb-3">
                                    <img src="{{ $rel->imagem_capa }}" alt="{{ $rel->titulo }}"
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                                </div>
                            @endif
                            <h3 class="text-white text-sm font-medium leading-snug line-clamp-2 group-hover:text-[#0084aa] transition-colors">
                                {{ $rel->titulo }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">{{ $rel->publicado_em->format('d/m/Y') }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
