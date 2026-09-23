@extends('layouts.internal')

@section('title', 'ICMS-ST — Guia '.$preparado['nNF'].' — WR Assessoria')

@php
    $badgeStatus = [
        'RASCUNHO' => 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300',
        'PRONTA_PARA_EMISSAO' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400',
        'EMITIDA' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400',
        'PAGA' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400',
    ];
@endphp

@section('content')
    <div class="max-w-4xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    Guia de Recolhimento — {{ $uf === 'RS' ? 'GNRE' : 'DAE / SIARE-MG' }}
                </h1>
                <p class="text-gray-600 dark:text-slate-400 text-sm">NF-e {{ $preparado['nNF'] }} — chave {{ $chaveAcesso }}</p>
            </div>
            <a href="{{ route('icms-st.detalhe', $chaveAcesso) }}" class="text-sm text-[#0084aa] no-underline"><i class="fa-solid fa-arrow-left"></i> Voltar à NF-e</a>
        </div>

        @if (session('status'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">{{ session('status') }}</div>
        @endif

        @if (! empty($preparado['pendentes']))
            <div class="mb-6 px-4 py-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-sm">
                <i class="fa-solid fa-triangle-exclamation"></i> Esta NF-e ainda tem {{ count($preparado['pendentes']) }} item(ns) pendente(s) de validação —
                não entraram no valor abaixo. Resolva antes de emitir a guia:
                <ul class="list-disc ml-5 mt-1">
                    @foreach ($preparado['pendentes'] as $p)
                        <li>item {{ $p['item'] }} ({{ $p['produto'] }}): {{ $p['status'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($uf === 'MG')
            <div class="mb-6 px-4 py-3 rounded-lg bg-gray-50 dark:bg-slate-700/50 text-gray-600 dark:text-slate-400 text-sm">
                Portal próprio de MG (SIARE/SEF-MG) — não é a GNRE nacional. Tela "Documento de Arrecadação Estadual" →
                grupo "ICMS" → "ICMS PAGAMENTO POR NOTA FISCAL".
            </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-600 dark:text-slate-400 mb-3">Contribuinte (= destinatário, autolançamento)</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-sm text-gray-800 dark:text-slate-200">
                <div>CNPJ/CPF: {{ $preparado['contribuinte_cnpj'] }}</div>
                <div>IE: {{ $preparado['contribuinte_ie'] }}</div>
                <div>Razão Social: {{ $preparado['contribuinte_nome'] }}</div>
                <div>Município/UF: {{ $preparado['contribuinte_municipio'] }}/{{ $preparado['contribuinte_uf'] }}</div>
                @if ($uf === 'RS')
                    <div class="md:col-span-2">Endereço: {{ $preparado['contribuinte_endereco'] }}</div>
                    <div>CEP: {{ $preparado['contribuinte_cep'] }}</div>
                    <div>Fone: {{ $preparado['contribuinte_fone'] }}</div>
                @endif
            </div>
        </div>

        @if ($guia)
            <div class="mb-6">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeStatus[$guia->status] }}">{{ $guia->status }}</span>
            </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6 mb-6">
            <form method="POST" action="{{ route('icms-st.guias.salvar', $chaveAcesso) }}" class="space-y-4">
                @csrf
                @if ($uf === 'RS')
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Código de receita (GNRE) — escolha explícita, nunca presumida</label>
                        <select name="codigo_receita" required class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach ($receitasGnre as $codigo => $descricao)
                                <option value="{{ $codigo }}" @selected(old('codigo_receita', $guia->codigo_receita ?? '') === $codigo)>{{ $codigo }} - {{ $descricao }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Receita (SIARE)</label>
                        <input type="text" name="codigo_receita" value="{{ old('codigo_receita', $guia->codigo_receita ?? $receitaPadraoMg) }}" required class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Valor ICMS-ST (R$)</label>
                        <input type="number" step="0.01" name="valor_icms_st" value="{{ old('valor_icms_st', $guia->valor_icms_st ?? $preparado['valor_icms_st']) }}" required class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Adicional ({{ $uf === 'RS' ? 'AMPARA/RS' : 'FEM/MG' }}) (R$)</label>
                        <input type="number" step="0.01" name="valor_adicional" value="{{ old('valor_adicional', $guia->valor_adicional ?? $preparado['valor_adicional']) }}" required class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Data de vencimento</label>
                        <input type="date" name="data_vencimento" value="{{ old('data_vencimento', optional($guia?->data_vencimento)->format('Y-m-d')) }}" required class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Data de pagamento (se já paga)</label>
                        <input type="date" name="data_pagamento" value="{{ old('data_pagamento', optional($guia?->data_pagamento)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>

                @if ($uf === 'MG' && $guia?->periodo_referencia_mes)
                    <p class="text-xs text-gray-500 dark:text-slate-400">Período de Referência: Mensal — {{ $guia->periodo_referencia_mes }}/{{ $guia->periodo_referencia_ano }} (mês da data de vencimento)</p>
                @endif

                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Observação</label>
                    <textarea name="observacao" rows="2" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">{{ old('observacao', $guia->observacao ?? '') }}</textarea>
                </div>

                <button type="submit" class="py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors">Salvar dados da guia</button>
                <p class="text-xs text-gray-500 dark:text-slate-400">O sistema não emite a guia — copie estes dados no portal oficial ({{ $uf === 'RS' ? 'GNRE nacional' : 'SIARE/SEF-MG' }}), emita, baixe o PDF e anexe abaixo.</p>
            </form>
        </div>

        @if ($guia)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6 mb-6">
                <h2 class="text-sm font-semibold text-gray-600 dark:text-slate-400 mb-3">Anexos</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">PDF da guia emitida</p>
                        @if ($guia->pdf_guia_path)
                            <a href="{{ route('icms-st.guias.download', [$guia, 'guia']) }}" class="text-[#0084aa] text-sm no-underline"><i class="fa-solid fa-download"></i> Baixar guia anexada</a>
                        @endif
                        <form method="POST" action="{{ route('icms-st.guias.upload-pdf', $guia) }}" enctype="multipart/form-data" class="mt-2 flex items-center gap-2">
                            @csrf
                            <input type="file" name="pdf" accept="application/pdf" required class="text-xs">
                            <button type="submit" class="py-1.5 px-3 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors">Anexar</button>
                        </form>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">Comprovante de pagamento</p>
                        @if ($guia->pdf_comprovante_path)
                            <a href="{{ route('icms-st.guias.download', [$guia, 'comprovante']) }}" class="text-[#0084aa] text-sm no-underline"><i class="fa-solid fa-download"></i> Baixar comprovante anexado</a>
                        @endif
                        <form method="POST" action="{{ route('icms-st.guias.upload-comprovante', $guia) }}" enctype="multipart/form-data" class="mt-2 flex items-center gap-2">
                            @csrf
                            <input type="date" name="data_pagamento" required class="text-xs rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1.5">
                            <input type="file" name="pdf" accept="application/pdf" required class="text-xs">
                            <button type="submit" class="py-1.5 px-3 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors">Anexar</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
