@extends('layouts.internal')

@section('title', 'Relatório de Clientes — WR Assessoria')

@section('content')
    <div class="py-6 px-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fa-regular fa-building"></i> Relatório de Clientes</h1>
                <p class="text-gray-700">Visão geral do portfólio de clientes e suas tarefas.</p>
            </div>
        </div>

        {{-- Filtro de período --}}
        <form method="GET" action="{{ route('relatorios.clientes') }}" id="form-relatorio"
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
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Ativos</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ $totalAtivos }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Inativos</p>
                <p class="mt-1 text-3xl font-bold text-gray-400">{{ $totalInativos }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">PJ</p>
                <p class="mt-1 text-3xl font-bold text-blue-600">{{ $totalPJ }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">PF</p>
                <p class="mt-1 text-3xl font-bold text-emerald-600">{{ $totalPF }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total de clientes</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalClientes }}</p>
            </div>
        </div>

        {{-- Linha 1: Mais tarefas + Mais concluídas --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-list-check mr-1 text-brand"></i> Clientes com mais tarefas no período
                </h2>
                @if($clientesComMaisTarefas->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:240px">
                        <canvas id="chartMaisTarefas"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-circle-check mr-1 text-brand"></i> Clientes com mais tarefas concluídas
                </h2>
                @if($clientesComMaisConcluidas->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:240px">
                        <canvas id="chartMaisConcluidas"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Linha 2: Clientes com vencidas + Novos clientes por mês --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-circle-exclamation mr-1 text-red-500"></i> Clientes com tarefas vencidas
                </h2>
                @if($clientesComVencidas->isEmpty())
                    <p class="text-sm text-gray-400 italic">Nenhum cliente com tarefas vencidas.</p>
                @else
                    <div style="position:relative;height:240px">
                        <canvas id="chartVencidas"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4">
                    <i class="fa-solid fa-chart-line mr-1 text-brand"></i> Novos clientes por mês (últimos 12 meses)
                </h2>
                <div style="position:relative;height:240px">
                    <canvas id="chartNovosPorMes"></canvas>
                </div>
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

    @if($clientesComMaisTarefas->isNotEmpty())
    new Chart(document.getElementById('chartMaisTarefas'), {
        type: 'bar',
        data: {
            labels: {!! $clientesComMaisTarefas->pluck('nome')->toJson() !!},
            datasets: [{
                label: 'Tarefas',
                data: {!! $clientesComMaisTarefas->pluck('total')->toJson() !!},
                backgroundColor: palette.slice(0, {{ $clientesComMaisTarefas->count() }}),
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

    @if($clientesComMaisConcluidas->isNotEmpty())
    new Chart(document.getElementById('chartMaisConcluidas'), {
        type: 'bar',
        data: {
            labels: {!! $clientesComMaisConcluidas->pluck('nome')->toJson() !!},
            datasets: [{
                label: 'Concluídas',
                data: {!! $clientesComMaisConcluidas->pluck('total')->toJson() !!},
                backgroundColor: '#10b981',
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

    @if($clientesComVencidas->isNotEmpty())
    new Chart(document.getElementById('chartVencidas'), {
        type: 'bar',
        data: {
            labels: {!! $clientesComVencidas->pluck('nome')->toJson() !!},
            datasets: [{
                label: 'Vencidas',
                data: {!! $clientesComVencidas->pluck('total')->toJson() !!},
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
                y: { ticks: { font: { size: 11 } } },
            },
        },
    });
    @endif

    new Chart(document.getElementById('chartNovosPorMes'), {
        type: 'bar',
        data: {
            labels: {!! $novosPorMes->pluck('mes')->toJson() !!},
            datasets: [{
                label: 'Novos clientes',
                data: {!! $novosPorMes->pluck('total')->toJson() !!},
                backgroundColor: '#3b82f6',
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
</script>
@endpush
