@extends('layouts.internal')

@section('title', 'Consultar declarações — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-magnifying-glass"></i> Consultar declarações</h1>
            <p class="text-gray-700 dark:text-gray-300">Histórico do PGDASD já transmitido, status do DAS, extrato e RBT12 por período.</p>
        </div>

        {{-- ─── Consultar declarações do cliente (histórico + RBT12) ───────────── --}}
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

    @push('scripts')
    <script>
        window.PGDASD_TRIBUTOS = @json($nomesTributos);
    </script>
    @include('simples-nacional._shared')
    <script>
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
    </script>
    @endpush
@endsection
