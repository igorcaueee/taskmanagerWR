@php
    $isEditing = !is_null($produto);
    $action = $isEditing ? route('precificacao.produtos.update', $produto->id) : route('precificacao.produtos.save');
    $title = $isEditing ? 'Editar Produto' : 'Novo Produto';
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        @if($isEditing)
            <i class="fa-solid fa-pen-to-square mr-2"></i>
        @else
            <i class="fa-solid fa-plus mr-2"></i>
        @endif
        {{ $title }} — {{ $cliente->nome }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<form method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @else
        <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
    @endif

    <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Nome do produto</label>
            <input name="nome" type="text" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('nome', $isEditing ? $produto->nome : '') }}" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">NCM</label>
            <input name="ncm" type="text" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('ncm', $isEditing ? $produto->ncm : '') }}" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">CEST</label>
            <input name="cest" type="text" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('cest', $isEditing ? $produto->cest : '') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Unidade</label>
            <input name="unidade" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="UN, CX, KG..."
                   value="{{ old('unidade', $isEditing ? $produto->unidade : '') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Código interno</label>
            <input name="codigo_interno" type="text" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('codigo_interno', $isEditing ? $produto->codigo_interno : '') }}">
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

<script>
(function () {
    const container = document.getElementById('modalContent');
    const form = container ? container.querySelector('form') : null;
    if (!form) { return; }
    const markDirty = function () { window._modalHasChanges = true; };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', function () { window._modalHasChanges = false; });
})();
</script>
