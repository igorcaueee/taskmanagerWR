@extends('layouts.internal')

@section('title', 'DAS mensal (PGDASD) — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-invoice-dollar"></i> DAS mensal (PGDASD)</h1>
            <p class="text-gray-700 dark:text-gray-300">Lançar receita e transmitir, consultar declarações, importar do Domínio e ver o histórico de processamento — tudo em um só lugar.</p>
        </div>

        {{-- ─── Abas ─────────────────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-slate-700 mb-6">
            <button type="button" class="das-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-slate-400 hover:text-brand bg-transparent" data-tab="transmitir">
                <i class="fa-solid fa-paper-plane"></i> Lançar receita e transmitir
            </button>
            <button type="button" class="das-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-slate-400 hover:text-brand bg-transparent" data-tab="declaracoes">
                <i class="fa-solid fa-magnifying-glass"></i> Consultar declarações
            </button>
            <button type="button" class="das-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-slate-400 hover:text-brand bg-transparent" data-tab="importar">
                <i class="fa-solid fa-file-import"></i> Importar do Domínio
            </button>
            <button type="button" class="das-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-slate-400 hover:text-brand bg-transparent" data-tab="processamentos">
                <i class="fa-solid fa-list-check"></i> Processamentos em lote
            </button>
        </div>

        {{-- ═══════════════════════════ ABA: Lançar receita e transmitir ═══════════════════════════ --}}
        <div id="tabPanel-transmitir" class="das-tab-panel">
            <div id="cardTransmitirDeclaracao" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
                <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-paper-plane text-brand"></i> Lançar receita do mês e transmitir declaração
                </h2>
                <div class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 mb-4">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Transmitir cria uma declaração fiscal <strong>real</strong> perante a Receita Federal — não é possível desfazer. Confira os valores com atenção antes de confirmar.
                </div>

                <div class="flex flex-wrap gap-3 items-end mb-4">
                    <div class="flex-1 min-w-60">
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                        <select id="selectClienteTransmissao"
                                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Período (YYYYMM)</label>
                        <input type="text" id="inputPeriodoTransmissao" maxlength="6" placeholder="{{ now()->format('Ym') }}"
                               class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm w-32">
                    </div>
                </div>

                <div id="transmissaoCamposWrapper" class="hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3 pb-4 border-b border-gray-100 dark:border-slate-700">
                        <div class="md:col-span-3 text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Dados fiscais do cliente (cadastro único)</div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CNAE (referência)</label>
                            <input type="text" id="inputCnaeFiscal" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Anexo</label>
                            <input type="text" id="inputAnexoFiscal" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div class="md:col-span-3">
                            <button type="button" id="btnSalvarDadosFiscais" class="py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                                Salvar dados fiscais
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                        <div class="md:col-span-3 text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Receita bruta do período</div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Receita bruta (competência) R$</label>
                            <input type="number" step="0.01" id="inputReceitaCompetencia" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Receita bruta (caixa) R$</label>
                            <input type="number" step="0.01" id="inputReceitaCaixa" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Regime de apuração</label>
                            <select id="selectRegimeApuracao" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                                <option value="competencia">Competência</option>
                                <option value="caixa">Caixa</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <button type="button" id="btnSalvarReceitaMensal" class="py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                                Salvar receita do mês
                            </button>
                        </div>
                    </div>

                    {{-- ─── Atividades e Receitas do período (réplica do assistente do e-CAC) ── --}}
                    <div class="mb-4 pb-4 border-b border-gray-100 dark:border-slate-700">
                        <div class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase mb-2">Atividades e receitas do período</div>
                        <p class="text-xs text-gray-400 mb-3">Marque as atividades que tiveram receita neste período, informe o valor de cada uma e o tratamento tributário por tributo (se houver isenção/redução/substituição). A soma deve bater com a receita bruta lançada acima.</p>

                        <div class="relative mb-3" id="dropdownAtividadesWrapper">
                            <button type="button" id="btnToggleAtividades"
                                    class="w-full flex items-center justify-between text-left border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200">
                                <span id="resumoAtividadesSelecionadas" class="truncate">Selecionar atividades...</span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400 ml-2 transition-transform"></i>
                            </button>
                            <div id="checklistAtividades" class="hidden absolute z-20 mt-1 w-full max-h-72 overflow-y-auto bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg shadow-lg p-3 space-y-3"></div>
                        </div>

                        <div id="painelAtividades" class="space-y-4"></div>

                        <div id="somaAtividadesResumo" class="mt-3 text-xs rounded-lg px-3 py-2"></div>

                        <button type="button" id="btnSalvarAtividades" class="mt-3 py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                            Salvar atividades e receitas
                        </button>
                    </div>

                    <button type="button" id="btnTransmitirDeclaracao"
                            class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                        <i class="fa-solid fa-triangle-exclamation"></i> Transmitir declaração (real)
                    </button>

                    <div id="transmitirResultado" class="hidden mt-3 text-sm rounded-lg px-3 py-2"></div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════ ABA: Consultar declarações ═══════════════════════════ --}}
        <div id="tabPanel-declaracoes" class="das-tab-panel hidden">
            <div id="cardConsultarDeclaracoes" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
                <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-magnifying-glass text-brand"></i> Consultar declarações do cliente
                </h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                    Selecione o cliente e o ano para ver o histórico de declarações do PGDASD já transmitidas e o status do DAS de cada período. Depois é só clicar em "Ver RBT12" para abrir o PDF da declaração daquele período.
                </p>

                <div class="flex flex-wrap gap-3 items-end mb-4">
                    <div class="flex-1 min-w-60">
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                        <select id="selectClienteDeclaracoes"
                                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}" data-cnpj="{{ $cli->cpfcnpj }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ano</label>
                        <input type="text" id="inputAnoDeclaracoes" value="{{ now()->year }}" maxlength="4"
                               class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm w-24">
                    </div>
                    <button type="button" id="btnBuscarDeclaracoes"
                            class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                        Buscar declarações
                    </button>
                </div>

                <div id="declaracoesErro" class="hidden text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mb-3"></div>

                <div id="declaracoesTabelaWrapper" class="hidden overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Período</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nº Declaração</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transmitida em</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">DAS</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Emitir DAS</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Extrato</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recibo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Declaração (RBT12)</th>
                            </tr>
                        </thead>
                        <tbody id="declaracoesTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-sm text-gray-700 dark:text-gray-300"></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════ ABA: Importar do Domínio ═══════════════════════════ --}}
        <div id="tabPanel-importar" class="das-tab-panel hidden">
            <div id="cardImportarDominio" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
                <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-file-import text-brand"></i> Importar receita do relatório do Domínio (.txt)
                </h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                    Exporte o relatório de apuração do Simples Nacional do Domínio em .txt e suba aqui — o sistema tenta identificar o cliente pelo CNPJ e sugerir a atividade automaticamente. <strong>Confira a prévia antes de confirmar</strong>, principalmente a atividade sugerida.
                </p>

                <div class="flex items-center gap-3 mb-3">
                    <input type="file" id="inputArquivoDominio" accept=".txt"
                           class="text-sm text-gray-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand file:text-white file:text-xs">
                    <button type="button" id="btnPreviaDominio" class="py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                        Gerar prévia
                    </button>
                </div>

                <div id="importDominioErro" class="hidden text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mb-3"></div>

                <div id="importDominioPreviaWrapper" class="hidden">
                    <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">Período identificado: <strong id="importDominioPeriodo"></strong></p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                            <thead class="bg-gray-50 dark:bg-slate-900">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CNPJ (relatório)</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cliente no sistema</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">RBT12 / RBA</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Receita (competência/caixa)</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Atividade sugerida</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Receita da atividade</th>
                                </tr>
                            </thead>
                            <tbody id="importDominioTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700"></tbody>
                        </table>
                    </div>

                    <button type="button" id="btnConfirmarImportDominio" class="mt-3 py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                        Confirmar e salvar
                    </button>

                    <div id="importDominioResultado" class="hidden mt-3 text-sm rounded-lg px-3 py-2"></div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════ ABA: Processamentos em lote ═══════════════════════════ --}}
        <div id="tabPanel-processamentos" class="das-tab-panel hidden">
            <div class="bg-white dark:bg-slate-800 rounded shadow overflow-x-auto">
                <form method="GET" action="{{ route('simples-nacional.das.tela') }}" id="form-filtros-das"
                      class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                    <input type="hidden" name="tab" value="processamentos">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Período (YYYYMM)</label>
                        <input type="text" name="periodo" value="{{ $periodo }}" maxlength="6"
                               onchange="document.getElementById('form-filtros-das').submit()"
                               class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-32">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select name="status" onchange="document.getElementById('form-filtros-das').submit()"
                                class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                            <option value="">Todos</option>
                            <option value="sucesso" @selected(request('status') === 'sucesso')>Sucesso</option>
                            <option value="erro" @selected(request('status') === 'erro')>Erro</option>
                            <option value="ja_transmitido" @selected(request('status') === 'ja_transmitido')>Já transmitido</option>
                            <option value="pendente" @selected(request('status') === 'pendente')>Pendente</option>
                        </select>
                    </div>
                </form>

                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">CNPJ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recibo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Erro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Processado em</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                        @forelse($processamentos as $p)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $p->cliente->nome ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $p->cliente->cpfcnpj ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap">
                                    @php
                                        $badges = [
                                            'sucesso' => 'bg-green-100 text-green-800',
                                            'erro' => 'bg-red-100 text-red-800',
                                            'ja_transmitido' => 'bg-blue-100 text-blue-800',
                                            'pendente' => 'bg-yellow-100 text-yellow-800',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 rounded text-xs font-medium {{ $badges[$p->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst(str_replace('_', ' ', $p->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $p->numero_recibo ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-red-700 dark:text-red-400">{{ $p->mensagem_erro ?? '—' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $p->processado_em?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum processamento encontrado para este período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="px-6 py-4">
                    {{ $processamentos->links() }}
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.PGDASD_ATIVIDADES = @json($atividadesCatalogo);
        window.PGDASD_TRIBUTOS = @json($nomesTributos);
    </script>
    @include('simples-nacional._shared')
    <script>
    // ─── Abas ───────────────────────────────────────────────────────────────────
    const dasTabButtons = document.querySelectorAll('.das-tab-btn');
    const dasTabPanels  = document.querySelectorAll('.das-tab-panel');

    function ativarAbaDas(nome) {
        dasTabButtons.forEach(btn => {
            const ativo = btn.dataset.tab === nome;
            btn.classList.toggle('border-brand', ativo);
            btn.classList.toggle('text-brand', ativo);
            btn.classList.toggle('text-gray-500', !ativo);
            btn.classList.toggle('dark:text-slate-400', !ativo);
        });
        dasTabPanels.forEach(panel => {
            panel.classList.toggle('hidden', panel.id !== `tabPanel-${nome}`);
        });
    }

    dasTabButtons.forEach(btn => {
        btn.addEventListener('click', () => ativarAbaDas(btn.dataset.tab));
    });

    ativarAbaDas(new URLSearchParams(location.search).get('tab') ?? 'transmitir');

    // ═══════════════════════════ Lançar receita e transmitir ═══════════════════════════
    const selectClienteTransmissao   = document.getElementById('selectClienteTransmissao');
    const inputPeriodoTransmissao    = document.getElementById('inputPeriodoTransmissao');
    const transmissaoCamposWrapper   = document.getElementById('transmissaoCamposWrapper');
    const inputCnaeFiscal            = document.getElementById('inputCnaeFiscal');
    const inputAnexoFiscal           = document.getElementById('inputAnexoFiscal');
    const btnSalvarDadosFiscais      = document.getElementById('btnSalvarDadosFiscais');
    const inputReceitaCompetencia    = document.getElementById('inputReceitaCompetencia');
    const inputReceitaCaixa          = document.getElementById('inputReceitaCaixa');
    const selectRegimeApuracao       = document.getElementById('selectRegimeApuracao');
    const btnSalvarReceitaMensal     = document.getElementById('btnSalvarReceitaMensal');
    const btnTransmitirDeclaracao    = document.getElementById('btnTransmitirDeclaracao');
    const transmitirResultado        = document.getElementById('transmitirResultado');

    async function carregarDadosFiscais() {
        const resp = await fetch(`{{ route('simples-nacional.dados-fiscais.get') }}?cliente_id=${selectClienteTransmissao.value}`, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();
        inputCnaeFiscal.value = data.cnae_principal ?? '';
        inputAnexoFiscal.value = data.anexo_simples ?? '';

        // Só sugere automaticamente se o cliente ainda não tem CNAE cadastrado —
        // nunca sobrescreve um valor já salvo manualmente.
        if (!inputCnaeFiscal.value) {
            sugerirCnaeAutomatico();
        }
    }

    async function sugerirCnaeAutomatico() {
        try {
            const resp = await fetch(`{{ route('simples-nacional.dados-fiscais.sugerir-cnae') }}?cliente_id=${selectClienteTransmissao.value}`, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (data.cnae && !inputCnaeFiscal.value) {
                inputCnaeFiscal.value = `${data.cnae.codigo} - ${data.cnae.descricao}`;
            }
        } catch (e) {
            // Falha silenciosa — é só uma sugestão, não bloqueia o cadastro manual.
        }
    }

    async function carregarReceitaMensal() {
        const url = new URL('{{ route('simples-nacional.receita-mensal.get') }}');
        url.searchParams.set('cliente_id', selectClienteTransmissao.value);
        url.searchParams.set('periodo_apuracao', inputPeriodoTransmissao.value);

        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();
        inputReceitaCompetencia.value = data.receita_bruta_competencia ?? '';
        inputReceitaCaixa.value = data.receita_bruta_caixa ?? '';
        selectRegimeApuracao.value = data.regime_apuracao ?? 'competencia';
    }

    function atualizarCamposTransmissao() {
        const pronto = !!selectClienteTransmissao.value && /^\d{6}$/.test(inputPeriodoTransmissao.value);
        transmissaoCamposWrapper.classList.toggle('hidden', !pronto);
        transmitirResultado.classList.add('hidden');

        if (pronto) {
            carregarDadosFiscais();
            carregarReceitaMensal();
            carregarReceitasAtividades();
        }
    }

    selectClienteTransmissao.addEventListener('change', atualizarCamposTransmissao);
    inputPeriodoTransmissao.addEventListener('change', atualizarCamposTransmissao);

    btnSalvarDadosFiscais.addEventListener('click', async function () {
        this.disabled = true;

        try {
            const resp = await fetch('{{ route('simples-nacional.dados-fiscais.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente_id: selectClienteTransmissao.value,
                    cnae_principal: inputCnaeFiscal.value,
                    anexo_simples: inputAnexoFiscal.value,
                }),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
        }
    });

    btnSalvarReceitaMensal.addEventListener('click', async function () {
        if (!inputReceitaCompetencia.value) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe a receita bruta (competência).' });
            return;
        }

        this.disabled = true;

        try {
            const resp = await fetch('{{ route('simples-nacional.receita-mensal.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente_id: selectClienteTransmissao.value,
                    periodo_apuracao: inputPeriodoTransmissao.value,
                    receita_bruta_competencia: inputReceitaCompetencia.value,
                    receita_bruta_caixa: inputReceitaCaixa.value || null,
                    regime_apuracao: selectRegimeApuracao.value,
                }),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
        }
    });

    btnTransmitirDeclaracao.addEventListener('click', async function () {
        const nomeCliente = selectClienteTransmissao.options[selectClienteTransmissao.selectedIndex]?.text ?? '';
        const periodo = inputPeriodoTransmissao.value;

        const valorTributavel = selectRegimeApuracao.value === 'caixa'
            ? parseFloat(inputReceitaCaixa.value || '0')
            : parseFloat(inputReceitaCompetencia.value || '0');

        let confirmarReceitaZerada = false;

        if (!valorTributavel || valorTributavel <= 0) {
            const resultZerada = await Swal.fire({
                title: 'Receita zerada?',
                html: `A receita bruta do regime selecionado (${selectRegimeApuracao.value}) está <strong>zero</strong>. Confirma que é uma declaração <strong>sem movimento</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, é sem movimento mesmo',
                cancelButtonText: 'Cancelar, vou corrigir o valor',
            });

            if (!resultZerada.isConfirmed) return;
            confirmarReceitaZerada = true;
        }

        const result = await Swal.fire({
            title: 'Transmitir declaração?',
            html: `Isso vai transmitir a declaração REAL do PGDASD de <strong>${escapeHtml(nomeCliente)}</strong> para o período <strong>${escapeHtml(periodo)}</strong> perante a Receita Federal. Não pode ser desfeito. Tem certeza?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, transmitir',
            cancelButtonText: 'Cancelar',
        });

        {
            if (!result.isConfirmed) return;

            btnTransmitirDeclaracao.disabled = true;
            btnTransmitirDeclaracao.textContent = 'Transmitindo...';
            transmitirResultado.classList.add('hidden');

            try {
                const resp = await fetch('{{ route('simples-nacional.transmitir') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        cliente_id: selectClienteTransmissao.value,
                        periodo_apuracao: periodo,
                        confirmar_receita_zerada: confirmarReceitaZerada,
                    }),
                });
                const data = await resp.json();

                transmitirResultado.classList.remove('hidden');
                transmitirResultado.classList.remove(
                    'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                    'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
                );

                if (!resp.ok || data.error) {
                    transmitirResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                    transmitirResultado.textContent = data.error ?? 'Falha ao transmitir.';
                } else {
                    transmitirResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
                    transmitirResultado.textContent = data.message;
                }
            } catch (e) {
                transmitirResultado.classList.remove('hidden');
                transmitirResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                transmitirResultado.textContent = 'Erro de comunicação com o servidor.';
            } finally {
                btnTransmitirDeclaracao.disabled = false;
                btnTransmitirDeclaracao.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Transmitir declaração (real)';
            }
        }
    });

    // ─── Atividades e receitas do período (réplica do assistente do e-CAC) ─────
    const dropdownAtividadesWrapper = document.getElementById('dropdownAtividadesWrapper');
    const btnToggleAtividades = document.getElementById('btnToggleAtividades');
    const resumoAtividadesSelecionadas = document.getElementById('resumoAtividadesSelecionadas');
    const checklistAtividades = document.getElementById('checklistAtividades');
    const painelAtividades = document.getElementById('painelAtividades');
    const somaAtividadesResumo = document.getElementById('somaAtividadesResumo');
    const btnSalvarAtividades = document.getElementById('btnSalvarAtividades');

    function abrirDropdownAtividades() {
        checklistAtividades.classList.remove('hidden');
        btnToggleAtividades.querySelector('i').classList.add('rotate-180');
    }

    function fecharDropdownAtividades() {
        checklistAtividades.classList.add('hidden');
        btnToggleAtividades.querySelector('i').classList.remove('rotate-180');
        atualizarResumoAtividadesSelecionadas();
    }

    function atualizarResumoAtividadesSelecionadas() {
        const selecionadas = Array.from(checklistAtividades.querySelectorAll('.checkbox-atividade:checked'));

        resumoAtividadesSelecionadas.textContent = selecionadas.length === 0
            ? 'Selecionar atividades...'
            : selecionadas.map(cb => cb.value).join(', ') + (selecionadas.length > 1 ? ' atividades' : ' atividade');
    }

    btnToggleAtividades.addEventListener('click', () => {
        checklistAtividades.classList.contains('hidden') ? abrirDropdownAtividades() : fecharDropdownAtividades();
    });

    document.addEventListener('click', (event) => {
        if (!dropdownAtividadesWrapper.contains(event.target) && !checklistAtividades.classList.contains('hidden')) {
            fecharDropdownAtividades();
        }
    });

    const MOTIVOS_SUSPENSAO = { 1: 'Liminar em MS', 2: 'Depósito Judicial', 3: 'Antecipação de Tutela', 4: 'Liminar em Medida Cautelar', 5: 'Depósito Administrativo', 6: 'Outros' };
    const OPCOES_AJUSTE = {
        normal: 'Normal',
        imunidade: 'Imunidade',
        lancamento_oficio: 'Lançamento de Ofício',
        substituicao_tributaria: 'Substituição Tributária',
        tributacao_monofasica: 'Tributação Monofásica',
        antecipacao_encerramento: 'Antecipação c/ Encerramento',
        retencao_iss: 'Retenção de ISS',
        isencao: 'Isenção',
        reducao: 'Redução',
        exigibilidade_suspensa: 'Exigibilidade Suspensa',
    };

    function renderChecklistAtividades() {
        const grupos = {};
        Object.entries(window.PGDASD_ATIVIDADES).forEach(([id, a]) => {
            grupos[a.categoria] = grupos[a.categoria] || [];
            grupos[a.categoria].push({ id, ...a });
        });

        checklistAtividades.innerHTML = Object.entries(grupos).map(([categoria, itens]) => `
            <div>
                <div class="text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">${escapeHtml(categoria)}</div>
                ${itens.map(it => `
                    <label class="flex items-start gap-2 text-xs py-0.5 cursor-pointer">
                        <input type="checkbox" class="checkbox-atividade mt-0.5" value="${it.id}">
                        <span>${it.id} — ${escapeHtml(it.descricao)}</span>
                    </label>
                `).join('')}
            </div>
        `).join('');

        checklistAtividades.querySelectorAll('.checkbox-atividade').forEach(cb => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    adicionarPainelAtividade(cb.value);
                } else {
                    removerPainelAtividade(cb.value);
                }
                atualizarSomaAtividades();
                atualizarResumoAtividadesSelecionadas();
            });
        });
    }

    function renderTributoCell(codTributo, dadosExistentes) {
        const tipoAjuste = dadosExistentes?.tipo_ajuste ?? 'normal';
        const identificador = dadosExistentes?.identificador_isencao ?? 1;
        const percentual = dadosExistentes?.percentual_reducao ?? '';
        const motivo = dadosExistentes?.motivo_suspensao ?? 1;

        const cell = document.createElement('td');
        cell.className = 'align-top px-2 py-2 tributo-row';
        cell.dataset.codTributo = codTributo;

        cell.innerHTML = `
            <select class="select-tipo-ajuste w-full text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-1.5 py-1">
                ${Object.entries(OPCOES_AJUSTE).map(([v, l]) => `<option value="${v}" ${v === tipoAjuste ? 'selected' : ''}>${escapeHtml(l)}</option>`).join('')}
            </select>
            <select class="select-identificador hidden w-full mt-1 text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-1.5 py-1">
                <option value="1" ${identificador == 1 ? 'selected' : ''}>Normal</option>
                <option value="2" ${identificador == 2 ? 'selected' : ''}>Cesta básica</option>
            </select>
            <input type="number" step="0.01" placeholder="% redução" value="${percentual}" class="input-percentual hidden w-full mt-1 text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-1.5 py-1">
            <select class="select-motivo hidden w-full mt-1 text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-1.5 py-1">
                ${Object.entries(MOTIVOS_SUSPENSAO).map(([v, l]) => `<option value="${v}" ${v == motivo ? 'selected' : ''}>${escapeHtml(l)}</option>`).join('')}
            </select>
        `;

        const selectTipo = cell.querySelector('.select-tipo-ajuste');
        const selectIdent = cell.querySelector('.select-identificador');
        const inputPercentual = cell.querySelector('.input-percentual');
        const selectMotivo = cell.querySelector('.select-motivo');

        function atualizarVisibilidadeAjuste() {
            const v = selectTipo.value;
            selectIdent.classList.toggle('hidden', !(v === 'isencao' || v === 'reducao'));
            inputPercentual.classList.toggle('hidden', v !== 'reducao');
            selectMotivo.classList.toggle('hidden', v !== 'exigibilidade_suspensa');
        }
        atualizarVisibilidadeAjuste();
        selectTipo.addEventListener('change', atualizarVisibilidadeAjuste);

        return cell;
    }

    function adicionarPainelAtividade(idAtividade, dadosExistentes) {
        if (painelAtividades.querySelector(`[data-id-atividade="${idAtividade}"]`)) return;

        const atividade = window.PGDASD_ATIVIDADES[idAtividade];
        if (!atividade) return;

        const div = document.createElement('div');
        div.className = 'atividade-panel rounded-lg overflow-hidden border border-gray-200 dark:border-slate-700';
        div.dataset.idAtividade = idAtividade;

        div.innerHTML = `
            <div class="bg-green-600 text-white text-xs font-medium px-3 py-2">${idAtividade} — ${escapeHtml(atividade.descricao)}</div>
            <div class="p-3 overflow-x-auto">
                <table class="text-xs border-separate" style="border-spacing: 0.5rem 0;">
                    <thead>
                        <tr>
                            <th class="text-left text-gray-500 dark:text-slate-400 font-medium pb-1">Receita (R$)</th>
                            <th class="tributos-thead-cells"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="align-top py-2 pr-2">
                                <input type="number" step="0.01" class="input-valor-atividade w-32 rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1 text-sm" value="${dadosExistentes?.valor ?? ''}">
                            </td>
                            <td class="tributos-tbody-cells"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;

        const tributosExistentesMap = {};
        (dadosExistentes?.tributos ?? []).forEach(t => { tributosExistentesMap[t.cod_tributo] = t; });

        // As células de tributo vêm como <td> soltos (um por tributo) — inserimos cada
        // um como irmão do <td> placeholder da receita, e no cabeçalho o nome correspondente.
        const linhaCabecalho = div.querySelector('thead tr');
        const linhaCorpo = div.querySelector('tbody tr');
        const thPlaceholder = div.querySelector('.tributos-thead-cells');
        const tdPlaceholder = div.querySelector('.tributos-tbody-cells');
        thPlaceholder.remove();
        tdPlaceholder.remove();

        atividade.tributos.forEach(codTributo => {
            const th = document.createElement('th');
            th.className = 'text-left text-gray-500 dark:text-slate-400 font-medium pb-1';
            th.textContent = window.PGDASD_TRIBUTOS[codTributo] ?? codTributo;
            linhaCabecalho.appendChild(th);

            linhaCorpo.appendChild(renderTributoCell(codTributo, tributosExistentesMap[codTributo]));
        });

        div.querySelector('.input-valor-atividade').addEventListener('input', atualizarSomaAtividades);

        painelAtividades.appendChild(div);
    }

    function removerPainelAtividade(idAtividade) {
        const el = painelAtividades.querySelector(`[data-id-atividade="${idAtividade}"]`);
        if (el) el.remove();
        atualizarSomaAtividades();
    }

    function atualizarSomaAtividades() {
        let soma = 0;
        painelAtividades.querySelectorAll('.atividade-panel').forEach(p => {
            soma += parseFloat(p.querySelector('.input-valor-atividade').value || '0');
        });

        const alvo = selectRegimeApuracao.value === 'caixa'
            ? parseFloat(inputReceitaCaixa.value || '0')
            : parseFloat(inputReceitaCompetencia.value || '0');

        const bate = Math.abs(soma - alvo) < 0.01;

        somaAtividadesResumo.textContent = `Soma das atividades: R$ ${soma.toFixed(2)} — Receita bruta lançada (${selectRegimeApuracao.value}): R$ ${alvo.toFixed(2)} ${bate ? '✓ bate' : '✗ não bate'}`;
        somaAtividadesResumo.classList.remove('bg-green-50', 'text-green-700', 'dark:bg-green-900/20', 'dark:text-green-400', 'bg-yellow-50', 'text-yellow-700', 'dark:bg-yellow-900/20', 'dark:text-yellow-400');
        somaAtividadesResumo.classList.add(...(bate
            ? ['bg-green-50', 'text-green-700', 'dark:bg-green-900/20', 'dark:text-green-400']
            : ['bg-yellow-50', 'text-yellow-700', 'dark:bg-yellow-900/20', 'dark:text-yellow-400']));
    }

    inputReceitaCompetencia.addEventListener('input', atualizarSomaAtividades);
    inputReceitaCaixa.addEventListener('input', atualizarSomaAtividades);
    selectRegimeApuracao.addEventListener('change', atualizarSomaAtividades);

    async function carregarReceitasAtividades() {
        painelAtividades.innerHTML = '';
        checklistAtividades.querySelectorAll('.checkbox-atividade').forEach(cb => cb.checked = false);

        const url = new URL('{{ route('simples-nacional.receitas-atividades.get') }}');
        url.searchParams.set('cliente_id', selectClienteTransmissao.value);
        url.searchParams.set('periodo_apuracao', inputPeriodoTransmissao.value);

        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();

        (data.atividades ?? []).forEach(a => {
            const cb = checklistAtividades.querySelector(`.checkbox-atividade[value="${a.id_atividade}"]`);
            if (cb) cb.checked = true;
            adicionarPainelAtividade(a.id_atividade, a);
        });

        atualizarSomaAtividades();
        atualizarResumoAtividadesSelecionadas();
    }

    btnSalvarAtividades.addEventListener('click', async function () {
        const atividades = [];

        painelAtividades.querySelectorAll('.atividade-panel').forEach(p => {
            const idAtividade = parseInt(p.dataset.idAtividade, 10);
            const valor = parseFloat(p.querySelector('.input-valor-atividade').value || '0');
            const tributos = [];

            p.querySelectorAll('.tributo-row').forEach(row => {
                const tipoAjuste = row.querySelector('.select-tipo-ajuste').value;
                if (tipoAjuste === 'normal') return;

                const identificadorEl = row.querySelector('.select-identificador');
                const percentualEl = row.querySelector('.input-percentual');
                const motivoEl = row.querySelector('.select-motivo');

                tributos.push({
                    cod_tributo: parseInt(row.dataset.codTributo, 10),
                    tipo_ajuste: tipoAjuste,
                    identificador_isencao: (tipoAjuste === 'isencao' || tipoAjuste === 'reducao') ? parseInt(identificadorEl.value, 10) : null,
                    percentual_reducao: tipoAjuste === 'reducao' ? parseFloat(percentualEl.value || '0') : null,
                    motivo_suspensao: tipoAjuste === 'exigibilidade_suspensa' ? parseInt(motivoEl.value, 10) : null,
                    valor: valor,
                });
            });

            atividades.push({ id_atividade: idAtividade, valor, tributos });
        });

        if (atividades.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione ao menos uma atividade.' });
            return;
        }

        this.disabled = true;

        try {
            const resp = await fetch('{{ route('simples-nacional.receitas-atividades.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente_id: selectClienteTransmissao.value,
                    periodo_apuracao: inputPeriodoTransmissao.value,
                    atividades,
                }),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
        }
    });

    renderChecklistAtividades();
    protegerComConfigSerpro('cardTransmitirDeclaracao');

    // ═══════════════════════════ Consultar declarações ═══════════════════════════
    const selectClienteDeclaracoes = document.getElementById('selectClienteDeclaracoes');
    const inputAnoDeclaracoes      = document.getElementById('inputAnoDeclaracoes');
    const btnBuscarDeclaracoes     = document.getElementById('btnBuscarDeclaracoes');
    const declaracoesErro          = document.getElementById('declaracoesErro');
    const declaracoesTabelaWrapper = document.getElementById('declaracoesTabelaWrapper');
    const declaracoesTabelaBody    = document.getElementById('declaracoesTabelaBody');

    function renderStatusDas(dasList) {
        if (!dasList || dasList.length === 0) {
            return '<span class="text-xs text-gray-400">Não emitido</span>';
        }

        return dasList.map(das => {
            const pago = das.dasPago;
            const cor = pago ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
            const texto = pago ? 'Pago' : 'Emitido, não pago';
            return `<span class="px-2 py-0.5 rounded text-xs font-medium ${cor}">${escapeHtml(texto)}</span>`;
        }).join(' ');
    }

    btnBuscarDeclaracoes.addEventListener('click', async function () {
        const clienteId = selectClienteDeclaracoes.value;
        const ano = inputAnoDeclaracoes.value;

        if (!clienteId || !ano) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente e informe o ano.' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Buscando...';
        declaracoesErro.classList.add('hidden');
        declaracoesTabelaWrapper.classList.add('hidden');

        try {
            const url = new URL('{{ route('simples-nacional.declaracoes') }}');
            url.searchParams.set('cliente_id', clienteId);
            url.searchParams.set('ano_calendario', ano);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                declaracoesErro.textContent = data.error ?? 'Falha ao buscar declarações.';
                declaracoesErro.classList.remove('hidden');
                return;
            }

            declaracoesTabelaBody.innerHTML = '';

            if (data.periodos.length === 0) {
                declaracoesTabelaBody.innerHTML = '<tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">Nenhuma declaração encontrada para este ano.</td></tr>';
            }

            data.periodos.forEach(periodo => {
                const tr = document.createElement('tr');
                const numeroDeclaracao = periodo.numeroDeclaracao;

                tr.innerHTML = `
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(formatarPeriodo(periodo.periodoApuracao))}</td>
                    <td class="px-4 py-2 whitespace-nowrap font-mono text-xs">${escapeHtml(numeroDeclaracao ?? '—')}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(formatarDataHora(periodo.dataTransmissao))}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${renderStatusDas(periodo.das)}</td>
                    <td class="px-4 py-2 whitespace-nowrap emitir-das-cell"></td>
                    <td class="px-4 py-2 whitespace-nowrap extrato-cell"></td>
                    <td class="px-4 py-2 whitespace-nowrap recibo-cell"></td>
                    <td class="px-4 py-2 whitespace-nowrap declaracao-cell"></td>
                `;

                const emitirDasCell = tr.querySelector('.emitir-das-cell');

                if (numeroDeclaracao) {
                    const btnEmitirDas = document.createElement('button');
                    btnEmitirDas.type = 'button';
                    btnEmitirDas.title = 'Emitir/reemitir DAS';
                    btnEmitirDas.className = 'text-brand bg-transparent border-0 text-sm p-1 hover:text-brand/70';
                    btnEmitirDas.innerHTML = '<i class="fa-solid fa-file-invoice"></i>';
                    btnEmitirDas.addEventListener('click', async () => {
                        const amanha = new Date();
                        amanha.setDate(amanha.getDate() + 1);
                        const minData = amanha.toISOString().slice(0, 10);

                        const escolhaData = await Swal.fire({
                            title: 'Emitir DAS',
                            html: `
                                <p class="text-sm text-left mb-2">Deixe em branco para emitir com a data de hoje, ou escolha uma data futura para consolidar o DAS nela (igual ao "Consolidar para outra data" do e-CAC).</p>
                                <input type="date" id="swalDataConsolidacao" min="${minData}" class="swal2-input" style="width: 80%;">
                            `,
                            showCancelButton: true,
                            confirmButtonText: 'Emitir',
                            cancelButtonText: 'Cancelar',
                            preConfirm: () => document.getElementById('swalDataConsolidacao').value,
                        });

                        if (!escolhaData.isConfirmed) return;

                        const dataConsolidacao = escolhaData.value ? escolhaData.value.replaceAll('-', '') : null;

                        btnEmitirDas.disabled = true;
                        btnEmitirDas.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                        try {
                            const respDas = await fetch('{{ route('simples-nacional.declaracoes.emitir-das') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    cliente_id: clienteId,
                                    periodo_apuracao: String(periodo.periodoApuracao),
                                    data_consolidacao: dataConsolidacao,
                                }),
                            });
                            const dataDas = await respDas.json();

                            if (!respDas.ok || dataDas.error) {
                                Swal.fire({ icon: 'error', title: 'Erro', text: dataDas.error ?? 'Falha ao emitir DAS.' });
                                return;
                            }

                            const det = dataDas.detalhamento ?? {};
                            const valores = det.valores ?? {};
                            const composicao = det.composicao ?? [];
                            const link = dataDas.arquivo
                                ? `<a href="${escapeHtml(dataDas.arquivo.url)}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-brand hover:bg-brand/80 text-white text-sm font-medium rounded-lg no-underline"><i class="fa-solid fa-file-pdf"></i> Baixar DAS</a>`
                                : '<em>Nenhum PDF retornado.</em>';

                            const linhasComposicao = composicao.map(c => `
                                <tr>
                                    <td class="pr-3 text-gray-500">${escapeHtml(window.PGDASD_TRIBUTOS?.[c.codigo] ?? c.codigo)} — ${escapeHtml(c.denominacao ?? '')}</td>
                                    <td class="text-right">R$ ${formatarMoeda(c.valores?.total)}</td>
                                </tr>
                            `).join('');

                            Swal.fire({
                                icon: 'success',
                                title: 'DAS emitido',
                                html: `
                                    <div class="mb-3">${link}</div>
                                    <table class="text-xs text-left w-full mt-3">
                                        <tr><td class="pr-3 text-gray-500">Nº documento</td><td>${escapeHtml(det.numeroDocumento ?? '—')}</td></tr>
                                        <tr><td class="pr-3 text-gray-500">Vencimento</td><td>${formatarData8(det.dataVencimento)}</td></tr>
                                        <tr><td class="pr-3 text-gray-500">Limite de acolhimento</td><td>${formatarData8(det.dataLimiteAcolhimento)}</td></tr>
                                        <tr><td class="pr-3 text-gray-500">Principal</td><td>R$ ${formatarMoeda(valores.principal)}</td></tr>
                                        <tr><td class="pr-3 text-gray-500">Multa</td><td>R$ ${formatarMoeda(valores.multa)}</td></tr>
                                        <tr><td class="pr-3 text-gray-500">Juros</td><td>R$ ${formatarMoeda(valores.juros)}</td></tr>
                                        <tr><td class="pr-3 text-gray-500 font-semibold">Total</td><td class="font-semibold">R$ ${formatarMoeda(valores.total)}</td></tr>
                                    </table>
                                    ${linhasComposicao ? `
                                        <div class="text-xs text-gray-500 mt-3 mb-1 font-semibold">Composição por tributo</div>
                                        <table class="text-xs text-left w-full">${linhasComposicao}</table>
                                    ` : ''}
                                `,
                                width: 500,
                            });
                        } catch (e) {
                            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
                        } finally {
                            btnEmitirDas.disabled = false;
                            btnEmitirDas.innerHTML = '<i class="fa-solid fa-file-invoice"></i>';
                        }
                    });
                    emitirDasCell.appendChild(btnEmitirDas);
                } else {
                    emitirDasCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                }

                const extratoCell = tr.querySelector('.extrato-cell');

                if (periodo.das && periodo.das.length > 0) {
                    periodo.das.forEach((das, idx) => {
                        if (!das.numeroDas) return;

                        const rotuloExtrato = periodo.das.length > 1 ? `Extrato DAS ${idx + 1}` : 'Extrato do DAS';

                        const btnExtrato = document.createElement('button');
                        btnExtrato.type = 'button';
                        btnExtrato.title = rotuloExtrato;
                        btnExtrato.className = 'text-brand bg-transparent border-0 text-sm p-1 hover:text-brand/70';
                        btnExtrato.innerHTML = '<i class="fa-solid fa-file-invoice-dollar"></i>';
                        btnExtrato.addEventListener('click', async () => {
                            btnExtrato.disabled = true;
                            btnExtrato.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                            try {
                                const respExtrato = await fetch('{{ route('simples-nacional.declaracoes.extrato') }}', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ cliente_id: clienteId, numero_das: das.numeroDas }),
                                });
                                const dataExtrato = await respExtrato.json();

                                if (!respExtrato.ok || dataExtrato.error) {
                                    Swal.fire({ icon: 'error', title: 'Erro', text: dataExtrato.error ?? 'Falha ao buscar extrato.' });
                                    return;
                                }

                                const arquivo = (dataExtrato.arquivos ?? [])[0];

                                if (arquivo) {
                                    window.open(arquivo.url, '_blank');
                                } else {
                                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum PDF retornado para este extrato.' });
                                }
                            } catch (e) {
                                Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
                            } finally {
                                btnExtrato.disabled = false;
                                btnExtrato.innerHTML = '<i class="fa-solid fa-file-invoice-dollar"></i>';
                            }
                        });
                        extratoCell.appendChild(btnExtrato);
                    });
                } else {
                    extratoCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                }

                const reciboCell = tr.querySelector('.recibo-cell');
                const declaracaoCell = tr.querySelector('.declaracao-cell');

                if (numeroDeclaracao) {
                    // Busca automática ao carregar a linha (sem clique) — cada período consultado
                    // aqui dispara uma chamada paga de CONSDECREC15 na SERPRO.
                    reciboCell.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-gray-400"></i>';
                    declaracaoCell.innerHTML = '';

                    fetch('{{ route('simples-nacional.declaracoes.rbt12') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cliente_id: clienteId, numero_declaracao: numeroDeclaracao }),
                    })
                        .then(resp => resp.json().then(data => ({ ok: resp.ok, data })))
                        .then(({ ok, data }) => {
                            if (!ok || data.error) {
                                reciboCell.innerHTML = `<span class="text-xs text-red-600" title="${escapeHtml(data.error ?? 'Falha ao buscar.')}"><i class="fa-solid fa-triangle-exclamation"></i></span>`;
                                declaracaoCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                                return;
                            }

                            const arquivos = data.arquivos ?? [];
                            const recibo = arquivos.find(a => a.nomeArquivo.toUpperCase().includes('RECIBO'));
                            const declaracao = arquivos.find(a => a.nomeArquivo.toUpperCase().includes('DECLARACAO'));

                            reciboCell.innerHTML = recibo
                                ? `<a href="${escapeHtml(recibo.url)}" target="_blank" title="Recibo" class="text-brand hover:text-brand/70"><i class="fa-solid fa-file-pdf"></i></a>`
                                : '<span class="text-xs text-gray-400">—</span>';

                            declaracaoCell.innerHTML = declaracao
                                ? `<a href="${escapeHtml(declaracao.url)}" target="_blank" title="Declaração (RBT12)" class="text-brand hover:text-brand/70"><i class="fa-solid fa-file-pdf"></i></a>`
                                : '<span class="text-xs text-gray-400">—</span>';
                        })
                        .catch(() => {
                            reciboCell.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-red-600" title="Erro de comunicação"></i>';
                            declaracaoCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                        });
                } else {
                    reciboCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                    declaracaoCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                }

                declaracoesTabelaBody.appendChild(tr);
            });

            declaracoesTabelaWrapper.classList.remove('hidden');
        } catch (e) {
            declaracoesErro.textContent = 'Erro de comunicação com o servidor.';
            declaracoesErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'Buscar declarações';
        }
    });

    protegerComConfigSerpro('cardConsultarDeclaracoes');

    // ═══════════════════════════ Importar do Domínio ═══════════════════════════
    const inputArquivoDominio = document.getElementById('inputArquivoDominio');
    const btnPreviaDominio = document.getElementById('btnPreviaDominio');
    const importDominioErro = document.getElementById('importDominioErro');
    const importDominioPreviaWrapper = document.getElementById('importDominioPreviaWrapper');
    const importDominioPeriodo = document.getElementById('importDominioPeriodo');
    const importDominioTabelaBody = document.getElementById('importDominioTabelaBody');
    const btnConfirmarImportDominio = document.getElementById('btnConfirmarImportDominio');
    const importDominioResultado = document.getElementById('importDominioResultado');

    function montarSelectAtividadesDominio(idSelecionado) {
        const grupos = {};
        Object.entries(window.PGDASD_ATIVIDADES).forEach(([id, a]) => {
            grupos[a.categoria] = grupos[a.categoria] || [];
            grupos[a.categoria].push({ id, ...a });
        });

        let html = '<option value="">Selecione...</option>';
        Object.entries(grupos).forEach(([categoria, itens]) => {
            html += `<optgroup label="${escapeHtml(categoria)}">`;
            itens.forEach(it => {
                html += `<option value="${it.id}" ${String(idSelecionado) === it.id ? 'selected' : ''}>${it.id} — ${escapeHtml(it.descricao)}</option>`;
            });
            html += '</optgroup>';
        });

        return html;
    }

    // Competência e caixa são mutuamente exclusivos (regime_apuracao só aceita um dos dois no banco),
    // mas o usuário pediu 2 checkboxes em vez de um <select> fixo — força exclusividade e impede
    // deixar as duas desmarcadas, senão não dá pra saber qual valor usar ao salvar.
    importDominioTabelaBody.addEventListener('change', function (ev) {
        const target = ev.target;
        if (!target.classList.contains('checkbox-regime-competencia') && !target.classList.contains('checkbox-regime-caixa')) {
            return;
        }

        const tr = target.closest('tr');
        const checkboxCompetencia = tr.querySelector('.checkbox-regime-competencia');
        const checkboxCaixa = tr.querySelector('.checkbox-regime-caixa');

        if (!target.checked) {
            target.checked = true; // não permite deixar as duas desmarcadas
            return;
        }

        if (target === checkboxCompetencia) {
            checkboxCaixa.checked = false;
        } else {
            checkboxCompetencia.checked = false;
        }
    });

    btnPreviaDominio.addEventListener('click', async function () {
        if (!inputArquivoDominio.files[0]) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o arquivo .txt exportado do Domínio.' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Lendo...';
        importDominioErro.classList.add('hidden');
        importDominioPreviaWrapper.classList.add('hidden');

        try {
            const formData = new FormData();
            formData.append('arquivo', inputArquivoDominio.files[0]);

            const resp = await fetch('{{ route('simples-nacional.importar-dominio.previa') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: formData,
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                importDominioErro.textContent = data.error ?? 'Falha ao ler o arquivo.';
                importDominioErro.classList.remove('hidden');
                return;
            }

            importDominioPeriodo.textContent = formatarPeriodo(data.periodo_apuracao);
            importDominioTabelaBody.innerHTML = '';

            data.estabelecimentos.forEach(e => {
                const tr = document.createElement('tr');
                tr.dataset.clienteId = e.cliente_id ?? '';

                const clienteHtml = e.cliente_id
                    ? `<span class="text-green-700 dark:text-green-400">${escapeHtml(e.cliente_nome)}</span>`
                    : `<span class="text-red-600">Não encontrado (CNPJ ${escapeHtml(e.cnpj)})</span>`;

                const avisoTributos = (e.tributos_divergentes ?? []).length > 0
                    ? `<div class="text-xs text-amber-600 mt-1"><i class="fa-solid fa-triangle-exclamation"></i> Tributos com situação diferente de "Tributado" — ajuste manualmente depois em "Atividades e receitas".</div>`
                    : '';

                // sugere caixa só quando o relatório já traz um valor de caixa; senão parte de competência.
                // O valor é único e editável — o Domínio pode marcar como "competência" um cliente que na
                // prática lança como "caixa" no eCAC, então o checkbox só decide o destino, sem depender
                // de qual coluna o relatório preencheu.
                const regimeSugerido = (e.rpa_caixa !== null && e.rpa_caixa !== undefined && Number(e.rpa_caixa) > 0) ? 'caixa' : 'competencia';
                const valorInicial = regimeSugerido === 'caixa' ? (e.rpa_caixa ?? '') : (e.rpa_competencia ?? '');

                tr.innerHTML = `
                    <td class="px-3 py-2 font-mono text-xs whitespace-nowrap">${escapeHtml(e.cnpj)}</td>
                    <td class="px-3 py-2 whitespace-nowrap">${clienteHtml}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-xs">RBT12: R$ ${formatarMoeda(e.rbt12)}<br>RBA atual: R$ ${formatarMoeda(e.rba_atual)}<br>RBA anterior: R$ ${formatarMoeda(e.rba_anterior)}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-xs">
                        <input type="number" step="0.01" class="input-valor-regime-dominio block mb-1.5 text-sm rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1 w-28" value="${valorInicial}">
                        <label class="flex items-center gap-1.5 mb-1 cursor-pointer">
                            <input type="checkbox" class="checkbox-regime-competencia" ${regimeSugerido === 'competencia' ? 'checked' : ''}>
                            Competência
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" class="checkbox-regime-caixa" ${regimeSugerido === 'caixa' ? 'checked' : ''}>
                            Caixa
                        </label>
                    </td>
                    <td class="px-3 py-2 select-atividade-cell">
                        <select class="select-atividade-dominio text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1 w-64">
                            ${montarSelectAtividadesDominio(e.id_atividade_sugerido)}
                        </select>
                        <div class="text-xs text-gray-400 mt-1">Confiança do match: ${Math.round((e.confianca_match ?? 0) * 100)}% — texto do relatório: "${escapeHtml(e.tabela_texto ?? '—')}"</div>
                        ${avisoTributos}
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" step="0.01" class="input-receita-atividade-dominio text-sm rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1 w-28" value="${e.receita_tributada_total ?? ''}">
                    </td>
                `;

                importDominioTabelaBody.appendChild(tr);
            });

            importDominioPreviaWrapper.classList.remove('hidden');
        } catch (e) {
            importDominioErro.textContent = 'Erro de comunicação com o servidor.';
            importDominioErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'Gerar prévia';
        }
    });

    btnConfirmarImportDominio.addEventListener('click', async function () {
        const linhas = Array.from(importDominioTabelaBody.querySelectorAll('tr'));

        const estabelecimentos = [];
        let temSemCliente = false;

        linhas.forEach(tr => {
            const clienteId = tr.dataset.clienteId;
            if (!clienteId) {
                temSemCliente = true;
                return; // pula estabelecimentos sem cliente encontrado
            }

            const idAtividade = tr.querySelector('.select-atividade-dominio').value;
            const receita = tr.querySelector('.input-receita-atividade-dominio').value;
            const regimeApuracao = tr.querySelector('.checkbox-regime-caixa').checked ? 'caixa' : 'competencia';
            const valorRegime = tr.querySelector('.input-valor-regime-dominio').value || null;

            estabelecimentos.push({
                cliente_id: clienteId,
                // receita_bruta_competencia é sempre exigida pelo PGDASD (mesmo em regime caixa, ver
                // PgdasdService::montarDeclaracao) — por isso o valor escolhido aqui sempre alimenta
                // competência, e também alimenta caixa quando esse é o regime marcado.
                rpa_competencia: valorRegime,
                rpa_caixa: regimeApuracao === 'caixa' ? valorRegime : null,
                regime_apuracao: regimeApuracao,
                id_atividade: idAtividade,
                receita_tributada_total: receita,
            });
        });

        if (estabelecimentos.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum estabelecimento com cliente identificado para importar.' });
            return;
        }

        const confirmacao = await Swal.fire({
            title: 'Confirmar importação?',
            html: `Vai salvar receita/atividade de <strong>${estabelecimentos.length}</strong> estabelecimento(s)` + (temSemCliente ? ' (os sem cliente encontrado serão ignorados)' : '') + '.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, salvar',
            cancelButtonText: 'Cancelar',
        });

        if (!confirmacao.isConfirmed) return;

        this.disabled = true;

        try {
            // extrai o período no formato AAAAMM a partir do texto exibido (MM/AAAA)
            const [mm, aaaa] = importDominioPeriodo.textContent.split('/');
            const periodoApuracao = `${aaaa}${mm}`;

            const resp = await fetch('{{ route('simples-nacional.importar-dominio.confirmar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ periodo_apuracao: periodoApuracao, estabelecimentos }),
            });
            const data = await resp.json();

            importDominioResultado.classList.remove('hidden');
            importDominioResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                importDominioResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                importDominioResultado.textContent = data.error ?? 'Falha ao salvar.';
            } else {
                importDominioResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
                importDominioResultado.textContent = data.message;
            }
        } catch (e) {
            importDominioResultado.classList.remove('hidden');
            importDominioResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            importDominioResultado.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
        }
    });

    protegerComConfigSerpro('cardImportarDominio');
    </script>
    @endpush
@endsection
