@extends('layouts.internal')

@section('title', 'Painel — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mb-6">Painel</h1>

        {{-- Banner do questionário: desativado por enquanto, reativar trocando @if(false) por @if(true) --}}
        @if(false)
        <div id="avisoQuestionarioPainel" class="hidden relative bg-[#0084aa]/5 dark:bg-[#0084aa]/10 border border-[#0084aa]/30 dark:border-[#0084aa]/40 rounded-xl px-5 py-4 mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
            <button type="button" id="btnFecharAvisoQuestionarioPainel" aria-label="Fechar aviso"
                class="absolute top-3 right-3 bg-transparent border-0 appearance-none p-1 leading-none text-gray-400 dark:text-slate-500 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="shrink-0 w-10 h-10 rounded-full bg-[#0084aa]/10 dark:bg-[#0084aa]/20 flex items-center justify-center text-[#0084aa]">
                <i class="fa-solid fa-shield-halved text-lg"></i>
            </div>
            <div class="flex-1 pr-6">
                <p class="text-sm font-semibold text-gray-800 dark:text-slate-200">Sua opinião é importante — responda um questionário rápido</p>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
                    A pesquisa é <strong>100% segura e sigilosa</strong>: as respostas não são identificadas e ninguém vai saber quem respondeu.
                </p>
            </div>
            <a href="https://docs.google.com/forms/d/e/1FAIpQLSe-HN7P3PADKUYgg4sfSRCy2QuA1MRuotb_zFyzxPjhQipbsA/viewform?usp=header"
                target="_blank" rel="noopener noreferrer"
                class="shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand text-white text-xs font-semibold hover:bg-[#00708c] transition-colors">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Responder questionário
            </a>
        </div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Usuários ativos</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $totalUsuariosAtivos }}</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tarefas no ciclo atual</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $totalTarefasCiclo }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500 truncate">{{ $cicloAtual->nome }}</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Minhas tarefas no ciclo</p>
                <p class="mt-1 text-3xl font-bold text-brand">{{ $tarefasUsuarioCiclo }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500 truncate">{{ $cicloAtual->nome }}</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Concluídas hoje</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ $tarefasConcluidasHoje }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500 truncate">{{ now()->format('d/m/Y') }}</p>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">XMLs baixados no mês</p>
                <p class="mt-1 text-3xl font-bold text-brand">{{ $totalXmlsBaixadosMes }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500 truncate">{{ ucfirst(now()->translatedFormat('F Y')) }}</p>
            </div>

            {{-- Aniversariantes do dia --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    <i class="fa-solid fa-cake-candles mr-1 text-pink-400"></i> Aniversariantes hoje
                </p>
                @if($aniversariantesHoje->isEmpty())
                    <p class="mt-2 text-sm text-gray-400 dark:text-slate-500 italic">Nenhum aniversariante hoje.</p>
                @else
                    <ul class="mt-2 space-y-1">
                        @foreach($aniversariantesHoje as $aniversariante)
                            <li class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-pink-100 dark:bg-pink-900/30 text-pink-600 dark:text-pink-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($aniversariante->nome, 0, 1)) }}
                                </span>
                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $aniversariante->nome }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Aniversário de empresa --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                    <i class="fa-solid fa-building mr-1 text-amber-400"></i> Aniversário de empresa
                </p>
                @if($aniversariantesEmpresaHoje->isEmpty())
                    <p class="mt-2 text-sm text-gray-400 dark:text-slate-500 italic">Nenhum aniversário de empresa hoje.</p>
                @else
                    <ul class="mt-2 space-y-1">
                        @foreach($aniversariantesEmpresaHoje as $colab)
                            <li class="flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {{ strtoupper(substr($colab->nome, 0, 1)) }}
                                </span>
                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate flex-1 min-w-0" title="{{ $colab->nome }}">{{ $colab->nome }}</span>
                                <span class="text-xs font-semibold text-amber-600 flex-shrink-0">{{ $colab->anos_empresa }} {{ $colab->anos_empresa == 1 ? 'ano' : 'anos' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Card unificado de clientes ativos --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4 col-span-2 sm:col-span-2 lg:col-span-2 flex flex-col sm:flex-row items-center gap-6">
                <div class="flex-shrink-0 w-40 h-40">
                    <canvas id="chartClientes"></canvas>
                </div>
                <div class="flex flex-col gap-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Clientes ativos</p>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Pessoa Jurídica (PJ)</span>
                        <span class="ml-auto text-lg font-bold text-gray-900 dark:text-slate-100">{{ $totalClientesPJ }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full bg-emerald-400"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Pessoa Física (PF)</span>
                        <span class="ml-auto text-lg font-bold text-gray-900 dark:text-slate-100">{{ $totalClientesPF }}</span>
                    </div>
                    <div class="border-t border-gray-100 dark:border-slate-700 pt-2 flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total</span>
                        <span class="ml-auto text-xl font-bold text-gray-900 dark:text-slate-100">{{ $totalClientesAtivos }}</span>
                    </div>
                </div>
            </div>

            {{-- Card PGDAS do Simples Nacional --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4 col-span-2 sm:col-span-2 lg:col-span-2 flex flex-col sm:flex-row items-center gap-6">
                <div class="flex-shrink-0 w-40 h-40">
                    <canvas id="chartPgdas"></canvas>
                </div>
                <div class="flex flex-col gap-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        PGDAS enviados — {{ ucfirst(\Carbon\Carbon::createFromFormat('Ym', $periodoPgdas)->translatedFormat('F/Y')) }}
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Enviados</span>
                        <span class="ml-auto text-lg font-bold text-gray-900 dark:text-slate-100">{{ $totalPgdasEnviados }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full bg-amber-400"></span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Pendentes</span>
                        <span class="ml-auto text-lg font-bold text-gray-900 dark:text-slate-100">{{ $totalPgdasPendentes }}</span>
                    </div>
                    <div class="border-t border-gray-100 dark:border-slate-700 pt-2 flex items-center gap-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total de empresas do SN</span>
                        <span class="ml-auto text-xl font-bold text-gray-900 dark:text-slate-100">{{ $totalGruposSn }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    @if(false)
    <script>
        (function () {
            const CHAVE_AVISO = 'painel_aviso_questionario_fechado';
            const aviso = document.getElementById('avisoQuestionarioPainel');
            const btnFechar = document.getElementById('btnFecharAvisoQuestionarioPainel');

            if (aviso && !localStorage.getItem(CHAVE_AVISO)) {
                aviso.classList.remove('hidden');
                aviso.classList.add('flex');
            }

            if (btnFechar) {
                btnFechar.addEventListener('click', function () {
                    localStorage.setItem(CHAVE_AVISO, '1');
                    aviso.classList.add('hidden');
                    aviso.classList.remove('flex');
                });
            }
        })();
    </script>
    @endif
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

        const ctxPgdas = document.getElementById('chartPgdas').getContext('2d');
        new Chart(ctxPgdas, {
            type: 'doughnut',
            data: {
                labels: ['Enviados', 'Pendentes'],
                datasets: [{
                    data: [{{ $totalPgdasEnviados }}, {{ $totalPgdasPendentes }}],
                    backgroundColor: ['#22c55e', '#fbbf24'],
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
