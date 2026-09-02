@extends('layouts.internal')

@section('title', 'NF-e / NFC-e / CT-e — Distribuição DFe')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    {{-- Cabeçalho --}}
    <div class="mb-6">
        <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2 mt-1">
            <i class="fa-solid fa-truck-ramp-box text-[#0084aa]"></i>
            NF-e / NFC-e / CT-e — Distribuição DFe
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Consulte e baixe XMLs de NF-e, NFC-e e CT-e usando o certificado da própria contabilidade (padrão) — ou, se preferir, o certificado individual de cada cliente.</p>
    </div>

    @if(auth()->user()?->canConfigurarCertificadoContabilidade())
    {{-- ─── Webservice de contabilistas (SEFAZ-RS) — NF-e, NFC-e e CT-e ──────── --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice text-[#0084aa]"></i> Certificado da Contabilidade (SEFAZ-RS)
                </h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
                    Certificado digital da própria contabilidade (webservice de contabilistas da SEFAZ-RS), usado por padrão para trazer NF-e, NFC-e e CT-e de qualquer cliente que tenha autorizado o acesso via e-CAC — sem precisar do certificado individual de cada empresa. Quem busca escolhe o modo (contabilidade ou certificado do cliente) direto no card de período de busca.
                </p>
            </div>
            <a href="{{ route('nfe.rs.sincronizacao.tela') }}"
               class="inline-flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 bg-white dark:bg-slate-800 no-underline whitespace-nowrap">
                <i class="fa-solid fa-list-check"></i> Acompanhar sincronização automática
            </a>
        </div>

        <div id="certContabilidadeStatus" class="mt-3 space-y-2">
            <div id="certRsOk" class="hidden items-center gap-2 text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2 flex-wrap">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Certificado da contabilidade configurado — vence <strong id="certRsVencimento"></strong></span>
                <button type="button" class="btn-atualizar-cert-rs ml-auto underline text-[#0084aa] bg-transparent border-0">Atualizar certificado</button>
            </div>
            <div id="certRsAlert" class="hidden items-center gap-2 text-sm text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 flex-wrap">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Certificado da contabilidade vence em breve: <strong id="certRsVencimentoAlert"></strong></span>
                <button type="button" class="btn-atualizar-cert-rs ml-auto underline text-[#0084aa] bg-transparent border-0">Atualizar certificado</button>
            </div>
            <div id="certRsExpired" class="hidden items-center gap-2 text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 flex-wrap">
                <i class="fa-solid fa-circle-xmark"></i>
                <span>Certificado da contabilidade <strong>vencido</strong>! Atualize-o antes de consultar.</span>
                <button type="button" class="btn-atualizar-cert-rs ml-auto underline text-[#0084aa] bg-transparent border-0">Atualizar certificado</button>
            </div>
            <div id="certRsNone" class="hidden items-center gap-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-slate-700 rounded-lg px-3 py-2 flex-wrap">
                <i class="fa-solid fa-key"></i>
                <span>Nenhum certificado da contabilidade configurado.</span>
                <button type="button" id="btnAbrirUploadRs" class="ml-auto underline text-[#0084aa] bg-transparent border-0">Cadastrar agora</button>
            </div>
        </div>

        {{-- Form de upload do certificado da contabilidade --}}
        <div id="formUploadRs" class="hidden mt-4 pt-4 border-t border-gray-100 dark:border-slate-700 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Certificado (.pfx)</label>
                <input type="file" id="inputCertRs" accept=".pfx,.p12"
                       class="w-full text-sm text-gray-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-[#0084aa] file:text-white file:text-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Senha</label>
                <input type="password" id="inputSenhaRs"
                       class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ambiente</label>
                <select id="selectAmbienteRs"
                        class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    <option value="producao">Produção</option>
                    <option value="homologacao">Homologação</option>
                </select>
            </div>
            <div class="md:col-span-4">
                <button type="button" id="btnSalvarCertRs"
                        class="py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors">
                    Salvar certificado
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Linha de topo: cards de configuração ──────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

            {{-- Seleção de empresa --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="font-semibold text-gray-800 dark:text-slate-200 mb-3 text-sm uppercase tracking-wide">
                    <i class="fa-regular fa-building mr-1 text-[#0084aa]"></i> Empresa
                </h2>
                <select id="selectCliente"
                        class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                    <option value="">Selecione a empresa...</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" data-cnpj="{{ $cliente->cpfcnpj }}">
                            {{ $cliente->nome }}
                        </option>
                    @endforeach
                </select>
                <p id="clienteCnpj" class="hidden mt-2 text-xs text-gray-500 dark:text-gray-400">
                    CNPJ: <span id="clienteCnpjValor" class="font-medium text-gray-700 dark:text-slate-300"></span>
                </p>

                {{-- Status do certificado --}}
                <div id="certStatus" class="hidden mt-3">
                    <div id="certOk" class="hidden items-center gap-2 text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Certificado configurado — vence <strong id="certVencimento"></strong></span>
                    </div>
                    <div id="certAlert" class="hidden items-center gap-2 text-sm text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Certificado vence em breve: <strong id="certVencimentoAlert"></strong></span>
                    </div>
                    <div id="certExpired" class="hidden items-center gap-2 text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Certificado <strong>vencido</strong>! Atualize-o antes de consultar.</span>
                    </div>
                    <div id="certNone" class="hidden items-center gap-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-slate-700 rounded-lg px-3 py-2">
                        <i class="fa-solid fa-key"></i>
                        <span>Nenhum certificado configurado para esta empresa. <a href="{{ route('nfse.index') }}" class="underline text-[#0084aa]">Configure na tela de NFS-e</a>.</span>
                    </div>
                </div>
            </div>

            {{-- Filtro de período --}}
            <div id="cardFiltro" class="hidden bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="font-semibold text-gray-800 dark:text-slate-200 mb-3 text-sm uppercase tracking-wide">
                    <i class="fa-solid fa-calendar-range mr-1 text-[#0084aa]"></i> Período de Busca
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Data inicial</label>
                        <input type="date" id="dataInicio"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Data final</label>
                        <input type="date" id="dataFim"
                               class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                    </div>
                </div>

                <div class="flex flex-wrap gap-1.5 mt-3">
                    <button type="button" data-periodo="mes-atual" class="btn-periodo text-xs px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] transition-colors">Mês atual</button>
                    <button type="button" data-periodo="mes-anterior" class="btn-periodo text-xs px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] transition-colors">Mês anterior</button>
                    <button type="button" data-periodo="trimestre" class="btn-periodo text-xs px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] transition-colors">Trimestre</button>
                    <button type="button" data-periodo="ano" class="btn-periodo text-xs px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] transition-colors">Ano atual</button>
                </div>

                <label class="flex items-center gap-2 text-xs text-gray-600 dark:text-slate-400 cursor-pointer mt-3">
                    <input type="checkbox" id="checkModoRs" class="rounded text-[#0084aa]" checked>
                    Buscar com o certificado da contabilidade (padrão) — desmarque para usar o certificado do cliente
                </label>

                <button type="button" id="btnBuscar"
                        class="w-full mt-3 py-2.5 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span id="btnBuscarLabel">Buscar NF-e / NFC-e / CT-e</span>
                </button>

                {{-- Uma nota específica às vezes fica fora da sincronização sequencial por
                     NSU mesmo dentro do período (falha reconhecida pela própria Sefaz), ou é
                     antiga demais pra valer a pena resincronizar o histórico inteiro de um
                     cliente (ex.: migrando de outro provedor) — esse campo busca um único
                     documento direto pela chave de acesso, sem depender da faixa de NSU. --}}
                <div id="areaBuscarCtePorChave" class="hidden mt-3 pt-3 border-t border-gray-100 dark:border-slate-700">
                    <button type="button" id="btnToggleBuscarChave"
                            class="bg-transparent border-0 appearance-none p-0 text-xs text-gray-500 dark:text-slate-400 hover:text-[#0084aa] flex items-center gap-1 cursor-pointer">
                        <i class="fa-solid fa-chevron-right text-[10px]" id="iconToggleBuscarChave"></i>
                        Nota não apareceu na busca? Buscar por chave específica
                    </button>
                    <div id="formBuscarChave" class="hidden mt-2">
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 mb-1">
                            Uma chave por linha (44 dígitos cada). Com mais de uma linha, só CT-e via contabilidade (SEFAZ-RS) é suportado.
                        </p>
                        <div class="flex gap-2">
                            <textarea id="inputChaveCte" rows="2" placeholder="Chave de acesso (44 dígitos) — uma por linha"
                                   class="flex-1 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-[#0084aa] font-mono"></textarea>
                            <button type="button" id="btnBuscarChave"
                                    class="px-3 py-2 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 text-xs font-semibold rounded-lg transition-colors whitespace-nowrap self-start">
                                Buscar e salvar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

    </div>

    {{-- ─── Painel de resultados ────────────────────────────── --}}
    <div>

            {{-- Abas: Documentos (busca/listagem de XMLs) x Dashboards (relatórios sobre os XMLs já sincronizados) --}}
            <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-slate-700 mb-6">
                <button type="button" data-aba="documentos"
                        class="btn-aba-nfe appearance-none bg-transparent cursor-pointer px-4 py-2 text-sm font-medium border-0 border-b-2 -mb-px border-brand text-brand">
                    <i class="fa-solid fa-file-invoice"></i> Documentos
                </button>
                <button type="button" data-aba="dashboards"
                        class="btn-aba-nfe appearance-none bg-transparent cursor-pointer px-4 py-2 text-sm font-medium border-0 border-b-2 -mb-px border-transparent text-gray-500 dark:text-slate-400 hover:text-brand">
                    <i class="fa-solid fa-chart-simple"></i> Dashboards
                </button>
            </div>

        <div id="abaDocumentos">

            {{-- Estado inicial --}}
            <div id="estadoInicial" class="h-64 flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <i class="fa-solid fa-truck-ramp-box text-5xl mb-3 opacity-30"></i>
                <p class="text-sm">Selecione uma empresa e o período para buscar as NF-e/NFC-e/CT-e.</p>
            </div>

            {{-- Loading --}}
            <div id="estadoLoading" class="hidden h-64 flex flex-col items-center justify-center text-[#0084aa] bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <svg class="animate-spin h-10 w-10 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <p class="text-sm text-gray-500 dark:text-slate-400" id="loadingTitulo">Consultando o Ambiente Nacional (NFeDistribuicaoDFe)...</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1" id="loadingTempo">Isso pode levar alguns segundos.</p>
            </div>

            {{-- Erro --}}
            <div id="estadoErro" class="hidden bg-white dark:bg-slate-800 rounded-xl border border-red-200 dark:border-red-900 p-6">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-red-500 text-xl mt-0.5"></i>
                    <div>
                        <h3 class="font-semibold text-red-700 dark:text-red-400 mb-1">Erro ao consultar API</h3>
                        <p id="erroMsg" class="text-sm text-red-600 dark:text-red-300"></p>
                        <p class="text-xs text-gray-500 dark:text-slate-500 mt-2">Verifique se o certificado é válido e se o ambiente está correto.</p>
                    </div>
                </div>
            </div>

            {{-- Vazio --}}
            <div id="estadoVazio" class="hidden h-40 flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <i class="fa-solid fa-inbox text-4xl mb-2 opacity-40"></i>
                <p class="text-sm">Nenhuma NF-e/NFC-e/CT-e encontrada no período selecionado.</p>
            </div>

            {{-- Resultados --}}
            <div id="estadoResultados" class="hidden bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">

                {{-- Toolbar --}}
                <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-sm font-semibold text-gray-800 dark:text-slate-200">
                            <span id="totalDocs">0</span> documento(s) encontrado(s)
                        </span>
                        <label class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-slate-400 cursor-pointer">
                            <input type="checkbox" id="checkTodos" class="rounded text-[#0084aa]">
                            Selecionar todas
                        </label>
                        {{-- Soma só dos marcados via checkbox — útil pra conferir manualmente um
                             subconjunto de notas (ex.: comparando com um relatório externo) sem
                             precisar isolar elas num filtro. --}}
                        <span id="totalSelecionadosWrap" class="hidden items-center gap-1 text-xs text-gray-500 dark:text-slate-400 bg-gray-50 dark:bg-slate-700/40 rounded-lg px-2.5 py-1.5">
                            <span id="totalSelecionados">0</span> selecionado(s):
                            <span id="somaSelecionados" class="font-bold text-[#0084aa]"></span>
                        </span>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <input type="text" id="filtroBusca" placeholder="Buscar número, emitente ou chave..."
                               class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-[#0084aa] w-48">
                        <input type="date" id="filtroDataInicio" title="Data emissão — de"
                               class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2 py-1.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                        <input type="date" id="filtroDataFim" title="Data emissão — até"
                               class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2 py-1.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                        <select id="filtroDirecao"
                                class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                            <option value="">Entradas e saídas</option>
                            <option value="saida">Somente saídas</option>
                            <option value="entrada">Somente entradas</option>
                        </select>
                        <select id="filtroTipo"
                                class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                            <option value="">Todos os tipos</option>
                            <option value="ambos">NF-e + NFC-e (sem CT-e)</option>
                            <option value="nfe">NF-e</option>
                            <option value="nfce">NFC-e</option>
                            <option value="cte">CT-e</option>
                        </select>
                        <select id="filtroSituacao"
                                class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                            <option value="">Normais e canceladas</option>
                            <option value="normal">Somente normais</option>
                            <option value="cancelada">Somente canceladas</option>
                        </select>
                        <button type="button" id="btnDownloadZip"
                                class="hidden items-center gap-1.5 px-3 py-1.5 bg-[#0084aa] hover:bg-[#006e8e] text-white text-xs font-semibold rounded-lg transition-colors">
                            <i class="fa-solid fa-file-zipper"></i>
                            Baixar selecionados (.zip)
                        </button>
                        <button type="button" id="btnDownloadZipPdf"
                                class="hidden items-center gap-1.5 px-3 py-1.5 bg-[#0084aa] hover:bg-[#006e8e] text-white text-xs font-semibold rounded-lg transition-colors">
                            <i class="fa-solid fa-file-pdf"></i>
                            Baixar PDFs selecionados (.zip)
                        </button>
                        <button type="button" id="btnExportarRelatorio"
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors">
                            <i class="fa-solid fa-file-excel text-green-600"></i>
                            <span id="btnExportarRelatorioLabel">Exportar relatório (NF-e + NFC-e)</span>
                        </button>
                        @if(auth()->user()?->canConfigurarCertificadoContabilidade())
                        <button type="button" id="btnReconsultarNsu"
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            <span id="btnReconsultarNsuLabel">Reconsultar NSU</span>
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Tabela --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr id="rowTotalValor" class="hidden border-b border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/40">
                                <td colspan="6" class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">Total dos documentos visíveis</td>
                                <td class="px-4 py-2 text-right text-sm font-bold text-[#0084aa]" id="totalValor"></td>
                                <td colspan="2"></td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-slate-700 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">
                                <th class="px-4 py-3 text-left w-8"></th>
                                <th class="px-4 py-3 text-left">Tipo</th>
                                <th class="px-4 py-3 text-left">Número</th>
                                <th class="px-4 py-3 text-left">Data Emissão</th>
                                <th class="px-4 py-3 text-left">Data Saída/Ent.</th>
                                <th class="px-4 py-3 text-left">Emitente</th>
                                <th class="px-4 py-3 text-right">Valor</th>
                                <th class="px-4 py-3 text-left">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaDocs" class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        </tbody>
                    </table>
                </div>

                {{-- Paginação --}}
                <div class="px-5 py-3 border-t border-gray-100 dark:border-slate-700 flex items-center justify-between flex-wrap gap-2">
                    <span class="text-xs text-gray-500 dark:text-slate-400" id="paginaInfo"></span>
                    <div class="flex items-center gap-1.5">
                        <button type="button" id="btnPaginaAnterior"
                                class="px-2.5 py-1 text-xs rounded-lg border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] disabled:opacity-40 disabled:hover:border-gray-300 disabled:hover:text-gray-600 transition-colors">
                            <i class="fa-solid fa-chevron-left"></i> Anterior
                        </button>
                        <button type="button" id="btnPaginaProxima"
                                class="px-2.5 py-1 text-xs rounded-lg border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] disabled:opacity-40 disabled:hover:border-gray-300 disabled:hover:text-gray-600 transition-colors">
                            Próxima <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
        {{-- /#abaDocumentos --}}

        <div id="abaDashboards" class="hidden space-y-4">

            <div id="dashAviso" class="h-40 flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 px-6 text-center">
                <i class="fa-solid fa-chart-simple text-4xl mb-2 opacity-30"></i>
                <p class="text-sm">Busque as notas na aba <strong>Documentos</strong> — os dashboards são gerados automaticamente para o mesmo período.</p>
            </div>

            <div id="dashCards" class="hidden space-y-4">

            {{-- Top Fornecedores (Simples Nacional) --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200">
                            <i class="fa-solid fa-ranking-star text-brand mr-1.5"></i> Top 10 Fornecedores (Simples Nacional)
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            Fornecedores enquadrados no Simples Nacional (CRT 1 ou 2 no XML das NF-e de entrada), pelos 10 maiores valores comprados no período.
                        </p>
                    </div>
                    <div id="dashFornSimplesResumo" class="hidden text-right shrink-0 text-xs leading-tight">
                        <p class="font-semibold text-gray-700 dark:text-slate-200" id="dashFornSimplesMesLabel"></p>
                        <p class="text-gray-500 dark:text-slate-400 mt-1">Total <span id="dashFornSimplesTotal" class="font-bold text-[#0084aa]"></span></p>
                    </div>
                </div>

                <div id="dashFornSimplesLoading" class="hidden h-40 flex flex-col items-center justify-center text-[#0084aa]">
                    <svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Montando o ranking...</p>
                </div>

                <div id="dashFornSimplesVazio" class="hidden h-40 flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 px-6 text-center">
                    <i class="fa-solid fa-inbox text-3xl mb-2 opacity-40"></i>
                    <p class="text-sm">Nenhum fornecedor do Simples Nacional no período.</p>
                    <p class="text-xs mt-1">Se as notas são antigas, rode a sincronização ou o comando <code>fiscal:backfill-emitente-crt</code>.</p>
                </div>

                <div id="dashFornSimplesResultado" class="hidden">
                    <ul id="dashFornSimplesLista" class="m-0 p-0 divide-y divide-gray-100 dark:divide-slate-700/50" style="list-style:none;margin:0;padding:0"></ul>
                </div>
            </div>

            {{-- Top Produtos vendidos --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200">
                            <i class="fa-solid fa-box-open text-brand mr-1.5"></i> Top 10 Produtos (mais vendidos)
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            Os 10 produtos com maior valor vendido no período, somados dos itens das NF-e de saída. Agrupa por código do produto (ou descrição), com NCM, CEST, unidade e CFOPs.
                        </p>
                    </div>
                    <div id="dashProdVendidosResumo" class="hidden text-right shrink-0 text-xs leading-tight">
                        <p class="font-semibold text-gray-700 dark:text-slate-200" id="dashProdVendidosMesLabel"></p>
                        <p class="text-gray-500 dark:text-slate-400 mt-1" id="dashProdVendidosNotas"></p>
                        <p class="text-gray-500 dark:text-slate-400 mt-1">Total <span id="dashProdVendidosTotal" class="font-bold text-[#0084aa]"></span></p>
                    </div>
                </div>

                <div id="dashProdVendidosLoading" class="hidden h-40 flex flex-col items-center justify-center text-[#0084aa]">
                    <svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Lendo os itens das notas...</p>
                </div>

                <div id="dashProdVendidosVazio" class="hidden h-40 flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 px-6 text-center">
                    <i class="fa-solid fa-inbox text-3xl mb-2 opacity-40"></i>
                    <p class="text-sm">Nenhuma NF-e de saída com itens no período.</p>
                </div>

                <div id="dashProdVendidosResultado" class="hidden">
                    <ul id="dashProdVendidosLista" class="m-0 p-0 divide-y divide-gray-100 dark:divide-slate-700/50" style="list-style:none;margin:0;padding:0"></ul>
                </div>
            </div>

            {{-- Compras e Vendas Interestaduais (mapa) --}}
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-start justify-between flex-wrap gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-slate-200">
                            <i class="fa-solid fa-map-location-dot text-brand mr-1.5"></i> Compras e Vendas Interestaduais
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            Total das operações com outras UFs no período — entradas de fornecedores de fora e saídas para destinatários de fora do estado do cliente.
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-2 shrink-0">
                        <div class="inline-flex rounded-lg border border-gray-200 dark:border-slate-600 overflow-hidden text-xs">
                            <button type="button" data-metrica="total" class="btn-interest-metrica px-3 py-1.5 font-semibold bg-brand text-white">Total</button>
                            <button type="button" data-metrica="compras" class="btn-interest-metrica px-3 py-1.5 font-semibold bg-white dark:bg-slate-700 text-gray-500 dark:text-slate-300 border-l border-gray-200 dark:border-slate-600">Compras</button>
                            <button type="button" data-metrica="vendas" class="btn-interest-metrica px-3 py-1.5 font-semibold bg-white dark:bg-slate-700 text-gray-500 dark:text-slate-300 border-l border-gray-200 dark:border-slate-600">Vendas</button>
                        </div>
                        <div id="dashInterestResumo" class="hidden text-right text-xs leading-tight">
                            <p class="font-semibold text-gray-700 dark:text-slate-200"><span id="dashInterestMesLabel"></span> &middot; UF <span id="dashInterestUf"></span></p>
                            <p class="text-gray-500 dark:text-slate-400 mt-1">
                                Compras <span id="dashInterestTotalCompras" class="font-bold text-[#0084aa]"></span>
                                &nbsp;·&nbsp; Vendas <span id="dashInterestTotalVendas" class="font-bold text-[#0084aa]"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div id="dashInterestLoading" class="hidden h-40 flex flex-col items-center justify-center text-[#0084aa]">
                    <svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Analisando as notas...</p>
                </div>

                <div id="dashInterestVazio" class="hidden h-40 flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 px-6 text-center">
                    <i class="fa-solid fa-inbox text-3xl mb-2 opacity-40"></i>
                    <p class="text-sm">Nenhuma operação interestadual no período.</p>
                </div>

                <div id="dashInterestResultado" class="hidden">
                    <div class="p-5 grid gap-5 md:grid-cols-[1fr_280px] items-start">
                        <div class="w-full max-w-[240px] mx-auto">
                            @include('nfe._mapa-brasil')
                        </div>
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide mb-2">Total por UF</p>
                                <ul id="dashInterestLista" class="m-0 p-0 space-y-1.5 text-sm" style="list-style:none;margin:0;padding:0"></ul>
                            </div>
                            <div class="pt-3 border-t border-gray-100 dark:border-slate-700">
                                <p class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">Total geral consolidado</p>
                                <p id="dashInterestTotalGeral" class="text-lg font-bold text-gray-900 dark:text-white mt-1 tabular-nums"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            </div>
        </div>
        {{-- /#abaDashboards --}}

    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const selectCliente = document.getElementById('selectCliente');
    const clienteCnpjEl    = document.getElementById('clienteCnpj');
    const clienteCnpjValor = document.getElementById('clienteCnpjValor');
    const cardFiltro     = document.getElementById('cardFiltro');
    const certStatus     = document.getElementById('certStatus');

    function formatarCnpjCpf(valor) {
        const digitos = (valor || '').replace(/\D/g, '');
        if (digitos.length === 14) {
            return digitos.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }
        if (digitos.length === 11) {
            return digitos.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        return valor || '';
    }

    const certOk      = document.getElementById('certOk');
    const certAlert   = document.getElementById('certAlert');
    const certExpired = document.getElementById('certExpired');
    const certNone    = document.getElementById('certNone');

    const dataInicio = document.getElementById('dataInicio');
    const dataFim    = document.getElementById('dataFim');
    const btnBuscar  = document.getElementById('btnBuscar');

    // ─── Buscar CT-e por chave específica (SEFAZ-RS) ───────────────────────────
    const areaBuscarCtePorChave = document.getElementById('areaBuscarCtePorChave');
    const btnToggleBuscarChave  = document.getElementById('btnToggleBuscarChave');
    const iconToggleBuscarChave = document.getElementById('iconToggleBuscarChave');
    const formBuscarChave       = document.getElementById('formBuscarChave');
    const inputChaveCte         = document.getElementById('inputChaveCte');
    const btnBuscarChave        = document.getElementById('btnBuscarChave');

    btnToggleBuscarChave.addEventListener('click', function () {
        formBuscarChave.classList.toggle('hidden');
        iconToggleBuscarChave.classList.toggle('fa-chevron-right');
        iconToggleBuscarChave.classList.toggle('fa-chevron-down');
    });

    btnBuscarChave.addEventListener('click', async function () {
        const clienteId = selectCliente.value;
        const chaves = inputChaveCte.value
            .split('\n')
            .map(l => soDigitos(l))
            .filter(l => l.length > 0);

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione uma empresa primeiro.' });
            return;
        }
        if (chaves.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe ao menos uma chave de acesso.' });
            return;
        }
        const chaveInvalida = chaves.find(c => c.length !== 44);
        if (chaveInvalida) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: `Chave inválida (precisa ter 44 dígitos): ${chaveInvalida}` });
            return;
        }

        this.disabled = true;
        const labelOrig = this.textContent;

        try {
            if (chaves.length > 1) {
                // Busca em lote: só suportada pra CT-e via contabilidade (SEFAZ-RS) hoje.
                if (!checkModoRs.checked) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Busca em lote só está disponível pra CT-e com o certificado da contabilidade (marque a opção acima).' });
                    return;
                }
                const modeloDiferente = chaves.find(c => c.substring(20, 22) !== '57');
                if (modeloDiferente) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: `Busca em lote só suporta CT-e. Chave fora do padrão: ${modeloDiferente}` });
                    return;
                }

                this.textContent = `Buscando ${chaves.length}...`;
                const resp = await fetch('/nfe/rs/cte/buscar-por-chave-lote', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                    body: JSON.stringify({ cliente_id: clienteId, chaves_acesso: chaves }),
                });
                const data = await resp.json().catch(() => ({}));

                if (!resp.ok || !data.resultados) {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao buscar os documentos.' });
                    return;
                }

                const sucessos = data.resultados.filter(r => r.sucesso);
                const falhas = data.resultados.filter(r => !r.sucesso);
                const listaFalhas = falhas.map(r => `• ${r.chave_acesso.slice(-8)}: ${r.mensagem}`).join('<br>');

                Swal.fire({
                    icon: falhas.length === 0 ? 'success' : 'warning',
                    title: `${sucessos.length} de ${data.resultados.length} encontrado(s)`,
                    html: falhas.length ? `Falharam:<br>${listaFalhas}` : 'Clique em "Buscar" pra atualizar a lista.',
                });
                if (falhas.length === 0) {
                    inputChaveCte.value = '';
                } else {
                    inputChaveCte.value = falhas.map(r => r.chave_acesso).join('\n');
                }
                return;
            }

            const chave = chaves[0];

            // Modelo do documento vem embutido na própria chave (posições 21-22, 1-indexed):
            // 55=NF-e, 65=NFC-e, 57=CT-e — decide qual endpoint chamar sem precisar perguntar.
            const modelo = chave.substring(20, 22);
            const ehCte = modelo === '57';
            const ehNfce = modelo === '65';

            let url;
            if (ehCte) {
                if (!checkModoRs.checked) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Busca de CT-e por chave só está disponível com o certificado da contabilidade (marque a opção acima).' });
                    return;
                }
                url = '/nfe/rs/cte/buscar-por-chave';
            } else if (checkModoRs.checked) {
                url = '/nfe/rs/buscar-por-chave';
            } else {
                if (ehNfce) {
                    Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Busca de NFC-e por chave só está disponível com o certificado da contabilidade (marque a opção acima).' });
                    return;
                }
                url = '/nfe/buscar-por-chave';
            }

            this.textContent = 'Buscando...';
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ cliente_id: clienteId, chave_acesso: chave }),
            });
            const data = await resp.json().catch(() => ({}));

            if (!resp.ok || !data.sucesso) {
                Swal.fire({ icon: 'error', title: 'Não encontrado', text: data.mensagem ?? data.error ?? 'Falha ao buscar o documento.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Encontrado!', text: data.mensagem + ' Clique em "Buscar" pra atualizar a lista.' });
            inputChaveCte.value = '';
        } catch {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
            this.textContent = labelOrig;
        }
    });

    // ─── Busca via contabilidade (SEFAZ-RS) ────────────────────────────────────
    const checkModoRs     = document.getElementById('checkModoRs');
    @if(auth()->user()?->canConfigurarCertificadoContabilidade())
    const certRsOk        = document.getElementById('certRsOk');
    const certRsAlert     = document.getElementById('certRsAlert');
    const certRsExpired   = document.getElementById('certRsExpired');
    const certRsNone      = document.getElementById('certRsNone');
    const btnAbrirUploadRs = document.getElementById('btnAbrirUploadRs');
    const formUploadRs     = document.getElementById('formUploadRs');
    const inputCertRs      = document.getElementById('inputCertRs');
    const inputSenhaRs     = document.getElementById('inputSenhaRs');
    const selectAmbienteRs = document.getElementById('selectAmbienteRs');
    const btnSalvarCertRs  = document.getElementById('btnSalvarCertRs');

    async function carregarStatusCertRs() {
        const resp = await fetch('/nfe/rs/certificado', { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();

        [certRsOk, certRsAlert, certRsExpired, certRsNone].forEach(el => el.classList.add('hidden'));

        if (!data.configurado || !data.arquivo_ok) {
            certRsNone.classList.remove('hidden');
            return;
        }

        if (data.vencido) {
            certRsExpired.classList.remove('hidden');
        } else if (data.alerta) {
            certRsAlert.classList.remove('hidden');
            document.getElementById('certRsVencimentoAlert').textContent = data.vencimento;
        } else {
            certRsOk.classList.remove('hidden');
            document.getElementById('certRsVencimento').textContent = data.vencimento;
        }
    }

    btnAbrirUploadRs?.addEventListener('click', function () {
        formUploadRs.classList.toggle('hidden');
    });

    document.querySelectorAll('.btn-atualizar-cert-rs').forEach(btn => {
        btn.addEventListener('click', function () {
            formUploadRs.classList.toggle('hidden');
        });
    });

    btnSalvarCertRs.addEventListener('click', async function () {
        if (!inputCertRs.files[0] || !inputSenhaRs.value) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o arquivo .pfx e informe a senha.' });
            return;
        }

        const formData = new FormData();
        formData.append('certificado', inputCertRs.files[0]);
        formData.append('senha', inputSenhaRs.value);
        formData.append('ambiente', selectAmbienteRs.value);

        this.disabled = true;
        this.textContent = 'Salvando...';

        try {
            const resp = await fetch('/nfe/rs/certificado', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF },
                body: formData,
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar certificado.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message });
            inputSenhaRs.value = '';
            inputCertRs.value = '';
            formUploadRs.classList.add('hidden');
            await carregarStatusCertRs();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
            this.textContent = 'Salvar certificado';
        }
    });

    carregarStatusCertRs();
    @endif

    const estadoInicial    = document.getElementById('estadoInicial');
    const estadoLoading    = document.getElementById('estadoLoading');
    const estadoErro       = document.getElementById('estadoErro');
    const estadoVazio      = document.getElementById('estadoVazio');
    const estadoResultados = document.getElementById('estadoResultados');

    const tabelaDocs     = document.getElementById('tabelaDocs');
    const totalDocs      = document.getElementById('totalDocs');
    const checkTodos     = document.getElementById('checkTodos');
    const btnDownloadZip = document.getElementById('btnDownloadZip');
    const btnDownloadZipPdf = document.getElementById('btnDownloadZipPdf');
    const btnExportarRelatorio = document.getElementById('btnExportarRelatorio');
    const btnExportarRelatorioLabel = document.getElementById('btnExportarRelatorioLabel');
    const filtroTipo       = document.getElementById('filtroTipo');
    const filtroDirecao    = document.getElementById('filtroDirecao');
    const filtroSituacao   = document.getElementById('filtroSituacao');
    const filtroBusca      = document.getElementById('filtroBusca');
    const filtroDataInicio = document.getElementById('filtroDataInicio');
    const filtroDataFim    = document.getElementById('filtroDataFim');

    let docsAtuais   = [];
    let clienteCnpj  = ''; // CNPJ (só dígitos) da empresa selecionada, usado para classificar entrada/saída
    let paginaAtual  = 1;
    const porPagina  = 50;
    const selecionados = new Set(); // nsu (string) dos documentos marcados, persiste entre páginas (dentro do mesmo filtro)

    const btnPaginaAnterior = document.getElementById('btnPaginaAnterior');
    const btnPaginaProxima  = document.getElementById('btnPaginaProxima');
    const paginaInfo        = document.getElementById('paginaInfo');

    function soDigitos(str) {
        return (str || '').replace(/\D/g, '');
    }

    // tpNF (0=entrada, 1=saída) é sempre da perspectiva de quem EMITIU a nota — não da
    // empresa que estamos consultando. Uma venda normal de terceiro pra o cliente tem
    // tpNF=1 (saída do terceiro), mas é uma entrada pro cliente. Por isso a direção real
    // depende de duas coisas: se o cliente é o emitente ou o destinatário, e o tpNF.
    // - Cliente é o emitente: direção = tpNF direto (0=entrada — nota de entrada emitida
    //   pelo próprio destinatário, ex. compra de produtor rural; 1=saída — venda normal).
    // - Cliente é o destinatário: direção = oposto do tpNF (terceiro emite saída -> é
    //   entrada pro cliente; terceiro emite entrada, ex. devolução -> é saída pro cliente).
    // Sem tpNf (CT-e, ou documento antigo ainda não migrado): cai no fallback por emitente.
    function direcaoDoc(doc) {
        const clienteEhEmitente = !!clienteCnpj && soDigitos(doc.emitenteDoc) === clienteCnpj;

        if (doc.tpNf === 0 || doc.tpNf === '0' || doc.tpNf === 1 || doc.tpNf === '1') {
            const ehSaidaDoEmitente = Number(doc.tpNf) === 1;
            if (clienteEhEmitente) {
                return ehSaidaDoEmitente ? 'saida' : 'entrada';
            }
            return ehSaidaDoEmitente ? 'entrada' : 'saida';
        }

        if (!clienteCnpj) return null;
        return clienteEhEmitente ? 'saida' : 'entrada';
    }

    function docsFiltrados() {
        let docs = docsAtuais;

        if (filtroTipo.value === 'ambos') {
            docs = docs.filter(d => d.tipo === 'nfe' || d.tipo === 'nfce');
        } else if (filtroTipo.value) {
            docs = docs.filter(d => d.tipo === filtroTipo.value);
        }

        if (filtroDirecao.value) {
            docs = docs.filter(d => direcaoDoc(d) === filtroDirecao.value);
        }

        if (filtroSituacao.value === 'cancelada') {
            docs = docs.filter(d => d.situacao === 'cancelada');
        } else if (filtroSituacao.value === 'normal') {
            docs = docs.filter(d => d.situacao !== 'cancelada');
        }

        // Refina dentro do período já buscado — não dispara nova consulta à Sefaz.
        if (filtroDataInicio.value) {
            docs = docs.filter(d => d.dataEmissao && d.dataEmissao.slice(0, 10) >= filtroDataInicio.value);
        }
        if (filtroDataFim.value) {
            docs = docs.filter(d => d.dataEmissao && d.dataEmissao.slice(0, 10) <= filtroDataFim.value);
        }

        const termoBusca = filtroBusca.value.trim().toLowerCase();
        if (termoBusca) {
            docs = docs.filter(d =>
                String(d.numero ?? '').toLowerCase().includes(termoBusca)
                || String(d.emitenteNome ?? '').toLowerCase().includes(termoBusca)
                || String(d.chaveAcesso ?? '').toLowerCase().includes(termoBusca)
            );
        }

        return docs;
    }

    // Ao trocar qualquer filtro, limpa a seleção — evita que documentos marcados
    // sob um filtro anterior (ex.: "todos") sejam incluídos no zip ao gerar com
    // outro filtro aplicado (ex.: "somente saídas").
    function limparSelecaoAoFiltrar() {
        selecionados.clear();
        checkTodos.checked = false;
        paginaAtual = 1;
        renderizarPaginaAtual();
    }

    filtroDirecao.addEventListener('change', limparSelecaoAoFiltrar);
    filtroDirecao.addEventListener('change', atualizarBotaoExportarRelatorio);

    function atualizarResumo() {
        const filtrados = docsFiltrados();
        // Soma sempre o que está sendo exibido — quem controla se cancelada entra ou não
        // é o próprio filtro de Situação (Normais / Canceladas / Normais e canceladas).
        const soma = filtrados.reduce((acc, d) => acc + (parseFloat(d.valor) || 0), 0);

        totalDocs.textContent = filtrados.length;

        const rowTotal = document.getElementById('rowTotalValor');
        if (filtrados.length > 0) {
            document.getElementById('totalValor').textContent = soma.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            rowTotal.classList.remove('hidden');
        } else {
            rowTotal.classList.add('hidden');
        }
    }

    filtroTipo.addEventListener('change', limparSelecaoAoFiltrar);
    filtroTipo.addEventListener('change', atualizarBotaoExportarRelatorio);
    filtroSituacao.addEventListener('change', limparSelecaoAoFiltrar);
    filtroDataInicio.addEventListener('change', limparSelecaoAoFiltrar);
    filtroDataFim.addEventListener('change', limparSelecaoAoFiltrar);
    filtroBusca.addEventListener('input', limparSelecaoAoFiltrar);

    // ─── Exportar relatório fiscal (Excel) — segue o filtro "Tipo" da toolbar ─
    // '' (Todos os tipos) inclui CT-e na tabela, mas o relatório junto (NF-e + NFC-e)
    // ignora o CT-e — para exportar CT-e é preciso filtrar o tipo antes.
    const TIPO_RELATORIO_POR_FILTRO = { '': 'ambos', ambos: 'ambos', nfe: 'nfe', nfce: 'nfce', cte: 'cte' };
    const LABEL_RELATORIO_POR_FILTRO = {
        '': 'Exportar relatório (NF-e + NFC-e)',
        ambos: 'Exportar relatório (NF-e + NFC-e)',
        nfe: 'Exportar relatório (somente NF-e)',
        nfce: 'Exportar relatório (somente NFC-e)',
        cte: 'Exportar relatório (somente CT-e)',
    };
    const SUFIXO_DIRECAO_LABEL  = { '': '', entrada: ' — entradas', saida: ' — saídas' };
    const SUFIXO_ARQUIVO_RELATORIO = { ambos: 'NFe_NFCe', nfe: 'NFe', nfce: 'NFCe', cte: 'CTe' };
    const SUFIXO_ARQUIVO_DIRECAO    = { '': '', entrada: '_Entradas', saida: '_Saidas' };

    function atualizarBotaoExportarRelatorio() {
        btnExportarRelatorio.classList.remove('hidden');
        const labelTipo = LABEL_RELATORIO_POR_FILTRO[filtroTipo.value] ?? LABEL_RELATORIO_POR_FILTRO[''];
        btnExportarRelatorioLabel.textContent = labelTipo + (SUFIXO_DIRECAO_LABEL[filtroDirecao.value] ?? '');
    }

    btnPaginaAnterior.addEventListener('click', function () {
        if (paginaAtual > 1) {
            paginaAtual--;
            renderizarPaginaAtual();
        }
    });

    btnPaginaProxima.addEventListener('click', function () {
        const totalPaginas = Math.max(1, Math.ceil(docsFiltrados().length / porPagina));
        if (paginaAtual < totalPaginas) {
            paginaAtual++;
            renderizarPaginaAtual();
        }
    });

    function renderizarPaginaAtual() {
        const filtrados     = docsFiltrados();
        const totalPaginas  = Math.max(1, Math.ceil(filtrados.length / porPagina));
        paginaAtual = Math.min(Math.max(paginaAtual, 1), totalPaginas);

        const inicio = (paginaAtual - 1) * porPagina;
        const pagina = filtrados.slice(inicio, inicio + porPagina);

        renderizarTabela(pagina);
        atualizarResumo();
        atualizarSelecao();

        paginaInfo.textContent = filtrados.length > 0
            ? `Página ${paginaAtual} de ${totalPaginas} (${filtrados.length} no total)`
            : 'Nenhum documento';
        btnPaginaAnterior.disabled = paginaAtual <= 1;
        btnPaginaProxima.disabled  = paginaAtual >= totalPaginas;
    }

    // ─── Seleção de empresa ──────────────────────────────────────────────────
    selectCliente.addEventListener('change', async function () {
        const clienteId = this.value;
        const cnpjSelecionado = this.options[this.selectedIndex]?.dataset.cnpj ?? '';
        clienteCnpj = soDigitos(cnpjSelecionado);

        esconderTodosEstados();
        estadoInicial.classList.remove('hidden');

        if (!clienteId) {
            cardFiltro.classList.add('hidden');
            certStatus.classList.add('hidden');
            clienteCnpjEl.classList.add('hidden');
            return;
        }

        clienteCnpjValor.textContent = formatarCnpjCpf(cnpjSelecionado);
        clienteCnpjEl.classList.remove('hidden');

        cardFiltro.classList.remove('hidden');
        atualizarVisibilidadeBuscarChave();

        if (checkModoRs.checked) {
            certStatus.classList.add('hidden'); // status do certificado é o da contabilidade, já exibido acima
            return;
        }

        await carregarStatusCertificado(clienteId);
    });

    // Disponível nos dois modos (RS e nacional) — o próprio clique decide o endpoint
    // certo a partir do modelo embutido na chave, só precisa de uma empresa selecionada.
    function atualizarVisibilidadeBuscarChave() {
        areaBuscarCtePorChave.classList.toggle('hidden', !selectCliente.value);
    }

    checkModoRs.addEventListener('change', function () {
        certStatus.classList.add('hidden');
        atualizarVisibilidadeBuscarChave();
        if (selectCliente.value && !this.checked) {
            carregarStatusCertificado(selectCliente.value);
        }
    });

    async function carregarStatusCertificado(clienteId) {
        const resp = await fetch(`/nfse/certificado/${clienteId}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();

        certStatus.classList.remove('hidden');
        [certOk, certAlert, certExpired, certNone].forEach(el => el.classList.add('hidden'));

        if (!data.configurado || !data.arquivo_ok) {
            certNone.classList.remove('hidden');
            return;
        }

        if (data.vencido) {
            certExpired.classList.remove('hidden');
        } else if (data.alerta) {
            certAlert.classList.remove('hidden');
            document.getElementById('certVencimentoAlert').textContent = data.vencimento;
        } else {
            certOk.classList.remove('hidden');
            document.getElementById('certVencimento').textContent = data.vencimento;
        }
    }

    // ─── Atalhos de período ──────────────────────────────────────────────────
    document.querySelectorAll('.btn-periodo').forEach(btn => {
        btn.addEventListener('click', function () {
            const periodo = this.dataset.periodo;
            const hoje    = new Date();

            let inicio, fim;

            switch (periodo) {
                case 'mes-atual':
                    inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
                    fim    = hoje;
                    break;
                case 'mes-anterior':
                    inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 1, 1);
                    fim    = new Date(hoje.getFullYear(), hoje.getMonth(), 0);
                    break;
                case 'trimestre':
                    inicio = new Date(hoje.getFullYear(), hoje.getMonth() - 2, 1);
                    fim    = hoje;
                    break;
                case 'ano':
                    inicio = new Date(hoje.getFullYear(), 0, 1);
                    fim    = hoje;
                    break;
            }

            dataInicio.value = formatDate(inicio);
            dataFim.value    = formatDate(fim);
        });
    });

    function formatDate(d) {
        return d.toISOString().split('T')[0];
    }

    // ─── Busca ───────────────────────────────────────────────────────────────
    btnBuscar.addEventListener('click', buscar);

    // Limite de segurança de chunks por fase — evita loop infinito em caso de
    // bug de paginação (400 chunks * 12 lotes = 4800 lotes, bem acima do
    // necessário mesmo para as maiores empresas). Mesmo padrão da tela de NFS-e.
    const NFE_MAX_CHUNKS_POR_FASE = 400;

    async function chamarChunk(url, body) {
        const controller = new AbortController();
        // Cada chunk processa poucos lotes — 90s é folga suficiente e fica
        // abaixo do timeout de proxy/CDN (Cloudflare derruba em ~100s com HTTP 524).
        const timeoutId = setTimeout(() => controller.abort(), 90_000);

        let resp;
        try {
            resp = await fetch(url, {
                method: 'POST',
                signal: controller.signal,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify(body),
            });
        } finally {
            clearTimeout(timeoutId);
        }

        const texto = await resp.text();
        let data;
        try {
            data = JSON.parse(texto);
        } catch (_) {
            console.error('[NF-e] Resposta não-JSON recebida:', texto.substring(0, 1000));
            throw new Error(`Resposta inválida do servidor (HTTP ${resp.status}, não-JSON). Verifique o console do navegador (F12) para detalhes.`);
        }

        if (!resp.ok || data.error) {
            console.error('[NF-e] Resposta de erro:', resp.status, data);
            throw new Error(data.error ?? `Erro desconhecido (HTTP ${resp.status}).`);
        }

        return data;
    }

    /**
     * Sincroniza uma fase até 'concluido'=true, chamando a rota de chunk
     * repetidas vezes (nsu_inicio = proximo_nsu da chamada anterior). Cada
     * chamada processa só uma fatia — evita que a sincronização inteira (que
     * pode levar minutos) derrube no timeout do Cloudflare.
     */
    async function sincronizarFaseAteConcluir(url, bodyBase, labelProgresso) {
        let nsuAtual  = undefined; // primeira chamada: omite, backend usa o NSU salvo
        let concluido = false;
        let chunks    = 0;
        let aviso     = null;

        while (!concluido) {
            chunks++;
            if (chunks > NFE_MAX_CHUNKS_POR_FASE) {
                throw new Error(`${labelProgresso}: excedeu o limite de páginas de segurança.`);
            }

            document.getElementById('loadingTempo').textContent = `Buscando... (parte ${chunks})`;

            const body = { ...bodyBase };
            if (nsuAtual !== undefined) {
                body.nsu_inicio = nsuAtual;
            }

            const data = await chamarChunk(url, body);

            nsuAtual  = data.proximo_nsu;
            concluido = !!data.concluido;
            if (data.aviso) {
                aviso = data.aviso;
            }
        }

        return aviso;
    }

    async function buscar() {
        const clienteId = selectCliente.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione uma empresa.' });
            return;
        }
        if (!dataInicio.value || !dataFim.value) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Preencha o período de busca.' });
            return;
        }

        esconderTodosEstados();
        estadoLoading.classList.remove('hidden');
        document.getElementById('loadingTitulo').textContent = checkModoRs.checked
            ? 'Sincronizando com a Sefaz-RS (contabilidade)...'
            : 'Sincronizando com o Ambiente Nacional (NFeDistribuicaoDFe / CTeDistribuicaoDFe)...';
        document.getElementById('loadingTempo').textContent = 'Iniciando...';
        btnBuscar.disabled = true;
        document.getElementById('btnBuscarLabel').textContent = 'Buscando...';

        try {
            const avisos = [];
            const bodyBase = { cliente_id: clienteId };

            if (checkModoRs.checked) {
                // Uma fase de cada vez — NF-e, NFC-e e CT-e têm NSU independente,
                // e rodar em sequência (não em paralelo) evita rajada de
                // requisições no mesmo certificado da contabilidade.
                const fases = [
                    ['nfe',  'Buscando NF-e'],
                    ['nfce', 'Buscando NFC-e'],
                    ['cte',  'Buscando CT-e'],
                ];

                for (const [fase, label] of fases) {
                    const aviso = await sincronizarFaseAteConcluir(
                        '/nfe/rs/sincronizar-chunk',
                        { ...bodyBase, fase },
                        label
                    );
                    if (aviso) avisos.push(aviso);
                }
            } else {
                // NF-e (NFeDistribuicaoDFe) e CT-e (CTeDistribuicaoDFe) são webservices
                // nacionais distintos, com NSU independente — mesmo padrão do modo RS.
                const fasesNacional = [
                    ['/nfe/sincronizar-chunk', 'Buscando NF-e'],
                    ['/nfe/cte-nacional/sincronizar-chunk', 'Buscando CT-e'],
                ];

                for (const [url, label] of fasesNacional) {
                    const aviso = await sincronizarFaseAteConcluir(url, bodyBase, label);
                    if (aviso) avisos.push(aviso);
                }
            }

            // A lista sempre traz tudo que já está no cofre (nacional + RS, todos os
            // modelos) — o toggle "modo RS" só decide com qual webservice sincronizar
            // acima, não o que a tabela exibe. Mesma base que o relatório Excel lê.
            const url = '/nfe/buscar';

            // A lista é paginada no backend por cursor/keyset (cada documento carrega
            // o XML inteiro; trazer um período com milhares de docs de uma vez
            // estoura a memória do PHP) — busca "página" a "página" até "concluido",
            // igual ao padrão de sincronização em chunks acima. Cursor em vez de
            // número de página: com OFFSET, o cron de sincronização automática
            // rodando em paralelo pode inserir/atualizar um documento entre duas
            // chamadas e deslocar o offset, fazendo um documento existente nunca
            // aparecer na busca (confirmado em produção com clientes grandes).
            let cursor      = null;
            let concluido   = false;
            let documentos  = [];
            let numChamada  = 1;

            do {
                document.getElementById('loadingTempo').textContent = numChamada > 1
                    ? `Carregando resultados... (parte ${numChamada})`
                    : 'Carregando resultados...';

                const body = {
                    cliente_id:  clienteId,
                    data_inicio: dataInicio.value,
                    data_fim:    dataFim.value,
                };
                if (cursor) {
                    body.cursor_data = cursor.data;
                    body.cursor_id   = cursor.id;
                }

                const data = await chamarChunk(url, body);

                documentos = documentos.concat(data.documentos ?? []);
                cursor     = data.proximo_cursor ?? null;
                concluido  = data.concluido ?? true;
                numChamada++;
            } while (!concluido && cursor);

            esconderTodosEstados();

            docsAtuais = documentos;
            selecionados.clear();

            if (avisos.length > 0) {
                Swal.fire({ icon: 'warning', title: 'Sincronização parcial', text: avisos.join(' '), timer: 8000, timerProgressBar: true });
            }

            if (docsAtuais.length === 0) {
                estadoVazio.classList.remove('hidden');
                gerarDashboards();
                return;
            }

            filtroTipo.value = '';
            filtroDirecao.value = '';
            filtroSituacao.value = '';
            filtroBusca.value = '';
            filtroDataInicio.value = '';
            filtroDataFim.value = '';
            paginaAtual = 1;
            estadoResultados.classList.remove('hidden');
            atualizarBotaoExportarRelatorio();
            renderizarPaginaAtual();
            gerarDashboards();

        } catch (e) {
            esconderTodosEstados();
            estadoErro.classList.remove('hidden');
            const msg = e.name === 'AbortError'
                ? 'Tempo limite excedido numa das etapas da sincronização. Tente novamente em instantes.'
                : 'Erro de comunicação: ' + e.message;
            document.getElementById('erroMsg').textContent = msg;
        } finally {
            btnBuscar.disabled = false;
            document.getElementById('btnBuscarLabel').textContent = 'Buscar NF-e / NFC-e / CT-e';
        }
    }

    @if(auth()->user()?->canConfigurarCertificadoContabilidade())
    // ─── Reconsulta manual de NSU (volta N posições a partir do checkpoint) ────
    // Mesma lógica do comando `fiscal:reconsultar-notas-rs`, só que disparada
    // pra um único cliente (o selecionado na tela) em vez de todos de uma vez.
    // A janela padrão (50M) vem do backend (JANELA_NSU_BACKFILL) — o modal só
    // manda 'janela_nsu' quando o usuário digita um valor diferente.
    const btnReconsultarNsu      = document.getElementById('btnReconsultarNsu');
    const btnReconsultarNsuLabel = document.getElementById('btnReconsultarNsuLabel');
    const JANELA_NSU_PADRAO      = 50_000_000;

    btnReconsultarNsu.addEventListener('click', async () => {
        const clienteId = selectCliente.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione uma empresa antes de reconsultar.' });
            return;
        }

        const { value: janela } = await Swal.fire({
            icon: 'question',
            title: 'Reconsultar NSU',
            html: 'Volta quantas posições de NSU a partir do checkpoint atual e reconsulta NF-e, NFC-e e CT-e via certificado da contabilidade — útil quando algum documento ficou pra trás e não aparece na busca normal. Pode levar alguns minutos.',
            input: 'number',
            inputLabel: 'Janela de NSU a retroagir',
            inputValue: JANELA_NSU_PADRAO,
            inputAttributes: { min: 1, step: 1 },
            showCancelButton: true,
            confirmButtonText: 'Reconsultar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0084aa',
            inputValidator: (value) => {
                const numero = parseInt(value, 10);
                if (!value || isNaN(numero) || numero <= 0) {
                    return 'Informe um número de NSU maior que zero.';
                }
            },
        });
        if (!janela) return;

        const janelaNsu = parseInt(janela, 10);

        btnReconsultarNsu.disabled = true;
        const labelOrig = btnReconsultarNsuLabel.textContent;
        btnReconsultarNsuLabel.textContent = 'Reconsultando...';

        try {
            const fases = [
                ['nfe',  'Reconsultando NF-e'],
                ['nfce', 'Reconsultando NFC-e'],
                ['cte',  'Reconsultando CT-e'],
            ];
            const avisos = [];

            for (const [fase, label] of fases) {
                const aviso = await sincronizarFaseAteConcluir(
                    '/nfe/rs/sincronizar-chunk',
                    { cliente_id: clienteId, fase, modo_backfill: true, janela_nsu: janelaNsu },
                    label
                );
                if (aviso) avisos.push(aviso);
            }

            Swal.fire({
                icon: 'success',
                title: 'Reconsulta concluída',
                text: avisos.length > 0
                    ? avisos.join(' ')
                    : `NF-e, NFC-e e CT-e reconsultadas ${janelaNsu.toLocaleString('pt-BR')} NSU pra trás. Clique em "Buscar" pra atualizar a lista.`,
            });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro na reconsulta', text: e.message });
        } finally {
            btnReconsultarNsu.disabled = false;
            btnReconsultarNsuLabel.textContent = labelOrig;
        }
    });
    @endif

    // ─── Tabela de resultados ────────────────────────────────────────────────
    function renderizarTabela(docs) {
        tabelaDocs.innerHTML = '';

        docs.forEach((doc) => {
            const nsu    = doc.nsu ?? '';
            const tipo   = doc.tipo ?? 'nfe';
            const numero = doc.numero || `NSU ${nsu}`;
            const data       = doc.dataEmissao ? formatarData(doc.dataEmissao) : '-';
            const dataSaiEnt = doc.dataSaidaEntrada ? formatarData(doc.dataSaidaEntrada) : '-';
            const valor  = doc.valor != null && doc.valor !== '' ? formatarMoeda(parseFloat(doc.valor)) : '-';
            const temXml = !!doc.xmlContent;

            const badgesPorTipo = {
                cte:  '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">CT-e</span>',
                nfce: '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">NFC-e</span>',
                nfe:  '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">NF-e</span>',
            };
            const tipoBadge = badgesPorTipo[tipo] ?? badgesPorTipo.nfe;

            const direcao = direcaoDoc(doc);
            const direcaoBadge = direcao === 'saida'
                ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 ml-1">Saída</span>'
                : direcao === 'entrada'
                    ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 dark:bg-slate-700/50 dark:text-slate-400 ml-1">Entrada</span>'
                    : '';

            // Indica de onde veio o dado (nacional x contabilidade RS) e quando foi sincronizado pela
            // última vez — o resultado exibido pode vir do banco local (busca anterior), não
            // necessariamente da consulta que acabou de rodar (ex.: quando a Sefaz rejeita a
            // sincronização ao vivo, ainda mostramos o que já tínhamos salvo).
            const origemLabel = doc.origem === 'rs' ? 'Contabilidade (RS)' : doc.origem === 'nacional' ? 'Nacional' : null;
            const sincTexto = doc.sincronizadoEm ? `Sincronizado em ${formatarData(doc.sincronizadoEm)}` : '';
            const origemBadge = origemLabel
                ? `<span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-slate-700/60 dark:text-slate-400 ml-1" title="${sincTexto}">${origemLabel}</span>`
                : '';

            const canceladaBadge = doc.situacao === 'cancelada'
                ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 ml-1">Cancelada</span>'
                : '';

            // Só faz sentido pra CT-e: o Tomador do Serviço (quem contratou/paga o frete)
            // pode ser um terceiro diferente de quem aparece como remetente/destinatário.
            const papelBadge = (tipo === 'cte' && doc.papelCte)
                ? `<span class="text-xs font-medium px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400 ml-1" title="Papel da empresa consultada neste CT-e">${doc.papelCte}</span>`
                : '';

            const marcado = selecionados.has(String(nsu));

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors';
            tr.dataset.tipo  = tipo;
            tr.dataset.valor = doc.valor ?? 0;
            tr.innerHTML = `
                <td class="px-4 py-3">
                    <input type="checkbox" class="check-doc rounded text-[#0084aa]" data-nsu="${nsu}" ${!temXml ? 'disabled' : ''} ${marcado ? 'checked' : ''}>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">${tipoBadge}${direcaoBadge}${papelBadge}${origemBadge}${canceladaBadge}</td>
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-slate-200">${numero}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">${data}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">${dataSaiEnt}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-slate-300 max-w-[220px] truncate" title="${doc.emitenteNome ?? ''}">${doc.emitenteNome ?? '-'}</td>
                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-slate-200">${valor}</td>
                <td class="px-4 py-3 whitespace-nowrap">
                    ${doc.chaveAcesso ? `
                    <button type="button"
                            class="btn-ver-pdf p-1.5 text-gray-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 bg-transparent border-0 transition-colors"
                            title="Ver PDF (DANFE/DACTE)"
                            data-chave="${doc.chaveAcesso}">
                        <i class="fa-solid fa-file-pdf"></i>
                    </button>` : ''}
                    ${temXml ? `
                    <button type="button"
                            class="btn-download-xml p-1.5 text-gray-400 dark:text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 bg-transparent border-0 transition-colors"
                            title="Baixar XML"
                            data-nsu="${nsu}">
                        <i class="fa-solid fa-file-code"></i>
                    </button>` : ''}
                </td>
            `;
            tabelaDocs.appendChild(tr);
        });

        tabelaDocs.querySelectorAll('.btn-download-xml').forEach(btn => {
            btn.addEventListener('click', () => downloadXml(btn.dataset.nsu));
        });

        tabelaDocs.querySelectorAll('.btn-ver-pdf').forEach(btn => {
            btn.addEventListener('click', () => abrirPdf(btn.dataset.chave, btn));
        });

        tabelaDocs.querySelectorAll('.check-doc').forEach(cb => {
            cb.addEventListener('change', function () {
                if (this.checked) {
                    selecionados.add(this.dataset.nsu);
                } else {
                    selecionados.delete(this.dataset.nsu);
                }
                atualizarSelecao();
            });
        });
    }

    function formatarData(str) {
        if (!str) return '-';
        const d = str.substring(0, 10).split('-');
        return `${d[2]}/${d[1]}/${d[0]}`;
    }

    function formatarMoeda(val) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
    }

    // ─── Seleção múltipla (persiste entre páginas via Set de NSUs) ────────────
    checkTodos.addEventListener('change', function () {
        const marcarTodas = this.checked;

        docsFiltrados().forEach(doc => {
            if (!doc.xmlContent) return;
            const nsu = String(doc.nsu);
            if (marcarTodas) {
                selecionados.add(nsu);
            } else {
                selecionados.delete(nsu);
            }
        });

        tabelaDocs.querySelectorAll('.check-doc:not([disabled])').forEach(cb => {
            cb.checked = marcarTodas;
        });

        atualizarSelecao();
    });

    function atualizarSelecao() {
        if (selecionados.size > 0) {
            btnDownloadZip.classList.remove('hidden');
            btnDownloadZip.classList.add('flex');
            btnDownloadZip.innerHTML = `<i class="fa-solid fa-file-zipper"></i> Baixar ${selecionados.size} XML(s) (.zip)`;

            btnDownloadZipPdf.classList.remove('hidden');
            btnDownloadZipPdf.classList.add('flex');
            btnDownloadZipPdf.innerHTML = `<i class="fa-solid fa-file-pdf"></i> Baixar ${selecionados.size} PDF(s) (.zip)`;

            // docsAtuais já tem o resultado inteiro do período em memória (não só a
            // página atual), então a soma cobre a seleção mesmo espalhada entre páginas.
            const somaSelecionadosValor = docsAtuais
                .filter(d => selecionados.has(String(d.nsu)))
                .reduce((acc, d) => acc + (parseFloat(d.valor) || 0), 0);

            document.getElementById('totalSelecionados').textContent = selecionados.size;
            document.getElementById('somaSelecionados').textContent = formatarMoeda(somaSelecionadosValor);
            const wrapSelecionados = document.getElementById('totalSelecionadosWrap');
            wrapSelecionados.classList.remove('hidden');
            wrapSelecionados.classList.add('flex');
        } else {
            btnDownloadZip.classList.add('hidden');
            btnDownloadZip.classList.remove('flex');

            btnDownloadZipPdf.classList.add('hidden');
            btnDownloadZipPdf.classList.remove('flex');

            const wrapSelecionados = document.getElementById('totalSelecionadosWrap');
            wrapSelecionados.classList.add('hidden');
            wrapSelecionados.classList.remove('flex');
        }
    }

    // ─── Download XML individual (a partir do cache já baixado na busca) ─────
    function downloadXml(nsu) {
        const doc = docsAtuais.find(d => String(d.nsu) === String(nsu));
        if (!doc?.xmlContent) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'XML não disponível para este documento.' });
            return;
        }

        const blob = new Blob([doc.xmlContent], { type: 'application/xml' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `${doc.tipo}_nsu${nsu}.xml`;
        a.click();
        URL.revokeObjectURL(url);
    }

    // ─── PDF (DANFE/DANFE-NFC-e/DACTE) — GET /nfe/danfe?chave_acesso= ─────────
    async function abrirPdf(chaveAcesso, btn) {
        const iconOrig = btn.innerHTML;

        btn.disabled  = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        try {
            const resp = await fetch(`/nfe/danfe?chave_acesso=${encodeURIComponent(chaveAcesso)}`, {
                headers: { 'Accept': 'application/pdf,application/json' },
            });

            if (!resp.ok) {
                const data = await resp.json().catch(() => ({}));
                Swal.fire({ icon: 'warning', title: 'PDF indisponível', text: data.error ?? 'Falha ao gerar o PDF.' });
                return;
            }

            const blob = await resp.blob();
            const url  = URL.createObjectURL(blob);

            window.open(url, '_blank');
            setTimeout(() => URL.revokeObjectURL(url), 60_000);
        } catch {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            btn.disabled  = false;
            btn.innerHTML = iconOrig;
        }
    }

    // ─── Download ZIP (múltiplos, considerando seleção em todas as páginas) ──
    // Manda só as chaves de acesso — o servidor busca o XML direto do banco. Mandar o
    // XML inteiro de cada nota de volta pela rede travava a geração do zip com poucas
    // centenas de documentos selecionados (tamanho do payload).
    btnDownloadZip.addEventListener('click', async function () {
        const chaves = [...selecionados].map(nsu => {
            const doc = docsAtuais.find(d => String(d.nsu) === nsu);
            return doc?.chaveAcesso || null;
        }).filter(Boolean);

        if (!chaves.length) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum XML disponível para os documentos selecionados.' });
            return;
        }

        const nomeEmpresa = selectCliente.options[selectCliente.selectedIndex]?.text?.trim() || 'NFe-CTe';

        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando ZIP...';

        try {
            const resp = await fetch('/nfe/xml/zip-xmls', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ chaves, nome: nomeEmpresa }),
            });

            if (!resp.ok) {
                const data = await resp.json().catch(() => ({}));
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao gerar ZIP.' });
                return;
            }

            const blob = await resp.blob();
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = `${nomeEmpresa}.zip`;
            a.click();
            URL.revokeObjectURL(url);
        } catch {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
            atualizarSelecao();
        }
    });

    // ─── Download ZIP de PDFs (DANFE/DACTE) — mesmo esquema do ZIP de XMLs ────
    btnDownloadZipPdf.addEventListener('click', async function () {
        const chaves = [...selecionados].map(nsu => {
            const doc = docsAtuais.find(d => String(d.nsu) === nsu);
            return doc?.chaveAcesso || null;
        }).filter(Boolean);

        if (!chaves.length) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum documento disponível para os itens selecionados.' });
            return;
        }

        const nomeEmpresa = selectCliente.options[selectCliente.selectedIndex]?.text?.trim() || 'NFe-CTe';

        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando ZIP...';

        try {
            const resp = await fetch('/nfe/xml/zip-pdfs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ chaves, nome: nomeEmpresa }),
            });

            if (!resp.ok) {
                const data = await resp.json().catch(() => ({}));
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao gerar ZIP de PDFs.' });
                return;
            }

            const falhas = Number(resp.headers.get('X-Pdfs-Falhas') || 0);
            const blob = await resp.blob();
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = `${nomeEmpresa}-PDFs.zip`;
            a.click();
            URL.revokeObjectURL(url);

            if (falhas > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ZIP gerado com ressalvas',
                    text: `${falhas} documento(s) não puderam ter o PDF gerado (provavelmente XML ainda em formato resumido) e foram pulados.`,
                });
            }
        } catch {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
            atualizarSelecao();
        }
    });

    // ─── Exportar relatório fiscal (Excel, NF-e/NFC-e por item) ───────────────
    // Usa a empresa e o período já selecionados no card de filtro — não depende
    // da seleção de linhas na tabela (lê direto de `documentos_fiscais`), mas o
    // botão só aparece depois de uma busca e segue o filtro "Tipo" selecionado.
    btnExportarRelatorio.addEventListener('click', async function () {
        const clienteId = selectCliente.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione uma empresa.' });
            return;
        }
        if (!dataInicio.value || !dataFim.value) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Preencha o período de busca.' });
            return;
        }

        const tipo          = TIPO_RELATORIO_POR_FILTRO[filtroTipo.value] ?? 'ambos';
        const direcao       = filtroDirecao.value || null;
        const nomeEmpresa   = selectCliente.options[selectCliente.selectedIndex]?.text?.trim() || 'NFe-NFCe';
        const labelOriginal = btnExportarRelatorioLabel.textContent;

        this.disabled = true;
        btnExportarRelatorioLabel.textContent = 'Gerando...';

        try {
            const resp = await fetch('{{ route('nfe.relatorio') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    cliente_id:  clienteId,
                    data_inicio: dataInicio.value,
                    data_fim:    dataFim.value,
                    tipo:        tipo,
                    direcao:     direcao,
                }),
            });

            if (!resp.ok) {
                const data = await resp.json().catch(() => ({}));
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao gerar o relatório.' });
                return;
            }

            const blob   = await resp.blob();
            const url    = URL.createObjectURL(blob);
            const a      = document.createElement('a');
            const sufixo = (SUFIXO_ARQUIVO_RELATORIO[tipo] ?? 'NFe_NFCe') + (SUFIXO_ARQUIVO_DIRECAO[filtroDirecao.value] ?? '');
            a.href     = url;
            a.download = `Relatorio_${sufixo}_${nomeEmpresa}_${dataInicio.value}_a_${dataFim.value}.xlsx`;
            a.click();
            URL.revokeObjectURL(url);
        } catch {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
            btnExportarRelatorioLabel.textContent = labelOriginal;
        }
    });

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function esconderTodosEstados() {
        [estadoInicial, estadoLoading, estadoErro, estadoVazio, estadoResultados].forEach(el => {
            el.classList.add('hidden');
        });
    }

    const hoje = new Date();
    dataInicio.value = formatDate(new Date(hoje.getFullYear(), hoje.getMonth(), 1));
    dataFim.value    = formatDate(hoje);

    // ─── Abas Documentos / Dashboards ───────────────────────────────────────────
    const abaDocumentos = document.getElementById('abaDocumentos');
    const abaDashboards = document.getElementById('abaDashboards');

    document.querySelectorAll('.btn-aba-nfe').forEach(btn => {
        btn.addEventListener('click', function () {
            const alvo = this.dataset.aba;

            document.querySelectorAll('.btn-aba-nfe').forEach(b => {
                const ativo = b === this;
                b.classList.toggle('border-brand', ativo);
                b.classList.toggle('text-brand', ativo);
                b.classList.toggle('border-transparent', !ativo);
                b.classList.toggle('text-gray-500', !ativo);
                b.classList.toggle('dark:text-slate-400', !ativo);
                b.classList.toggle('hover:text-brand', !ativo);
            });

            abaDocumentos.classList.toggle('hidden', alvo !== 'documentos');
            abaDashboards.classList.toggle('hidden', alvo !== 'dashboards');
        });
    });

    // ─── Dashboards (gerados automaticamente a partir da busca de notas) ─────────
    const dashAviso = document.getElementById('dashAviso');
    const dashCards = document.getElementById('dashCards');

    const dashFornSimplesEstados = ['dashFornSimplesLoading', 'dashFornSimplesVazio', 'dashFornSimplesResultado']
        .map(id => document.getElementById(id));
    const dashProdVendidosEstados = ['dashProdVendidosLoading', 'dashProdVendidosVazio', 'dashProdVendidosResultado']
        .map(id => document.getElementById(id));
    const dashInterestEstados = ['dashInterestLoading', 'dashInterestVazio', 'dashInterestResultado']
        .map(id => document.getElementById(id));

    const dashMostrar = (estados, id) => estados.forEach(el => el.classList.toggle('hidden', el.id !== id));
    const dashFornSimplesMostrar = id => dashMostrar(dashFornSimplesEstados, id);
    const dashProdVendidosMostrar = id => dashMostrar(dashProdVendidosEstados, id);
    const dashInterestMostrar = id => dashMostrar(dashInterestEstados, id);

    function formatarCnpj(doc) {
        const s = String(doc || '').replace(/\D/g, '');
        if (s.length === 14) return s.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5');
        if (s.length === 11) return s.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
        return doc || '—';
    }

    function formatarQtd(val) {
        return new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 3 }).format(val || 0);
    }

    function esc(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }

    function renderFornSimples(dados) {
        const fornecedores = dados.fornecedores || [];
        const resumo = document.getElementById('dashFornSimplesResumo');
        if (fornecedores.length === 0) {
            resumo.classList.add('hidden');
            dashFornSimplesMostrar('dashFornSimplesVazio');
            return;
        }
        resumo.classList.remove('hidden');

        document.getElementById('dashFornSimplesMesLabel').textContent = dados.periodo || '';
        document.getElementById('dashFornSimplesTotal').textContent = formatarMoeda(dados.totalGeral || 0);

        const maior = Math.max(...fornecedores.map(f => f.total));
        const lista = document.getElementById('dashFornSimplesLista');
        lista.innerHTML = '';

        fornecedores.forEach((f, i) => {
            const pct = maior > 0 ? Math.max((f.total / maior) * 100, 3) : 0;
            const pos = i + 1;
            const medalha = pos <= 3 ? 'bg-brand text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400';
            const li = document.createElement('li');
            li.style.listStyle = 'none';
            li.className = 'px-5 py-4 hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors';
            li.innerHTML = `
                <div class="flex items-center gap-3">
                    <span class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold ${medalha}">${pos}</span>
                    <span class="min-w-0 flex-1 text-sm font-medium text-gray-800 dark:text-slate-100 truncate" title="${esc(f.nome)}">${esc(f.nome)}</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap tabular-nums">${formatarMoeda(f.total)}</span>
                </div>
                <div class="mt-2.5 ml-9 h-2 rounded-full bg-gray-100 dark:bg-slate-700/70">
                    <div class="h-full rounded-full bg-brand" style="width: ${pct}%"></div>
                </div>
                <div class="mt-2 ml-9 text-xs text-gray-400 dark:text-slate-500">
                    <span class="tabular-nums">${formatarCnpj(f.cnpj)}</span>
                    <span class="mx-2 text-gray-300 dark:text-slate-600">&bull;</span>
                    <span>${f.qtd} ${f.qtd === 1 ? 'nota' : 'notas'}</span>
                </div>`;
            lista.appendChild(li);
        });

        dashFornSimplesMostrar('dashFornSimplesResultado');
    }

    function renderProdVendidos(dados) {
        const produtos = dados.produtos || [];
        const resumo = document.getElementById('dashProdVendidosResumo');
        if (produtos.length === 0) {
            resumo.classList.add('hidden');
            dashProdVendidosMostrar('dashProdVendidosVazio');
            return;
        }
        resumo.classList.remove('hidden');

        document.getElementById('dashProdVendidosMesLabel').textContent = dados.periodo || '';
        document.getElementById('dashProdVendidosNotas').textContent =
            `${dados.qtdNotas} nota${dados.qtdNotas === 1 ? '' : 's'} de saída`;
        document.getElementById('dashProdVendidosTotal').textContent = formatarMoeda(dados.totalGeral || 0);

        const maior = Math.max(...produtos.map(p => p.valor));
        const lista = document.getElementById('dashProdVendidosLista');
        lista.innerHTML = '';

        produtos.forEach((p, i) => {
            const pct = maior > 0 ? Math.max((p.valor / maior) * 100, 2) : 0;
            const pos = i + 1;
            const medalha = pos <= 3 ? 'bg-brand text-white' : 'bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400';

            const fiscal = [];
            if (p.ncm) fiscal.push(`NCM ${esc(p.ncm)}`);
            if (p.cfops && p.cfops.length) fiscal.push(`CFOP ${p.cfops.map(esc).join(', ')}`);
            if (p.cest) fiscal.push(`CEST ${esc(p.cest)}`);

            const li = document.createElement('li');
            li.style.listStyle = 'none';
            li.className = 'px-5 py-4 hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors';
            li.innerHTML = `
                <div class="flex items-center gap-3">
                    <span class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold ${medalha}">${pos}</span>
                    <span class="min-w-0 flex-1 text-sm font-medium text-gray-800 dark:text-slate-100 truncate" title="${esc(p.descricao)}">${esc(p.descricao)}</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white whitespace-nowrap tabular-nums">${formatarMoeda(p.valor)}</span>
                </div>
                <div class="mt-2.5 ml-9 h-2 rounded-full bg-gray-100 dark:bg-slate-700/70">
                    <div class="h-full rounded-full bg-brand" style="width: ${pct}%"></div>
                </div>
                <div class="mt-2 ml-9 text-xs text-gray-400 dark:text-slate-500">
                    ${[
                        `<span class="tabular-nums">${formatarQtd(p.quantidade)} ${esc(p.unidade || 'un')}</span>`,
                        `<span>${p.notas} ${p.notas === 1 ? 'nota' : 'notas'}</span>`,
                        ...fiscal.map(t => `<span>${t}</span>`),
                        ...(p.codigo ? [`<span>cód. ${esc(p.codigo)}</span>`] : []),
                    ].join('<span class="mx-2 text-gray-300 dark:text-slate-600">&bull;</span>')}
                </div>`;
            lista.appendChild(li);
        });

        dashProdVendidosMostrar('dashProdVendidosResultado');
    }

    // ── Mapa interestadual (SVG do Brasil por UF em resources/views/nfe/_mapa-brasil) ──
    let interestDados = null;
    let interestMetrica = 'total';

    function renderInterest(dados) {
        interestDados = dados;

        const temAlgo = (dados.ufs || []).length > 0;
        const resumo = document.getElementById('dashInterestResumo');
        if (!temAlgo) {
            resumo.classList.add('hidden');
            dashInterestMostrar('dashInterestVazio');
            return;
        }
        resumo.classList.remove('hidden');

        document.getElementById('dashInterestMesLabel').textContent = dados.periodo || '';
        document.getElementById('dashInterestUf').textContent = dados.clienteUf || '—';
        document.getElementById('dashInterestTotalCompras').textContent = formatarMoeda(dados.totalCompras || 0);
        document.getElementById('dashInterestTotalVendas').textContent = formatarMoeda(dados.totalVendas || 0);

        pintarInterest();
        dashInterestMostrar('dashInterestResultado');
    }

    function pintarInterest() {
        if (!interestDados) return;

        const porUf = {};
        (interestDados.ufs || []).forEach(u => { porUf[u.uf] = u; });

        const valorDe = u => interestMetrica === 'compras' ? u.compras : interestMetrica === 'vendas' ? u.vendas : u.total;
        const valores = (interestDados.ufs || []).map(valorDe).filter(v => v > 0);
        const maior = valores.length ? Math.max(...valores) : 0;
        const totalMetrica = (interestDados.ufs || []).reduce((s, u) => s + valorDe(u), 0);

        // Mapa (colore cada <path data-uf> do SVG do Brasil)
        document.querySelectorAll('#mapaBrasilSvg path[data-uf]').forEach(path => {
            const uf = path.dataset.uf;
            const u = porUf[uf];
            const v = u ? valorDe(u) : 0;
            const ehCliente = uf === interestDados.clienteUf;
            const intensidade = maior > 0 && v > 0 ? 0.18 + 0.82 * (v / maior) : 0;

            if (ehCliente) {
                path.style.fill = 'var(--color-brand, #0084AA)';
                path.style.fillOpacity = '0.35';
                path.style.stroke = 'var(--color-brand, #0084AA)';
                path.style.strokeWidth = '1.6';
            } else {
                path.style.fill = v > 0 ? `rgba(0,132,170,${intensidade.toFixed(3)})` : 'rgba(148,163,184,0.20)';
                path.style.fillOpacity = '1';
                path.style.stroke = '';
                path.style.strokeWidth = '';
            }

            path.style.cursor = u ? 'pointer' : 'default';
            const titulo = path.querySelector('title');
            if (titulo) {
                const nome = titulo.dataset.nome || (titulo.dataset.nome = titulo.textContent);
                titulo.textContent = u
                    ? `${nome} — ${formatarMoeda(v)}`
                    : (ehCliente ? `${nome} — UF do cliente` : `${nome} — sem operação interestadual`);
            }
        });

        // Lista lateral
        const lista = document.getElementById('dashInterestLista');
        lista.innerHTML = '';
        (interestDados.ufs || [])
            .map(u => ({ uf: u.uf, v: valorDe(u) }))
            .filter(x => x.v > 0)
            .sort((a, b) => b.v - a.v)
            .forEach(x => {
                const li = document.createElement('li');
                li.style.listStyle = 'none';
                li.className = 'flex items-center justify-between gap-3';
                li.innerHTML = `
                    <span class="flex items-center gap-2 text-gray-700 dark:text-slate-300">
                        <span class="inline-block w-2.5 h-2.5 rounded-sm" style="background: rgba(0,132,170,${(0.18 + 0.82 * (maior > 0 ? x.v / maior : 0)).toFixed(3)})"></span>
                        ${x.uf}
                    </span>
                    <span class="font-semibold text-gray-900 dark:text-white tabular-nums">${formatarMoeda(x.v)}</span>`;
                lista.appendChild(li);
            });

        document.getElementById('dashInterestTotalGeral').textContent = formatarMoeda(totalMetrica);
    }

    document.querySelectorAll('.btn-interest-metrica').forEach(btn => {
        btn.addEventListener('click', function () {
            interestMetrica = this.dataset.metrica;
            document.querySelectorAll('.btn-interest-metrica').forEach(b => {
                const ativo = b === this;
                b.classList.toggle('bg-brand', ativo);
                b.classList.toggle('text-white', ativo);
                b.classList.toggle('bg-white', !ativo);
                b.classList.toggle('dark:bg-slate-700', !ativo);
                b.classList.toggle('text-gray-500', !ativo);
                b.classList.toggle('dark:text-slate-300', !ativo);
            });
            pintarInterest();
        });
    });

    async function carregarDash(rota, render, mostrar, prefixo, clienteId) {
        mostrar(`${prefixo}Loading`);
        try {
            const resp = await fetch(rota, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ cliente_id: clienteId, data_inicio: dataInicio.value, data_fim: dataFim.value }),
            });
            const dados = await resp.json();
            if (!resp.ok || !dados.success) throw new Error(dados.error || 'Falha ao gerar o dashboard.');
            render(dados);
        } catch (e) {
            mostrar(`${prefixo}Vazio`);
            console.error('[dashboards]', e);
        }
    }

    // Roda os dois dashboards para a empresa + período atuais da busca de notas.
    let dashboardsLiberados = false;

    async function gerarDashboards() {
        const clienteId = selectCliente.value;
        if (!clienteId || !dataInicio.value || !dataFim.value) return;

        dashboardsLiberados = true;
        dashAviso.classList.add('hidden');
        dashCards.classList.remove('hidden');

        await Promise.all([
            carregarDash('{{ route('nfe.dashboards.fornecedores-simples') }}', renderFornSimples, dashFornSimplesMostrar, 'dashFornSimples', clienteId),
            carregarDash('{{ route('nfe.dashboards.produtos-vendidos') }}', renderProdVendidos, dashProdVendidosMostrar, 'dashProdVendidos', clienteId),
            carregarDash('{{ route('nfe.dashboards.interestadual') }}', renderInterest, dashInterestMostrar, 'dashInterest', clienteId),
        ]);
    }

    // Se o período mudar depois de já ter buscado, regenera os dashboards.
    [dataInicio, dataFim].forEach(el => el.addEventListener('change', () => {
        if (dashboardsLiberados) gerarDashboards();
    }));

})();
</script>
@endpush
