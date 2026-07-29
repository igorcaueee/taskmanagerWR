@php
    $isEditing = !is_null($grupo);
    $action = $isEditing ? route('precificacao.ncmGrupos.update', $grupo->id) : route('precificacao.ncmGrupos.save');
    $title = $isEditing ? 'Editar Grupo de NCM' : 'Novo Grupo de NCM';
    $ncmsTexto = $isEditing ? $grupo->itens->pluck('ncm')->join("\n") : '';
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

    <div class="grid grid-cols-1 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Nome do grupo</label>
            <input name="nome" type="text" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('nome', $isEditing ? $grupo->nome : '') }}" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">NCMs do grupo</label>
            <textarea name="ncms" rows="6" class="mt-1 block w-full border rounded px-3 py-2 font-mono text-sm"
                      placeholder="Um NCM (ou prefixo) por linha, ex:&#10;87082999&#10;8708&#10;40111000"
                      required>{{ old('ncms', $ncmsTexto) }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Um NCM por linha (ou separados por vírgula/espaço). Pode usar um prefixo (ex: <span class="font-mono">8708</span>) para incluir todos os NCMs que começam com ele.</p>
            @error('ncms')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input name="ativo" type="checkbox" class="rounded border-gray-300"
                       {{ old('ativo', $isEditing ? $grupo->ativo : true) ? 'checked' : '' }}>
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
