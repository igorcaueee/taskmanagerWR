@extends('layouts.internal')

@section('title', 'Relatórios — WR Assessoria')

@section('content')
    <div class="py-6 px-6">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-chart-bar"></i> Relatórios</h1>
                <p class="text-gray-700 dark:text-gray-300">Análise de desempenho e produtividade das tarefas.</p>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('relatorios') }}" id="form-relatorio"
              class="bg-white dark:bg-slate-800 rounded shadow px-4 py-3 mb-6">

            <div class="flex flex-wrap gap-3 items-end">
                {{-- Período --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Período</label>
                    <select name="periodo" id="select-periodo"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="hoje"          @selected(request('periodo') === 'hoje')>Hoje</option>
                        <option value="semana"        @selected(request('periodo') === 'semana')>Esta semana</option>
                        <option value="mes"           @selected(request('periodo', 'mes') === 'mes')>Este mês</option>
                        <option value="trimestre"     @selected(request('periodo') === 'trimestre')>Últimos 3 meses</option>
                        <option value="semestre"      @selected(request('periodo') === 'semestre')>Últimos 6 meses</option>
                        <option value="ano"           @selected(request('periodo') === 'ano')>Este ano</option>
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

                {{-- Responsável --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Responsável</label>
                    <select name="responsavel_id"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($usuarios as $u)
                            <option value="{{ $u->id }}" @selected($responsavelFiltro == $u->id)>{{ $u->nome }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Etapa --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Etapa</label>
                    <select name="etapa_id"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todas</option>
                        @foreach($etapas as $e)
                            <option value="{{ $e->id }}" @selected($etapaFiltro == $e->id)>{{ $e->nome }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Departamento --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Departamento</label>
                    <select name="departamento_id"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($departamentos as $d)
                            <option value="{{ $d->id }}" @selected($departamentoFiltro == $d->id)>{{ $d->nome }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo de tarefa --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tipo</label>
                    <select name="tipo_tarefa_id"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($tiposTarefa as $t)
                            <option value="{{ $t->id }}" @selected($tipoTarefaFiltro == $t->id)>{{ $t->nome }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Cliente --}}
                <div class="relative" id="wrapper-cliente">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Cliente</label>
                    <input type="hidden" name="cliente_id" id="cliente-id-hidden" value="{{ $clienteFiltro }}">
                    <div class="relative">
                        <input type="text" id="cliente-search"
                               placeholder="Buscar cliente..."
                               autocomplete="off"
                               value="{{ $clienteFiltro ? $clientes->firstWhere('id', $clienteFiltro)?->nome : '' }}"
                               class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-56">
                        @if($clienteFiltro)
                            <button type="button" id="cliente-clear"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-4 h-4 flex items-center justify-center rounded-full bg-gray-400 hover:bg-gray-500 dark:bg-slate-500 dark:hover:bg-slate-400 text-white transition-colors">
                                <i class="fa-solid fa-xmark text-[10px]"></i>
                            </button>
                        @endif
                    </div>
                    <ul id="cliente-dropdown"
                        class="hidden absolute z-50 mt-1 min-w-full w-80 max-h-64 overflow-y-auto bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-lg shadow-lg text-sm">
                        @foreach($clientes as $cli)
                            <li class="cliente-opcao px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200"
                                data-id="{{ $cli->id }}" data-nome="{{ $cli->nome }}">
                                {{ $cli->nome }}
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Status</label>
                    <select name="status"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        <option value="concluida" @selected($statusFiltro === 'concluida')>Concluídas</option>
                        <option value="pendente"  @selected($statusFiltro === 'pendente')>Pendentes</option>
                        <option value="vencida"   @selected($statusFiltro === 'vencida')>Vencidas</option>
                    </select>
                </div>

                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-1.5 bg-brand text-white rounded border-0 text-sm focus:outline-none hover:bg-brand/80">
                    <i class="fa-solid fa-magnifying-glass"></i> Aplicar
                </button>

                @if(request()->hasAny(['responsavel_id','etapa_id','departamento_id','tipo_tarefa_id','cliente_id','status']))
                    <a href="{{ route('relatorios', array_filter(['periodo' => request('periodo'), 'data_inicio' => request('data_inicio'), 'data_fim' => request('data_fim')])) }}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400 hover:text-red-500 border border-gray-300 dark:border-slate-600 rounded">
                        <i class="fa-solid fa-xmark"></i> Limpar filtros
                    </a>
                @endif

                <p class="text-xs text-gray-400 dark:text-slate-500 self-center ml-auto">
                    {{ $dataInicio->format('d/m/Y') }} — {{ $dataFim->format('d/m/Y') }}
                </p>
            </div>
        </form>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total de tarefas</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $totalTarefas }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">no período selecionado</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Concluídas</p>
                <p class="mt-1 text-3xl font-bold text-green-600">{{ $totalConcluidas }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">
                    @if($totalTarefas > 0)
                        {{ round(($totalConcluidas / $totalTarefas) * 100) }}% do total
                    @else
                        —
                    @endif
                </p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Vencidas</p>
                <p class="mt-1 text-3xl font-bold text-red-600">{{ $totalVencidas }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">em aberto e atrasadas</p>
            </div>
            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Concluídas esta semana</p>
                <p class="mt-1 text-3xl font-bold text-brand">{{ $concluidasEstaSemana }}</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-slate-500">{{ now()->startOfWeek()->format('d/m') }} — {{ now()->endOfWeek()->format('d/m') }}</p>
            </div>
        </div>

        {{-- Linha 1: Concluídas por responsável + Evolução mensal --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                    <i class="fa-solid fa-user-check mr-1 text-brand"></i> Concluídas por responsável
                </h2>
                @if($porResponsavel->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-slate-500 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:220px">
                        <canvas id="chartResponsavel"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                    <i class="fa-solid fa-chart-line mr-1 text-brand"></i> Evolução mensal (últimos 12 meses)
                </h2>
                <div style="position:relative;height:220px">
                    <canvas id="chartEvolucao"></canvas>
                </div>
            </div>
        </div>

        {{-- Linha 2: Por etapa + Por cliente --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                    <i class="fa-solid fa-layer-group mr-1 text-brand"></i> Tarefas por etapa
                </h2>
                @if($porEtapa->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-slate-500 italic">Nenhum dado disponível.</p>
                @else
                    <div class="flex items-center gap-6">
                        <div style="position:relative;width:176px;height:176px" class="flex-shrink-0">
                            <canvas id="chartEtapa"></canvas>
                        </div>
                        <ul class="space-y-1 flex-1 min-w-0">
                            @foreach($porEtapa as $item)
                                <li class="flex items-center gap-2 text-sm">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $item['cor'] }}"></span>
                                    <span class="truncate text-gray-600 dark:text-gray-400">{{ $item['nome'] }}</span>
                                    <span class="ml-auto font-semibold text-gray-900 dark:text-slate-100">{{ $item['total'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                    <i class="fa-regular fa-building mr-1 text-brand"></i> Tarefas por cliente (top 10)
                </h2>
                @if($porCliente->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-slate-500 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:220px">
                        <canvas id="chartCliente"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Linha 3: Por departamento + Vencidas vs prazo + Por recorrência --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6" id="graficos-linha3">

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                    <i class="fa-solid fa-sitemap mr-1 text-brand"></i> Por departamento
                </h2>
                @if($porDepartamento->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-slate-500 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:220px">
                        <canvas id="chartDepartamento"></canvas>
                    </div>
                @endif
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
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
                                <span class="text-gray-600 dark:text-gray-400">{{ $item['label'] }}</span>
                                <span class="ml-2 font-semibold text-gray-900">{{ $item['total'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                    <i class="fa-solid fa-rotate mr-1 text-brand"></i> Por recorrência
                </h2>
                @if($porRecorrencia->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-slate-500 italic">Nenhum dado disponível.</p>
                @else
                    <div style="position:relative;height:220px">
                        <canvas id="chartRecorrencia"></canvas>
                    </div>
                @endif
            </div>
        </div>

        {{-- Tabela de tarefas --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 mb-6">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-slate-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <i class="fa-solid fa-list-check mr-1 text-brand"></i> Tarefas do período
                    <span class="ml-2 text-xs font-normal text-gray-400 dark:text-slate-500">({{ $tarefas->total() }} registros)</span>
                </h2>
                <a href="{{ route('relatorios.tarefas.export', request()->query()) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>
            </div>

            @php
                $sortUrl = fn(string $col) => request()->fullUrlWithQuery([
                    'ordem'    => $col,
                    'direcao'  => ($colunaOrdem === $col && $direcaoOrdem === 'asc') ? 'desc' : 'asc',
                    'page'     => 1,
                ]);
                $sortIcon = fn(string $col) => $colunaOrdem === $col
                    ? ($direcaoOrdem === 'asc' ? 'fa-sort-up' : 'fa-sort-down')
                    : 'fa-sort';
            @endphp

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-gray-400">
                                <a href="{{ $sortUrl('titulo') }}" class="inline-flex items-center gap-1 hover:text-brand">
                                    Título <i class="fa-solid {{ $sortIcon('titulo') }} text-xs"></i>
                                </a>
                            </th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Cliente</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Responsável</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Etapa</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                <a href="{{ $sortUrl('data_vencimento') }}" class="inline-flex items-center gap-1 hover:text-brand">
                                    Vencimento <i class="fa-solid {{ $sortIcon('data_vencimento') }} text-xs"></i>
                                </a>
                            </th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                <a href="{{ $sortUrl('data_conclusao') }}" class="inline-flex items-center gap-1 hover:text-brand">
                                    Conclusão <i class="fa-solid {{ $sortIcon('data_conclusao') }} text-xs"></i>
                                </a>
                            </th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                <a href="{{ $sortUrl('prioridade') }}" class="inline-flex items-center gap-1 hover:text-brand">
                                    Prioridade <i class="fa-solid {{ $sortIcon('prioridade') }} text-xs"></i>
                                </a>
                            </th>
                            <th class="text-left px-4 py-2 font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse($tarefas as $tarefa)
                            @php
                                $concluida = (bool) $tarefa->data_conclusao;
                                $concluidaAtrasada = $concluida && $tarefa->data_vencimento && $tarefa->data_conclusao->gt($tarefa->data_vencimento);
                                $vencida   = ! $concluida && $tarefa->data_vencimento?->isPast();
                                $dataVencidaVisual = $vencida || $concluidaAtrasada;
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40">
                                <td class="px-4 py-2 text-gray-800 dark:text-slate-200 max-w-xs truncate" title="{{ $tarefa->titulo }}">
                                    {{ $tarefa->titulo }}
                                </td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    {{ $tarefa->cliente?->nome ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    {{ $tarefa->responsavel?->nome ?? '—' }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    @if($tarefa->etapa)
                                        <span class="inline-flex items-center gap-1.5 text-xs px-2 py-0.5 rounded-full text-white font-medium"
                                              style="background-color: {{ $tarefa->etapa->cor ?? '#94a3b8' }}">
                                            {{ $tarefa->etapa->nome }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap {{ $dataVencidaVisual ? 'text-red-600 font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                                    {{ $tarefa->data_vencimento?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-2 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                    {{ $tarefa->data_conclusao?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    @if($tarefa->prioridade === 'alta')
                                        <span class="text-xs font-medium text-red-600"><i class="fa-solid fa-circle-exclamation mr-1"></i>Alta</span>
                                    @elseif($tarefa->prioridade === 'media')
                                        <span class="text-xs font-medium text-yellow-600"><i class="fa-solid fa-circle mr-1"></i>Média</span>
                                    @else
                                        <span class="text-xs font-medium text-gray-400 dark:text-slate-500"><i class="fa-regular fa-circle mr-1"></i>Baixa</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    @if($concluida)
                                        <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
                                            <i class="fa-solid fa-check-circle"></i> Concluída
                                        </span>
                                    @elseif($vencida)
                                        <span class="inline-flex items-center gap-1 text-xs text-red-600 font-medium">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Vencida
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs text-blue-500 font-medium">
                                            <i class="fa-solid fa-clock"></i> Pendente
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-slate-500 italic">
                                    Nenhuma tarefa encontrada para os filtros selecionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($tarefas->hasPages())
                <div class="px-5 py-3 border-t border-gray-200 dark:border-slate-700">
                    {{ $tarefas->links() }}
                </div>
            @endif
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

    // ── Busca de cliente (autocomplete) ─────────────────────────────────
    (function () {
        const input    = document.getElementById('cliente-search');
        const hidden   = document.getElementById('cliente-id-hidden');
        const dropdown = document.getElementById('cliente-dropdown');
        const clearBtn = document.getElementById('cliente-clear');
        const opcoes   = dropdown ? dropdown.querySelectorAll('.cliente-opcao') : [];

        if (!input) return;

        function filtrar(termo) {
            if (termo.length < 3) {
                dropdown.classList.add('hidden');
                return;
            }
            let visiveis = 0;
            opcoes.forEach(op => {
                const match = op.dataset.nome.toLowerCase().includes(termo.toLowerCase());
                op.classList.toggle('hidden', !match);
                if (match) visiveis++;
            });
            dropdown.classList.toggle('hidden', visiveis === 0);
        }

        input.addEventListener('input', () => { filtrar(input.value); hidden.value = ''; });

        opcoes.forEach(op => {
            op.addEventListener('mousedown', (e) => {
                e.preventDefault();
                input.value  = op.dataset.nome;
                hidden.value = op.dataset.id;
                dropdown.classList.add('hidden');
                document.getElementById('form-relatorio').submit();
            });
        });

        document.addEventListener('click', (e) => {
            if (!document.getElementById('wrapper-cliente')?.contains(e.target)) {
                dropdown.classList.add('hidden');
                if (!hidden.value) input.value = '';
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                input.value  = '';
                hidden.value = '';
                document.getElementById('form-relatorio').submit();
            });
        }
    })();

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
            labels: @json($porResponsavel->pluck('nome')),
            datasets: [{
                label: 'Concluídas',
                data: @json($porResponsavel->pluck('total')),
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
            labels: @json($evolucaoMensal->pluck('mes')),
            datasets: [
                {
                    label: 'Total',
                    data: @json($evolucaoMensal->pluck('total')),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                },
                {
                    label: 'Concluídas',
                    data: @json($evolucaoMensal->pluck('concluidas')),
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
            labels: @json($porEtapa->pluck('nome')),
            datasets: [{
                data: @json($porEtapa->pluck('total')),
                backgroundColor: @json($porEtapa->pluck('cor')),
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
            labels: @json($porCliente->pluck('nome')),
            datasets: [{
                label: 'Tarefas',
                data: @json($porCliente->pluck('total')),
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
            labels: @json($porDepartamento->pluck('nome')),
            datasets: [{
                data: @json($porDepartamento->pluck('total')),
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
            labels: @json($porRecorrencia->pluck('nome')),
            datasets: [{
                label: 'Tarefas',
                data: @json($porRecorrencia->pluck('total')),
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
