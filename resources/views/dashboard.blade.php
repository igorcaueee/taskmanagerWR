@extends('layouts.internal')

@section('title', 'Painel — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">Painel</h1>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Usuários ativos</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalUsuariosAtivos }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tarefas no ciclo atual</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ $totalTarefasCiclo }}</p>
                <p class="mt-1 text-xs text-gray-400 truncate">{{ $cicloAtual->nome }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Minhas tarefas no ciclo</p>
                <p class="mt-1 text-3xl font-bold text-brand">{{ $tarefasUsuarioCiclo }}</p>
                <p class="mt-1 text-xs text-gray-400 truncate">{{ $cicloAtual->nome }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Concluídas hoje</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ $tarefasConcluidasHoje }}</p>
                <p class="mt-1 text-xs text-gray-400 truncate">{{ now()->format('d/m/Y') }}</p>
            </div>

            {{-- Aniversariantes do dia --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                    <i class="fa-solid fa-cake-candles mr-1 text-pink-400"></i> Aniversariantes hoje
                </p>
                @if($aniversariantesHoje->isEmpty())
                    <p class="mt-2 text-sm text-gray-400 italic">Nenhum aniversariante hoje.</p>
                @else
                    <ul class="mt-2 space-y-1">
                        @foreach($aniversariantesHoje as $aniversariante)
                            <li class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-pink-100 text-pink-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($aniversariante->nome, 0, 1)) }}
                                </span>
                                <span class="text-sm text-gray-700 truncate">{{ $aniversariante->nome }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Aniversário de empresa --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                    <i class="fa-solid fa-building mr-1 text-amber-400"></i> Aniversário de empresa
                </p>
                @if($aniversariantesEmpresaHoje->isEmpty())
                    <p class="mt-2 text-sm text-gray-400 italic">Nenhum aniversário de empresa hoje.</p>
                @else
                    <ul class="mt-2 space-y-1">
                        @foreach($aniversariantesEmpresaHoje as $colab)
                            <li class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($colab->nome, 0, 1)) }}
                                </span>
                                <span class="text-sm text-gray-700 truncate flex-1">{{ $colab->nome }}</span>
                                <span class="text-xs font-semibold text-amber-600 flex-shrink-0">{{ $colab->anos_empresa }} {{ $colab->anos_empresa === 1 ? 'ano' : 'anos' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Card unificado de clientes ativos --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 col-span-2 sm:col-span-2 lg:col-span-2 flex flex-col sm:flex-row items-center gap-6">
                <div class="flex-shrink-0 w-40 h-40">
                    <canvas id="chartClientes"></canvas>
                </div>
                <div class="flex flex-col gap-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Clientes ativos</p>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-sm text-gray-600">Pessoa Jurídica (PJ)</span>
                        <span class="ml-auto text-lg font-bold text-gray-900">{{ $totalClientesPJ }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full bg-emerald-400"></span>
                        <span class="text-sm text-gray-600">Pessoa Física (PF)</span>
                        <span class="ml-auto text-lg font-bold text-gray-900">{{ $totalClientesPF }}</span>
                    </div>
                    <div class="border-t border-gray-100 pt-2 flex items-center gap-2">
                        <span class="text-sm text-gray-500">Total</span>
                        <span class="ml-auto text-xl font-bold text-gray-900">{{ $totalClientesPJ + $totalClientesPF }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        const ctx = document.getElementById('chartClientes').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['PJ', 'PF'],
                datasets: [{
                    data: [{{ $totalClientesPJ }}, {{ $totalClientesPF }}],
                    backgroundColor: ['#3b82f6', '#34d399'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '60%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${ctx.parsed}`,
                        },
                    },
                },
            },
        });
    </script>
@endpush
