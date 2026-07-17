@extends('layouts.portal')

@php
    $isEditing = !is_null($produto);
    $action = $isEditing ? route('portal.precificacao.produtos.update', $produto->id) : route('portal.precificacao.produtos.save');
    $voltar = $isEditing ? route('portal.precificacao.show', $produto->id) : route('portal.precificacao.index');
@endphp

@section('title', $isEditing ? 'Editar Produto' : 'Novo Produto')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <a href="{{ $voltar }}" class="text-sm text-[#0084AA] no-underline hover:underline">&larr; Voltar</a>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100 mt-2">{{ $isEditing ? 'Editar Produto' : 'Novo Produto' }}</h1>
    </div>

    <form method="POST" action="{{ $action }}"
          class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm space-y-5">
        @csrf
        @if($isEditing)
            @method('PUT')
        @endif

        <div>
            <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Nome do produto *</label>
            <input type="text" name="nome" id="nome" value="{{ old('nome', $isEditing ? $produto->nome : '') }}" required
                   class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="ncm" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">NCM *</label>
                <input type="text" name="ncm" id="ncm" value="{{ old('ncm', $isEditing ? $produto->ncm : '') }}" required
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
            <div>
                <label for="cest" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">CEST</label>
                <input type="text" name="cest" id="cest" value="{{ old('cest', $isEditing ? $produto->cest : '') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="unidade" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Unidade</label>
                <input type="text" name="unidade" id="unidade" value="{{ old('unidade', $isEditing ? $produto->unidade : '') }}" placeholder="UN, CX, KG..."
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
            <div>
                <label for="codigo_interno" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Código interno</label>
                <input type="text" name="codigo_interno" id="codigo_interno" value="{{ old('codigo_interno', $isEditing ? $produto->codigo_interno : '') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ $voltar }}" class="px-4 py-2 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-[#1e293b] border border-gray-300 dark:border-[#334155] rounded-lg no-underline hover:bg-gray-50 dark:hover:bg-[#334155]">
                Cancelar
            </a>
            <button type="submit" class="px-4 py-2 bg-[#0084AA] text-white text-sm rounded-lg border-0 hover:bg-[#006884]">
                Salvar
            </button>
        </div>
    </form>
</div>
@endsection
