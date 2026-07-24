@extends('layouts.internal')

@section('title', 'MIT — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-invoice"></i> MIT</h1>
            <p class="text-gray-700 dark:text-gray-300">Consulta de apurações do Módulo de Inclusão de Tributos (DCTFWeb) já encerradas.</p>
        </div>

        <div id="cardMit" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-invoice text-brand"></i> Apurações MIT do cliente
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                Consulta somente leitura das apurações já encerradas (pelo e-CAC ou por outro sistema). Encerrar uma apuração nova ainda não é feito por aqui.
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

            if (!data.apuracoes || data.apuracoes.length === 0) {
                mitTabelaBody.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Nenhuma apuração encontrada.</td></tr>';
            }

            (data.apuracoes ?? []).forEach(ap => {
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

    protegerComConfigSerpro('cardMit');
    </script>
    @endpush
@endsection
