@php
    $isEditing = !is_null($aliquota);
    $action = $isEditing ? route('precificacao.aliquotas.update', $aliquota->id) : route('precificacao.aliquotas.save');
    $title = $isEditing ? 'Editar Alíquota' : 'Nova Alíquota';
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

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">NCM</label>
            <input name="ncm" type="text" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('ncm', $isEditing ? $aliquota->ncm : '') }}" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">CEST</label>
            <input name="cest" type="text" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('cest', $isEditing ? $aliquota->cest : '') }}">
        </div>

        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Descrição</label>
            <input name="descricao" type="text" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('descricao', $isEditing ? $aliquota->descricao : '') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">UF de referência</label>
            <input name="uf_referencia" type="text" maxlength="2" class="mt-1 block w-full border rounded px-3 py-2 uppercase"
                   value="{{ old('uf_referencia', $isEditing ? $aliquota->uf_referencia : '') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">ICMS interno (%)</label>
            <input name="aliquota_icms_interna" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('aliquota_icms_interna', $isEditing ? $aliquota->aliquota_icms_interna : '0') }}" required>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer mt-6">
                <input name="aplica_st" type="checkbox" class="rounded border-gray-300"
                       {{ old('aplica_st', $isEditing ? $aliquota->aplica_st : false) ? 'checked' : '' }}>
                Aplica Substituição Tributária
            </label>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">ICMS-ST efetivo na compra (%)</label>
            <input name="aliquota_icms_st" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('aliquota_icms_st', $isEditing ? $aliquota->aliquota_icms_st : '0') }}" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Regime PIS/COFINS</label>
            <select name="regime_pis_cofins" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="tributado" @selected(old('regime_pis_cofins', $isEditing ? $aliquota->regime_pis_cofins : 'tributado') === 'tributado')>Tributado</option>
                <option value="monofasico" @selected(old('regime_pis_cofins', $isEditing ? $aliquota->regime_pis_cofins : '') === 'monofasico')>Monofásico (0% na venda)</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">PIS (%)</label>
            <input name="aliquota_pis" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('aliquota_pis', $isEditing ? $aliquota->aliquota_pis : '0') }}" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">COFINS (%)</label>
            <input name="aliquota_cofins" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('aliquota_cofins', $isEditing ? $aliquota->aliquota_cofins : '0') }}" required>
        </div>

        <div class="col-span-2">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input name="ativo" type="checkbox" class="rounded border-gray-300"
                       {{ old('ativo', $isEditing ? $aliquota->ativo : true) ? 'checked' : '' }}>
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
