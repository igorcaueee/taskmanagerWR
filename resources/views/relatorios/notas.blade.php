@extends('layouts.internal')

@section('title', 'Relatório de Notas Emitidas — WR Assessoria')

@section('content')
    <div class="py-6 px-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-file-invoice"></i> Notas Emitidas</h1>
                <p class="text-gray-700 dark:text-gray-300">Volume de notas fiscais emitidas por cliente no período.</p>
            </div>
            <a href="{{ route('notas-emitidas.index') }}" class="text-sm text-brand hover:text-brand/80 no-underline">
                <i class="fa-solid fa-plus"></i> Ir para o Contador de Notas
            </a>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('relatorios.notas') }}" id="form-relatorio"
              class="bg-white dark:bg-slate-800 rounded shadow px-4 py-3 mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Período</label>
                <select name="periodo" id="select-periodo"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
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
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">De</label>
                    <input type="date" name="data_inicio" value="{{ request('data_inicio') }}"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Até</label>
                    <input type="date" name="data_fim" value="{{ request('data_fim') }}"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                </div>
            </div>

            <div class="w-px h-8 bg-gray-200 dark:bg-slate-600 self-end hidden sm:block"></div>

            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pesquisar cliente</label>
                <input type="text" name="busca" value="{{ $busca }}"
                       placeholder="Buscar por nome..."
                       class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-52">
            </div>

            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-1.5 bg-brand text-white rounded border-0 text-sm focus:outline-none hover:bg-brand/80">
                <i class="fa-solid fa-magnifying-glass"></i> Aplicar
            </button>

            <p class="text-xs text-gray-400 dark:text-slate-500 self-center ml-auto">
                {{ $dataInicio->format('d/m/Y') }} — {{ $dataFim->format('d/m/Y') }}
            </p>
        </form>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Notas emitidas no período</p>
                <p class="mt-1 text-3xl font-bold text-brand">{{ $totalNotas }}</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Clientes atendidos no período</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $totalEmitentesComNotas }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                    <i class="fa-solid fa-ranking-star mr-1 text-brand"></i> Clientes com mais notas no período
                </h2>
                @if($topEmitentes->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-slate-500 italic">Nenhuma nota emitida no período.</p>
                @else
                    <div style="position:relative;height:240px">
                        <canvas id="chartTopEmitentes"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                    <i class="fa-solid fa-chart-column mr-1 text-brand"></i> Notas emitidas por dia
                </h2>
                @if($porDia->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-slate-500 italic">Nenhuma nota emitida no período.</p>
                @else
                    <div style="overflow-x:auto;">
                        <div style="position:relative;height:240px;min-width:{{ max(400, $porDia->count() * 40) }}px">
                            <canvas id="chartPorDia"></canvas>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Tabela detalhada --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notas no período</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($emitentesTabela as $emitente)
                        <tr>
                            <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-slate-100 whitespace-nowrap">{{ $emitente['nome'] }}</td>
                            <td class="px-6 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    {{ $emitente['total'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400">Nenhuma nota emitida no período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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

    @if($topEmitentes->isNotEmpty())
    new Chart(document.getElementById('chartTopEmitentes'), {
        type: 'bar',
        data: {
            labels: @json($topEmitentes->pluck('nome')),
            datasets: [{
                label: 'Notas',
                data: @json($topEmitentes->pluck('total')),
                backgroundColor: palette.slice(0, {{ $topEmitentes->count() }}),
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

    @if($porDia->isNotEmpty())
    new Chart(document.getElementById('chartPorDia'), {
        type: 'bar',
        data: {
            labels: @json($porDia->pluck('dia')),
            datasets: [{
                label: 'Notas',
                data: @json($porDia->pluck('total')),
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
                x: { ticks: { font: { size: 11 } } },
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    });
    @endif
</script>
@endpush
