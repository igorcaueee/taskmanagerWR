@php
    $isEditing = !is_null($cenario);
    $action = $isEditing
        ? route('precificacao.cenarios.update', [$produto->id, $cenario->id])
        : route('precificacao.cenarios.save', $produto->id);
    $title = $isEditing ? 'Editar Cenário' : 'Novo Cenário';
    $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
    $compraInternacional = old('compra_internacional', $isEditing ? $cenario->compra_internacional : false);
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        @if($isEditing)
            <i class="fa-solid fa-pen-to-square mr-2"></i>
        @else
            <i class="fa-solid fa-plus mr-2"></i>
        @endif
        {{ $title }} — {{ $produto->nome }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<form id="form-cenario" method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="grid grid-cols-2 gap-4">
        <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Rótulo do cenário</label>
            <input name="nome" type="text" class="mt-1 block w-full border rounded px-3 py-2" placeholder="Ex: Fornecedor SP"
                   value="{{ old('nome', $isEditing ? $cenario->nome : '') }}">
        </div>

        <div class="col-span-2">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="compra_internacional" id="compra_internacional" value="1" {{ $compraInternacional ? 'checked' : '' }} class="rounded border-gray-300">
                Compra internacional (importação)
            </label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">UF de compra</label>
            <select name="uf_compra" id="uf_compra" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="EX" @selected(old('uf_compra', $isEditing ? $cenario->uf_compra : '') === 'EX')>Exterior (EX)</option>
                @foreach($ufs as $uf)
                    <option value="{{ $uf }}" @selected(old('uf_compra', $isEditing ? $cenario->uf_compra : '') === $uf)>{{ $uf }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">UF de venda</label>
            <select name="uf_venda" class="mt-1 block w-full border rounded px-3 py-2">
                @foreach($ufs as $uf)
                    <option value="{{ $uf }}" @selected(old('uf_venda', $isEditing ? $cenario->uf_venda : ($produto->cliente->estado ?? '')) === $uf)>{{ $uf }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Tipo de ICMS na compra</label>
            <select name="tipo_icms_compra" id="tipo_icms_compra" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="normal" @selected(old('tipo_icms_compra', $isEditing ? $cenario->tipo_icms_compra : 'normal') === 'normal')>Normal (tributado)</option>
                <option value="st" @selected(old('tipo_icms_compra', $isEditing ? $cenario->tipo_icms_compra : '') === 'st')>Substituição Tributária (ST)</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Alíquota do ICMS na compra (%)</label>
            <input name="aliquota_icms_compra_pct" id="aliquota_icms_compra_pct" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('aliquota_icms_compra_pct', $isEditing ? $cenario->aliquota_icms_compra_pct : '12') }}" required>
            <div class="flex gap-1 mt-1">
                <button type="button" data-aliquota="4" class="btn-aliquota-preset px-2 py-0.5 text-xs rounded border border-gray-300 text-gray-600 hover:bg-gray-100 bg-transparent">4%</button>
                <button type="button" data-aliquota="12" class="btn-aliquota-preset px-2 py-0.5 text-xs rounded border border-gray-300 text-gray-600 hover:bg-gray-100 bg-transparent">12%</button>
                <button type="button" data-aliquota="17" class="btn-aliquota-preset px-2 py-0.5 text-xs rounded border border-gray-300 text-gray-600 hover:bg-gray-100 bg-transparent">17%</button>
            </div>
            <p class="text-xs text-gray-400 mt-1">Se ST: vira custo extra. Se Normal: vira crédito de ICMS.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Valor total de compra (R$)</label>
            <input name="valor_compra_total" type="number" step="0.01" min="0.01" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('valor_compra_total', $isEditing ? $cenario->valor_compra_total : '') }}" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Quantidade</label>
            <input name="quantidade" type="number" step="0.001" min="0.001" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('quantidade', $isEditing ? $cenario->quantidade : '') }}" required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Frete de compra (R$)</label>
            <input name="frete_compra" type="number" step="0.01" min="0" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('frete_compra', $isEditing ? $cenario->frete_compra : '0') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">IPI (%)</label>
            <input name="ipi_pct" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('ipi_pct', $isEditing ? $cenario->ipi_pct : '0') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Markup (%)</label>
            <input name="markup_pct" type="number" step="0.01" min="0" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('markup_pct', $isEditing ? $cenario->markup_pct : '') }}" required>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Comissão (%)</label>
            <input name="comissao_pct" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('comissao_pct', $isEditing ? $cenario->comissao_pct : '0') }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Frete s/venda (%)</label>
            <input name="frete_venda_pct" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('frete_venda_pct', $isEditing ? $cenario->frete_venda_pct : '0') }}">
        </div>
    </div>

    <div id="preview-resultado" class="mt-4 p-4 bg-gray-50 rounded text-sm">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Prévia do cálculo</p>
        <p class="text-gray-400">Preencha os campos acima para ver o resultado.</p>
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
    const form = container ? container.querySelector('#form-cenario') : null;
    if (!form) { return; }

    const markDirty = function () { window._modalHasChanges = true; };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', function () { window._modalHasChanges = false; });

    const resultado = document.getElementById('preview-resultado');
    const previewUrl = @json(route('precificacao.cenarios.preview', $produto->id));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    function formatarMoeda(v) {
        return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    async function recalcular() {
        const data = new FormData(form);

        try {
            const resp = await fetch(previewUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: data,
            });

            if (!resp.ok) {
                resultado.innerHTML = '<p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Prévia do cálculo</p><p class="text-gray-400">Preencha os campos obrigatórios para ver o resultado.</p>';
                return;
            }

            const r = await resp.json();

            resultado.innerHTML = `
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Prévia do cálculo</p>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-gray-500">Custo unitário</p>
                        <p class="text-base font-semibold text-gray-800">${formatarMoeda(r.custo_unitario)}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Preço de venda</p>
                        <p class="text-base font-semibold text-gray-800">${formatarMoeda(r.preco_venda)}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Margem</p>
                        <p class="text-base font-semibold ${r.margem_contribuicao_valor >= 0 ? 'text-green-600' : 'text-red-600'}">${formatarMoeda(r.margem_contribuicao_valor)} (${Number(r.margem_contribuicao_percentual).toFixed(2)}%)</p>
                    </div>
                </div>
                ${!r.aliquota_encontrada ? '<p class="mt-2 text-xs text-yellow-600"><i class="fa-solid fa-triangle-exclamation"></i> Nenhuma alíquota cadastrada para este NCM/CEST — impostos considerados como 0%.</p>' : ''}
            `;
        } catch (e) {
            resultado.innerHTML = '<p class="text-gray-400">Não foi possível calcular a prévia.</p>';
        }
    }

    const compraInternacionalCheckbox = document.getElementById('compra_internacional');
    const ufCompraSelect = document.getElementById('uf_compra');

    function aplicarCompraInternacional() {
        if (compraInternacionalCheckbox.checked) {
            ufCompraSelect.value = 'EX';
        } else if (ufCompraSelect.value === 'EX') {
            ufCompraSelect.value = '';
        }
    }
    compraInternacionalCheckbox.addEventListener('change', function () {
        aplicarCompraInternacional();
        recalcular();
    });
    aplicarCompraInternacional();

    document.querySelectorAll('.btn-aliquota-preset').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('aliquota_icms_compra_pct').value = btn.dataset.aliquota;
            recalcular();
        });
    });

    form.addEventListener('input', recalcular);
    form.addEventListener('change', recalcular);
    recalcular();
})();
</script>
