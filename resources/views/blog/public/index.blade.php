@extends('layouts.app')

@section('title', 'Blog — WR Assessoria')

@section('content')
<div class="pt-28 pb-20 px-4 min-h-screen" style="background:#0a0a0a;">

    <div class="max-w-6xl mx-auto">
        {{-- Header --}}
        <div class="text-center mb-12">
            <span class="inline-block text-xs font-semibold tracking-widest uppercase mb-3" style="color:#0084aa;">Nosso Blog</span>
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-4">Conteúdo para o seu negócio</h1>
            <p class="text-gray-400 max-w-xl mx-auto">Artigos sobre contabilidade, tributação, abertura de empresas e gestão empresarial para empreendedores brasileiros.</p>
        </div>

        @if ($artigos->isEmpty())
            <div class="text-center py-20 text-gray-500">
                <i class="fa-regular fa-newspaper text-5xl mb-4 block"></i>
                Nenhum artigo publicado ainda.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($artigos as $artigo)
                    <a href="{{ route('blog.show', $artigo->slug) }}"
                       class="group block rounded-xl overflow-hidden no-underline transition-transform duration-200 hover:-translate-y-1"
                       style="background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08);">

                        @if ($artigo->imagem_capa)
                            <div class="h-44 overflow-hidden">
                                <img src="{{ $artigo->imagem_capa }}" alt="{{ $artigo->titulo }}"
                                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                            </div>
                        @else
                            <div class="h-44 flex items-center justify-center" style="background:rgba(0,132,170,.08);">
                                <i class="fa-solid fa-newspaper text-4xl" style="color:rgba(0,132,170,.4);"></i>
                            </div>
                        @endif

                        <div class="p-5">
                            <p class="text-xs text-gray-500 mb-2">
                                {{ $artigo->publicado_em->format('d/m/Y') }}
                                @if ($artigo->autor)
                                    · {{ $artigo->autor->nome }}
                                @endif
                            </p>
                            <h2 class="text-white font-semibold text-base leading-snug mb-2 group-hover:text-[#0084aa] transition-colors line-clamp-2">
                                {{ $artigo->titulo }}
                            </h2>
                            @if ($artigo->resumo)
                                <p class="text-gray-400 text-sm line-clamp-3">{{ $artigo->resumo }}</p>
                            @endif
                            <span class="inline-block mt-4 text-xs font-semibold" style="color:#0084aa;">Ler artigo →</span>
                        </div>
                    </a>
                @endforeach
            </div>

            @if ($artigos->hasPages())
                <div class="mt-10 flex justify-center">
                    {{ $artigos->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
