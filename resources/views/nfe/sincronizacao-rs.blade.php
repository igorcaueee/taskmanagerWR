@extends('layouts.internal')

@section('title', 'Acompanhamento da Sincronização Fiscal (SEFAZ-RS)')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    {{-- Cabeçalho --}}
    <div class="mb-6">
        <a href="{{ route('nfe.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2 mt-1">
            <i class="fa-solid fa-list-check text-[#0084aa]"></i>
            Acompanhamento da Sincronização Fiscal (SEFAZ-RS)
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Log das rotinas
            <code class="text-xs bg-gray-100 dark:bg-slate-700 rounded px-1 py-0.5">fiscal:sincronizar-notas-rs</code> (diária às 18:30, para às 07:00)
            e <code class="text-xs bg-gray-100 dark:bg-slate-700 rounded px-1 py-0.5">fiscal:reconsultar-notas-rs</code> (diária às 07:15, sem corte)
            — NF-e, NFC-e e CT-e via SEFAZ-RS dos clientes com "Importar notas" ativada.
        </p>
    </div>

    {{-- Seletor de período --}}
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        @foreach(['cliente_id','status','tipo'] as $keep)
            @if(request()->filled($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif
        @endforeach
        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Período das métricas</label>
            <select name="dias" onchange="this.form.submit()"
                    class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                @foreach([7,14,30,60,90] as $d)
                    <option value="{{ $d }}" @selected($dias === $d)>Últimos {{ $d }} dias</option>
                @endforeach
            </select>
        </div>
        <span class="text-xs text-gray-400 pb-2">desde {{ $desde->format('d/m/Y') }}</span>
    </form>

    {{-- Cards de resumo --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        @php
            $taxaSucesso = $resumo['total'] > 0 ? round($resumo['sucesso'] / $resumo['total'] * 100, 1) : 0;
            $cards = [
                ['Execuções (fases)', number_format($resumo['total'], 0, ',', '.'), 'fa-database', 'text-sky-500'],
                ['Taxa de sucesso', $taxaSucesso.'%', 'fa-circle-check', $taxaSucesso >= 99 ? 'text-emerald-500' : ($taxaSucesso >= 95 ? 'text-amber-500' : 'text-red-500')],
                ['Com erro', number_format($resumo['erro'], 0, ',', '.'), 'fa-triangle-exclamation', $resumo['erro'] > 0 ? 'text-red-500' : 'text-gray-400'],
                ['Rodaram no expediente', number_format($resumo['no_expediente'], 0, ',', '.'), 'fa-business-time', $resumo['no_expediente'] > 0 ? 'text-amber-500' : 'text-emerald-500'],
                ['Clientes sincronizados', number_format($resumo['clientes'], 0, ',', '.'), 'fa-users', 'text-sky-500'],
            ];
        @endphp
        @foreach($cards as [$label, $valor, $icone, $cor])
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    <i class="fa-solid {{ $icone }} mr-1 {{ $cor }}"></i> {{ $label }}
                </p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $valor }}</p>
            </div>
        @endforeach
    </div>

    {{-- Histograma por hora do dia --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                <i class="fa-solid fa-chart-column mr-1 text-sky-500"></i> Execuções por hora do dia
            </p>
            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-sky-500"></span> sincronizar (18:30→07:00)</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-violet-400"></span> reconsultar (07:15→...)</span>
                <span class="flex items-center gap-1"><span class="inline-block w-3 h-3 rounded-sm bg-red-500/20 border border-red-500/40"></span> expediente</span>
            </div>
        </div>
        <div class="h-64">
            <canvas id="chartPorHora"></canvas>
        </div>
        <p class="text-xs text-gray-400 mt-2">
            Barras dentro da faixa vermelha (08:00–17:30, dias úteis) indicam rotina invadindo o horário comercial — considere adiar o início ou reduzir a janela da reconsulta.
        </p>
    </div>

    {{-- Execuções por dia --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-x-auto mb-6">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide px-4 pt-4">
            <i class="fa-solid fa-calendar-days mr-1 text-sky-500"></i> Janela de execução por dia
        </p>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 mt-3">
            <thead class="bg-gray-50 dark:bg-slate-900">
                <tr>
                    @foreach(['Dia','Rotina','Início','Fim','Duração','Fases','Clientes','Erros','No expediente'] as $th)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">{{ $th }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                @forelse($porDia as $linha)
                    <tr class="{{ $linha['no_expediente'] > 0 ? 'bg-red-50 dark:bg-red-900/10' : '' }}">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $linha['data']->format('d/m/Y') }} <span class="text-xs text-gray-400">{{ $linha['data']->translatedFormat('D') }}</span></td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $linha['tipo'] === 'backfill' ? 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300' : 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' }}">
                                {{ $linha['tipo'] === 'backfill' ? 'reconsultar' : 'sincronizar' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $linha['inicio']->format('H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap {{ $linha['no_expediente'] > 0 ? 'text-red-600 dark:text-red-400 font-semibold' : '' }}">{{ $linha['fim']->format('H:i') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ intdiv($linha['duracao_min'], 60) }}h{{ str_pad($linha['duracao_min'] % 60, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $linha['total'] }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $linha['clientes'] }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap {{ $linha['erros'] > 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-400' }}">{{ $linha['erros'] }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap {{ $linha['no_expediente'] > 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-400' }}">{{ $linha['no_expediente'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">Nenhuma execução no período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Erros agrupados + clientes pendentes --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                <i class="fa-solid fa-bug mr-1 text-red-500"></i> Erros mais frequentes no período
            </p>
            @forelse($errosAgrupados as $erro)
                <div class="py-2 border-b border-gray-100 dark:border-slate-700 last:border-0">
                    <div class="flex items-start gap-2">
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 flex-shrink-0">{{ $erro['total'] }}x</span>
                        <span class="text-sm text-gray-700 dark:text-gray-300 break-words">{{ $erro['mensagem'] ?: '(sem mensagem)' }}</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1 ml-9">último: {{ $erro['ultimo_em']->format('d/m/Y H:i') }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-400 italic">Nenhum erro no período. 🎉</p>
            @endforelse
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                <i class="fa-solid fa-clock-rotate-left mr-1 text-amber-500"></i> Há mais tempo sem sincronizar
            </p>
            <ul class="space-y-1">
                @forelse($clientesPendentes as $c)
                    <li class="flex items-center gap-2 py-1">
                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1 min-w-0" title="{{ $c->nome }}">{{ $c->nome }}</span>
                        <span class="text-xs font-semibold flex-shrink-0 {{ $c->ultima_sincronizacao ? 'text-gray-500 dark:text-gray-400' : 'text-red-500' }}">
                            {{ $c->ultima_sincronizacao ? \Carbon\Carbon::parse($c->ultima_sincronizacao)->diffForHumans() : 'nunca' }}
                        </span>
                    </li>
                @empty
                    <li class="text-sm text-gray-400 italic">Nenhum cliente elegível.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Tabela detalhada --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-x-auto">
        <form method="GET" id="form-filtros-sincronizacao-rs"
              class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
            <input type="hidden" name="dias" value="{{ $dias }}">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Cliente</label>
                <select name="cliente_id" onchange="document.getElementById('form-filtros-sincronizacao-rs').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-56">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected((string) request('cliente_id') === (string) $cliente->id)>{{ $cliente->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Rotina</label>
                <select name="tipo" onchange="document.getElementById('form-filtros-sincronizacao-rs').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todas</option>
                    <option value="normal" @selected(request('tipo') === 'normal')>sincronizar</option>
                    <option value="backfill" @selected(request('tipo') === 'backfill')>reconsultar</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Status</label>
                <select name="status" onchange="document.getElementById('form-filtros-sincronizacao-rs').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos</option>
                    <option value="sucesso" @selected(request('status') === 'sucesso')>Sucesso</option>
                    <option value="erro" @selected(request('status') === 'erro')>Erro</option>
                </select>
            </div>
        </form>

        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
            <thead class="bg-gray-50 dark:bg-slate-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fase</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Erro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Executado em</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                @forelse($sincronizacoes as $s)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $s->cliente->nome ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap uppercase">{{ $s->fase }}</td>
                        <td class="px-6 py-4 text-sm whitespace-nowrap">
                            @php
                                $badges = [
                                    'sucesso' => 'bg-green-100 text-green-800',
                                    'erro' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $badges[$s->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($s->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-red-700 dark:text-red-400">{{ $s->mensagem_erro ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $s->executado_em?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Nenhuma sincronização registrada ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $sincronizacoes->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const dados = @json($porHora);
            const expInicio = @json($expedienteInicio);
            const expFim = @json($expedienteFim);
            const escuro = document.documentElement.classList.contains('dark');
            const corGrade = escuro ? 'rgba(148,163,184,0.15)' : 'rgba(0,0,0,0.06)';
            const corTexto = escuro ? '#94a3b8' : '#64748b';

            // Faixa do expediente pintada atrás das barras
            const faixaExpediente = {
                id: 'faixaExpediente',
                beforeDatasetsDraw(chart) {
                    const { ctx, chartArea, scales } = chart;
                    if (!chartArea) return;
                    const x = scales.x;
                    const larguraCol = x.getPixelForValue(1) - x.getPixelForValue(0);
                    const xIni = x.getPixelForValue(expInicio) - larguraCol / 2;
                    const xFim = x.getPixelForValue(Math.floor(expFim)) - larguraCol / 2 + larguraCol * (expFim - Math.floor(expFim));
                    ctx.save();
                    ctx.fillStyle = 'rgba(239,68,68,0.12)';
                    ctx.fillRect(xIni, chartArea.top, xFim - xIni, chartArea.bottom - chartArea.top);
                    ctx.restore();
                },
            };

            new Chart(document.getElementById('chartPorHora').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: dados.map(d => String(d.hora).padStart(2, '0') + 'h'),
                    datasets: [
                        { label: 'sincronizar', data: dados.map(d => d.normal), backgroundColor: '#0ea5e9', stack: 's' },
                        { label: 'reconsultar', data: dados.map(d => d.backfill), backgroundColor: '#a78bfa', stack: 's' },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, grid: { color: corGrade }, ticks: { color: corTexto } },
                        y: { stacked: true, beginAtZero: true, grid: { color: corGrade }, ticks: { color: corTexto, precision: 0 } },
                    },
                    plugins: { legend: { display: false } },
                },
                plugins: [faixaExpediente],
            });
        })();
    </script>
@endpush
