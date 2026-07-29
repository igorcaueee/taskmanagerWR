@extends('layouts.internal')

@section('title', 'MIT — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-invoice"></i> MIT</h1>
            <p class="text-gray-700 dark:text-gray-300">Consultar apurações já encerradas, encerrar sem movimento ou encerrar com movimento (débitos por tributo) — tudo em um só lugar.</p>
        </div>

        {{-- ─── Abas ─────────────────────────────────────────────────────────────── --}}
        <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-slate-700 mb-6">
            <button type="button" class="mit-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-slate-400 hover:text-brand bg-transparent" data-tab="consultar">
                <i class="fa-solid fa-magnifying-glass"></i> Consultar apurações
            </button>
            <button type="button" class="mit-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-slate-400 hover:text-brand bg-transparent" data-tab="sem-movimento">
                <i class="fa-solid fa-file-circle-check"></i> Encerrar sem movimento
            </button>
            <button type="button" class="mit-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 dark:text-slate-400 hover:text-brand bg-transparent" data-tab="com-movimento">
                <i class="fa-solid fa-file-invoice-dollar"></i> Encerrar com movimento
            </button>
        </div>

        <div id="tabPanel-consultar" class="mit-tab-panel">
        <div id="cardMit" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-invoice text-brand"></i> Apurações MIT do cliente
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                Consulta somente leitura das apurações já encerradas (pelo e-CAC ou por outro sistema).
            </p>

            <div class="flex flex-wrap gap-3 items-end mb-4">
                <div class="flex-1 min-w-60">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                    <select id="selectClienteMit"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ano</label>
                    <input type="text" id="inputAnoMit" value="{{ now()->year }}" maxlength="4"
                           class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm w-24">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Mês</label>
                    <select id="selectMesMit" class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Todos</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Situação</label>
                    <select id="selectSituacaoMit" class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Todas</option>
                        <option value="1">Em edição (com pendências)</option>
                        <option value="2">Em edição</option>
                        <option value="3">Encerrada</option>
                        <option value="4">Encerramento em curso</option>
                    </select>
                </div>
                <button type="button" id="btnBuscarMit"
                        class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    Buscar apurações
                </button>
            </div>

            <div id="mitErro" class="hidden text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mb-3"></div>

            <div id="mitTabelaWrapper" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Período</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Situação</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Encerramento</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Evento especial</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor total</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody id="mitTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-sm text-gray-700 dark:text-gray-300"></tbody>
                </table>
            </div>
        </div>
        </div>

        <div id="tabPanel-sem-movimento" class="mit-tab-panel hidden">
        <div id="cardEncerrarMitSemMovimento" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-circle-check text-brand"></i> Encerrar apuração sem movimento
            </h2>
            <div class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Encerra a apuração MIT desse período como <strong>sem movimento</strong> perante a Receita Federal — declaração fiscal <strong>real</strong>, não é possível desfazer.
            </div>

            <div id="painelMitSemMovimento">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                        <select id="selectClienteEncerrarMit"
                                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Mês</label>
                        <select id="selectMesEncerrarMit" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected($m == now()->subMonthNoOverflow()->month)>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ano</label>
                        <input type="text" id="inputAnoEncerrarMit" value="{{ now()->year }}" maxlength="4"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CPF do responsável</label>
                        <input type="text" id="inputCpfResponsavelMit" maxlength="11" placeholder="Só números"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Qualificação da pessoa jurídica</label>
                        <select id="selectQualificacaoPj" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="1" selected>PJ em geral</option>
                            <option value="2">Agência de Fomento, Banco ou outra PJ de que trata o § 1º do art. 22 da Lei nº 8.212/1991</option>
                            <option value="3">Cooperativa de Crédito</option>
                            <option value="4">Sociedade Corretora de Seguros</option>
                            <option value="5">Sociedade Seguradora e de Capitalização ou Entidade Aberta de Previdência Complementar com fins lucrativos</option>
                            <option value="6">Entidade Fechada de Previdência Complementar ou Entidade Aberta de Previdência Complementar sem fins lucrativos</option>
                            <option value="7">Sociedade Cooperativa</option>
                            <option value="8">Sociedade Cooperativa de Produção Agropecuária ou de Consumo</option>
                            <option value="9">Autarquia ou Fundação Pública</option>
                            <option value="10">Empresa Pública, Sociedade de Economia Mista ou PJ de que trata o inc. III do art. 34 da Lei nº 10.833/2003</option>
                            <option value="11">Estado, Distrito Federal, Município ou Órgão Público da Administração Direta</option>
                            <option value="12">Mais de uma qualificação durante o mês</option>
                        </select>
                    </div>
                </div>

                <button type="button" id="btnEncerrarMitSemMovimento"
                        class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    <i class="fa-solid fa-triangle-exclamation"></i> Encerrar sem movimento (real)
                </button>

                <div id="encerrarMitResultado" class="hidden mt-3 text-sm rounded-lg px-3 py-2"></div>
            </div>
        </div>
        </div>

        <div id="tabPanel-com-movimento" class="mit-tab-panel hidden">
        <div id="cardEncerrarMitComMovimento" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-invoice-dollar text-brand"></i> Encerrar apuração com movimento
            </h2>
            <div class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Encerra a apuração MIT desse período <strong>com os débitos abaixo</strong> perante a Receita Federal — declaração fiscal <strong>real</strong>, não é possível desfazer.
            </div>

            <div id="painelMitComMovimento">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                        <select id="selectClienteComMit"
                                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach($clientes as $cli)
                                <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Mês</label>
                        <select id="selectMesComMit" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected($m == now()->subMonthNoOverflow()->month)>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ano</label>
                        <input type="text" id="inputAnoComMit" value="{{ now()->year }}" maxlength="4"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CPF do responsável</label>
                        <input type="text" id="inputCpfResponsavelComMit" maxlength="11" placeholder="Só números"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="flex justify-end mb-4">
                    <button type="button" id="btnCarregarRascunhoComMit"
                            class="py-1.5 px-3 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-200 text-xs font-medium rounded-lg transition-colors border-0">
                        <i class="fa-solid fa-rotate"></i> Carregar rascunho salvo
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Qualificação da pessoa jurídica</label>
                        <select id="selectQualificacaoPjComMit" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="1" selected>PJ em geral</option>
                            <option value="2">Agência de Fomento, Banco ou outra PJ de que trata o § 1º do art. 22 da Lei nº 8.212/1991</option>
                            <option value="3">Cooperativa de Crédito</option>
                            <option value="4">Sociedade Corretora de Seguros</option>
                            <option value="5">Sociedade Seguradora e de Capitalização ou Entidade Aberta de Previdência Complementar com fins lucrativos</option>
                            <option value="6">Entidade Fechada de Previdência Complementar ou Entidade Aberta de Previdência Complementar sem fins lucrativos</option>
                            <option value="7">Sociedade Cooperativa</option>
                            <option value="8">Sociedade Cooperativa de Produção Agropecuária ou de Consumo</option>
                            <option value="9">Autarquia ou Fundação Pública</option>
                            <option value="10">Empresa Pública, Sociedade de Economia Mista ou PJ de que trata o inc. III do art. 34 da Lei nº 10.833/2003</option>
                            <option value="11">Estado, Distrito Federal, Município ou Órgão Público da Administração Direta</option>
                            <option value="12">Mais de uma qualificação durante o mês</option>
                        </select>
                    </div>
                    <div id="campoTributacaoLucro">
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Forma de tributação do lucro</label>
                        <select id="selectTributacaoLucro" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="1" selected>Real Anual</option>
                            <option value="2">Real Trimestral</option>
                            <option value="3">Presumido</option>
                            <option value="4">Arbitrado</option>
                            <option value="5">Imune do IRPJ</option>
                            <option value="6">Isenta do IRPJ</option>
                            <option value="7">Optante pelo Simples Nacional</option>
                        </select>
                    </div>
                    <div id="campoRegimePisCofins">
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Regime de apuração (PIS/PASEP – COFINS)</label>
                        <select id="selectRegimePisCofins" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="1">Cumulativa</option>
                            <option value="2" selected>Não-cumulativa</option>
                            <option value="3">Cumulativa e Não-cumulativa</option>
                            <option value="4">Não se aplica</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Critério de reconhecimento de variações monetárias</label>
                        <select id="selectVariacoesMonetarias" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="1" selected>Regime de Caixa</option>
                            <option value="2">Regime de Competência</option>
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Padrão do sistema da RFB. Competência só pode ser eleito em janeiro/início de atividade.</p>
                    </div>
                </div>

                <div id="campoBalancoLucroReal" class="hidden grid grid-cols-1 md:grid-cols-2 gap-3 mb-3 bg-gray-50 dark:bg-slate-900 rounded-lg p-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                        <input type="checkbox" id="chkBalancoIrpj" class="rounded border-gray-300 dark:border-slate-600">
                        Balanço de suspensão/redução — IRPJ
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                        <input type="checkbox" id="chkBalancoCsll" class="rounded border-gray-300 dark:border-slate-600">
                        Balanço de suspensão/redução — CSLL
                    </label>
                </div>

                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-3 mb-4">
                    <p class="text-xs font-semibold text-gray-600 dark:text-slate-400 mb-2">Cenários não suportados por aqui (marque se algum se aplica — bloqueia o envio, lance manualmente pelo e-CAC)</p>
                    <div class="space-y-1">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                            <input type="checkbox" class="chkBloqueioComMit rounded border-gray-300 dark:border-slate-600">
                            Apuração com evento especial (fusão, cisão, incorporação, encerramento de atividade)
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                            <input type="checkbox" class="chkBloqueioComMit rounded border-gray-300 dark:border-slate-600">
                            Débito amparado por suspensão/redução da exigibilidade do crédito tributário
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                            <input type="checkbox" class="chkBloqueioComMit rounded border-gray-300 dark:border-slate-600">
                            Débito postergado de período de apuração anterior
                        </label>
                    </div>
                    <div id="avisoBloqueioComMit" class="hidden text-xs text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mt-2">
                        Esse cenário não é suportado aqui — faça o lançamento diretamente pelo e-CAC.
                    </div>
                </div>

                <div class="border border-gray-200 dark:border-slate-700 rounded-lg p-3 mb-4">
                    <p class="text-xs font-semibold text-gray-600 dark:text-slate-400 mb-2">Adicionar débito</p>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-2">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Código de receita</label>
                            <select id="selectCodigoDebitoMit" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm"></select>
                        </div>
                        <div id="campoMesDebitoMit">
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Mês</label>
                            <select id="selectMesDebitoMit" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div id="campoTrimestreDebitoMit" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Trimestre</label>
                            <select id="selectTrimestreDebitoMit" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                                <option value="1">1º (jan-mar)</option>
                                <option value="2">2º (abr-jun)</option>
                                <option value="3">3º (jul-set)</option>
                                <option value="4">4º (out-dez)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ano de referência</label>
                            <input type="text" id="inputAnoDebitoMit" maxlength="4" value="{{ now()->year }}"
                                   class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Valor</label>
                            <input type="number" step="0.01" min="0" id="inputValorDebitoMit"
                                   class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div id="campoCnpjEstabelecimentoMit" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CNPJ do estabelecimento</label>
                            <input type="text" id="inputCnpjEstabelecimentoMit" maxlength="14" placeholder="Só números"
                                   class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div id="campoCnpjIncorporacaoMit" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CNPJ da incorporação</label>
                            <input type="text" id="inputCnpjIncorporacaoMit" maxlength="14" placeholder="Só números"
                                   class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div id="campoCnpjScpMit" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CNPJ da SCP</label>
                            <input type="text" id="inputCnpjScpMit" maxlength="14" placeholder="Só números"
                                   class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                        <div id="campoMunicipioOuroMit" class="hidden">
                            <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Código do município</label>
                            <input type="text" id="inputMunicipioOuroMit" placeholder="IBGE"
                                   class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        </div>
                    </div>
                    <button type="button" id="btnAdicionarDebitoMit"
                            class="py-1.5 px-3 bg-brand hover:bg-brand/80 text-white text-xs font-semibold rounded-lg transition-colors border-0">
                        <i class="fa-solid fa-plus"></i> Adicionar débito
                    </button>
                </div>

                <div class="overflow-x-auto mb-4">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tributo</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Código</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Período</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Valor</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody id="tabelaDebitosMitBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-gray-700 dark:text-gray-300">
                            <tr id="linhaVaziaDebitosMit"><td colspan="5" class="px-3 py-4 text-center text-gray-400">Nenhum débito adicionado ainda.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex gap-2">
                    <button type="button" id="btnSalvarRascunhoComMit"
                            class="py-2 px-4 bg-gray-600 hover:bg-gray-700 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                        <i class="fa-solid fa-floppy-disk"></i> Salvar rascunho
                    </button>
                    <button type="button" id="btnEncerrarComMovimento"
                            class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                        <i class="fa-solid fa-triangle-exclamation"></i> Encerrar com movimento (real)
                    </button>
                </div>

                <div id="encerrarComMitResultado" class="hidden mt-3 text-sm rounded-lg px-3 py-2"></div>
            </div>
        </div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    const selectClienteMit   = document.getElementById('selectClienteMit');
    const inputAnoMit        = document.getElementById('inputAnoMit');
    const selectMesMit       = document.getElementById('selectMesMit');
    const selectSituacaoMit  = document.getElementById('selectSituacaoMit');
    const btnBuscarMit       = document.getElementById('btnBuscarMit');
    const mitErro            = document.getElementById('mitErro');
    const mitTabelaWrapper   = document.getElementById('mitTabelaWrapper');
    const mitTabelaBody      = document.getElementById('mitTabelaBody');

    const SITUACOES_MIT = {
        1: { texto: 'Em edição (pendências)', cor: 'bg-yellow-100 text-yellow-800' },
        2: { texto: 'Em edição', cor: 'bg-gray-100 text-gray-700' },
        3: { texto: 'Encerrada', cor: 'bg-green-100 text-green-800' },
        4: { texto: 'Encerramento em curso', cor: 'bg-blue-100 text-blue-800' },
    };

    const GRUPOS_TRIBUTO_MIT = ['Irpj', 'Csll', 'Irrf', 'Ipi', 'Iof', 'PisPasep', 'Cofins', 'ContribuicoesDiversas', 'Cpss', 'RetPagamentoUnificado'];

    function situacaoMitBadge(situacao) {
        const info = SITUACOES_MIT[situacao] ?? { texto: `Situação ${situacao ?? '—'}`, cor: 'bg-gray-100 text-gray-700' };
        return `<span class="px-2 py-1 rounded text-xs font-medium ${info.cor}">${escapeHtml(info.texto)}</span>`;
    }

    btnBuscarMit.addEventListener('click', async function () {
        const clienteId = selectClienteMit.value;
        const ano = inputAnoMit.value;

        if (!clienteId || !ano) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente e informe o ano.' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Buscando...';
        mitErro.classList.add('hidden');
        mitTabelaWrapper.classList.add('hidden');

        try {
            const url = new URL('{{ route('simples-nacional.mit.apuracoes') }}');
            url.searchParams.set('cliente_id', clienteId);
            url.searchParams.set('ano_apuracao', ano);
            if (selectMesMit.value) url.searchParams.set('mes_apuracao', selectMesMit.value);
            if (selectSituacaoMit.value) url.searchParams.set('situacao_apuracao', selectSituacaoMit.value);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                mitErro.textContent = data.error ?? 'Falha ao buscar apurações.';
                mitErro.classList.remove('hidden');
                return;
            }

            mitTabelaBody.innerHTML = '';

            const apuracoesOrdenadas = [...(data.apuracoes ?? [])].sort((a, b) => String(a.periodoApuracao ?? '').localeCompare(String(b.periodoApuracao ?? '')));

            if (apuracoesOrdenadas.length === 0) {
                mitTabelaBody.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Nenhuma apuração encontrada.</td></tr>';
            }

            apuracoesOrdenadas.forEach(ap => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(formatarPeriodo(ap.periodoApuracao))}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${situacaoMitBadge(ap.situacao)}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(formatarData8(ap.dataEncerramento))}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${ap.eventoEspecial ? 'Sim' : 'Não'}</td>
                    <td class="px-4 py-2 whitespace-nowrap">R$ ${formatarMoeda(ap.valorTotalApurado)}</td>
                    <td class="px-4 py-2 whitespace-nowrap detalhe-mit-cell"></td>
                `;

                const detalheCell = tr.querySelector('.detalhe-mit-cell');
                const btnDetalhe = document.createElement('button');
                btnDetalhe.type = 'button';
                btnDetalhe.className = 'text-brand bg-transparent border-0 text-sm p-1 hover:text-brand/70';
                btnDetalhe.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i>';
                btnDetalhe.addEventListener('click', async () => {
                    btnDetalhe.disabled = true;
                    const iconeOriginal = btnDetalhe.innerHTML;
                    btnDetalhe.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                    try {
                        const respDet = await fetch('{{ route('simples-nacional.mit.apuracao') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ cliente_id: clienteId, id_apuracao: ap.idApuracao }),
                        });
                        const dataDet = await respDet.json();

                        if (!respDet.ok || dataDet.error) {
                            Swal.fire({ icon: 'error', title: 'Erro', text: dataDet.error ?? 'Falha ao buscar detalhes.' });
                            return;
                        }

                        const raiz = dataDet.apuracao ?? {};
                        const item = (raiz.dadosApuracaoMit ?? raiz.DadosApuracaoMit ?? [])[0] ?? {};
                        const debitos = item.Debitos ?? item.debitos ?? {};
                        const suspensoes = item.ListaSuspensoes ?? item.listaSuspensoes ?? [];

                        let linhasDebitos = '';
                        GRUPOS_TRIBUTO_MIT.forEach(grupo => {
                            const g = debitos[grupo];
                            const lista = g?.ListaDebitos ?? g?.listaDebitos ?? [];
                            if (!lista || lista.length === 0) return;

                            const total = lista.reduce((soma, d) => soma + (Number(d.ValorDebito ?? d.valorDebito ?? 0)), 0);
                            linhasDebitos += `
                                <tr>
                                    <td class="pr-3 text-gray-500">${escapeHtml(grupo)}</td>
                                    <td class="text-right">R$ ${formatarMoeda(total)}</td>
                                </tr>
                            `;
                        });

                        Swal.fire({
                            icon: 'info',
                            title: `Apuração ${escapeHtml(formatarPeriodo(item.PeriodoApuracao ? `${item.PeriodoApuracao.AnoApuracao}${String(item.PeriodoApuracao.MesApuracao).padStart(2, '0')}` : ap.periodoApuracao))}`,
                            html: `
                                <table class="text-xs text-left w-full mb-3">
                                    <tr><td class="pr-3 text-gray-500">Situação</td><td>${escapeHtml(raiz.textoSituacao ?? '—')}</td></tr>
                                    <tr><td class="pr-3 text-gray-500">Suspensões</td><td>${suspensoes.length}</td></tr>
                                </table>
                                ${linhasDebitos ? `
                                    <div class="text-xs text-gray-500 mb-1 font-semibold">Débitos por tributo</div>
                                    <table class="text-xs text-left w-full">${linhasDebitos}</table>
                                ` : '<p class="text-xs text-gray-400">Nenhum débito detalhado retornado.</p>'}
                            `,
                            width: 500,
                        });
                    } catch (e) {
                        Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
                    } finally {
                        btnDetalhe.disabled = false;
                        btnDetalhe.innerHTML = iconeOriginal;
                    }
                });
                detalheCell.appendChild(btnDetalhe);

                mitTabelaBody.appendChild(tr);
            });

            mitTabelaWrapper.classList.remove('hidden');
        } catch (e) {
            mitErro.textContent = 'Erro de comunicação com o servidor.';
            mitErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'Buscar apurações';
        }
    });

    // ─── Encerrar apuração sem movimento ───────────────────────────────────────
    const selectClienteEncerrarMit = document.getElementById('selectClienteEncerrarMit');
    const selectMesEncerrarMit     = document.getElementById('selectMesEncerrarMit');
    const inputAnoEncerrarMit      = document.getElementById('inputAnoEncerrarMit');
    const inputCpfResponsavelMit   = document.getElementById('inputCpfResponsavelMit');
    const selectQualificacaoPj     = document.getElementById('selectQualificacaoPj');
    const btnEncerrarMitSemMovimento = document.getElementById('btnEncerrarMitSemMovimento');
    const encerrarMitResultado     = document.getElementById('encerrarMitResultado');

    btnEncerrarMitSemMovimento.addEventListener('click', async function () {
        const clienteId = selectClienteEncerrarMit.value;
        const mes = selectMesEncerrarMit.value;
        const ano = inputAnoEncerrarMit.value;
        const cpf = inputCpfResponsavelMit.value.replace(/\D/g, '');
        const nomeCliente = selectClienteEncerrarMit.options[selectClienteEncerrarMit.selectedIndex]?.text ?? '';

        if (!clienteId || !ano || !cpf || cpf.length !== 11) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente, informe o ano e o CPF do responsável (11 dígitos).' });
            return;
        }

        const confirmacao = await Swal.fire({
            title: 'Encerrar apuração sem movimento?',
            html: `Isso vai encerrar a apuração MIT de <strong>${escapeHtml(nomeCliente)}</strong> — período <strong>${String(mes).padStart(2, '0')}/${escapeHtml(ano)}</strong> — como <strong>sem movimento</strong>, perante a Receita Federal. Não pode ser desfeito. Tem certeza?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, encerrar',
            cancelButtonText: 'Cancelar',
        });

        if (!confirmacao.isConfirmed) return;

        this.disabled = true;
        this.textContent = 'Encerrando...';
        encerrarMitResultado.classList.add('hidden');

        try {
            const resp = await fetch('{{ route('simples-nacional.mit.encerrar-sem-movimento') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente_id: clienteId,
                    ano_apuracao: ano,
                    mes_apuracao: mes,
                    qualificacao_pj: selectQualificacaoPj.value,
                    cpf_responsavel: cpf,
                }),
            });
            const data = await resp.json();

            encerrarMitResultado.classList.remove('hidden');
            encerrarMitResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                encerrarMitResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                encerrarMitResultado.textContent = data.error ?? 'Falha ao encerrar a apuração.';
                return;
            }

            encerrarMitResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
            encerrarMitResultado.textContent = data.message;
        } catch (e) {
            encerrarMitResultado.classList.remove('hidden');
            encerrarMitResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            encerrarMitResultado.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Encerrar sem movimento (real)';
        }
    });

    // ─── Abas ───────────────────────────────────────────────────────────────────
    const mitTabButtons = document.querySelectorAll('.mit-tab-btn');
    const mitTabPanels  = document.querySelectorAll('.mit-tab-panel');

    function ativarAbaMit(nome) {
        mitTabButtons.forEach(btn => {
            const ativo = btn.dataset.tab === nome;
            btn.classList.toggle('border-brand', ativo);
            btn.classList.toggle('text-brand', ativo);
            btn.classList.toggle('text-gray-500', !ativo);
            btn.classList.toggle('dark:text-slate-400', !ativo);
        });
        mitTabPanels.forEach(panel => {
            panel.classList.toggle('hidden', panel.id !== `tabPanel-${nome}`);
        });
    }

    mitTabButtons.forEach(btn => {
        btn.addEventListener('click', () => ativarAbaMit(btn.dataset.tab));
    });

    ativarAbaMit(new URLSearchParams(location.search).get('tab') ?? 'consultar');

    // ─── Encerrar apuração com movimento ────────────────────────────────────────
    const CATALOGO_RECEITA_MIT = @json($catalogoReceitaMit ?? []);
    const NOMES_GRUPOS_MIT = {
        Irpj: 'IRPJ', Csll: 'CSLL', Irrf: 'IRRF', Ipi: 'IPI', Iof: 'IOF',
        PisPasep: 'PIS/PASEP', Cofins: 'COFINS', ContribuicoesDiversas: 'Contribuições Diversas',
        Cpss: 'CPSS', RetPagamentoUnificado: 'RET/Pagamento Unificado',
    };

    function exigeEstabelecimentoMit(item) {
        return ['Ipi', 'ContribuicoesDiversas'].includes(item.grupo) && item.codigo !== '9197-01';
    }
    function exigeIncorporacaoMit(item) {
        return item.grupo === 'RetPagamentoUnificado' && item.codigo !== '6177-01';
    }
    function exigeScpMit(item) {
        return /-\s*SCP$/i.test((item.descricao ?? '').trim());
    }
    function exigeMunicipioOuroMit(item) {
        return item.codigo === '4028-02';
    }

    const selectCodigoDebitoMit = document.getElementById('selectCodigoDebitoMit');
    const gruposOrdenados = {};
    CATALOGO_RECEITA_MIT.forEach(item => {
        (gruposOrdenados[item.grupo] ??= []).push(item);
    });
    Object.keys(gruposOrdenados).sort().forEach(grupo => {
        const optgroup = document.createElement('optgroup');
        optgroup.label = NOMES_GRUPOS_MIT[grupo] ?? grupo;
        gruposOrdenados[grupo].forEach(item => {
            const option = document.createElement('option');
            option.value = item.codigo;
            option.dataset.grupo = item.grupo;
            option.dataset.periodicidade = item.periodicidade;
            option.dataset.descricao = item.descricao;
            option.textContent = `${item.codigo} — ${item.descricao}`;
            optgroup.appendChild(option);
        });
        selectCodigoDebitoMit.appendChild(optgroup);
    });

    const campoMesDebitoMit           = document.getElementById('campoMesDebitoMit');
    const campoTrimestreDebitoMit     = document.getElementById('campoTrimestreDebitoMit');
    const campoCnpjEstabelecimentoMit = document.getElementById('campoCnpjEstabelecimentoMit');
    const campoCnpjIncorporacaoMit    = document.getElementById('campoCnpjIncorporacaoMit');
    const campoCnpjScpMit             = document.getElementById('campoCnpjScpMit');
    const campoMunicipioOuroMit       = document.getElementById('campoMunicipioOuroMit');

    function itemSelecionadoMit() {
        const option = selectCodigoDebitoMit.selectedOptions[0];
        if (!option) return null;
        return { codigo: option.value, grupo: option.dataset.grupo, periodicidade: option.dataset.periodicidade, descricao: option.dataset.descricao };
    }

    function atualizarCamposDebitoMit() {
        const item = itemSelecionadoMit();
        if (!item) return;

        campoMesDebitoMit.classList.toggle('hidden', item.periodicidade !== 'ME');
        campoTrimestreDebitoMit.classList.toggle('hidden', item.periodicidade !== 'TR');
        campoCnpjEstabelecimentoMit.classList.toggle('hidden', !exigeEstabelecimentoMit(item));
        campoCnpjIncorporacaoMit.classList.toggle('hidden', !exigeIncorporacaoMit(item));
        campoCnpjScpMit.classList.toggle('hidden', !exigeScpMit(item));
        campoMunicipioOuroMit.classList.toggle('hidden', !exigeMunicipioOuroMit(item));
    }

    selectCodigoDebitoMit.addEventListener('change', atualizarCamposDebitoMit);
    atualizarCamposDebitoMit();

    const selectTributacaoLucro  = document.getElementById('selectTributacaoLucro');
    const campoBalancoLucroReal  = document.getElementById('campoBalancoLucroReal');

    selectTributacaoLucro.addEventListener('change', () => {
        campoBalancoLucroReal.classList.toggle('hidden', selectTributacaoLucro.value !== '1');
    });

    const chksBloqueioComMit  = document.querySelectorAll('.chkBloqueioComMit');
    const avisoBloqueioComMit = document.getElementById('avisoBloqueioComMit');
    const btnSalvarRascunhoComMit  = document.getElementById('btnSalvarRascunhoComMit');
    const btnEncerrarComMovimento  = document.getElementById('btnEncerrarComMovimento');

    function atualizarBloqueioComMit() {
        const bloqueado = Array.from(chksBloqueioComMit).some(c => c.checked);
        avisoBloqueioComMit.classList.toggle('hidden', !bloqueado);
        btnSalvarRascunhoComMit.disabled = bloqueado;
        btnEncerrarComMovimento.disabled = bloqueado;
    }
    chksBloqueioComMit.forEach(c => c.addEventListener('change', atualizarBloqueioComMit));

    let debitosMitAdicionados = [];
    const tabelaDebitosMitBody = document.getElementById('tabelaDebitosMitBody');

    function periodoTextoDebitoMit(d) {
        if (d.periodicidade === 'ME') return `${String(d.mes_referencia).padStart(2, '0')}/${d.ano_referencia}`;
        if (d.periodicidade === 'TR') return `${d.trimestre_referencia}º tri/${d.ano_referencia}`;
        return `${d.ano_referencia}`;
    }

    function renderizarDebitosMit() {
        tabelaDebitosMitBody.innerHTML = '';

        if (debitosMitAdicionados.length === 0) {
            tabelaDebitosMitBody.innerHTML = '<tr id="linhaVaziaDebitosMit"><td colspan="5" class="px-3 py-4 text-center text-gray-400">Nenhum débito adicionado ainda.</td></tr>';
            return;
        }

        debitosMitAdicionados.forEach((d, indice) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-3 py-2">${escapeHtml(NOMES_GRUPOS_MIT[d.grupo] ?? d.grupo)}</td>
                <td class="px-3 py-2">${escapeHtml(d.codigo_receita)}</td>
                <td class="px-3 py-2">${escapeHtml(periodoTextoDebitoMit(d))}</td>
                <td class="px-3 py-2 text-right">R$ ${formatarMoeda(d.valor)}</td>
                <td class="px-3 py-2 text-right"></td>
            `;
            const btnRemover = document.createElement('button');
            btnRemover.type = 'button';
            btnRemover.className = 'text-red-600 bg-transparent border-0 text-sm p-1 hover:text-red-800';
            btnRemover.innerHTML = '<i class="fa-solid fa-trash"></i>';
            btnRemover.addEventListener('click', () => {
                debitosMitAdicionados.splice(indice, 1);
                renderizarDebitosMit();
            });
            tr.querySelector('td:last-child').appendChild(btnRemover);
            tabelaDebitosMitBody.appendChild(tr);
        });
    }

    document.getElementById('btnAdicionarDebitoMit').addEventListener('click', () => {
        const item = itemSelecionadoMit();
        const valor = parseFloat(document.getElementById('inputValorDebitoMit').value);

        if (!item || !valor || valor <= 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o código de receita e informe um valor válido.' });
            return;
        }

        const debito = {
            grupo: item.grupo,
            codigo_receita: item.codigo,
            periodicidade: item.periodicidade,
            ano_referencia: document.getElementById('inputAnoDebitoMit').value,
            mes_referencia: item.periodicidade === 'ME' ? document.getElementById('selectMesDebitoMit').value : null,
            trimestre_referencia: item.periodicidade === 'TR' ? document.getElementById('selectTrimestreDebitoMit').value : null,
            valor: valor,
            cnpj_estabelecimento: exigeEstabelecimentoMit(item) ? document.getElementById('inputCnpjEstabelecimentoMit').value.replace(/\D/g, '') : null,
            cnpj_incorporacao: exigeIncorporacaoMit(item) ? document.getElementById('inputCnpjIncorporacaoMit').value.replace(/\D/g, '') : null,
            cnpj_scp: exigeScpMit(item) ? document.getElementById('inputCnpjScpMit').value.replace(/\D/g, '') : null,
            codigo_municipio_ouro: exigeMunicipioOuroMit(item) ? document.getElementById('inputMunicipioOuroMit').value : null,
        };

        debitosMitAdicionados.push(debito);
        renderizarDebitosMit();
        document.getElementById('inputValorDebitoMit').value = '';
    });

    function montarCabecalhoComMit() {
        return {
            cliente_id: document.getElementById('selectClienteComMit').value,
            ano_apuracao: document.getElementById('inputAnoComMit').value,
            mes_apuracao: document.getElementById('selectMesComMit').value,
            qualificacao_pj: document.getElementById('selectQualificacaoPjComMit').value,
            tributacao_lucro: document.getElementById('selectQualificacaoPjComMit').value !== '11' ? selectTributacaoLucro.value : null,
            variacoes_monetarias: document.getElementById('selectVariacoesMonetarias').value,
            regime_pis_cofins: document.getElementById('selectQualificacaoPjComMit').value !== '11' ? document.getElementById('selectRegimePisCofins').value : null,
            cpf_responsavel: document.getElementById('inputCpfResponsavelComMit').value.replace(/\D/g, ''),
            balanco_irpj: document.getElementById('chkBalancoIrpj').checked,
            balanco_csll: document.getElementById('chkBalancoCsll').checked,
        };
    }

    function preencherFormularioComMit(rascunho, debitos) {
        document.getElementById('selectQualificacaoPjComMit').value = rascunho.qualificacao_pj;
        if (rascunho.tributacao_lucro) selectTributacaoLucro.value = rascunho.tributacao_lucro;
        document.getElementById('selectVariacoesMonetarias').value = rascunho.variacoes_monetarias;
        if (rascunho.regime_pis_cofins) document.getElementById('selectRegimePisCofins').value = rascunho.regime_pis_cofins;
        document.getElementById('inputCpfResponsavelComMit').value = rascunho.cpf_responsavel ?? '';
        document.getElementById('chkBalancoIrpj').checked = !!rascunho.balanco_irpj;
        document.getElementById('chkBalancoCsll').checked = !!rascunho.balanco_csll;
        campoBalancoLucroReal.classList.toggle('hidden', String(rascunho.tributacao_lucro) !== '1');

        debitosMitAdicionados = (debitos ?? []).map(d => ({
            grupo: d.grupo,
            codigo_receita: d.codigo_receita,
            periodicidade: d.periodicidade,
            ano_referencia: d.ano_referencia,
            mes_referencia: d.mes_referencia,
            trimestre_referencia: d.trimestre_referencia,
            valor: d.valor,
            cnpj_estabelecimento: d.cnpj_estabelecimento,
            cnpj_incorporacao: d.cnpj_incorporacao,
            cnpj_scp: d.cnpj_scp,
            codigo_municipio_ouro: d.codigo_municipio_ouro,
        }));
        renderizarDebitosMit();
    }

    document.getElementById('btnCarregarRascunhoComMit').addEventListener('click', async function () {
        const clienteId = document.getElementById('selectClienteComMit').value;
        const ano = document.getElementById('inputAnoComMit').value;
        const mes = document.getElementById('selectMesComMit').value;

        if (!clienteId || !ano) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente e informe o ano.' });
            return;
        }

        try {
            const url = new URL('{{ route('simples-nacional.mit.apuracao-rascunho.get') }}');
            url.searchParams.set('cliente_id', clienteId);
            url.searchParams.set('ano_apuracao', ano);
            url.searchParams.set('mes_apuracao', mes);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (!data.rascunho) {
                Swal.fire({ icon: 'info', title: 'Nada encontrado', text: 'Não há rascunho salvo para esse cliente/período.' });
                return;
            }

            preencherFormularioComMit(data.rascunho, data.debitos);
            Swal.fire({ icon: 'success', title: 'Rascunho carregado', timer: 1200, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        }
    });

    document.getElementById('btnSalvarRascunhoComMit').addEventListener('click', async function () {
        const cabecalho = montarCabecalhoComMit();

        if (!cabecalho.cliente_id || !cabecalho.ano_apuracao || !cabecalho.cpf_responsavel || cabecalho.cpf_responsavel.length !== 11) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente, informe o ano e o CPF do responsável (11 dígitos).' });
            return;
        }

        if (debitosMitAdicionados.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Adicione ao menos 1 débito.' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Salvando...';

        try {
            const resp = await fetch('{{ route('simples-nacional.mit.apuracao-rascunho.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...cabecalho, debitos: debitosMitAdicionados }),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar o rascunho.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Rascunho salvo', text: data.message, timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salvar rascunho';
        }
    });

    const encerrarComMitResultado = document.getElementById('encerrarComMitResultado');

    document.getElementById('btnEncerrarComMovimento').addEventListener('click', async function () {
        const clienteId = document.getElementById('selectClienteComMit').value;
        const ano = document.getElementById('inputAnoComMit').value;
        const mes = document.getElementById('selectMesComMit').value;
        const nomeCliente = document.getElementById('selectClienteComMit').options[document.getElementById('selectClienteComMit').selectedIndex]?.text ?? '';

        if (!clienteId || !ano) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente e informe o ano.' });
            return;
        }

        const confirmacao = await Swal.fire({
            title: 'Encerrar apuração com movimento?',
            html: `Isso vai encerrar a apuração MIT de <strong>${escapeHtml(nomeCliente)}</strong> — período <strong>${String(mes).padStart(2, '0')}/${escapeHtml(ano)}</strong> — com os débitos do <strong>rascunho já salvo</strong>, perante a Receita Federal. Não pode ser desfeito. Tem certeza?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, encerrar',
            cancelButtonText: 'Cancelar',
        });

        if (!confirmacao.isConfirmed) return;

        this.disabled = true;
        this.textContent = 'Encerrando...';
        encerrarComMitResultado.classList.add('hidden');

        try {
            const resp = await fetch('{{ route('simples-nacional.mit.encerrar-com-movimento') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ cliente_id: clienteId, ano_apuracao: ano, mes_apuracao: mes }),
            });
            const data = await resp.json();

            encerrarComMitResultado.classList.remove('hidden');
            encerrarComMitResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                encerrarComMitResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                encerrarComMitResultado.textContent = data.error ?? 'Falha ao encerrar a apuração.';
                return;
            }

            encerrarComMitResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
            encerrarComMitResultado.textContent = data.message;
        } catch (e) {
            encerrarComMitResultado.classList.remove('hidden');
            encerrarComMitResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            encerrarComMitResultado.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Encerrar com movimento (real)';
        }
    });

    protegerComConfigSerpro('cardMit');
    protegerComConfigSerpro('cardEncerrarMitSemMovimento');
    protegerComConfigSerpro('cardEncerrarMitComMovimento');
    </script>
    @endpush
@endsection
