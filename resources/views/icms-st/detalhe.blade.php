@extends('layouts.internal')

@section('title', 'ICMS-ST — NF-e '.($itens->first()->nfe_numero ?? '').' — WR Assessoria')

@php
    $badgeOrigem = [
        'xml' => ['XML', 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300'],
        'atribuido_manualmente' => ['Atribuído manualmente', 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400'],
        'corrigido_manualmente' => ['Corrigido manualmente', 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'],
        'xml_confirmado_manualmente' => ['XML confirmado', 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400'],
    ];
    $badgeStatus = [
        'calculado' => ['Calculado', 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400'],
        'st_ja_destacada_no_xml' => ['ST já no XML', 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300'],
        'nao_sujeito_st' => ['Não sujeito a ST', 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300'],
        'pendente_cest' => ['Pendente: CEST', 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'],
        'pendente_aliquota' => ['Pendente: alíquota', 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'],
        'pendente_reducao_base' => ['Pendente: redução de base', 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'],
        'pendente_fem' => ['Pendente: adicional', 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400'],
        'uf_nao_suportada' => ['UF não suportada', 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400'],
    ];
@endphp

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-file-invoice"></i> NF-e {{ $itens->first()->nfe_numero }}</h1>
                <p class="text-gray-600 dark:text-slate-400 text-sm">{{ $cliente->nome ?? '' }} — chave {{ $chaveAcesso }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('icms-st.guias.form', $chaveAcesso) }}" class="py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors no-underline">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Gerar guia
                </a>
                <a href="{{ $voltarUrl }}" class="text-sm text-[#0084aa] no-underline"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">{{ session('status') }}</div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-x-auto">
            <table class="w-full text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-600 dark:text-slate-400">
                    <tr>
                        <th class="px-3 py-3 text-left">Item</th>
                        <th class="px-3 py-3 text-left">Produto / NCM</th>
                        <th class="px-3 py-3 text-left">CEST</th>
                        <th class="px-3 py-3 text-left">Segmento</th>
                        <th class="px-3 py-3 text-right">Base ST</th>
                        <th class="px-3 py-3 text-right">MVA</th>
                        <th class="px-3 py-3 text-right">Alíq.</th>
                        <th class="px-3 py-3 text-right">ICMS-ST</th>
                        <th class="px-3 py-3 text-right">Adicional</th>
                        <th class="px-3 py-3 text-right">Total</th>
                        <th class="px-3 py-3 text-left">Status</th>
                        <th class="px-3 py-3 text-left">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach ($itens as $item)
                        @php
                            $ov = $overridesCest->get($item->nfe_item);
                            $ovBase = $overridesBase->get($item->nfe_item);
                        @endphp
                        <tr>
                            <td class="px-3 py-3 text-gray-800 dark:text-slate-200">{{ $item->nfe_item }}</td>
                            <td class="px-3 py-3 text-gray-800 dark:text-slate-200 max-w-xs whitespace-normal">
                                {{ $item->produto }}
                                <div class="text-xs text-gray-500 dark:text-slate-400">NCM {{ $item->ncm }} · CFOP {{ $item->cfop }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <div class="text-gray-800 dark:text-slate-200">{{ $item->cest_usado ?? $item->cest_xml ?? '—' }}</div>
                                @if ($item->cest_origem && isset($badgeOrigem[$item->cest_origem]))
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $badgeOrigem[$item->cest_origem][1] }}">{{ $badgeOrigem[$item->cest_origem][0] }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-gray-600 dark:text-slate-400 max-w-[12rem] whitespace-normal text-xs">{{ $item->segmento }}</td>
                            <td class="px-3 py-3 text-right text-gray-800 dark:text-slate-200">{{ $item->base_st_calculada !== null ? 'R$ '.number_format($item->base_st_calculada, 2, ',', '.') : '—' }}</td>
                            <td class="px-3 py-3 text-right text-gray-600 dark:text-slate-400">{{ $item->mva_aplicada_pct !== null ? number_format($item->mva_aplicada_pct, 2, ',', '.').'%' : '—' }}</td>
                            <td class="px-3 py-3 text-right text-gray-600 dark:text-slate-400">{{ $item->aliquota_interna_pct !== null ? number_format($item->aliquota_interna_pct, 2, ',', '.').'%' : '—' }}</td>
                            <td class="px-3 py-3 text-right text-gray-800 dark:text-slate-200">{{ $item->icms_st_devido !== null ? 'R$ '.number_format($item->icms_st_devido, 2, ',', '.') : '—' }}</td>
                            <td class="px-3 py-3 text-right text-gray-800 dark:text-slate-200">{{ $item->adicional_valor !== null ? 'R$ '.number_format($item->adicional_valor, 2, ',', '.') : '—' }}</td>
                            <td class="px-3 py-3 text-right font-semibold text-gray-900 dark:text-slate-100">{{ $item->total_a_recolher !== null ? 'R$ '.number_format($item->total_a_recolher, 2, ',', '.') : '—' }}</td>
                            <td class="px-3 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeStatus[$item->status][1] ?? 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300' }}">{{ $badgeStatus[$item->status][0] ?? $item->status }}</span>
                                @if ($item->status_detalhe)
                                    <div class="text-xs text-gray-500 dark:text-slate-400 max-w-xs whitespace-normal mt-1">{{ $item->status_detalhe }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                @if (in_array($item->status, \App\Models\StCalculo::STATUS_PENDENTES, true) && $item->status !== 'uf_nao_suportada')
                                    <button type="button" onclick="abrirCorrecaoCest('{{ $item->nfe_item }}')" class="flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors appearance-none">
                                        <i class="fa-solid fa-pen"></i> Corrigir
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- <template> fica inerte (não renderiza, não afeta layout/scroll da tabela) --
             o conteúdo só é clonado e exibido dentro de um modal SweetAlert2 quando o
             usuário clica em "Corrigir" (ver abrirCorrecaoCest() abaixo). --}}
        @foreach ($itens as $item)
            @continue(! (in_array($item->status, \App\Models\StCalculo::STATUS_PENDENTES, true) && $item->status !== 'uf_nao_suportada'))
            @php
                $ov = $overridesCest->get($item->nfe_item);
                $ovBase = $overridesBase->get($item->nfe_item);
            @endphp
            <template id="override-{{ $item->nfe_item }}">
                <div class="text-left space-y-6">
                    <form method="POST" action="{{ route('icms-st.overrides.cest') }}" class="space-y-2">
                        @csrf
                        <input type="hidden" name="chave_acesso" value="{{ $chaveAcesso }}">
                        <input type="hidden" name="nfe_item" value="{{ $item->nfe_item }}">
                        <input type="hidden" name="voltar" value="{{ $voltarUrl }}">
                        <p class="text-xs font-semibold text-gray-600 dark:text-slate-400">Atribuir/corrigir CEST</p>
                        @if ($item->cest_xml)
                            <p class="text-xs text-gray-500 dark:text-slate-400">CEST do XML: <strong>{{ $item->cest_xml }}</strong> → CEST corrigido:</p>
                        @endif
                        <input type="text" id="cest-atribuido-{{ $item->nfe_item }}" name="cest_atribuido" maxlength="9" value="{{ $ov->cest_atribuido ?? '' }}" placeholder="Ex.: 2001300" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-slate-400">
                            <input type="checkbox" id="nao-sujeito-st-{{ $item->nfe_item }}" name="nao_sujeito_st" value="1" @checked($ov->nao_sujeito_st ?? false) onchange="document.getElementById('cest-atribuido-{{ $item->nfe_item }}').disabled = this.checked">
                            Este item não é sujeito a ICMS-ST (em vez de atribuir um CEST)
                        </label>
                        <textarea name="fundamento" required placeholder="Fundamento (obrigatório) -- ex.: match de NCM com a tabela oficial, item X, ou motivo de não ser sujeito a ST" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm" rows="2">{{ $ov->fundamento ?? '' }}</textarea>
                        <textarea name="observacao" placeholder="Observação (opcional)" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm" rows="2">{{ $ov->observacao ?? '' }}</textarea>
                        <button type="submit" class="py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors">Salvar e recalcular</button>
                    </form>

                    @if ($item->status === 'pendente_reducao_base')
                        <form method="POST" action="{{ route('icms-st.overrides.base') }}" class="space-y-2">
                            @csrf
                            <input type="hidden" name="chave_acesso" value="{{ $chaveAcesso }}">
                            <input type="hidden" name="nfe_item" value="{{ $item->nfe_item }}">
                            <input type="hidden" name="voltar" value="{{ $voltarUrl }}">
                            <p class="text-xs font-semibold text-gray-600 dark:text-slate-400">Percentual de redução de base do ST no destino ("0" se não houver)</p>
                            <input type="number" step="0.01" min="0" max="100" name="percentual_reducao_pct" value="{{ $ovBase->percentual_reducao_pct ?? '' }}" required class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <textarea name="observacao" placeholder="Observação (opcional)" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm" rows="2">{{ $ovBase->observacao ?? '' }}</textarea>
                            <button type="submit" class="py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors">Salvar e recalcular</button>
                        </form>
                    @endif
                </div>
            </template>
        @endforeach
    </div>
@endsection

@push('scripts')
<script>
    function abrirCorrecaoCest(nfeItem) {
        const template = document.getElementById('override-' + nfeItem);
        const wrapper = document.createElement('div');
        wrapper.appendChild(template.content.cloneNode(true));

        Swal.fire({
            title: 'Corrigir item ' + nfeItem,
            html: wrapper,
            showConfirmButton: false,
            showCloseButton: true,
            width: '40rem',
        });
    }
</script>
@endpush
