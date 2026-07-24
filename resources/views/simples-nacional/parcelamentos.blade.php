@extends('layouts.internal')

@section('title', 'Parcelamentos — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-hand-holding-dollar"></i> Parcelamentos (PARCSN)</h1>
            <p class="text-gray-700 dark:text-gray-300">Histórico de parcelamentos do cliente, detalhamento e parcelas pendentes de emissão.</p>
        </div>

        {{-- ─── Parcelamentos do cliente (PARCSN) ───────────────────────────────── --}}
        <div id="cardParcelamentos" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-hand-holding-dollar text-brand"></i> Parcelamentos do cliente (PARCSN)
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                Veja o histórico de parcelamentos do Simples Nacional do cliente, o detalhamento de cada um (valor consolidado, parcelas pagas) e quais parcelas ainda estão pendentes de emissão — é aí que aparece o gargalo.
            </p>

            <div class="flex flex-wrap gap-3 items-end mb-4">
                <div class="flex-1 min-w-60">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                    <select id="selectClienteParcelamentos"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="btnBuscarParcelamentos"
                        class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    Buscar parcelamentos
                </button>
                <button type="button" id="btnBuscarParcelasPendentes"
                        class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                    Ver parcelas pendentes
                </button>
            </div>

            <div id="parcelamentosErro" class="hidden text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mb-3"></div>

            <div id="parcelamentosTabelaWrapper" class="hidden overflow-x-auto mb-4">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nº Parcelamento</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data do pedido</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Situação</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Detalhes</th>
                        </tr>
                    </thead>
                    <tbody id="parcelamentosTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-sm text-gray-700 dark:text-gray-300"></tbody>
                </table>
            </div>

            <div id="parcelasPendentesWrapper" class="hidden overflow-x-auto">
                <div class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase mb-2">Parcelas pendentes de emissão</div>
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Parcela</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Emitir DAS</th>
                        </tr>
                    </thead>
                    <tbody id="parcelasPendentesTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-sm text-gray-700 dark:text-gray-300"></tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    const selectClienteParcelamentos   = document.getElementById('selectClienteParcelamentos');
    const btnBuscarParcelamentos       = document.getElementById('btnBuscarParcelamentos');
    const btnBuscarParcelasPendentes   = document.getElementById('btnBuscarParcelasPendentes');
    const parcelamentosErro            = document.getElementById('parcelamentosErro');
    const parcelamentosTabelaWrapper   = document.getElementById('parcelamentosTabelaWrapper');
    const parcelamentosTabelaBody      = document.getElementById('parcelamentosTabelaBody');
    const parcelasPendentesWrapper     = document.getElementById('parcelasPendentesWrapper');
    const parcelasPendentesTabelaBody  = document.getElementById('parcelasPendentesTabelaBody');

    function situacaoParcelamentoBadge(situacao) {
        const texto = situacao ?? '—';
        const encerrado = /encerrad/i.test(texto);
        const cor = encerrado ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-800';
        return `<span class="px-2 py-1 rounded text-xs font-medium ${cor}">${escapeHtml(texto)}</span>`;
    }

    btnBuscarParcelamentos.addEventListener('click', async function () {
        const clienteId = selectClienteParcelamentos.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente.' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Buscando...';
        parcelamentosErro.classList.add('hidden');
        parcelamentosTabelaWrapper.classList.add('hidden');

        try {
            const url = new URL('{{ route('simples-nacional.parcelamentos.pedidos') }}');
            url.searchParams.set('cliente_id', clienteId);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                parcelamentosErro.textContent = data.error ?? 'Falha ao buscar parcelamentos.';
                parcelamentosErro.classList.remove('hidden');
                return;
            }

            parcelamentosTabelaBody.innerHTML = '';

            if (!data.pedidos || data.pedidos.length === 0) {
                parcelamentosTabelaBody.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Nenhum parcelamento encontrado para este cliente.</td></tr>';
            }

            (data.pedidos ?? []).forEach(pedido => {
                const numero = pedido.numero ?? pedido.numeroParcelamento;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-2 whitespace-nowrap font-mono text-xs">${escapeHtml(numero ?? '—')}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(formatarData8(pedido.dataDoPedido))}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${situacaoParcelamentoBadge(pedido.situacao)}</td>
                    <td class="px-4 py-2 whitespace-nowrap detalhe-cell"></td>
                `;

                const detalheCell = tr.querySelector('.detalhe-cell');
                const btnDetalhe = document.createElement('button');
                btnDetalhe.type = 'button';
                btnDetalhe.className = 'text-brand bg-transparent border-0 text-sm p-1 hover:text-brand/70';
                btnDetalhe.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Ver detalhes';
                btnDetalhe.addEventListener('click', async () => {
                    btnDetalhe.disabled = true;
                    const iconeOriginal = btnDetalhe.innerHTML;
                    btnDetalhe.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                    try {
                        const respDetalhe = await fetch('{{ route('simples-nacional.parcelamentos.detalhe') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ cliente_id: clienteId, numero_parcelamento: String(numero) }),
                        });
                        const dataDetalhe = await respDetalhe.json();

                        if (!respDetalhe.ok || dataDetalhe.error) {
                            Swal.fire({ icon: 'error', title: 'Erro', text: dataDetalhe.error ?? 'Falha ao buscar detalhes.' });
                            return;
                        }

                        const p = dataDetalhe.parcelamento ?? {};
                        const cons = p.consolidacaoOriginal ?? {};
                        const pagamentos = cons.demonstrativoPagamentos ?? [];

                        const linhasPagamentos = pagamentos.map(pg => `
                            <tr>
                                <td class="pr-3 text-gray-500">${escapeHtml(formatarPeriodo(pg.mesDaParcela))}</td>
                                <td class="pr-3">${formatarData8(pg.dataDeArrecadacao)}</td>
                                <td class="text-right">R$ ${formatarMoeda(pg.valorPago)}</td>
                            </tr>
                        `).join('');

                        Swal.fire({
                            icon: 'info',
                            title: `Parcelamento ${escapeHtml(numero ?? '')}`,
                            html: `
                                <table class="text-xs text-left w-full mb-3">
                                    <tr><td class="pr-3 text-gray-500">Situação</td><td>${escapeHtml(p.situacao ?? '—')}</td></tr>
                                    <tr><td class="pr-3 text-gray-500">Valor total consolidado</td><td>R$ ${formatarMoeda(cons.valorTotalConsolidado)}</td></tr>
                                    <tr><td class="pr-3 text-gray-500">Quantidade de parcelas</td><td>${escapeHtml(cons.quantidadeParcelas ?? '—')}</td></tr>
                                    <tr><td class="pr-3 text-gray-500">Parcela básica</td><td>R$ ${formatarMoeda(cons.parcelaBasica)}</td></tr>
                                </table>
                                ${linhasPagamentos ? `
                                    <div class="text-xs text-gray-500 mb-1 font-semibold">Pagamentos já realizados</div>
                                    <table class="text-xs text-left w-full">${linhasPagamentos}</table>
                                ` : '<p class="text-xs text-gray-400">Nenhum pagamento registrado ainda.</p>'}
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

                parcelamentosTabelaBody.appendChild(tr);
            });

            parcelamentosTabelaWrapper.classList.remove('hidden');
        } catch (e) {
            parcelamentosErro.textContent = 'Erro de comunicação com o servidor.';
            parcelamentosErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'Buscar parcelamentos';
        }
    });

    btnBuscarParcelasPendentes.addEventListener('click', async function () {
        const clienteId = selectClienteParcelamentos.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente.' });
            return;
        }

        this.disabled = true;
        const textoOriginal = this.textContent;
        this.textContent = 'Buscando...';
        parcelamentosErro.classList.add('hidden');
        parcelasPendentesWrapper.classList.add('hidden');

        try {
            const url = new URL('{{ route('simples-nacional.parcelamentos.pendentes') }}');
            url.searchParams.set('cliente_id', clienteId);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                parcelamentosErro.textContent = data.error ?? 'Falha ao buscar parcelas pendentes.';
                parcelamentosErro.classList.remove('hidden');
                return;
            }

            parcelasPendentesTabelaBody.innerHTML = '';

            if (!data.parcelas || data.parcelas.length === 0) {
                parcelasPendentesTabelaBody.innerHTML = '<tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Nenhuma parcela pendente de emissão.</td></tr>';
            }

            (data.parcelas ?? []).forEach(item => {
                const parcela = item.parcela;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(formatarPeriodo(parcela))}</td>
                    <td class="px-4 py-2 whitespace-nowrap">R$ ${formatarMoeda(item.valor)}</td>
                    <td class="px-4 py-2 whitespace-nowrap emitir-cell"></td>
                `;

                const emitirCell = tr.querySelector('.emitir-cell');
                const btnEmitir = document.createElement('button');
                btnEmitir.type = 'button';
                btnEmitir.title = 'Emitir DAS desta parcela';
                btnEmitir.className = 'text-brand bg-transparent border-0 text-sm p-1 hover:text-brand/70';
                btnEmitir.innerHTML = '<i class="fa-solid fa-file-invoice"></i>';
                btnEmitir.addEventListener('click', async () => {
                    btnEmitir.disabled = true;
                    btnEmitir.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

                    try {
                        const respEmitir = await fetch('{{ route('simples-nacional.parcelamentos.emitir-das') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                            body: JSON.stringify({ cliente_id: clienteId, parcela: String(parcela) }),
                        });
                        const dataEmitir = await respEmitir.json();

                        if (!respEmitir.ok || dataEmitir.error) {
                            Swal.fire({ icon: 'error', title: 'Erro', text: dataEmitir.error ?? 'Falha ao emitir DAS.' });
                            return;
                        }

                        if (dataEmitir.arquivo) {
                            window.open(dataEmitir.arquivo.url, '_blank');
                        } else {
                            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum PDF retornado.' });
                        }
                    } catch (e) {
                        Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
                    } finally {
                        btnEmitir.disabled = false;
                        btnEmitir.innerHTML = '<i class="fa-solid fa-file-invoice"></i>';
                    }
                });
                emitirCell.appendChild(btnEmitir);

                parcelasPendentesTabelaBody.appendChild(tr);
            });

            parcelasPendentesWrapper.classList.remove('hidden');
        } catch (e) {
            parcelamentosErro.textContent = 'Erro de comunicação com o servidor.';
            parcelamentosErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = textoOriginal;
        }
    });

    protegerComConfigSerpro('cardParcelamentos');
    </script>
    @endpush
@endsection
