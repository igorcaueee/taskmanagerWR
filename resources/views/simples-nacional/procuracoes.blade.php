@extends('layouts.internal')

@section('title', 'Procurações — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-signature"></i> Procurações</h1>
            <p class="text-gray-700 dark:text-gray-300">Confere se o cliente tem procuração eletrônica ativa em nome do escritório, e para quais sistemas — antes de rodar outras consultas/emissões.</p>
        </div>

        <div id="cardProcuracoes" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-signature text-brand"></i> Procuração eletrônica do cliente
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                Verifique um cliente específico ou toda a carteira de uma vez — cada verificação é uma chamada paga na SERPRO, então rodar "toda a carteira" gera uma cobrança por cliente.
            </p>

            <div class="flex flex-wrap gap-3 items-end mb-4">
                <div class="flex-1 min-w-60">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                    <select id="selectClienteProcuracoes"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}" data-nome="{{ $cli->nome }}" data-cnpj="{{ $cli->cpfcnpj }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="btnVerificarCliente"
                        class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    Verificar este cliente
                </button>
                <button type="button" id="btnVerificarCarteira"
                        class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                    Verificar toda a carteira ({{ $clientes->count() }})
                </button>
            </div>

            <div id="procuracoesErro" class="hidden text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mb-3"></div>
            <div id="procuracoesProgresso" class="hidden text-sm text-gray-600 dark:text-gray-400 mb-3"></div>

            <div id="procuracoesTabelaWrapper" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Situação</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sistemas autorizados</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Validade mais próxima</th>
                        </tr>
                    </thead>
                    <tbody id="procuracoesTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-sm text-gray-700 dark:text-gray-300"></tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    const selectClienteProcuracoes  = document.getElementById('selectClienteProcuracoes');
    const btnVerificarCliente       = document.getElementById('btnVerificarCliente');
    const btnVerificarCarteira      = document.getElementById('btnVerificarCarteira');
    const procuracoesErro           = document.getElementById('procuracoesErro');
    const procuracoesProgresso      = document.getElementById('procuracoesProgresso');
    const procuracoesTabelaWrapper  = document.getElementById('procuracoesTabelaWrapper');
    const procuracoesTabelaBody     = document.getElementById('procuracoesTabelaBody');

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function situacaoProcuracaoBadge(procuracoes) {
        if (!procuracoes || procuracoes.length === 0) {
            return '<span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Sem procuração</span>';
        }
        return '<span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Ativa</span>';
    }

    function sistemasResumo(procuracoes) {
        if (!procuracoes || procuracoes.length === 0) return '—';
        const todos = new Set();
        procuracoes.forEach(p => (p.sistemas ?? []).forEach(s => todos.add(s)));
        return Array.from(todos).join(', ');
    }

    function validadeMaisProxima(procuracoes) {
        if (!procuracoes || procuracoes.length === 0) return '—';
        const datas = procuracoes.map(p => String(p.dtexpiracao ?? '')).filter(Boolean).sort();
        return datas.length ? formatarData8(datas[0]) : '—';
    }

    function adicionarLinhaProcuracao(nome, cnpj, procuracoes, erro) {
        const tr = document.createElement('tr');

        if (erro) {
            tr.innerHTML = `
                <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(nome)} <span class="text-xs text-gray-400">— ${escapeHtml(cnpj)}</span></td>
                <td class="px-4 py-2" colspan="3"><span class="text-xs text-red-600" title="${escapeHtml(erro)}"><i class="fa-solid fa-triangle-exclamation"></i> ${escapeHtml(erro)}</span></td>
            `;
        } else {
            tr.innerHTML = `
                <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(nome)} <span class="text-xs text-gray-400">— ${escapeHtml(cnpj)}</span></td>
                <td class="px-4 py-2 whitespace-nowrap">${situacaoProcuracaoBadge(procuracoes)}</td>
                <td class="px-4 py-2 text-xs">${escapeHtml(sistemasResumo(procuracoes))}</td>
                <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(validadeMaisProxima(procuracoes))}</td>
            `;
        }

        procuracoesTabelaBody.appendChild(tr);
    }

    async function verificarCliente(clienteId) {
        const url = new URL('{{ route('simples-nacional.procuracoes.consultar') }}');
        url.searchParams.set('cliente_id', clienteId);

        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();

        if (!resp.ok || data.error) {
            throw new Error(data.error ?? 'Falha ao consultar procuração.');
        }

        return data;
    }

    btnVerificarCliente.addEventListener('click', async function () {
        const option = selectClienteProcuracoes.options[selectClienteProcuracoes.selectedIndex];
        const clienteId = selectClienteProcuracoes.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente.' });
            return;
        }

        this.disabled = true;
        procuracoesErro.classList.add('hidden');
        procuracoesTabelaBody.innerHTML = '';
        procuracoesTabelaWrapper.classList.add('hidden');

        try {
            const data = await verificarCliente(clienteId);
            adicionarLinhaProcuracao(option.dataset.nome, option.dataset.cnpj, data.procuracoes, null);
            procuracoesTabelaWrapper.classList.remove('hidden');
        } catch (e) {
            procuracoesErro.textContent = e.message ?? 'Erro de comunicação com o servidor.';
            procuracoesErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
        }
    });

    btnVerificarCarteira.addEventListener('click', async function () {
        const opcoes = Array.from(selectClienteProcuracoes.options).filter(o => o.value);

        if (opcoes.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum cliente na carteira.' });
            return;
        }

        const confirmacao = await Swal.fire({
            title: 'Verificar toda a carteira?',
            html: `Isso vai fazer <strong>${opcoes.length}</strong> chamada(s) paga(s) na SERPRO, uma por cliente. Continuar?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, verificar',
            cancelButtonText: 'Cancelar',
        });

        if (!confirmacao.isConfirmed) return;

        this.disabled = true;
        btnVerificarCliente.disabled = true;
        procuracoesErro.classList.add('hidden');
        procuracoesTabelaBody.innerHTML = '';
        procuracoesTabelaWrapper.classList.remove('hidden');
        procuracoesProgresso.classList.remove('hidden');

        for (let i = 0; i < opcoes.length; i++) {
            const option = opcoes[i];
            procuracoesProgresso.textContent = `Verificando ${i + 1}/${opcoes.length}: ${option.dataset.nome}...`;

            try {
                const data = await verificarCliente(option.value);
                adicionarLinhaProcuracao(option.dataset.nome, option.dataset.cnpj, data.procuracoes, null);
            } catch (e) {
                adicionarLinhaProcuracao(option.dataset.nome, option.dataset.cnpj, null, e.message ?? 'Erro de comunicação.');
            }

            // pequena pausa entre chamadas pra não estourar limite de requisições simultâneas da SERPRO.
            await sleep(300);
        }

        procuracoesProgresso.classList.add('hidden');
        this.disabled = false;
        btnVerificarCliente.disabled = false;
    });

    protegerComConfigSerpro('cardProcuracoes');
    </script>
    @endpush
@endsection
