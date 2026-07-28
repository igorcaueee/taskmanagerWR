@extends('layouts.internal')

@section('title', 'NFS-e Portal Nacional — WR Assessoria')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    {{-- Cabeçalho --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
            <i class="fa-solid fa-file-invoice text-[#0084aa]"></i>
            NFS-e — Portal Nacional
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Consulte e baixe XMLs das Notas Fiscais de Serviço Eletrônicas diretamente do portal nacional do governo.</p>
    </div>

    {{-- ─── Linha de topo: cards de configuração ──────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">


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

                {{-- CNPJ do cliente selecionado --}}
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
                        <span>Nenhum certificado configurado para esta empresa.</span>
                    </div>
                </div>
            </div>

            {{-- Configurar certificado --}}
            <div id="cardCertificado" class="hidden bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide">
                        <i class="fa-solid fa-certificate mr-1 text-[#0084aa]"></i> Certificado Digital
                    </h2>
                    <button type="button" id="btnToggleCert"
                            class="text-xs text-gray-400 dark:text-slate-500 hover:text-[#0084aa] dark:hover:text-[#0084aa] transition-colors bg-transparent border-0 p-0">
                        <i class="fa-solid fa-gear mr-1"></i><span id="btnToggleCertLabel">Configurar</span>
                    </button>
                </div>

                <form id="formCertificado" class="hidden space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Arquivo .pfx / .p12</label>
                        <input type="file" id="certFile" accept=".pfx,.p12"
                               class="w-full text-sm text-gray-700 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#0084aa]/10 file:text-[#0084aa] hover:file:bg-[#0084aa]/20 cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Senha do certificado</label>
                        <div class="relative">
                            <input type="password" id="certSenha" placeholder="••••••••"
                                   class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm pr-9 focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                            <button type="button" id="btnShowSenha"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 bg-transparent border-0 p-0 leading-none">
                                <i class="fa-regular fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ambiente</label>
                        <select id="certAmbiente"
                                class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084aa]">
                            <option value="producao">Produção</option>
                            <option value="homologacao">Homologação (testes)</option>
                        </select>
                    </div>
                    <button type="button" id="btnSalvarCert"
                            class="w-full py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Salvar certificado
                    </button>
                </form>
            </div>

            {{-- Filtro de período --}}
            <div id="cardFiltro" class="hidden bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
                <h2 class="font-semibold text-gray-800 dark:text-slate-200 mb-3 text-sm uppercase tracking-wide">
                    <i class="fa-solid fa-calendar-range mr-1 text-[#0084aa]"></i> Período de Busca
                </h2>
                <div class="space-y-3">
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

                    {{-- Atalhos rápidos --}}
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" data-periodo="mes-atual" class="btn-periodo text-xs px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] transition-colors">Mês atual</button>
                        <button type="button" data-periodo="mes-anterior" class="btn-periodo text-xs px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] transition-colors">Mês anterior</button>
                        <button type="button" data-periodo="trimestre" class="btn-periodo text-xs px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] transition-colors">Trimestre</button>
                        <button type="button" data-periodo="ano" class="btn-periodo text-xs px-2.5 py-1 rounded-full border border-gray-300 dark:border-slate-600 text-gray-600 dark:text-slate-400 hover:border-[#0084aa] hover:text-[#0084aa] transition-colors">Ano atual</button>
                    </div>

                    <button type="button" id="btnBuscar"
                            class="w-full py-2.5 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span id="btnBuscarLabel">Buscar NFS-e</span>
                    </button>
                </div>
            </div>

    </div>

    {{-- ─── Painel de resultados (largura total) ───────────────────────────── --}}
    <div>

            {{-- Estado inicial --}}
            <div id="estadoInicial" class="h-64 flex flex-col items-center justify-center text-gray-400 dark:text-slate-600 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <i class="fa-solid fa-file-invoice text-5xl mb-3 opacity-30"></i>
                <p class="text-sm">Selecione uma empresa e o período para buscar as NFS-e.</p>
            </div>

            {{-- Loading --}}
            <div id="estadoLoading" class="hidden h-64 flex flex-col items-center justify-center text-[#0084aa] bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                <svg class="animate-spin h-10 w-10 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <p class="text-sm text-gray-500 dark:text-slate-400">Consultando o Portal Nacional NFS-e...</p>
                <p id="loadingTempo" class="text-xs text-gray-400 dark:text-slate-500 mt-1">Isso pode levar alguns minutos em empresas com muitas notas.</p>
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
                <p class="text-sm">Nenhuma NFS-e encontrada no período selecionado.</p>
            </div>

            {{-- Resultados --}}
            <div id="estadoResultados" class="hidden bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">

                {{-- Toolbar --}}
                <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-sm font-semibold text-gray-800 dark:text-slate-200">
                            <span id="totalNotas">0</span> nota(s) encontrada(s)
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
                            <option value="emitida">Emitida</option>
                            <option value="recebida">Recebida</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                        <button type="button" id="btnDownloadZip"
                                class="hidden items-center gap-1.5 px-3 py-1.5 bg-[#0084aa] hover:bg-[#006e8e] text-white text-xs font-semibold rounded-lg transition-colors">
                            <i class="fa-solid fa-file-zipper"></i>
                            Baixar selecionados (.zip)
                        </button>
                        <button type="button" id="btnExportarExcel"
                                class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-lg transition-colors">
                            <i class="fa-solid fa-file-excel"></i>
                            <span id="btnExportarExcelLabel">Exportar Excel</span>
                        </button>
                    </div>
                </div>

                {{-- Tabela --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr id="rowTotalValor" class="hidden border-b border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/40">
                                <td colspan="5" class="px-4 py-2 text-xs text-gray-500 dark:text-slate-400">Total das notas visíveis</td>
                                <td class="px-4 py-2 text-right text-sm font-bold text-[#0084aa]" id="totalValor"></td>
                                <td colspan="2"></td>
                            </tr>
                            <tr class="border-b border-gray-100 dark:border-slate-700 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">
                                <th class="px-4 py-3 text-left w-8"></th>
                                <th class="px-4 py-3 text-left">Número</th>
                                <th class="px-4 py-3 text-left">Tipo</th>
                                <th class="px-4 py-3 text-left">Data Emissão</th>
                                <th class="px-4 py-3 text-left">Tomador / Prestador</th>
                                <th class="px-4 py-3 text-right">Valor</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-left">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaNotas" class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        </tbody>
                    </table>
                </div>
            </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // Elementos
    const selectCliente   = document.getElementById('selectCliente');
    const clienteCnpj      = document.getElementById('clienteCnpj');
    const clienteCnpjValor = document.getElementById('clienteCnpjValor');
    const cardCertificado = document.getElementById('cardCertificado');
    const cardFiltro      = document.getElementById('cardFiltro');
    const certStatus      = document.getElementById('certStatus');

    const certOk          = document.getElementById('certOk');
    const certAlert       = document.getElementById('certAlert');
    const certExpired     = document.getElementById('certExpired');
    const certNone        = document.getElementById('certNone');

    const formCertificado = document.getElementById('formCertificado');
    const btnToggleCert   = document.getElementById('btnToggleCert');
    const btnToggleCertLabel = document.getElementById('btnToggleCertLabel');
    const btnSalvarCert   = document.getElementById('btnSalvarCert');
    const btnShowSenha    = document.getElementById('btnShowSenha');
    const certSenha       = document.getElementById('certSenha');

    const dataInicio  = document.getElementById('dataInicio');
    const dataFim     = document.getElementById('dataFim');
    const btnBuscar   = document.getElementById('btnBuscar');

    const estadoInicial    = document.getElementById('estadoInicial');
    const estadoLoading    = document.getElementById('estadoLoading');
    const estadoErro       = document.getElementById('estadoErro');
    const estadoVazio      = document.getElementById('estadoVazio');
    const estadoResultados = document.getElementById('estadoResultados');

    const tabelaNotas  = document.getElementById('tabelaNotas');
    const totalNotas   = document.getElementById('totalNotas');
    const checkTodos   = document.getElementById('checkTodos');
    const btnDownloadZip = document.getElementById('btnDownloadZip');
    const filtroTipo   = document.getElementById('filtroTipo');

    let notasAtuais      = [];
    let cnpjClienteAtual = '';

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

    function atualizarResumo() {
        const tipo = filtroTipo.value;
        let visíveis = 0, soma = 0;

        tabelaNotas.querySelectorAll('tr').forEach(tr => {
            const mostrar = !tipo || tr.dataset.tipo === tipo;
            tr.style.display = mostrar ? '' : 'none';
            if (mostrar) {
                visíveis++;
                soma += parseFloat(tr.dataset.valor) || 0;
            }
        });

        totalNotas.textContent = visíveis;

        const rowTotal = document.getElementById('rowTotalValor');
        if (visíveis > 0) {
            document.getElementById('totalValor').textContent = soma.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
            rowTotal.classList.remove('hidden');
        } else {
            rowTotal.classList.add('hidden');
        }
    }

    filtroTipo.addEventListener('change', function () {
        atualizarResumo();
        limparSelecaoAoFiltrar();
    });

    // Ao trocar o filtro, desmarca tudo — evita que notas selecionadas sob um
    // filtro anterior (ex.: "todos os tipos") sejam incluídas no zip/Excel ao
    // gerar com outro filtro aplicado (ex.: "somente emitidas"), já que ficam
    // ocultas mas continuam marcadas no DOM.
    function limparSelecaoAoFiltrar() {
        checkTodos.checked = false;
        tabelaNotas.querySelectorAll('.check-nota:checked').forEach(cb => cb.checked = false);
        atualizarSelecao();
    }

    // ─── Seleção de empresa ──────────────────────────────────────────────────
    selectCliente.addEventListener('change', async function () {
        const clienteId = this.value;

        esconderTodosEstados();
        estadoInicial.classList.remove('hidden');

        // Limpa o form do certificado ao trocar de empresa
        document.getElementById('certFile').value = '';
        certSenha.value = '';
        formCertificado.classList.add('hidden');
        btnToggleCertLabel.textContent = 'Configurar';

        if (!clienteId) {
            cardCertificado.classList.add('hidden');
            cardFiltro.classList.add('hidden');
            certStatus.classList.add('hidden');
            clienteCnpj.classList.add('hidden');
            return;
        }

        const cnpjSelecionado = this.options[this.selectedIndex]?.dataset.cnpj ?? '';
        clienteCnpjValor.textContent = formatarCnpjCpf(cnpjSelecionado);
        clienteCnpj.classList.remove('hidden');

        cardCertificado.classList.remove('hidden');
        cardFiltro.classList.remove('hidden');

        await carregarStatusCertificado(clienteId);
    });

    async function carregarStatusCertificado(clienteId) {
        const resp = await fetch(`/nfse/certificado/${clienteId}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();

        // Resetar status
        certStatus.classList.remove('hidden');
        [certOk, certAlert, certExpired, certNone].forEach(el => el.classList.add('hidden'));

        if (!data.configurado) {
            certNone.classList.remove('hidden');
            // Abre o form automaticamente se nunca configurado
            formCertificado.classList.remove('hidden');
            btnToggleCertLabel.textContent = 'Fechar';
            return;
        }

        // Arquivo registrado no banco mas não existe no disco — precisa reenviar
        if (data.configurado && !data.arquivo_ok) {
            certExpired.classList.remove('hidden');
            certExpired.querySelector('span').innerHTML =
                'Arquivo do certificado <strong>não encontrado no servidor</strong>. Faça o upload novamente.';
            formCertificado.classList.remove('hidden');
            btnToggleCertLabel.textContent = 'Fechar';
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

        btnToggleCertLabel.textContent = 'Atualizar';
        formCertificado.classList.add('hidden');
    }

    // ─── Toggle form certificado ─────────────────────────────────────────────
    btnToggleCert.addEventListener('click', function () {
        const aberto = !formCertificado.classList.contains('hidden');
        if (aberto) {
            formCertificado.classList.add('hidden');
            btnToggleCertLabel.textContent = 'Atualizar';
        } else {
            formCertificado.classList.remove('hidden');
            btnToggleCertLabel.textContent = 'Fechar';
        }
    });

    // ─── Mostrar/esconder senha ──────────────────────────────────────────────
    btnShowSenha.addEventListener('click', function () {
        const visible = certSenha.type === 'text';
        certSenha.type = visible ? 'password' : 'text';
        this.querySelector('i').className = visible ? 'fa-regular fa-eye text-sm' : 'fa-regular fa-eye-slash text-sm';
    });

    // ─── Salvar certificado ──────────────────────────────────────────────────
    btnSalvarCert.addEventListener('click', async function () {
        const clienteId = selectCliente.value;
        const file      = document.getElementById('certFile').files[0];
        const senha     = certSenha.value;
        const ambiente  = document.getElementById('certAmbiente').value;

        if (!clienteId || !file || !senha) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Preencha todos os campos do certificado.' });
            return;
        }

        btnSalvarCert.disabled = true;
        btnSalvarCert.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Salvando...';

        const formData = new FormData();
        formData.append('cliente_id', clienteId);
        formData.append('certificado', file);
        formData.append('senha', senha);
        formData.append('ambiente', ambiente);
        formData.append('_token', CSRF);

        try {
            const resp = await fetch('/nfse/certificado', { method: 'POST', body: formData });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar certificado.' });
                return;
            }

            Swal.fire({
                icon: 'success',
                title: 'Certificado salvo!',
                text: data.vencimento ? `Vence em ${data.vencimento}` : 'Certificado salvo com sucesso.',
                timer: 2500,
                showConfirmButton: false,
            });

            formCertificado.classList.add('hidden');
            btnToggleCertLabel.textContent = 'Atualizar';
            await carregarStatusCertificado(clienteId);
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Falha na comunicação com o servidor.' });
        } finally {
            btnSalvarCert.disabled = false;
            btnSalvarCert.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Salvar certificado';
        }
    });

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

    // Limite de segurança de páginas (chunks) por busca — evita loop infinito
    // em caso de bug de paginação. 400 chunks * 12 lotes = 4800 lotes, bem
    // acima do necessário mesmo para as empresas maiores.
    const NFSE_MAX_CHUNKS = 400;

    /**
     * Busca um chunk de NSUs. Lança Error com mensagem pronta pra exibir em
     * caso de falha (HTTP não-2xx, JSON inválido, ou erro reportado pela API).
     */
    async function buscarChunkNfse(clienteId, dataInicioVal, dataFimVal, nsuInicio) {
        const controller = new AbortController();
        // Cada chunk processa poucos lotes — 90s é folga suficiente e fica
        // abaixo do timeout de proxy/CDN (Cloudflare derruba em ~100s com HTTP 524).
        const timeoutId = setTimeout(() => controller.abort(), 90_000);

        let resp;
        try {
            resp = await fetch('/nfse/buscar', {
                method: 'POST',
                signal: controller.signal,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({
                    cliente_id:  clienteId,
                    data_inicio: dataInicioVal,
                    data_fim:    dataFimVal,
                    nsu_inicio:  nsuInicio,
                }),
            });
        } finally {
            clearTimeout(timeoutId);
        }

        // Lê como texto primeiro para diagnóstico em caso de resposta inválida
        const texto = await resp.text();
        let data;
        try {
            data = JSON.parse(texto);
        } catch (_) {
            console.error('[NFS-e] Resposta não-JSON recebida:', texto.substring(0, 1000));
            throw new Error(`Resposta inválida do servidor (HTTP ${resp.status}, não-JSON). Verifique o console do navegador (F12) para detalhes.`);
        }

        if (!resp.ok || data.error) {
            console.error('[NFS-e] Resposta de erro:', resp.status, data);
            throw new Error(data.error ?? data.message ?? `Erro desconhecido (HTTP ${resp.status}).`);
        }

        return data;
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

        cnpjClienteAtual = selectCliente.options[selectCliente.selectedIndex]?.dataset.cnpj ?? '';

        esconderTodosEstados();
        estadoLoading.classList.remove('hidden');
        btnBuscar.disabled = true;
        document.getElementById('btnBuscarLabel').textContent = 'Buscando...';

        // Contador de tempo decorrido + progresso — a busca é feita em várias
        // chamadas (chunks) pra não estourar o timeout do proxy/CDN, então sem
        // isso a tela parece travada em empresas grandes.
        const loadingTempoEl = document.getElementById('loadingTempo');
        const inicioBusca    = Date.now();
        let notasEncontradasAteAgora = 0;
        const atualizarLoadingTexto = () => {
            const s = Math.floor((Date.now() - inicioBusca) / 1000);
            const m = Math.floor(s / 60);
            const rest = String(s % 60).padStart(2, '0');
            loadingTempoEl.textContent =
                `Buscando há ${m}:${rest}... ${notasEncontradasAteAgora} nota(s) encontrada(s) até agora.`;
        };
        const intervalTempo = setInterval(atualizarLoadingTexto, 1000);

        try {
            let notasAcumuladas       = [];
            const canceledChavesTodas = new Set();
            let nsuAtual              = 0;
            let concluido             = false;
            let chunks                = 0;

            while (!concluido) {
                chunks++;
                if (chunks > NFSE_MAX_CHUNKS) {
                    throw new Error('A busca excedeu o limite de páginas de segurança. Tente reduzir o período pesquisado.');
                }

                const data = await buscarChunkNfse(clienteId, dataInicio.value, dataFim.value, nsuAtual);

                notasAcumuladas = notasAcumuladas.concat(data.notas ?? []);
                (data.canceled_chaves ?? []).forEach(c => canceledChavesTodas.add(c));
                nsuAtual   = data.proximo_nsu ?? nsuAtual;
                concluido  = !!data.concluido;

                notasEncontradasAteAgora = notasAcumuladas.length;
                atualizarLoadingTexto();
            }

            // Cancelamentos podem ter sido encontrados num chunk posterior ao
            // da nota original — reaplica em toda a lista acumulada.
            if (canceledChavesTodas.size > 0) {
                notasAcumuladas.forEach(nota => {
                    if (nota.chaveAcesso && canceledChavesTodas.has(nota.chaveAcesso)) {
                        nota.status = 'CANCELADA';
                    }
                });
            }

            esconderTodosEstados();
            notasAtuais = notasAcumuladas;

            if (notasAtuais.length === 0) {
                estadoVazio.classList.remove('hidden');
                return;
            }

            filtroTipo.value = '';
            renderizarTabela(notasAtuais, cnpjClienteAtual);
            estadoResultados.classList.remove('hidden');
            atualizarResumo();

        } catch (e) {
            esconderTodosEstados();
            estadoErro.classList.remove('hidden');
            const msg = e.name === 'AbortError'
                ? 'Tempo limite excedido numa das páginas da busca. Tente novamente em instantes.'
                : e.message;
            document.getElementById('erroMsg').textContent = msg;
        } finally {
            clearInterval(intervalTempo);
            btnBuscar.disabled = false;
            document.getElementById('btnBuscarLabel').textContent = 'Buscar NFS-e';
        }
    }

    // ─── Tabela de resultados ────────────────────────────────────────────────
    function renderizarTabela(notas, clienteCnpj = '') {
        tabelaNotas.innerHTML = '';

        const cnpjCliente = clienteCnpj.replace(/\D/g, '');

        // Municípios com sistema Tecnos — PDF via SOAP (não via Portal Nacional)
        // Chave de acesso do Portal Nacional começa com 7 dígitos do IBGE do emitente
        const IBGE_TECNOS    = ['4321451']; // Teutônia-RS
        const NOMES_TECNOS   = ['TEUTONIA', 'TEUTONIA/RS'];

        const normalizarCidade = (s) => (s ?? '').toUpperCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '').trim();

        notas.forEach((nota) => {
            const nsu         = nota.nsu ?? '';
            const chave       = nota.chaveAcesso ?? '';
            const numero      = nota.numero ?? `NSU ${nsu}`;
            const numeroNfse  = nota.numero ?? '';
            const data        = nota.dataEmissao ? formatarData(nota.dataEmissao) : '-';
            const valor       = nota.valorServico != null ? formatarMoeda(nota.valorServico) : '-';
            const status      = renderStatus(nota.status);
            const temNsu      = !!nsu;

            // Determina se a nota foi emitida ou recebida pelo cliente
            const cnpjPrest  = (nota.cnpjPrestador ?? '').replace(/\D/g, '');
            const isEmitida  = cnpjPrest && cnpjCliente && cnpjPrest === cnpjCliente;
            const isRecebida = cnpjPrest && cnpjCliente && cnpjPrest !== cnpjCliente;
            const tipoBadge  = isEmitida
                ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Emitida</span>'
                : isRecebida
                ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Recebida</span>'
                : '<span class="text-gray-400 text-xs">—</span>';

            // Para nota recebida mostra o prestador; para emitida mostra o tomador
            const contraparte = isRecebida
                ? (nota.prestadorNome ?? nota.tomadorNome ?? '-')
                : (nota.tomadorNome ?? '-');

            // Detecta Teutônia: pelo IBGE nos primeiros 7 dígitos da chave (mais confiável)
            // ou pelo nome da cidade no XML (fallback)
            const ibgeChave   = chave.substring(0, 7);
            const locEmi      = normalizarCidade(nota.xLocEmi);
            const isTecnos    = (IBGE_TECNOS.includes(ibgeChave) || NOMES_TECNOS.includes(locEmi))
                                && !!nota.cnpjPrestador && !!nota.imPrestador && !!numeroNfse;
            const temDanfse   = isTecnos || !!chave;

            const isCancelada = nota.status === 'CANCELADA';
            const tipoKey = isCancelada ? 'cancelada' : isEmitida ? 'emitida' : isRecebida ? 'recebida' : '';

            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors';
            tr.dataset.tipo  = tipoKey;
            tr.dataset.valor = nota.valorServico ?? 0;
            tr.innerHTML = `
                <td class="px-4 py-3">
                    <input type="checkbox" class="check-nota rounded text-[#0084aa]" data-nsu="${nsu}" ${!temNsu ? 'disabled' : ''}>
                </td>
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-slate-200">${numero}</td>
                <td class="px-4 py-3">${tipoBadge}</td>
                <td class="px-4 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">${data}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-slate-300 max-w-[180px] truncate" title="${contraparte}">${contraparte}</td>
                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-slate-200">${valor}</td>
                <td class="px-4 py-3 text-center">${status}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-1">
                        ${temNsu ? `
                        <button type="button"
                                class="btn-download-xml p-1.5 text-gray-400 dark:text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 bg-transparent border-0 transition-colors"
                                title="Baixar XML"
                                data-nsu="${nsu}">
                            <i class="fa-solid fa-file-code"></i>
                        </button>` : ''}
                        ${temDanfse ? `
                        <button type="button"
                                class="btn-danfse p-1.5 text-gray-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 bg-transparent border-0 transition-colors"
                                title="${isTecnos ? 'Visualizar DANFSE — Tecnos Teutônia' : 'Visualizar DANFSE (PDF)'}"
                                data-chave="${chave}"
                                data-is-tecnos="${isTecnos ? '1' : '0'}"
                                data-cnpj="${nota.cnpjPrestador ?? ''}"
                                data-im="${nota.imPrestador ?? ''}"
                                data-numero="${numeroNfse}">
                            <i class="fa-solid fa-file-pdf"></i>
                        </button>` : ''}
                    </div>
                </td>
            `;
            tabelaNotas.appendChild(tr);
        });

        // Eventos de download individual
        tabelaNotas.querySelectorAll('.btn-download-xml').forEach(btn => {
            btn.addEventListener('click', () => downloadXml(btn.dataset.nsu));
        });

        // Eventos DANFSE — usa Tecnos ou Portal Nacional conforme o município
        tabelaNotas.querySelectorAll('.btn-danfse').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.dataset.isTecnos === '1') {
                    abrirDanfseTecnos(btn.dataset.cnpj, btn.dataset.im, btn.dataset.numero, btn);
                } else {
                    abrirDanfse(btn.dataset.chave, btn);
                }
            });
        });

        // Eventos de checkbox
        tabelaNotas.querySelectorAll('.check-nota').forEach(cb => {
            cb.addEventListener('change', atualizarSelecao);
        });
    }

    function renderStatus(status) {
        if (!status) return '<span class="text-gray-400 text-xs">—</span>';

        const mapa = {
            'AUTORIZADA': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'CANCELADA':  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
            'SUBSTITUIDA': 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-slate-400',
        };

        const cls = mapa[status?.toUpperCase()] ?? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        return `<span class="text-xs font-semibold px-2 py-0.5 rounded-full ${cls}">${status}</span>`;
    }

    function formatarData(str) {
        if (!str) return '-';
        const d = str.substring(0, 10).split('-');
        return `${d[2]}/${d[1]}/${d[0]}`;
    }

    function formatarMoeda(val) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
    }

    // ─── Seleção múltipla ────────────────────────────────────────────────────
    checkTodos.addEventListener('change', function () {
        tabelaNotas.querySelectorAll('tr').forEach(tr => {
            if (tr.style.display === 'none') return;
            const cb = tr.querySelector('.check-nota:not([disabled])');
            if (cb) cb.checked = this.checked;
        });
        atualizarSelecao();
    });

    function atualizarSelecao() {
        const selecionadas = [...tabelaNotas.querySelectorAll('.check-nota:checked')];
        if (selecionadas.length > 0) {
            btnDownloadZip.classList.remove('hidden');
            btnDownloadZip.classList.add('flex');
            btnDownloadZip.innerHTML = `<i class="fa-solid fa-file-zipper"></i> Baixar ${selecionadas.length} XML(s) (.zip)`;
            const labelEl = document.getElementById('btnExportarExcelLabel');
            if (labelEl) labelEl.textContent = `Exportar ${selecionadas.length} nota(s) (.xlsx)`;
        } else {
            btnDownloadZip.classList.add('hidden');
            btnDownloadZip.classList.remove('flex');
            const labelEl = document.getElementById('btnExportarExcelLabel');
            if (labelEl) labelEl.textContent = 'Exportar Excel';
        }
    }

    // ─── DANFSE (PDF) — GET /danfse/{chaveAcesso} ────────────────────────────
    async function abrirDanfse(chaveAcesso, btn) {
        const clienteId = selectCliente.value;
        const iconOrig  = btn.innerHTML;

        btn.disabled  = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-lg"></i>';

        try {
            const resp = await fetch(
                `/nfse/danfse?cliente_id=${clienteId}&chave_acesso=${encodeURIComponent(chaveAcesso)}`,
                { headers: { 'Accept': 'application/pdf,application/json' } }
            );

            if (!resp.ok) {
                const data = await resp.json().catch(() => ({}));
                const msg  = data.error ?? 'Falha ao gerar DANFSE.';
                const is502 = msg.includes('502');
                Swal.fire({
                    icon: 'warning',
                    title: is502 ? 'DANFSE indisponível' : 'Erro',
                    html: is502
                        ? 'O servidor do governo não conseguiu gerar o DANFSE para esta nota.<br><br>'
                          + '<small class="text-gray-500">Possíveis causas: NFS-e emitida por sistema municipal próprio (o DANFSE não fica no Portal Nacional), '
                          + 'ou a API do governo está instável. Tente baixar o XML e usar o visualizador da prefeitura.</small>'
                        : msg,
                });
                return;
            }

            const blob = await resp.blob();
            const url  = URL.createObjectURL(blob);

            // Abre o PDF em nova aba (o navegador exibe nativamente)
            window.open(url, '_blank');

            // Libera memória após 60s
            setTimeout(() => URL.revokeObjectURL(url), 60_000);
        } catch {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            btn.disabled  = false;
            btn.innerHTML = iconOrig;
        }
    }

    // ─── DANFSE via Tecnos Municipal (Teutônia) ──────────────────────────────
    async function abrirDanfseTecnos(cnpj, im, numero, btn) {
        const iconOrig = btn.innerHTML;
        btn.disabled  = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-lg"></i>';

        try {
            const resp = await fetch(
                `/nfse/danfse-tecnos?cnpj=${encodeURIComponent(cnpj)}&im=${encodeURIComponent(im)}&numero=${encodeURIComponent(numero)}`,
                { headers: { 'Accept': 'application/json' } }
            );

            const data = await resp.json().catch(() => ({}));

            if (!resp.ok) {
                Swal.fire({ icon: 'error', title: 'Erro ao buscar DANFSE', text: data.error ?? 'Falha ao consultar o sistema Tecnos.' });
                return;
            }

            if (data.url) {
                window.open(data.url, '_blank');
            } else {
                Swal.fire({ icon: 'warning', title: 'Sem link', text: 'O sistema Tecnos não retornou um link para esta nota.' });
            }
        } catch {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            btn.disabled  = false;
            btn.innerHTML = iconOrig;
        }
    }

    // ─── Download XML individual (por NSU → GET /DFe/{NSU}?lote=false) ───────
    async function downloadXml(nsu) {
        const clienteId = selectCliente.value;

        const resp = await fetch(`/nfse/xml/download?cliente_id=${clienteId}&nsu=${encodeURIComponent(nsu)}`, {
            headers: { 'Accept': 'application/xml,application/json' }
        });

        if (!resp.ok) {
            const data = await resp.json().catch(() => ({}));
            Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao baixar XML.' });
            return;
        }

        const blob = await resp.blob();
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `nfse_nsu${nsu}.xml`;
        a.click();
        URL.revokeObjectURL(url);
    }

    // ─── Download ZIP (múltiplos NSUs) ───────────────────────────────────────
    btnDownloadZip.addEventListener('click', async function () {
        const clienteId  = selectCliente.value;
        const checkboxes = [...tabelaNotas.querySelectorAll('.check-nota:checked')];

        // Monta lista de {nsu, xml} usando o cache da busca (sem re-fetch da API)
        const items = checkboxes.map(cb => {
            const nsu  = parseInt(cb.dataset.nsu);
            const nota = notasAtuais.find(n => n.nsu == nsu);
            return nota?.xmlContent ? { nsu, xml: nota.xmlContent } : null;
        }).filter(Boolean);

        if (!items.length) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum XML disponível para as notas selecionadas.' });
            return;
        }

        const nomeEmpresa = selectCliente.options[selectCliente.selectedIndex]?.text?.trim() || 'NFS-e';

        this.disabled = true;
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando ZIP...';

        try {
            const resp = await fetch('/nfse/xml/zip-xmls', {
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

    // ─── Exportar Excel ──────────────────────────────────────────────────────
    const btnExportarExcel = document.getElementById('btnExportarExcel');

    function resetarBotaoExcel(label) {
        btnExportarExcel.disabled = false;
        btnExportarExcel.innerHTML = `<i class="fa-solid fa-file-excel"></i> <span id="btnExportarExcelLabel">${label}</span>`;
    }

    btnExportarExcel.addEventListener('click', async function () {
        const clienteId = selectCliente.value;
        if (!clienteId || !notasAtuais.length) return;

        const selecionadasNsus = [...tabelaNotas.querySelectorAll('.check-nota:checked')].map(cb => parseInt(cb.dataset.nsu));
        const nsusVisiveis = new Set(
            [...tabelaNotas.querySelectorAll('tr')]
                .filter(tr => tr.style.display !== 'none')
                .map(tr => tr.querySelector('.check-nota')?.dataset.nsu)
                .filter(Boolean)
                .map(Number)
        );
        const notasParaExportar = selecionadasNsus.length > 0
            ? notasAtuais.filter(n => selecionadasNsus.includes(n.nsu))
            : notasAtuais.filter(n => n.xmlContent && nsusVisiveis.has(n.nsu));

        if (!notasParaExportar.length) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhuma nota com XML disponível para exportar.' });
            return;
        }

        const nomeEmpresa   = selectCliente.options[selectCliente.selectedIndex]?.text?.trim() ?? 'NFS-e';
        const labelOriginal = document.getElementById('btnExportarExcelLabel')?.textContent ?? 'Exportar Excel';

        // Monta arrays paralelos de XMLs e statuses (índice → status para o servidor corrigir cStat)
        const xmls     = notasParaExportar.map(n => n.xmlContent);
        const statuses = {};
        notasParaExportar.forEach((n, i) => { statuses[i] = n.status ?? ''; });

        btnExportarExcel.disabled = true;
        btnExportarExcel.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> Gerando ${notasParaExportar.length} nota(s)...`;

        try {
            const resp = await fetch('/nfse/exportar-excel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ xmls, statuses, nome: nomeEmpresa, cnpj_cliente: cnpjClienteAtual }),
            });

            if (!resp.ok) {
                const data = await resp.json().catch(() => ({}));
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao gerar Excel.' });
                return;
            }

            const blob = await resp.blob();
            const url  = URL.createObjectURL(blob);
            const a    = document.createElement('a');
            a.href     = url;
            a.download = `${nomeEmpresa} - NFS-e.xlsx`;
            a.click();
            URL.revokeObjectURL(url);
        } catch {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            resetarBotaoExcel(labelOriginal);
        }
    });

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function esconderTodosEstados() {
        [estadoInicial, estadoLoading, estadoErro, estadoVazio, estadoResultados].forEach(el => {
            el.classList.add('hidden');
        });
    }

    // Pré-preencher datas com mês atual
    const hoje  = new Date();
    dataInicio.value = formatDate(new Date(hoje.getFullYear(), hoje.getMonth(), 1));
    dataFim.value    = formatDate(hoje);

})();
</script>
@endpush
