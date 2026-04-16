@php
    $isEditing = !is_null($produto);
    $action = $isEditing ? route('produtos.update', $produto->id) : route('produtos.save');
    $title = $isEditing ? 'Editar Produto' : 'Novo Produto';
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        @if($isEditing)
            <i class="fa-solid fa-pen-to-square mr-2"></i>
        @else
            <i class="fa-solid fa-plus mr-2"></i>
        @endif
        {{ $title }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<form method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Nome</label>
            <input name="nome" type="text"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('nome', $isEditing ? $produto->nome : '') }}"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Descrição</label>
            <input name="descricao" type="text"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('descricao', $isEditing ? $produto->descricao : '') }}"
                   placeholder="Breve descrição do produto/serviço">
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input name="ativo" type="checkbox" class="rounded border-gray-300"
                       {{ old('ativo', $isEditing ? $produto->ativo : true) ? 'checked' : '' }}>
                Ativo
            </label>
        </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 bg-transparent">
            Cancelar
        </button>
        <button type="submit" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
            Salvar
        </button>
    </div>
</form>
