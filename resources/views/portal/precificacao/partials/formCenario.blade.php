@extends('layouts.portal')

@php
    $isEditing = !is_null($cenario);
    $action = $isEditing
        ? route('portal.precificacao.cenarios.update', [$produto->id, $cenario->id])
        : route('portal.precificacao.cenarios.save', $produto->id);
    $voltar = route('portal.precificacao.show', $produto->id);
    $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
    $compraInternacional = old('compra_internacional', $isEditing ? $cenario->compra_internacional : false);
@endphp

@section('title', $isEditing ? 'Editar Cenário' : 'Novo Cenário')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <a href="{{ $voltar }}" class="text-sm text-[#0084AA] no-underline hover:underline">&larr; Voltar para {{ $produto->nome }}</a>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100 mt-2">{{ $isEditing ? 'Editar Cenário' : 'Novo Cenário' }}</h1>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Informe os dados de compra e venda. O resultado é recalculado automaticamente.</p>
    </div>

    <form id="form-cenario" method="POST" action="{{ $action }}"
          class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm space-y-5">
        @csrf
        @if($isEditing)
            @method('PUT')
        @endif

        <div>
            <label for="nome" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Rótulo do cenário</label>
            <input type="text" name="nome" id="nome" value="{{ old('nome', $isEditing ? $cenario->nome : '') }}" placeholder="Ex: Fornecedor SP"
                   class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300 cursor-pointer">
                <input type="checkbox" name="compra_internacional" id="compra_internacional" value="1" {{ $compraInternacional ? 'checked' : '' }}
                       class="rounded border-gray-300 dark:border-[#334155] text-[#0084AA] focus:ring-[#0084AA]">
                Compra internacional (importação)
            </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="uf_compra" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">UF de compra *</label>
                <select name="uf_compra" id="uf_compra" required
                        class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
                    <option value="EX" @selected(old('uf_compra', $isEditing ? $cenario->uf_compra : '') === 'EX')>Exterior (EX)</option>
                    @foreach($ufs as $uf)
                        <option value="{{ $uf }}" @selected(old('uf_compra', $isEditing ? $cenario->uf_compra : '') === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="uf_venda" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">UF de venda *</label>
                <select name="uf_venda" id="uf_venda" required
                        class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
                    @foreach($ufs as $uf)
                        <option value="{{ $uf }}" @selected(old('uf_venda', $isEditing ? $cenario->uf_venda : ($cliente->estado ?? '')) === $uf)>{{ $uf }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="tipo_icms_compra" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Tipo de ICMS na compra *</label>
                <select name="tipo_icms_compra" id="tipo_icms_compra" required
                        class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
                    <option value="normal" @selected(old('tipo_icms_compra', $isEditing ? $cenario->tipo_icms_compra : 'normal') === 'normal')>Normal (tributado)</option>
                    <option value="st" @selected(old('tipo_icms_compra', $isEditing ? $cenario->tipo_icms_compra : '') === 'st')>Substituição Tributária (ST)</option>
                </select>
            </div>
            <div>
                <label for="aliquota_icms_compra_pct" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Alíquota do ICMS na compra (%) *</label>
                <input type="number" step="0.01" min="0" max="100" name="aliquota_icms_compra_pct" id="aliquota_icms_compra_pct"
                       value="{{ old('aliquota_icms_compra_pct', $isEditing ? $cenario->aliquota_icms_compra_pct : '12') }}" required
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
                <div class="flex gap-1 mt-1">
                    <button type="button" data-aliquota="4" class="btn-aliquota-preset px-2 py-0.5 text-xs rounded border border-gray-300 dark:border-[#334155] text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-[#334155] bg-transparent">4%</button>
                    <button type="button" data-aliquota="12" class="btn-aliquota-preset px-2 py-0.5 text-xs rounded border border-gray-300 dark:border-[#334155] text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-[#334155] bg-transparent">12%</button>
                    <button type="button" data-aliquota="17" class="btn-aliquota-preset px-2 py-0.5 text-xs rounded border border-gray-300 dark:border-[#334155] text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-[#334155] bg-transparent">17%</button>
                </div>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Se ST: vira custo extra (ICMS-ST). Se Normal: vira crédito de ICMS na compra.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="valor_compra_total" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Valor total de compra (R$) *</label>
                <input type="number" step="0.01" min="0.01" name="valor_compra_total" id="valor_compra_total" value="{{ old('valor_compra_total', $isEditing ? $cenario->valor_compra_total : '') }}" required
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
            <div>
                <label for="quantidade" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Quantidade *</label>
                <input type="number" step="0.001" min="0.001" name="quantidade" id="quantidade" value="{{ old('quantidade', $isEditing ? $cenario->quantidade : '') }}" required
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="frete_compra" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Frete de compra (R$)</label>
                <input type="number" step="0.01" min="0" name="frete_compra" id="frete_compra" value="{{ old('frete_compra', $isEditing ? $cenario->frete_compra : '0') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
            <div>
                <label for="ipi_pct" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">IPI (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="ipi_pct" id="ipi_pct" value="{{ old('ipi_pct', $isEditing ? $cenario->ipi_pct : '0') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="markup_pct" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Markup (%) *</label>
                <input type="number" step="0.01" min="0" name="markup_pct" id="markup_pct" value="{{ old('markup_pct', $isEditing ? $cenario->markup_pct : '') }}" required
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
            <div>
                <label for="comissao_pct" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Comissão (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="comissao_pct" id="comissao_pct" value="{{ old('comissao_pct', $isEditing ? $cenario->comissao_pct : '0') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
            </div>
            <div>
                <label for="frete_venda_pct" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Frete s/venda (%)</label>
                <input type="number" step="0.01" min="0" max="100" name="frete_venda_pct" id="frete_venda_pct" value="{{ old('frete_venda_pct', $isEditing ? $cenario->frete_venda_pct : '0') }}"
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

    <div id="preview-resultado" class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm">
        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Prévia do cálculo</p>
        <p class="text-sm text-gray-400 dark:text-slate-500">Preencha os campos acima para ver o resultado.</p>
    </div>
</div>

@push('scripts')
<script type="module">
(function () {
    const form = document.getElementById('form-cenario');
    const resultado = document.getElementById('preview-resultado');
    const previewUrl = @json(route('portal.precificacao.cenarios.preview', $produto->id));
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
                resultado.innerHTML = '<p class="text-sm text-gray-400 dark:text-slate-500">Preencha os campos obrigatórios para ver o resultado.</p>';
                return;
            }

            const r = await resp.json();

            resultado.innerHTML = `
                <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-3">Prévia do cálculo</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Custo unitário</p>
                        <p class="text-lg font-semibold text-gray-800 dark:text-slate-100">${formatarMoeda(r.custo_unitario)}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Preço de venda</p>
                        <p class="text-lg font-semibold text-gray-800 dark:text-slate-100">${formatarMoeda(r.preco_venda)}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 dark:text-slate-400">Margem de contribuição</p>
                        <p class="text-lg font-semibold ${r.margem_contribuicao_valor >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'}">${formatarMoeda(r.margem_contribuicao_valor)} (${Number(r.margem_contribuicao_percentual).toFixed(2)}%)</p>
                    </div>
                </div>
                ${!r.aliquota_encontrada ? '<p class="mt-3 text-xs text-yellow-600 dark:text-yellow-400"><i class="fa-solid fa-triangle-exclamation"></i> Nenhuma alíquota cadastrada para este NCM/CEST — impostos considerados como 0%.</p>' : ''}
            `;
        } catch (e) {
            resultado.innerHTML = '<p class="text-sm text-gray-400 dark:text-slate-500">Não foi possível calcular a prévia.</p>';
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
@endpush
@endsection
