@php
    $isEditing = !is_null($tipo);
    $action = $isEditing ? route('tipos-tarefa.update', $tipo->id) : route('tipos-tarefa.save');
    $title = $isEditing ? 'Editar Tipo de Tarefa' : 'Novo Tipo de Tarefa';
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        @if($isEditing)
            <i class="fa-solid fa-pen-to-square mr-2"></i>
        @else
            <i class="fa-solid fa-plus mr-2"></i>
        @endif
        {{ $title }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

@if($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded text-sm">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-red-500">*</span></label>
            <input name="nome" type="text"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('nome', $isEditing ? $tipo->nome : '') }}"
                   placeholder="Ex: FGTS, INSS, Folha de Pagamento..."
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fa-solid fa-heading mr-1 text-brand"></i> Título Padrão da Tarefa
            </label>
            <input name="titulo_padrao" type="text"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('titulo_padrao', $isEditing ? $tipo->titulo_padrao : '') }}"
                   placeholder="Ex: Apuração FGTS — Junho/2026">
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Ao selecionar este tipo em uma tarefa, o título será preenchido automaticamente.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fa-regular fa-calendar mr-1 text-brand"></i> Data de Vencimento Padrão
            </label>
            <input name="data_vencimento" type="date"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('data_vencimento', $isEditing && $tipo->data_vencimento ? $tipo->data_vencimento->format('Y-m-d') : '') }}">
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Ao selecionar este tipo em uma tarefa, a data de vencimento será preenchida automaticamente.
            </p>
        </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent dark:bg-transparent">
            Cancelar
        </button>
        <button type="submit" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
            Salvar
        </button>
    </div>
</form>
