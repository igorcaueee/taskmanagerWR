@extends('layouts.internal')

@section('title', ($instrucao ? 'Editar Instrução' : 'Nova Instrução') . ' — WR Assessoria')

@section('content')
<div class="py-6 px-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('liri.instrucoes.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-brand no-underline text-sm">
            <i class="fa-solid fa-arrow-left"></i> Voltar
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
            {{ $instrucao ? 'Editar Instrução' : 'Nova Instrução' }}
        </h1>
    </div>

    @if (session('error'))
        <div class="mb-4 px-4 py-3 rounded bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="max-w-3xl">
        <form method="POST"
              action="{{ $instrucao ? route('liri.instrucoes.update', $instrucao->id) : route('liri.instrucoes.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if ($instrucao) @method('PUT') @endif

            <div class="bg-white dark:bg-slate-800 rounded shadow p-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Título *</label>
                    <input type="text" name="titulo"
                           value="{{ old('titulo', $instrucao?->titulo) }}" required maxlength="255"
                           placeholder="Ex: Como apurar empresas do Simples Nacional com filial"
                           class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand">
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Conteúdo</label>
                    <textarea name="conteudo" rows="12"
                              placeholder="Escreva aqui a instrução, lição ou orientação que a LIRI deve seguir..."
                              class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-900 dark:text-slate-100 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-brand">{{ old('conteudo', $instrucao?->conteudo) }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Pode ficar em branco se você for enviar um arquivo abaixo — o texto será extraído automaticamente.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Anexar arquivo (PDF ou Word, até 10MB)</label>
                    <input type="file" name="arquivo" accept=".pdf,.doc,.docx"
                           class="w-full text-sm text-gray-700 dark:text-slate-200 file:mr-3 file:py-2 file:px-3 file:rounded file:border-0 file:bg-brand file:text-white file:text-sm file:font-medium hover:file:bg-brand/80 file:cursor-pointer cursor-pointer bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded">

                    @if ($instrucao?->arquivo_nome)
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            <i class="fa-solid fa-paperclip"></i> Arquivo atual: {{ $instrucao->arquivo_nome }} — enviar um novo substitui o texto extraído dele.
                        </p>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('liri.instrucoes.index') }}"
                       class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-slate-600 rounded hover:bg-gray-100 dark:hover:bg-slate-700 no-underline">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-5 py-2 text-sm font-medium bg-brand text-white rounded border-0 hover:bg-brand/80 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk"></i> {{ $instrucao ? 'Salvar Alterações' : 'Criar Instrução' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
