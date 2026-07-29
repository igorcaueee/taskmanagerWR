@extends('layouts.internal')

@section('title', 'Caixa Postal — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-envelope"></i> Caixa Postal</h1>
            <p class="text-gray-700 dark:text-gray-300">Mensagens da Receita Federal (e-CAC) do cliente e situação de enquadramento no DTE, sem precisar checar manualmente.</p>
        </div>

        <div id="cardCaixaPostal" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-envelope text-brand"></i> Caixa Postal do cliente
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                Selecione o cliente para ver o indicador rápido de mensagens novas ou a lista completa de mensagens da caixa postal na Receita Federal.
            </p>

            <div class="flex flex-wrap gap-3 items-end mb-4">
                <div class="flex-1 min-w-60">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                    <select id="selectClienteCaixaPostal"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="btnVerIndicador"
                        class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                    Ver indicador
                </button>
                <button type="button" id="btnVerDte"
                        class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                    Ver situação DTE
                </button>
                <button type="button" id="btnBuscarMensagens"
                        class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    Buscar mensagens
                </button>
            </div>

            <div id="indicadorResultado" class="hidden mb-3 text-sm rounded-lg px-3 py-2"></div>

            <div id="caixaPostalErro" class="hidden text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mb-3"></div>

            <div id="caixaPostalTabelaWrapper" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assunto</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Origem</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Enviada em</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Situação</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ver</th>
                        </tr>
                    </thead>
                    <tbody id="caixaPostalTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-sm text-gray-700 dark:text-gray-300"></tbody>
                </table>

                <button type="button" id="btnCarregarMaisMensagens" class="hidden mt-3 py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                    Carregar mais
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    const selectClienteCaixaPostal    = document.getElementById('selectClienteCaixaPostal');
    const btnVerIndicador             = document.getElementById('btnVerIndicador');
    const btnVerDte                   = document.getElementById('btnVerDte');
    const btnBuscarMensagens          = document.getElementById('btnBuscarMensagens');
    const indicadorResultado          = document.getElementById('indicadorResultado');
    const caixaPostalErro             = document.getElementById('caixaPostalErro');
    const caixaPostalTabelaWrapper    = document.getElementById('caixaPostalTabelaWrapper');
    const caixaPostalTabelaBody       = document.getElementById('caixaPostalTabelaBody');
    const btnCarregarMaisMensagens    = document.getElementById('btnCarregarMaisMensagens');

    let ponteiroProximaPagina = null;

    function situacaoMensagemBadge(msg) {
        const lida = !!msg.dataLeitura;
        const cor = lida ? 'bg-gray-100 text-gray-700' : 'bg-blue-100 text-blue-800';
        const texto = lida ? 'Lida' : 'Não lida';
        return `<span class="px-2 py-1 rounded text-xs font-medium ${cor}">${escapeHtml(texto)}</span>`;
    }

    btnVerIndicador.addEventListener('click', async function () {
        const clienteId = selectClienteCaixaPostal.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente.' });
            return;
        }

        this.disabled = true;
        indicadorResultado.classList.add('hidden');

        try {
            const url = new URL('{{ route('simples-nacional.caixa-postal.indicador') }}');
            url.searchParams.set('cliente_id', clienteId);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            indicadorResultado.classList.remove('hidden');
            indicadorResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-blue-700', 'dark:text-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                indicadorResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                indicadorResultado.textContent = data.error ?? 'Falha ao consultar indicador.';
                return;
            }

            const valor = String(data.indicador ?? '0');
            const mapa = { '0': 'Nenhuma mensagem nova', '1': 'Uma mensagem nova', '2': 'Mais de uma mensagem nova' };

            if (valor === '0') {
                indicadorResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
            } else {
                indicadorResultado.classList.add('text-blue-700', 'dark:text-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
            }
            indicadorResultado.textContent = mapa[valor] ?? `Indicador: ${valor}`;
        } catch (e) {
            indicadorResultado.classList.remove('hidden');
            indicadorResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            indicadorResultado.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
        }
    });

    const TEXTOS_INDICADOR_DTE = {
        '-2': 'CNPJ inválido',
        '-1': 'Não participante do DTE',
        '0': 'Participante do DTE',
        '1': 'Participante do DTE-SN (Simples Nacional)',
        '2': 'Participante do DTE e do DTE-SN',
    };

    btnVerDte.addEventListener('click', async function () {
        const clienteId = selectClienteCaixaPostal.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente.' });
            return;
        }

        this.disabled = true;
        const textoOriginal = this.textContent;
        this.textContent = 'Consultando...';

        try {
            const url = new URL('{{ route('simples-nacional.caixa-postal.dte') }}');
            url.searchParams.set('cliente_id', clienteId);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao consultar situação DTE.' });
                return;
            }

            const indicador = String(data.indicador_enquadramento ?? '');
            const participa = indicador === '0' || indicador === '1' || indicador === '2';

            Swal.fire({
                icon: participa ? 'info' : 'warning',
                title: 'Situação DTE',
                html: `
                    <p class="text-sm mb-2">${escapeHtml(TEXTOS_INDICADOR_DTE[indicador] ?? `Indicador: ${indicador}`)}</p>
                    ${data.status_enquadramento ? `<p class="text-xs text-gray-500">${escapeHtml(data.status_enquadramento)}</p>` : ''}
                `,
            });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
            this.textContent = textoOriginal;
        }
    });

    function abrirMensagem(clienteId, isn) {
        return async () => {
            try {
                const resp = await fetch('{{ route('simples-nacional.caixa-postal.mensagem') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cliente_id: clienteId, isn }),
                });
                const data = await resp.json();

                if (!resp.ok || data.error) {
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao buscar a mensagem.' });
                    return;
                }

                const msg = data.mensagem ?? {};

                Swal.fire({
                    title: msg.assuntoModelo ?? 'Mensagem',
                    html: msg.corpoModelo ?? '<p class="text-gray-400">Sem conteúdo.</p>',
                    width: 600,
                });
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
            }
        };
    }

    function renderMensagens(clienteId, mensagens) {
        mensagens.forEach(msg => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="px-4 py-2">${escapeHtml(msg.assuntoModelo ?? '—')}</td>
                <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(msg.descricaoOrigem ?? '—')}</td>
                <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(formatarData8(msg.dataEnvio))}</td>
                <td class="px-4 py-2 whitespace-nowrap">${situacaoMensagemBadge(msg)}</td>
                <td class="px-4 py-2 whitespace-nowrap ver-cell"></td>
            `;

            const verCell = tr.querySelector('.ver-cell');
            const btnVer = document.createElement('button');
            btnVer.type = 'button';
            btnVer.title = 'Ver conteúdo';
            btnVer.className = 'text-brand bg-transparent border-0 text-sm p-1 hover:text-brand/70';
            btnVer.innerHTML = '<i class="fa-solid fa-eye"></i>';
            btnVer.addEventListener('click', abrirMensagem(clienteId, msg.isn));
            verCell.appendChild(btnVer);

            caixaPostalTabelaBody.appendChild(tr);
        });
    }

    async function buscarMensagens(clienteId, ponteiroPagina, acumular) {
        const url = new URL('{{ route('simples-nacional.caixa-postal.mensagens') }}');
        url.searchParams.set('cliente_id', clienteId);
        if (ponteiroPagina) {
            url.searchParams.set('ponteiro_pagina', ponteiroPagina);
        }

        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();

        if (!resp.ok || data.error) {
            caixaPostalErro.textContent = data.error ?? 'Falha ao buscar mensagens.';
            caixaPostalErro.classList.remove('hidden');
            return;
        }

        if (!acumular) {
            caixaPostalTabelaBody.innerHTML = '';

            if (!data.mensagens || data.mensagens.length === 0) {
                caixaPostalTabelaBody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Nenhuma mensagem encontrada.</td></tr>';
            }
        }

        renderMensagens(clienteId, data.mensagens ?? []);

        ponteiroProximaPagina = data.ponteiro_proxima_pagina ?? null;
        btnCarregarMaisMensagens.classList.toggle('hidden', data.indicador_ultima_pagina === 'S' || !ponteiroProximaPagina);

        caixaPostalTabelaWrapper.classList.remove('hidden');
    }

    btnBuscarMensagens.addEventListener('click', async function () {
        const clienteId = selectClienteCaixaPostal.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente.' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Buscando...';
        caixaPostalErro.classList.add('hidden');
        caixaPostalTabelaWrapper.classList.add('hidden');
        ponteiroProximaPagina = null;

        try {
            await buscarMensagens(clienteId, null, false);
        } catch (e) {
            caixaPostalErro.textContent = 'Erro de comunicação com o servidor.';
            caixaPostalErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'Buscar mensagens';
        }
    });

    btnCarregarMaisMensagens.addEventListener('click', async function () {
        const clienteId = selectClienteCaixaPostal.value;

        if (!clienteId || !ponteiroProximaPagina) return;

        this.disabled = true;
        this.textContent = 'Carregando...';

        try {
            await buscarMensagens(clienteId, ponteiroProximaPagina, true);
        } catch (e) {
            caixaPostalErro.textContent = 'Erro de comunicação com o servidor.';
            caixaPostalErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'Carregar mais';
        }
    });

    protegerComConfigSerpro('cardCaixaPostal');
    </script>
    @endpush
@endsection
