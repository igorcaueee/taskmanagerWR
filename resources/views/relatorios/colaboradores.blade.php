@extends('layouts.internal')

@section('title', 'Relatório de Colaboradores — WR Assessoria')

@section('content')
    <div class="py-6 px-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fa-regular fa-user"></i> Relatório de Colaboradores</h1>
                <p class="text-gray-700">Produtividade e desempenho da equipe.</p>
            </div>
        </div>

        {{-- Filtro de período --}}
        <form method="GET" action="{{ route('relatorios.colaboradores') }}" id="form-relatorio"
              class="bg-white rounded shadow px-4 py-3 mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Período</label>
                <select name="periodo" id="select-periodo"
                        class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="hoje"       @selected(request('periodo') === 'hoje')>Hoje</option>
                    <option value="semana"     @selected(request('periodo') === 'semana')>Esta semana</option>
                    <option value="mes"        @selected(request('periodo', 'mes') === 'mes')>Este mês</option>
                    <option value="trimestre"  @selected(request('periodo') === 'trimestre')>Últimos 3 meses</option>
                    <option value="semestre"   @selected(request('periodo') === 'semestre')>Últimos 6 meses</option>
                    <option value="ano"        @selected(request('periodo') === 'ano')>Este ano</option>
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
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total de colaboradores</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalColaboradores }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ativos</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ $totalAtivos }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Inativos</p>
                <p class="mt-1 text-3xl font-bold text-gray-400">{{ $totalInativos }}</p>
            </div>
        </div>

        {{-- Linha 1: Concluídas + Abertas --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-circle-check mr-1 text-brand"></i> Tarefas concluídas por colaborador no período
                </h2>
                @if($concluidasPorColab->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:240px">
                        <canvas id="chartConcluidas"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-hourglass-half mr-1 text-brand"></i> Tarefas em aberto por colaborador
                </h2>
                @if($abertasPorColab->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:240px">
                        <canvas id="chartAbertas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Linha 2: Vencidas + Evolução dos top 5 --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-circle-exclamation mr-1 text-red-500"></i> Tarefas vencidas por colaborador
                </h2>
                @if($vencidasPorColab->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum colaborador com tarefas vencidas.</p>
                @else
                    <div style="position:relative;height:240px">
                        <canvas id="chartVencidas"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-chart-line mr-1 text-brand"></i> Evolução mensal — top 5 colaboradores
                </h2>
                @if($topColabs->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:240px">
                        <canvas id="chartEvolucao"></canvas>
                    </div>
                @endif
            </div>
        </div>

    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
    document.getElementById('select-periodo').addEventListener('change', function () {
        const datas = document.getElementById('datas-personalizadas');
        datas.classList.toggle('hidden', this.value !== 'personalizado');
        datas.classList.toggle('flex',   this.value === 'personalizado');
    });

    const palette = [
        '#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6',
        '#ec4899','#06b6d4','#84cc16','#f97316','#6366f1',
    ];

    @if($concluidasPorColab->isNotEmpty())
    new Chart(document.getElementById('chartConcluidas'), {
        type: 'bar',
        data: {
            labels: {!! $concluidasPorColab->pluck('nome')->toJson() !!},
            datasets: [{
                label: 'Concluídas',
                data: {!! $concluidasPorColab->pluck('total')->toJson() !!},
                backgroundColor: palette.slice(0, {{ $concluidasPorColab->count() }}),
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

    @if($abertasPorColab->isNotEmpty())
    new Chart(document.getElementById('chartAbertas'), {
        type: 'bar',
        data: {
            labels: {!! $abertasPorColab->pluck('nome')->toJson() !!},
            datasets: [{
                label: 'Em aberto',
                data: {!! $abertasPorColab->pluck('total')->toJson() !!},
                backgroundColor: '#f59e0b',
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

    @if($vencidasPorColab->isNotEmpty())
    new Chart(document.getElementById('chartVencidas'), {
        type: 'bar',
        data: {
            labels: {!! $vencidasPorColab->pluck('nome')->toJson() !!},
            datasets: [{
                label: 'Vencidas',
                data: {!! $vencidasPorColab->pluck('total')->toJson() !!},
                backgroundColor: '#ef4444',
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

    @if($topColabs->isNotEmpty())
    const meses = {!! $evolucaoColabs->pluck('mes')->toJson() !!};
    const nomes = {!! $topColabs->map(fn($c) => $c->responsavel->nome ?? 'N/A')->toJson() !!};
    const cores = palette.slice(0, nomes.length);

    const datasets = nomes.map((nome, i) => ({
        label: nome,
        data: {!! $evolucaoColabs->toJson() !!}.map(entry => entry[nome] ?? 0),
        borderColor: cores[i],
        backgroundColor: cores[i] + '22',
        tension: 0.3,
        fill: false,
        pointRadius: 3,
    }));

    new Chart(document.getElementById('chartEvolucao'), {
        type: 'line',
        data: { labels: meses, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: false,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    });
    @endif
</script>
@endpush
