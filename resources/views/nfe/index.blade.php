@extends('layouts.internal')

@section('title', 'NF-e / CT-e — Distribuição DFe')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    {{-- Cabeçalho --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-truck-ramp-box text-[#0084aa]"></i>
            NF-e / CT-e — Distribuição DFe
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Consulte e baixe XMLs de NF-e e CT-e diretamente do webservice nacional (NFeDistribuicaoDFe), usando o certificado já cadastrado na tela de NFS-e.</p>
    </div>

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

                <button type="button" id="btnBuscar"
                        class="w-full mt-3 py-2.5 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span id="btnBuscarLabel">Buscar NF-e / CT-e</span>
                </button>
            </div>

    </div>

    {{-- ─── Painel de resultados ────────────────────────────── --}}
    <div>

            {{-- Estado inicial --}}
            <div id="estadoInicial" class="h-64 flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <i class="fa-solid fa-truck-ramp-box text-5xl mb-3 opacity-30"></i>
                <p class="text-sm">Selecione uma empresa e o período para buscar as NF-e/CT-e.</p>
            </div>

            {{-- Loading --}}
            <div id="estadoLoading" class="hidden h-64 flex flex-col items-center justify-center text-[#0084aa] bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <svg class="animate-spin h-10 w-10 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <p class="text-sm text-gray-500 dark:text-slate-400">Consultando o Ambiente Nacional (NFeDistribuicaoDFe)...</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Isso pode levar alguns segundos.</p>
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
                <p class="text-sm">Nenhuma NF-e/CT-e encontrada no período selecionado.</p>
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
                    </div>
                    <div class="flex items-center gap-2">
                        <select id="filtroTipo"
                                class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                            <option value="">Todos os tipos</option>
                            <option value="nfe">NF-e</option>
                            <option value="cte">CT-e</option>
                        </select>
                        <button type="button" id="btnDownloadZip"
                                class="hidden items-center gap-1.5 px-3 py-1.5 bg-[#0084aa] hover:bg-[#006e8e] text-white text-xs font-semibold rounded-lg transition-colors">
                            <i class="fa-solid fa-file-zipper"></i>
                            Baixar selecionados (.zip)
                        </button>
                    </div>
                </div>

                {{-- Tabela --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr id="rowTotalValor" class="hidden border-b border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/40">
                                <td colspan="5" class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">Total dos documentos visíveis</td>
                                <td class="px-4 py-2 text-right text-sm font-bold text-[#0084aa]" id="totalValor"></td>
                                <td colspan="2"></td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-slate-700 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">
                                <th class="px-4 py-3 text-left w-8"></th>
                                <th class="px-4 py-3 text-left">Tipo</th>
                                <th class="px-4 py-3 text-left">Número</th>
                                <th class="px-4 py-3 text-left">Data Emissão</th>
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

</div>
@endsection

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const selectCliente = document.getElementById('selectCliente');
    const cardFiltro     = document.getElementById('cardFiltro');
    const certStatus     = document.getElementById('certStatus');

    const certOk      = document.getElementById('certOk');
    const certAlert   = document.getElementById('certAlert');
    const certExpired = document.getElementById('certExpired');
    const certNone    = document.getElementById('certNone');

    const dataInicio = document.getElementById('dataInicio');
    const dataFim    = document.getElementById('dataFim');
    const btnBuscar  = document.getElementById('btnBuscar');

    const estadoInicial    = document.getElementById('estadoInicial');
    const estadoLoading    = document.getElementById('estadoLoading');
    const estadoErro       = document.getElementById('estadoErro');
    const estadoVazio      = document.getElementById('estadoVazio');
    const estadoResultados = document.getElementById('estadoResultados');

    const tabelaDocs     = document.getElementById('tabelaDocs');
    const totalDocs      = document.getElementById('totalDocs');
    const checkTodos     = document.getElementById('checkTodos');
    const btnDownloadZip = document.getElementById('btnDownloadZip');
    const filtroTipo     = document.getElementById('filtroTipo');

    let docsAtuais  = [];
    let paginaAtual = 1;
    const porPagina = 50;
    const selecionados = new Set(); // nsu (string) dos documentos marcados, persiste entre páginas

    const btnPaginaAnterior = document.getElementById('btnPaginaAnterior');
    const btnPaginaProxima  = document.getElementById('btnPaginaProxima');
    const paginaInfo        = document.getElementById('paginaInfo');

    function docsFiltrados() {
        const tipo = filtroTipo.value;
        return tipo ? docsAtuais.filter(d => d.tipo === tipo) : docsAtuais;
    }

    function atualizarResumo() {
        const filtrados = docsFiltrados();
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

    filtroTipo.addEventListener('change', function () {
        paginaAtual = 1;
        renderizarPaginaAtual();
    });

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

        esconderTodosEstados();
        estadoInicial.classList.remove('hidden');

        if (!clienteId) {
            cardFiltro.classList.add('hidden');
            certStatus.classList.add('hidden');
            return;
        }

        cardFiltro.classList.remove('hidden');
        await carregarStatusCertificado(clienteId);
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
        btnBuscar.disabled = true;
        document.getElementById('btnBuscarLabel').textContent = 'Buscando...';

        try {
            const controller = new AbortController();
            const timeoutId  = setTimeout(() => controller.abort(), 300_000); // 5 min

            const resp = await fetch('/nfe/buscar', {
                method: 'POST',
                signal: controller.signal,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    cliente_id:  clienteId,
                    data_inicio: dataInicio.value,
                    data_fim:    dataFim.value,
                }),
            });
            clearTimeout(timeoutId);

            const texto = await resp.text();
            let data;
            try {
                data = JSON.parse(texto);
            } catch (_) {
                console.error('[NF-e] Resposta não-JSON recebida:', texto.substring(0, 1000));
                esconderTodosEstados();
                estadoErro.classList.remove('hidden');
                document.getElementById('erroMsg').textContent =
                    'Resposta inválida do servidor (não-JSON). Verifique o console do navegador (F12) para detalhes.';
                return;
            }

            esconderTodosEstados();

            if (!resp.ok || data.error) {
                estadoErro.classList.remove('hidden');
                document.getElementById('erroMsg').textContent = data.error ?? 'Erro desconhecido.';
                return;
            }

            docsAtuais = data.documentos ?? [];
            selecionados.clear();

            if (docsAtuais.length === 0) {
                estadoVazio.classList.remove('hidden');
                return;
            }

            filtroTipo.value = '';
            paginaAtual = 1;
            estadoResultados.classList.remove('hidden');
            renderizarPaginaAtual();

        } catch (e) {
            esconderTodosEstados();
            estadoErro.classList.remove('hidden');
            const msg = e.name === 'AbortError'
                ? 'Tempo limite excedido (5 min). Tente novamente em instantes.'
                : 'Erro de comunicação: ' + e.message;
            document.getElementById('erroMsg').textContent = msg;
        } finally {
            btnBuscar.disabled = false;
            document.getElementById('btnBuscarLabel').textContent = 'Buscar NF-e / CT-e';
        }
    }

    // ─── Tabela de resultados ────────────────────────────────────────────────
    function renderizarTabela(docs) {
        tabelaDocs.innerHTML = '';

        docs.forEach((doc) => {
            const nsu    = doc.nsu ?? '';
            const tipo   = doc.tipo ?? 'nfe';
            const numero = doc.numero || `NSU ${nsu}`;
            const data   = doc.dataEmissao ? formatarData(doc.dataEmissao) : '-';
            const valor  = doc.valor != null && doc.valor !== '' ? formatarMoeda(parseFloat(doc.valor)) : '-';
            const temXml = !!doc.xmlContent;

            const tipoBadge = tipo === 'cte'
                ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">CT-e</span>'
                : '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">NF-e</span>';

            const marcado = selecionados.has(String(nsu));

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors';
            tr.dataset.tipo  = tipo;
            tr.dataset.valor = doc.valor ?? 0;
            tr.innerHTML = `
                <td class="px-4 py-3">
                    <input type="checkbox" class="check-doc rounded text-[#0084aa]" data-nsu="${nsu}" ${!temXml ? 'disabled' : ''} ${marcado ? 'checked' : ''}>
                </td>
                <td class="px-4 py-3">${tipoBadge}</td>
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-slate-200">${numero}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">${data}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-slate-300 max-w-[220px] truncate" title="${doc.emitenteNome ?? ''}">${doc.emitenteNome ?? '-'}</td>
                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-slate-200">${valor}</td>
                <td class="px-4 py-3">
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
        } else {
            btnDownloadZip.classList.add('hidden');
            btnDownloadZip.classList.remove('flex');
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

    // ─── Download ZIP (múltiplos, considerando seleção em todas as páginas) ──
    btnDownloadZip.addEventListener('click', async function () {
        const items = [...selecionados].map(nsu => {
            const doc = docsAtuais.find(d => String(d.nsu) === nsu);
            return doc?.xmlContent ? { nsu, xml: doc.xmlContent } : null;
        }).filter(Boolean);

        if (!items.length) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum XML disponível para os documentos selecionados.' });
            return;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando ZIP...';

        try {
            const resp = await fetch('/nfe/xml/zip-xmls', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ items }),
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
            a.download = 'nfe_cte_xmls.zip';
            a.click();
            URL.revokeObjectURL(url);
        } catch {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
            atualizarSelecao();
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

})();
</script>
@endpush
