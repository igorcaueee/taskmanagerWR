@extends('layouts.internal')

@section('title', 'NF-e / CT-e — Distribuição DFe')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    {{-- Cabeçalho --}}
    <div class="mb-6">
        <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2 mt-1">
            <i class="fa-solid fa-truck-ramp-box text-[#0084aa]"></i>
            NF-e / CT-e — Distribuição DFe
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Consulte e baixe XMLs de NF-e e CT-e diretamente do webservice nacional (NFeDistribuicaoDFe), usando o certificado já cadastrado na tela de NFS-e.</p>
    </div>

    {{-- ─── Webservice de contabilistas (SEFAZ-RS) — NF-e e NFC-e ──────────── --}}
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice text-[#0084aa]"></i> Busca via Contabilidade (SEFAZ-RS)
                </h2>
                <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
                    Usa o certificado digital da própria contabilidade (webservice de contabilistas da SEFAZ-RS) para trazer NF-e e NFC-e de qualquer cliente que tenha autorizado o acesso via e-CAC — sem precisar do certificado individual de cada empresa.
                </p>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300 cursor-pointer whitespace-nowrap">
                <input type="checkbox" id="checkModoRs" class="rounded text-[#0084aa]">
                Usar busca via contabilidade
            </label>
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
                        <select id="filtroDirecao"
                                class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                            <option value="">Entradas e saídas</option>
                            <option value="saida">Somente saídas</option>
                            <option value="entrada">Somente entradas</option>
                        </select>
                        <select id="filtroTipo"
                                class="text-xs border border-gray-200 dark:border-slate-600 rounded-lg px-2.5 py-1.5 bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                            <option value="">Todos os tipos</option>
                            <option value="nfe">NF-e</option>
                            <option value="nfce">NFC-e</option>
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

    // ─── Busca via contabilidade (SEFAZ-RS) ────────────────────────────────────
    const checkModoRs     = document.getElementById('checkModoRs');
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
    const filtroDirecao  = document.getElementById('filtroDirecao');

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

    // Saída = documento emitido pela própria empresa selecionada; entrada = emitido por terceiros.
    function direcaoDoc(doc) {
        if (!clienteCnpj) return null;
        return soDigitos(doc.emitenteDoc) === clienteCnpj ? 'saida' : 'entrada';
    }

    function docsFiltrados() {
        let docs = docsAtuais;

        if (filtroTipo.value) {
            docs = docs.filter(d => d.tipo === filtroTipo.value);
        }

        if (filtroDirecao.value) {
            docs = docs.filter(d => direcaoDoc(d) === filtroDirecao.value);
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

    filtroTipo.addEventListener('change', limparSelecaoAoFiltrar);

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
        clienteCnpj = soDigitos(this.options[this.selectedIndex]?.dataset.cnpj);

        esconderTodosEstados();
        estadoInicial.classList.remove('hidden');

        if (!clienteId) {
            cardFiltro.classList.add('hidden');
            certStatus.classList.add('hidden');
            return;
        }

        cardFiltro.classList.remove('hidden');

        if (checkModoRs.checked) {
            certStatus.classList.add('hidden'); // status do certificado é o da contabilidade, já exibido acima
            return;
        }

        await carregarStatusCertificado(clienteId);
    });

    checkModoRs.addEventListener('change', function () {
        certStatus.classList.add('hidden');
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

            document.getElementById('loadingTempo').textContent = `${labelProgresso}... (parte ${chunks})`;

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
            : 'Sincronizando com o Ambiente Nacional (NFeDistribuicaoDFe)...';
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
                const aviso = await sincronizarFaseAteConcluir(
                    '/nfe/sincronizar-chunk',
                    bodyBase,
                    'Buscando NF-e/CT-e'
                );
                if (aviso) avisos.push(aviso);
            }

            document.getElementById('loadingTempo').textContent = 'Carregando resultados...';

            const url = checkModoRs.checked ? '/nfe/rs/buscar' : '/nfe/buscar';
            const data = await chamarChunk(url, {
                cliente_id:  clienteId,
                data_inicio: dataInicio.value,
                data_fim:    dataFim.value,
            });

            esconderTodosEstados();

            docsAtuais = data.documentos ?? [];
            selecionados.clear();

            if (avisos.length > 0) {
                Swal.fire({ icon: 'warning', title: 'Sincronização parcial', text: avisos.join(' '), timer: 8000, timerProgressBar: true });
            }

            if (docsAtuais.length === 0) {
                estadoVazio.classList.remove('hidden');
                return;
            }

            filtroTipo.value = '';
            filtroDirecao.value = '';
            paginaAtual = 1;
            estadoResultados.classList.remove('hidden');
            renderizarPaginaAtual();

        } catch (e) {
            esconderTodosEstados();
            estadoErro.classList.remove('hidden');
            const msg = e.name === 'AbortError'
                ? 'Tempo limite excedido numa das etapas da sincronização. Tente novamente em instantes.'
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

            const marcado = selecionados.has(String(nsu));

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors';
            tr.dataset.tipo  = tipo;
            tr.dataset.valor = doc.valor ?? 0;
            tr.innerHTML = `
                <td class="px-4 py-3">
                    <input type="checkbox" class="check-doc rounded text-[#0084aa]" data-nsu="${nsu}" ${!temXml ? 'disabled' : ''} ${marcado ? 'checked' : ''}>
                </td>
                <td class="px-4 py-3 whitespace-nowrap">${tipoBadge}${direcaoBadge}${origemBadge}</td>
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-slate-200">${numero}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">${data}</td>
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
    btnDownloadZip.addEventListener('click', async function () {
        const items = [...selecionados].map(nsu => {
            const doc = docsAtuais.find(d => String(d.nsu) === nsu);
            return doc?.xmlContent ? { nsu, xml: doc.xmlContent } : null;
        }).filter(Boolean);

        if (!items.length) {
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
                body: JSON.stringify({ items, nome: nomeEmpresa }),
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
