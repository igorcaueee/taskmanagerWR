@extends('layouts.portal')

@section('title', 'Dashboard Fiscal')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Dashboard Fiscal</h1>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Indicadores fiscais de {{ $cliente->nome }}.</p>
    </div>

    @if (! $dashboard)
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-10 text-center text-gray-400 dark:text-slate-500 shadow-sm">
            <p class="text-5xl mb-3">📊</p>
            <p class="font-medium">{{ $erro ?? 'Dashboard fiscal indisponível no momento.' }}</p>
            <p class="text-xs mt-1">Entre em contato com a WR Assessoria caso precise de mais informações.</p>
        </div>
    @else
        @php
            $empresa = $dashboard['empresa'];
            $kpis = $dashboard['kpis'];
            $faturamentoMensal = $dashboard['faturamentoMensal'];
            $rbt12 = $dashboard['rbt12'];
            $icms = $dashboard['icms'];
            $pis = $dashboard['pis'];
            $cofins = $dashboard['cofins'];
        @endphp

        {{-- Cabeçalho da empresa --}}
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-semibold text-gray-800 dark:text-slate-100">{{ $empresa['nome'] ?? $cliente->nome }}</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                    {{ $empresa['cnpj'] ?? $cliente->cpfcnpj }} · Período: {{ $empresa['periodo'] ?? '—' }}
                </p>
            </div>
            @if(!empty($dashboard['regime']))
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400">
                    {{ str_replace('_', ' ', mb_strtoupper($dashboard['regime'])) }}
                </span>
            @endif
        </div>

        {{-- KPIs --}}
        @if(!empty($kpis))
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($kpis as $kpi)
                <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm p-4">
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-medium uppercase tracking-wide">{{ $kpi['label'] }}</p>
                    <p class="text-xl font-bold text-gray-800 dark:text-slate-100 mt-1">
                        @if(!empty($kpi['isPercent']))
                            {{ number_format($kpi['valor'], 2, ',', '.') }}%
                        @else
                            R$ {{ number_format($kpi['valor'], 2, ',', '.') }}
                        @endif
                    </p>
                    @if(!empty($kpi['extra']))
                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">{{ $kpi['extra'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        {{-- RBT12 (Simples Nacional) --}}
        @if($rbt12)
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm p-5">
            <p class="text-xs text-gray-500 dark:text-slate-400 font-medium uppercase tracking-wide mb-1">Receita Bruta dos Últimos 12 Meses (RBT12)</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-slate-100">R$ {{ number_format($rbt12['valor'], 2, ',', '.') }}</p>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">{{ $rbt12['referencia'] ?? '' }} · Faixa: {{ $rbt12['faixa'] ?? '—' }}</p>
        </div>
        @endif

        {{-- Faturamento mensal --}}
        @if(!empty($faturamentoMensal))
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm p-5">
            <p class="text-sm font-semibold text-gray-800 dark:text-slate-100 mb-3">Compras, Vendas e Serviços Prestados</p>
            <div class="h-64"><canvas id="chartFaturamento"></canvas></div>
        </div>
        @endif

        {{-- Evolução mensal dos impostos (histórico completo, com destaque do mês selecionado) --}}
        @foreach ([['ICMS', $icms, 'chartIcms', '#ef4444'], ['PIS', $pis, 'chartPis', '#f59e0b'], ['COFINS', $cofins, 'chartCofins', '#8b5cf6']] as [$nomeImposto, $dadosImposto, $chartId, $cor])
            @if($dadosImposto)
            <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm p-5">
                <p class="text-xs text-gray-500 dark:text-slate-400 font-medium uppercase tracking-wide mb-3">Evolução Mensal — {{ $nomeImposto }}</p>
                <div class="h-48"><canvas id="{{ $chartId }}" data-cor="{{ $cor }}" data-labels='@json(collect($dadosImposto['evolucao'])->pluck('mes'))' data-valores='@json(collect($dadosImposto['evolucao'])->pluck('valor'))'></canvas></div>
            </div>
            @endif
        @endforeach

        {{-- Detalhamento por mês (seletor) --}}
        @if(!empty($dashboard['meses']))
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <p class="text-sm font-semibold text-gray-800 dark:text-slate-100">Detalhamento do Mês</p>
                <select id="seletor-mes" class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-[#0084AA]">
                    @foreach($dashboard['meses'] as $i => $mes)
                        <option value="{{ $i }}" @selected($i === count($dashboard['meses']) - 1)>{{ $mes['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div id="mes-cards" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-5"></div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-medium uppercase tracking-wide mb-2">Faturamento por CFOP</p>
                    <div id="mes-cfop-vendas" class="space-y-2.5"></div>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-medium uppercase tracking-wide mb-2">Compras por CFOP</p>
                    <div id="mes-cfop-compras" class="space-y-2.5"></div>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-medium uppercase tracking-wide mb-2">Top Clientes</p>
                    <div id="mes-top-clientes" class="space-y-2.5"></div>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-medium uppercase tracking-wide mb-2">Top Fornecedores</p>
                    <div id="mes-top-fornecedores" class="space-y-2.5"></div>
                </div>
            </div>
        </div>
        @endif
    @endif

</div>

@if($dashboard)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const isDark = document.documentElement.classList.contains('dark');
    Chart.defaults.color = isDark ? '#cbd5e1' : '#475569';

    const MESES = @json($dashboard['meses'] ?? []);
    const CFOP_LABELS = @json(\App\Support\Cfop::todas());
    const charts = [];

    const chartOpts = {
        responsive: true,
        maintainAspectRatio: false,
        animation: false,
        scales: { y: { beginAtZero: true } },
    };

    function corDestaque(qtd, idxAtivo, corPadrao) {
        return Array.from({ length: qtd }, (_, i) => i === idxAtivo ? corPadrao : corPadrao + '55');
    }

    @if(!empty($faturamentoMensal))
    const chartFaturamento = new Chart(document.getElementById('chartFaturamento'), {
        type: 'bar',
        data: {
            labels: @json(collect($faturamentoMensal)->pluck('mes')),
            datasets: [
                { label: 'Vendas', data: @json(collect($faturamentoMensal)->pluck('vendas')), backgroundColor: '#0084AA', borderRadius: 4 },
                { label: 'Compras', data: @json(collect($faturamentoMensal)->pluck('compras')), backgroundColor: '#f59e0b', borderRadius: 4 },
                { label: 'Serviços', data: @json(collect($faturamentoMensal)->pluck('servicos')), backgroundColor: '#10b981', borderRadius: 4 },
            ],
        },
        options: chartOpts,
    });
    charts.push({ chart: chartFaturamento, cores: ['#0084AA', '#f59e0b', '#10b981'] });
    @endif

    document.querySelectorAll('canvas[data-labels]').forEach(function (canvas) {
        const cor = canvas.dataset.cor;
        const chart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: JSON.parse(canvas.dataset.labels),
                datasets: [{
                    label: 'Valor (R$)',
                    data: JSON.parse(canvas.dataset.valores),
                    backgroundColor: cor,
                    borderRadius: 4,
                }],
            },
            options: { ...chartOpts, plugins: { legend: { display: false } } },
        });
        charts.push({ chart, cores: [cor] });
    });

    function destacarMes(idx) {
        charts.forEach(({ chart, cores }) => {
            chart.data.datasets.forEach((ds, di) => {
                const n = ds.data.length;
                ds.backgroundColor = Array.from({ length: n }, (_, i) => i === idx ? cores[di % cores.length] : cores[di % cores.length] + '40');
            });
            chart.update();
        });
    }

    function formatarMoeda(v) {
        return 'R$ ' + Number(v ?? 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function barraLista(itens, corBarra, tipo) {
        if (!itens || !itens.length) {
            return '<p class="text-xs text-gray-400 dark:text-slate-500">Nenhum dado para o mês selecionado.</p>';
        }
        const max = Math.max(...itens.map(i => i.valor || 0)) || 1;
        return itens.map(item => {
            const label = tipo === 'cfop'
                ? `${item.cfop} — ${CFOP_LABELS[item.cfop] ?? ('CFOP ' + item.cfop)}`
                : `${item.nome}${item.cnpj_cpf ? ' — ' + item.cnpj_cpf : ''}`;
            const pct = Math.round((item.valor / max) * 1000) / 10;
            return `<div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-600 dark:text-slate-300 truncate pr-2">${label}</span>
                    <span class="font-medium text-gray-800 dark:text-slate-100 whitespace-nowrap">${formatarMoeda(item.valor)}</span>
                </div>
                <div class="h-1.5 rounded-full bg-gray-100 dark:bg-slate-700 overflow-hidden">
                    <div class="h-full ${corBarra}" style="width: ${pct}%"></div>
                </div>
            </div>`;
        }).join('');
    }

    function cardImposto(nome, dados, corBg, corTxt) {
        if (!dados) return '';
        const saldoNegativo = (dados.saldo ?? 0) < 0;
        return `<div class="bg-gray-50 dark:bg-slate-900/40 rounded-lg p-3">
            <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mb-2">${nome}</p>
            <div class="grid grid-cols-3 gap-1.5 text-center">
                ${dados.debito !== null && dados.debito !== undefined ? `<div><p class="text-[10px] text-red-500">Débito</p><p class="text-xs font-bold text-red-700 dark:text-red-400">${formatarMoeda(dados.debito)}</p></div>` : '<div></div>'}
                ${dados.credito !== null && dados.credito !== undefined ? `<div><p class="text-[10px] text-green-600">Crédito</p><p class="text-xs font-bold text-green-700 dark:text-green-400">${formatarMoeda(dados.credito)}</p></div>` : '<div></div>'}
                <div><p class="text-[10px] ${saldoNegativo ? 'text-green-600' : 'text-amber-600'}">${saldoNegativo ? 'Saldo Credor' : 'A Pagar'}</p><p class="text-xs font-bold ${saldoNegativo ? 'text-green-700 dark:text-green-400' : 'text-amber-700 dark:text-amber-400'}">${formatarMoeda(Math.abs(dados.saldo ?? 0))}</p></div>
            </div>
        </div>`;
    }

    function renderMes(idx) {
        const mes = MESES[idx];
        if (!mes) return;

        document.getElementById('mes-cards').innerHTML = [
            cardImposto('ICMS', mes.icms),
            cardImposto('PIS', mes.pis),
            cardImposto('COFINS', mes.cofins),
        ].join('');

        document.getElementById('mes-cfop-vendas').innerHTML = barraLista(mes.cfopVendas, 'bg-[#0084AA]', 'cfop');
        document.getElementById('mes-cfop-compras').innerHTML = barraLista(mes.cfopCompras, 'bg-purple-500', 'cfop');
        document.getElementById('mes-top-clientes').innerHTML = barraLista(mes.topClientes, 'bg-[#0084AA]', 'entidade');
        document.getElementById('mes-top-fornecedores').innerHTML = barraLista(mes.topFornecedores, 'bg-amber-500', 'entidade');

        destacarMes(idx);
    }

    const seletorMes = document.getElementById('seletor-mes');
    if (seletorMes) {
        seletorMes.addEventListener('change', () => renderMes(Number(seletorMes.value)));
        renderMes(Number(seletorMes.value));
    }
});
</script>
@endpush
@endif

@endsection
