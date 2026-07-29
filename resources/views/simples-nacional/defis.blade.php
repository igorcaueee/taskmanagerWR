@extends('layouts.internal')

@section('title', 'DEFIS — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-lines"></i> DEFIS</h1>
            <p class="text-gray-700 dark:text-gray-300">Lançar dados anuais e transmitir, ou consultar declarações já transmitidas.</p>
        </div>

        {{-- ─── Abas ─────────────────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-slate-700 mb-6">
            <button type="button" class="defis-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-slate-400 hover:text-brand bg-transparent" data-tab="transmitir">
                <i class="fa-solid fa-paper-plane"></i> Transmitir DEFIS
            </button>
            <button type="button" class="defis-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-slate-400 hover:text-brand bg-transparent" data-tab="declaracoes">
                <i class="fa-solid fa-magnifying-glass"></i> Consultar declarações
            </button>
        </div>

        {{-- ═══════════════════════════ ABA: Transmitir DEFIS ═══════════════════════════ --}}
        <div id="tabPanel-transmitir" class="defis-tab-panel">
            <div id="cardDefisTransmitir" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
                <div class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 mb-4">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Transmitir cria uma declaração fiscal <strong>real</strong> perante a Receita Federal — não é possível desfazer. Suporta só <strong>um estabelecimento</strong> (a matriz do cliente); e não cobre situação especial (cisão/fusão/incorporação/extinção), "não optante", comerciais exportadoras, doações a campanha eleitoral, nem os casos de "informação opcional" por estabelecimento — se algum se aplicar, lance a DEFIS manualmente pelo e-CAC.
                </div>

                <div class="flex flex-wrap gap-3 items-end mb-4">
                    <div class="flex-1 min-w-60">
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                        <select id="selectClienteDefisT"
                                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ano-calendário</label>
                        <input type="text" id="inputAnoDefisT" maxlength="4" placeholder="{{ now()->subYear()->year }}"
                               class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm w-24">
                    </div>
                    <button type="button" id="btnCarregarDefisT"
                            class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                        Carregar dados
                    </button>
                </div>

                <div id="defisTStatusExistente" class="hidden mb-4 text-sm rounded-lg px-3 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400"></div>

                <div id="defisTFormWrapper" class="hidden">
                    {{-- ─── Bloqueios de cenários não suportados ─────────────────────── --}}
                    <div class="mb-4 pb-4 border-b border-gray-100 dark:border-slate-700 space-y-2">
                        <div class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase mb-2">Cenários não suportados — confirme que nenhum se aplica</div>
                        <label class="flex items-start gap-2 text-sm cursor-pointer">
                            <input type="checkbox" id="chkSituacaoEspecial" class="mt-0.5 chk-bloqueio-defis">
                            Empresa teve situação especial no período (cisão, fusão, incorporação ou extinção)
                        </label>
                        <label class="flex items-start gap-2 text-sm cursor-pointer">
                            <input type="checkbox" id="chkNaoOptante" class="mt-0.5 chk-bloqueio-defis">
                            Empresa é "não optante" pelo Simples Nacional nesse período
                        </label>
                        <label class="flex items-start gap-2 text-sm cursor-pointer">
                            <input type="checkbox" id="chkComerciaisExportadoras" class="mt-0.5 chk-bloqueio-defis">
                            Empresa teve receita de exportação via comercial exportadora
                        </label>
                        <label class="flex items-start gap-2 text-sm cursor-pointer">
                            <input type="checkbox" id="chkDoacoesEleitorais" class="mt-0.5 chk-bloqueio-defis">
                            Empresa fez doação para campanha eleitoral
                        </label>
                        <label class="flex items-start gap-2 text-sm cursor-pointer">
                            <input type="checkbox" id="chkInformacaoOpcional" class="mt-0.5 chk-bloqueio-defis">
                            Estabelecimento se enquadra em alguma situação de "informação opcional" (transferência entre filiais, venda ambulante, produção rural em múltiplos municípios etc.)
                        </label>
                        <div id="avisoBloqueioDefis" class="hidden text-xs text-red-600 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mt-2">
                            <i class="fa-solid fa-triangle-exclamation"></i> Esse cenário ainda não é suportado por aqui — lance essa DEFIS manualmente pelo e-CAC.
                        </div>
                    </div>

                    {{-- ─── Empresa ─────────────────────────────────────────────────── --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 pb-4 border-b border-gray-100 dark:border-slate-700">
                        <div class="md:col-span-3 text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Dados da empresa</div>

                        <div id="wrapperInatividade" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Inatividade (obrigatório p/ anos &lt; 2025)</label>
                            <select id="selectInatividade" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                                <option value="">Selecione...</option>
                                <option value="0">Atividades totalizam zero — respondeu NÃO à permanência sem atividade</option>
                                <option value="1">Atividades totalizam zero — respondeu SIM à permanência sem atividade</option>
                                <option value="2">Total de atividades maior que zero</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ganho de capital (R$)</label>
                            <input type="number" step="0.01" id="inputGanhoCapital" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ganho renda variável (R$)</label>
                            <input type="number" step="0.01" id="inputGanhoRendaVariavel" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Receita exportação direta (R$)</label>
                            <input type="number" step="0.01" id="inputReceitaExportacao" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Empregados no início do ano</label>
                            <input type="number" id="inputEmpregadoInicial" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Empregados no fim do ano</label>
                            <input type="number" id="inputEmpregadoFinal" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Lucro contábil (opcional, R$)</label>
                            <input type="number" step="0.01" id="inputLucroContabil" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Participação em cotas de tesouraria (opcional, R$)</label>
                            <input type="number" step="0.01" id="inputParticipacaoCotas" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>

                    {{-- ─── Estabelecimento (matriz) ───────────────────────────────────── --}}
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 pb-4 border-b border-gray-100 dark:border-slate-700">
                        <div class="md:col-span-3 text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Estabelecimento (matriz)</div>

                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Estoque inicial (R$)</label>
                            <input type="number" step="0.01" id="inputEstoqueInicial" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Estoque final (R$)</label>
                            <input type="number" step="0.01" id="inputEstoqueFinal" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Saldo de caixa inicial (R$)</label>
                            <input type="number" step="0.01" id="inputCaixaInicial" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Saldo de caixa final (R$)</label>
                            <input type="number" step="0.01" id="inputCaixaFinal" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Aquisições mercado interno (R$)</label>
                            <input type="number" step="0.01" id="inputAquisicoesInterno" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Importações (R$)</label>
                            <input type="number" step="0.01" id="inputImportacoes" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Total entradas por transferência (R$)</label>
                            <input type="number" step="0.01" id="inputEntradasTransferencia" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Total saídas por transferência (R$)</label>
                            <input type="number" step="0.01" id="inputSaidasTransferencia" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Total devoluções de vendas (R$)</label>
                            <input type="number" step="0.01" id="inputDevolucoesVendas" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Total entradas (R$)</label>
                            <input type="number" step="0.01" id="inputTotalEntradas" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Total devoluções de compras (R$)</label>
                            <input type="number" step="0.01" id="inputDevolucoesCompras" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Total despesas (R$)</label>
                            <input type="number" step="0.01" id="inputTotalDespesas" value="0" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">ISS retido na fonte (opcional, R$)</label>
                            <input type="number" step="0.01" id="inputIssRetido" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Prestações serviço comunicação (opcional, R$)</label>
                            <input type="number" step="0.01" id="inputPrestacoesComunicacao" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                        <div><label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Prestações serviço transporte (opcional, R$)</label>
                            <input type="number" step="0.01" id="inputPrestacoesTransporte" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></div>
                    </div>

                    {{-- ─── Sócios ──────────────────────────────────────────────────────── --}}
                    <div class="mb-4 pb-4 border-b border-gray-100 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Sócios</div>
                            <button type="button" id="btnAdicionarSocio" class="py-1 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-xs hover:bg-gray-50 dark:hover:bg-slate-600">
                                <i class="fa-solid fa-plus"></i> Adicionar sócio
                            </button>
                        </div>
                        <div id="listaSocios" class="space-y-3"></div>
                    </div>

                    <button type="button" id="btnSalvarDefisT" class="py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600 mr-2">
                        Salvar rascunho
                    </button>
                    <button type="button" id="btnTransmitirDefisT" class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                        <i class="fa-solid fa-triangle-exclamation"></i> Transmitir DEFIS (real)
                    </button>

                    <div id="defisTResultado" class="hidden mt-3 text-sm rounded-lg px-3 py-2"></div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════ ABA: Consultar declarações ═══════════════════════════ --}}
        <div id="tabPanel-declaracoes" class="defis-tab-panel hidden">
            <div id="cardDefis" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
                <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-magnifying-glass text-brand"></i> DEFIS — declarações do cliente
                </h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                    Consulta somente leitura das DEFIS (declaração anual do Simples Nacional) já transmitidas.
                </p>

                <div class="flex flex-wrap gap-3 items-end mb-4">
                    <div class="flex-1 min-w-60">
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                        <select id="selectClienteDefis"
                                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="btnBuscarDefis"
                            class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                        Buscar DEFIS
                    </button>
                </div>

                <div id="defisErro" class="hidden text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mb-3"></div>

                <div id="defisTabelaWrapper" class="hidden overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ano</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nº DEFIS</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transmitida em</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recibo</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Declaração</th>
                            </tr>
                        </thead>
                        <tbody id="defisTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-sm text-gray-700 dark:text-gray-300"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    // ─── Abas ───────────────────────────────────────────────────────────────────
    const defisTabButtons = document.querySelectorAll('.defis-tab-btn');
    const defisTabPanels  = document.querySelectorAll('.defis-tab-panel');

    function ativarAbaDefis(nome) {
        defisTabButtons.forEach(btn => {
            const ativo = btn.dataset.tab === nome;
            btn.classList.toggle('border-brand', ativo);
            btn.classList.toggle('text-brand', ativo);
            btn.classList.toggle('text-gray-500', !ativo);
            btn.classList.toggle('dark:text-slate-400', !ativo);
        });
        defisTabPanels.forEach(panel => {
            panel.classList.toggle('hidden', panel.id !== `tabPanel-${nome}`);
        });
    }

    defisTabButtons.forEach(btn => {
        btn.addEventListener('click', () => ativarAbaDefis(btn.dataset.tab));
    });

    ativarAbaDefis(new URLSearchParams(location.search).get('tab') ?? 'transmitir');

    // ═══════════════════════════ Transmitir DEFIS ═══════════════════════════
    const selectClienteDefisT = document.getElementById('selectClienteDefisT');
    const inputAnoDefisT      = document.getElementById('inputAnoDefisT');
    const btnCarregarDefisT   = document.getElementById('btnCarregarDefisT');
    const defisTStatusExistente = document.getElementById('defisTStatusExistente');
    const defisTFormWrapper   = document.getElementById('defisTFormWrapper');
    const wrapperInatividade  = document.getElementById('wrapperInatividade');
    const selectInatividade   = document.getElementById('selectInatividade');
    const listaSocios         = document.getElementById('listaSocios');
    const btnAdicionarSocio   = document.getElementById('btnAdicionarSocio');
    const btnSalvarDefisT     = document.getElementById('btnSalvarDefisT');
    const btnTransmitirDefisT = document.getElementById('btnTransmitirDefisT');
    const defisTResultado     = document.getElementById('defisTResultado');
    const avisoBloqueioDefis  = document.getElementById('avisoBloqueioDefis');

    const CAMPOS_EMPRESA = {
        inputGanhoCapital: 'ganho_capital',
        inputGanhoRendaVariavel: 'ganho_renda_variavel',
        inputReceitaExportacao: 'receita_exportacao_direta',
        inputEmpregadoInicial: 'qtd_empregado_inicial',
        inputEmpregadoFinal: 'qtd_empregado_final',
        inputLucroContabil: 'lucro_contabil',
        inputParticipacaoCotas: 'participacao_cotas_tesouraria',
        inputEstoqueInicial: 'estoque_inicial',
        inputEstoqueFinal: 'estoque_final',
        inputCaixaInicial: 'saldo_caixa_inicial',
        inputCaixaFinal: 'saldo_caixa_final',
        inputAquisicoesInterno: 'aquisicoes_mercado_interno',
        inputImportacoes: 'importacoes',
        inputEntradasTransferencia: 'total_entradas_por_transferencia',
        inputSaidasTransferencia: 'total_saidas_por_transferencia',
        inputDevolucoesVendas: 'total_devolucoes_vendas',
        inputTotalEntradas: 'total_entradas',
        inputDevolucoesCompras: 'total_devolucoes_compras',
        inputTotalDespesas: 'total_despesas',
        inputIssRetido: 'iss_retidos_fonte',
        inputPrestacoesComunicacao: 'prestacoes_servico_comunicacao',
        inputPrestacoesTransporte: 'prestacoes_servico_transporte',
    };

    function algumBloqueioMarcado() {
        return Array.from(document.querySelectorAll('.chk-bloqueio-defis')).some(chk => chk.checked);
    }

    function atualizarAvisoBloqueio() {
        const bloqueado = algumBloqueioMarcado();
        avisoBloqueioDefis.classList.toggle('hidden', !bloqueado);
        btnSalvarDefisT.disabled = bloqueado;
        btnTransmitirDefisT.disabled = bloqueado;
    }

    document.querySelectorAll('.chk-bloqueio-defis').forEach(chk => chk.addEventListener('change', atualizarAvisoBloqueio));

    function linhaSocio(socio) {
        socio = socio ?? {};
        const div = document.createElement('div');
        div.className = 'socio-row grid grid-cols-1 md:grid-cols-6 gap-2 items-end border border-gray-200 dark:border-slate-700 rounded-lg p-3';
        div.innerHTML = `
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CPF</label>
                <input type="text" maxlength="11" class="input-socio-cpf w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1.5 text-sm" value="${socio.cpf ?? ''}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Rendimentos isentos (R$)</label>
                <input type="number" step="0.01" class="input-socio-rendimentos-isentos w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1.5 text-sm" value="${socio.rendimentos_isentos ?? 0}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Rendimentos tributáveis (R$)</label>
                <input type="number" step="0.01" class="input-socio-rendimentos-tributaveis w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1.5 text-sm" value="${socio.rendimentos_tributaveis ?? 0}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">% capital social</label>
                <input type="number" step="0.01" class="input-socio-participacao w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1.5 text-sm" value="${socio.participacao_capital_social ?? 0}">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">IR retido na fonte (R$)</label>
                <input type="number" step="0.01" class="input-socio-ir w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1.5 text-sm" value="${socio.ir_retido_fonte ?? 0}">
            </div>
            <div>
                <button type="button" class="btn-remover-socio py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-red-600 rounded text-xs hover:bg-gray-50 dark:hover:bg-slate-600 w-full">
                    <i class="fa-solid fa-trash"></i> Remover
                </button>
            </div>
        `;
        div.querySelector('.btn-remover-socio').addEventListener('click', () => div.remove());
        return div;
    }

    btnAdicionarSocio.addEventListener('click', () => listaSocios.appendChild(linhaSocio()));

    function limparFormulario() {
        Object.keys(CAMPOS_EMPRESA).forEach(id => {
            const input = document.getElementById(id);
            input.value = input.hasAttribute('value') && input.getAttribute('value') !== '' ? input.value : '';
        });
        document.getElementById('inputGanhoCapital').value = 0;
        document.getElementById('inputGanhoRendaVariavel').value = 0;
        document.getElementById('inputReceitaExportacao').value = 0;
        document.getElementById('inputEmpregadoInicial').value = 0;
        document.getElementById('inputEmpregadoFinal').value = 0;
        document.getElementById('inputLucroContabil').value = '';
        document.getElementById('inputParticipacaoCotas').value = '';
        ['inputEstoqueInicial','inputEstoqueFinal','inputCaixaInicial','inputCaixaFinal','inputAquisicoesInterno','inputImportacoes','inputEntradasTransferencia','inputSaidasTransferencia','inputDevolucoesVendas','inputTotalEntradas','inputDevolucoesCompras','inputTotalDespesas'].forEach(id => document.getElementById(id).value = 0);
        ['inputIssRetido','inputPrestacoesComunicacao','inputPrestacoesTransporte'].forEach(id => document.getElementById(id).value = '');
        selectInatividade.value = '';
        document.querySelectorAll('.chk-bloqueio-defis').forEach(chk => chk.checked = false);
        listaSocios.innerHTML = '';
        atualizarAvisoBloqueio();
    }

    function preencherFormulario(declaracao, socios) {
        Object.entries(CAMPOS_EMPRESA).forEach(([id, campo]) => {
            document.getElementById(id).value = declaracao?.[campo] ?? (id.includes('Lucro') || id.includes('Cotas') || id.includes('Iss') || id.includes('Prestacoes') ? '' : 0);
        });
        selectInatividade.value = declaracao?.inatividade ?? '';
        listaSocios.innerHTML = '';
        (socios ?? []).forEach(s => listaSocios.appendChild(linhaSocio(s)));
    }

    btnCarregarDefisT.addEventListener('click', async function () {
        const clienteId = selectClienteDefisT.value;
        const ano = inputAnoDefisT.value;

        if (!clienteId || !/^\d{4}$/.test(ano)) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente e informe o ano-calendário.' });
            return;
        }

        wrapperInatividade.classList.toggle('hidden', Number(ano) >= 2025);

        this.disabled = true;
        defisTResultado.classList.add('hidden');
        defisTStatusExistente.classList.add('hidden');

        try {
            const url = new URL('{{ route('simples-nacional.defis.dados.get') }}');
            url.searchParams.set('cliente_id', clienteId);
            url.searchParams.set('ano_calendario', ano);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao carregar dados.' });
                return;
            }

            limparFormulario();
            wrapperInatividade.classList.toggle('hidden', Number(ano) >= 2025);

            if (data.declaracao) {
                preencherFormulario(data.declaracao, data.socios);

                if (data.declaracao.status === 'transmitida') {
                    defisTStatusExistente.classList.remove('hidden');
                    defisTStatusExistente.textContent = `Esta DEFIS já foi transmitida (idDefis: ${data.declaracao.id_defis ?? '—'}). Os dados abaixo são só consulta do rascunho salvo.`;
                }
            }

            defisTFormWrapper.classList.remove('hidden');
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
        }
    });

    function coletarPayload() {
        const socios = Array.from(listaSocios.querySelectorAll('.socio-row')).map(row => ({
            cpf: row.querySelector('.input-socio-cpf').value.replace(/\D/g, ''),
            rendimentos_isentos: row.querySelector('.input-socio-rendimentos-isentos').value || 0,
            rendimentos_tributaveis: row.querySelector('.input-socio-rendimentos-tributaveis').value || 0,
            participacao_capital_social: row.querySelector('.input-socio-participacao').value || 0,
            ir_retido_fonte: row.querySelector('.input-socio-ir').value || 0,
        }));

        const payload = {
            cliente_id: selectClienteDefisT.value,
            ano_calendario: inputAnoDefisT.value,
            socios,
        };

        Object.entries(CAMPOS_EMPRESA).forEach(([id, campo]) => {
            const valor = document.getElementById(id).value;
            payload[campo] = valor === '' ? null : valor;
        });

        if (!wrapperInatividade.classList.contains('hidden')) {
            payload.inatividade = selectInatividade.value === '' ? null : selectInatividade.value;
        }

        return payload;
    }

    btnSalvarDefisT.addEventListener('click', async function () {
        if (algumBloqueioMarcado()) return;

        if (listaSocios.children.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Adicione ao menos um sócio.' });
            return;
        }

        this.disabled = true;

        try {
            const resp = await fetch('{{ route('simples-nacional.defis.dados.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(coletarPayload()),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', html: data.error ? escapeHtml(data.error) : (data.errors ? Object.values(data.errors).flat().map(escapeHtml).join('<br>') : 'Falha ao salvar.') });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
        }
    });

    btnTransmitirDefisT.addEventListener('click', async function () {
        if (algumBloqueioMarcado()) return;

        const nomeCliente = selectClienteDefisT.options[selectClienteDefisT.selectedIndex]?.text ?? '';
        const ano = inputAnoDefisT.value;

        const confirmacao1 = await Swal.fire({
            title: 'Confirmar dados?',
            html: `Confira com atenção todos os valores lançados para <strong>${escapeHtml(nomeCliente)}</strong> — ano <strong>${escapeHtml(ano)}</strong> antes de continuar.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Estão corretos, continuar',
            cancelButtonText: 'Cancelar',
        });

        if (!confirmacao1.isConfirmed) return;

        const confirmacao2 = await Swal.fire({
            title: 'Transmitir DEFIS?',
            html: `Isso vai transmitir a declaração REAL perante a Receita Federal. <strong>Não pode ser desfeito.</strong> Tem certeza?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, transmitir',
            cancelButtonText: 'Cancelar',
        });

        if (!confirmacao2.isConfirmed) return;

        this.disabled = true;
        this.textContent = 'Transmitindo...';
        defisTResultado.classList.add('hidden');

        try {
            const resp = await fetch('{{ route('simples-nacional.defis.transmitir') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ cliente_id: selectClienteDefisT.value, ano_calendario: ano }),
            });
            const data = await resp.json();

            defisTResultado.classList.remove('hidden');
            defisTResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                defisTResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                defisTResultado.textContent = data.error ?? 'Falha ao transmitir.';
                return;
            }

            defisTResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');

            const links = (data.arquivos ?? []).map(a => `<a href="${escapeHtml(a.url)}" target="_blank" class="underline">${escapeHtml(a.nomeArquivo)}</a>`).join(' | ');
            defisTResultado.innerHTML = escapeHtml(data.message) + (links ? '<br>PDFs: ' + links : '');
        } catch (e) {
            defisTResultado.classList.remove('hidden');
            defisTResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            defisTResultado.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Transmitir DEFIS (real)';
        }
    });

    protegerComConfigSerpro('cardDefisTransmitir');

    // ═══════════════════════════ Consultar declarações ═══════════════════════════
    const selectClienteDefis = document.getElementById('selectClienteDefis');
    const btnBuscarDefis     = document.getElementById('btnBuscarDefis');
    const defisErro          = document.getElementById('defisErro');
    const defisTabelaWrapper = document.getElementById('defisTabelaWrapper');
    const defisTabelaBody    = document.getElementById('defisTabelaBody');

    function tipoDefisLabel(tipo) {
        const mapa = { 1: 'Original', 2: 'Retificadora' };
        return mapa[tipo] ?? `Tipo ${tipo ?? '—'}`;
    }

    btnBuscarDefis.addEventListener('click', async function () {
        const clienteId = selectClienteDefis.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente.' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Buscando...';
        defisErro.classList.add('hidden');
        defisTabelaWrapper.classList.add('hidden');

        try {
            const url = new URL('{{ route('simples-nacional.defis.declaracoes') }}');
            url.searchParams.set('cliente_id', clienteId);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                defisErro.textContent = data.error ?? 'Falha ao buscar DEFIS.';
                defisErro.classList.remove('hidden');
                return;
            }

            defisTabelaBody.innerHTML = '';

            if (!data.declaracoes || data.declaracoes.length === 0) {
                defisTabelaBody.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Nenhuma DEFIS encontrada para este cliente.</td></tr>';
            }

            (data.declaracoes ?? []).forEach(decl => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(decl.anoCalendario ?? '—')}</td>
                    <td class="px-4 py-2 whitespace-nowrap font-mono text-xs">${escapeHtml(decl.idDefis ?? '—')}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(tipoDefisLabel(decl.tipo))}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(formatarDataHora(decl.dataHora))}</td>
                    <td class="px-4 py-2 whitespace-nowrap recibo-defis-cell"></td>
                    <td class="px-4 py-2 whitespace-nowrap declaracao-defis-cell"></td>
                `;

                const reciboCell = tr.querySelector('.recibo-defis-cell');
                const declaracaoCell = tr.querySelector('.declaracao-defis-cell');
                reciboCell.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-gray-400"></i>';

                fetch('{{ route('simples-nacional.defis.recibo') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cliente_id: clienteId, id_defis: decl.idDefis }),
                })
                    .then(r => r.json().then(d => ({ ok: r.ok, d })))
                    .then(({ ok, d }) => {
                        if (!ok || d.error) {
                            reciboCell.innerHTML = `<span class="text-xs text-red-600" title="${escapeHtml(d.error ?? 'Falha ao buscar.')}"><i class="fa-solid fa-triangle-exclamation"></i></span>`;
                            declaracaoCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                            return;
                        }

                        const arquivos = d.arquivos ?? [];
                        const recibo = arquivos.find(a => a.nomeArquivo.toLowerCase().includes('recibo'));
                        const declaracao = arquivos.find(a => a.nomeArquivo.toLowerCase().includes('declaracao'));

                        reciboCell.innerHTML = recibo
                            ? `<a href="${escapeHtml(recibo.url)}" target="_blank" class="text-brand hover:text-brand/70"><i class="fa-solid fa-file-pdf"></i></a>`
                            : '<span class="text-xs text-gray-400">—</span>';

                        declaracaoCell.innerHTML = declaracao
                            ? `<a href="${escapeHtml(declaracao.url)}" target="_blank" class="text-brand hover:text-brand/70"><i class="fa-solid fa-file-pdf"></i></a>`
                            : '<span class="text-xs text-gray-400">—</span>';
                    })
                    .catch(() => {
                        reciboCell.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-red-600" title="Erro de comunicação"></i>';
                        declaracaoCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                    });

                defisTabelaBody.appendChild(tr);
            });

            defisTabelaWrapper.classList.remove('hidden');
        } catch (e) {
            defisErro.textContent = 'Erro de comunicação com o servidor.';
            defisErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'Buscar DEFIS';
        }
    });

    protegerComConfigSerpro('cardDefis');
    </script>
    @endpush
@endsection
