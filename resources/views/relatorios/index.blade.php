@extends('layouts.internal')

@section('title', 'Relatórios — WR Assessoria')

@section('content')
    <div class="py-6 px-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fa-solid fa-chart-bar"></i> Relatórios</h1>
                <p class="text-gray-700">Análise de desempenho e produtividade das tarefas.</p>
            </div>
        </div>

        {{-- Filtros de período --}}
        <form method="GET" action="{{ route('relatorios') }}" id="form-relatorio"
              class="bg-white rounded shadow px-4 py-3 mb-6 flex flex-wrap gap-3 items-end">

            <div>
                <label class="block text-xs text-gray-500 mb-1">Período</label>
                <select name="periodo" id="select-periodo"
                        class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="hoje"      @selected(request('periodo') === 'hoje')>Hoje</option>
                    <option value="semana"    @selected(request('periodo') === 'semana')>Esta semana</option>
                    <option value="mes"       @selected(request('periodo', 'mes') === 'mes')>Este mês</option>
                    <option value="trimestre" @selected(request('periodo') === 'trimestre')>Últimos 3 meses</option>
                    <option value="semestre"  @selected(request('periodo') === 'semestre')>Últimos 6 meses</option>
                    <option value="ano"       @selected(request('periodo') === 'ano')>Este ano</option>
                    <option value="personalizado" @selected(request('periodo') === 'personalizado')>Personalizado</option>
                </select>
            </div>

            <div id="datas-personalizadas" class="{{ request('periodo') === 'personalizado' ? 'flex' : 'hidden' }} gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">De</label>
                    <input type="date" name="data_inicio" value="{{ request('data_inicio') }}"
                           class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Até</label>
                    <input type="date" name="data_fim" value="{{ request('data_fim') }}"
                           class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                </div>
            </div>

            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-1.5 bg-brand text-white rounded border-0 text-sm focus:outline-none hover:bg-brand/80">
                <i class="fa-solid fa-magnifying-glass"></i> Aplicar
            </button>

            <p class="text-xs text-gray-400 self-center ml-auto">
                {{ $dataInicio->format('d/m/Y') }} — {{ $dataFim->format('d/m/Y') }}
            </p>
        </form>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total de tarefas</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalTarefas }}</p>
                <p class="mt-1 text-xs text-gray-400">no período selecionado</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Concluídas</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ $totalConcluidas }}</p>
                <p class="mt-1 text-xs text-gray-400">
                    @if($totalTarefas > 0)
                        {{ round(($totalConcluidas / $totalTarefas) * 100) }}% do total
                    @else
                        —
                    @endif
                </p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Vencidas</p>
                <p class="mt-1 text-3xl font-bold text-red-600">{{ $totalVencidas }}</p>
                <p class="mt-1 text-xs text-gray-400">em aberto e atrasadas</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Concluídas esta semana</p>
                <p class="mt-1 text-3xl font-bold text-brand">{{ $concluidasEstaSemana }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ now()->startOfWeek()->format('d/m') }} — {{ now()->endOfWeek()->format('d/m') }}</p>
            </div>
        </div>

        {{-- Linha 1: Concluídas por responsável + Evolução mensal --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-user-check mr-1 text-brand"></i> Concluídas por responsável
                </h2>
                @if($porResponsavel->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:220px">
                        <canvas id="chartResponsavel"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-chart-line mr-1 text-brand"></i> Evolução mensal (últimos 12 meses)
                </h2>
                <div style="position:relative;height:220px">
                    <canvas id="chartEvolucao"></canvas>
                </div>
            </div>
        </div>

        {{-- Linha 2: Por etapa + Por cliente --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-layer-group mr-1 text-brand"></i> Tarefas por etapa
                </h2>
                @if($porEtapa->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div class="flex items-center gap-6">
                        <div style="position:relative;width:176px;height:176px" class="flex-shrink-0">
                            <canvas id="chartEtapa"></canvas>
                        </div>
                        <ul class="space-y-1 flex-1 min-w-0">
                            @foreach($porEtapa as $item)
                                <li class="flex items-center gap-2 text-sm">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $item['cor'] }}"></span>
                                    <span class="truncate text-gray-600">{{ $item['nome'] }}</span>
                                    <span class="ml-auto font-semibold text-gray-900">{{ $item['total'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-regular fa-building mr-1 text-brand"></i> Tarefas por cliente (top 10)
                </h2>
                @if($porCliente->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:220px">
                        <canvas id="chartCliente"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Linha 3: Por departamento + Vencidas vs prazo + Por recorrência --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-sitemap mr-1 text-brand"></i> Por departamento
                </h2>
                @if($porDepartamento->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:220px">
                        <canvas id="chartDepartamento"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-clock mr-1 text-brand"></i> Vencidas vs no prazo
                </h2>
                <div class="flex items-center gap-4">
                    <div style="position:relative;width:144px;height:144px" class="flex-shrink-0">
                        <canvas id="chartVencidas"></canvas>
                    </div>
                    <ul class="space-y-2">
                        @foreach($vencidasVsPrazo as $item)
                            <li class="flex items-center gap-2 text-sm">
                                <span class="w-3 h-3 rounded-full flex-shrink-0
                                    {{ $item['label'] === 'Vencidas' ? 'bg-red-500' : 'bg-green-400' }}"></span>
                                <span class="text-gray-600">{{ $item['label'] }}</span>
                                <span class="ml-2 font-semibold text-gray-900">{{ $item['total'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-rotate mr-1 text-brand"></i> Por recorrência
                </h2>
                @if($porRecorrencia->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:220px">
                        <canvas id="chartRecorrencia"></canvas>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    // ── Toggle datas personalizadas ────────────────────────────────────
    document.getElementById('select-periodo').addEventListener('change', function () {
        const datas = document.getElementById('datas-personalizadas');
        datas.classList.toggle('hidden', this.value !== 'personalizado');
        datas.classList.toggle('flex',   this.value === 'personalizado');
    });

    // ── Palette helper ─────────────────────────────────────────────────
    const palette = [
        '#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6',
        '#ec4899','#06b6d4','#84cc16','#f97316','#6366f1',
    ];

    // ── Concluídas por responsável (barras horizontais) ────────────────
    @if($porResponsavel->isNotEmpty())
    new Chart(document.getElementById('chartResponsavel'), {
        type: 'bar',
        data: {
            labels: {!! $porResponsavel->pluck('nome')->toJson() !!},
            datasets: [{
                label: 'Concluídas',
                data: {!! $porResponsavel->pluck('total')->toJson() !!},
                backgroundColor: palette.slice(0, {{ $porResponsavel->count() }}),
                borderRadius: 4,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } },
                y: { ticks: { font: { size: 12 } } },
            },
        },
    });
    @endif

    // ── Evolução mensal (linha) ─────────────────────────────────────────
    new Chart(document.getElementById('chartEvolucao'), {
        type: 'line',
        data: {
            labels: {!! $evolucaoMensal->pluck('mes')->toJson() !!},
            datasets: [
                {
                    label: 'Total',
                    data: {!! $evolucaoMensal->pluck('total')->toJson() !!},
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                },
                {
                    label: 'Concluídas',
                    data: {!! $evolucaoMensal->pluck('concluidas')->toJson() !!},
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { position: 'top', labels: { font: { size: 12 } } } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    });

    // ── Por etapa (donut) ──────────────────────────────────────────────
    @if($porEtapa->isNotEmpty())
    new Chart(document.getElementById('chartEtapa'), {
        type: 'doughnut',
        data: {
            labels: {!! $porEtapa->pluck('nome')->toJson() !!},
            datasets: [{
                data: {!! $porEtapa->pluck('total')->toJson() !!},
                backgroundColor: {!! $porEtapa->pluck('cor')->toJson() !!},
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            cutout: '55%',
            plugins: { legend: { display: false } },
        },
    });
    @endif

    // ── Por cliente (barras) ───────────────────────────────────────────
    @if($porCliente->isNotEmpty())
    new Chart(document.getElementById('chartCliente'), {
        type: 'bar',
        data: {
            labels: {!! $porCliente->pluck('nome')->toJson() !!},
            datasets: [{
                label: 'Tarefas',
                data: {!! $porCliente->pluck('total')->toJson() !!},
                backgroundColor: palette.slice(0, {{ $porCliente->count() }}),
                borderRadius: 4,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } },
                y: { ticks: { font: { size: 11 } } },
            },
        },
    });
    @endif

    // ── Por departamento (pizza) ───────────────────────────────────────
    @if($porDepartamento->isNotEmpty())
    new Chart(document.getElementById('chartDepartamento'), {
        type: 'pie',
        data: {
            labels: {!! $porDepartamento->pluck('nome')->toJson() !!},
            datasets: [{
                data: {!! $porDepartamento->pluck('total')->toJson() !!},
                backgroundColor: palette.slice(0, {{ $porDepartamento->count() }}),
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
        },
    });
    @endif

    // ── Vencidas vs no prazo (donut) ───────────────────────────────────
    new Chart(document.getElementById('chartVencidas'), {
        type: 'doughnut',
        data: {
            labels: ['No prazo', 'Vencidas'],
            datasets: [{
                data: [{{ $vencidasVsPrazo[0]['total'] }}, {{ $vencidasVsPrazo[1]['total'] }}],
                backgroundColor: ['#34d399', '#ef4444'],
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            cutout: '55%',
            plugins: { legend: { display: false } },
        },
    });

    // ── Por recorrência (barras) ───────────────────────────────────────
    @if($porRecorrencia->isNotEmpty())
    new Chart(document.getElementById('chartRecorrencia'), {
        type: 'bar',
        data: {
            labels: {!! $porRecorrencia->pluck('nome')->toJson() !!},
            datasets: [{
                label: 'Tarefas',
                data: {!! $porRecorrencia->pluck('total')->toJson() !!},
                backgroundColor: palette.slice(0, {{ $porRecorrencia->count() }}),
                borderRadius: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    });
    @endif
</script>
@endpush
