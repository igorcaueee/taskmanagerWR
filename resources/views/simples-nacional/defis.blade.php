@extends('layouts.internal')

@section('title', 'DEFIS — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-lines"></i> DEFIS</h1>
            <p class="text-gray-700 dark:text-gray-300">Consulta das declarações anuais (DEFIS) já transmitidas.</p>
        </div>

        {{-- ─── DEFIS — declarações já transmitidas ─────────────────────────────── --}}
        <div id="cardDefis" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-lines text-brand"></i> DEFIS — declarações do cliente
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                Consulta somente leitura das DEFIS (declaração anual do Simples Nacional) já transmitidas. Transmitir uma DEFIS nova ainda não é feito por aqui — use o e-CAC normalmente por enquanto.
            </p>

            <div class="flex flex-wrap gap-3 items-end mb-4">
                <div class="flex-1 min-w-60">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                    <select id="selectClienteDefis"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="btnBuscarDefis"
                        class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    Buscar DEFIS
                </button>
            </div>

            <div id="defisErro" class="hidden text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mb-3"></div>

            <div id="defisTabelaWrapper" class="hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ano</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nº DEFIS</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Transmitida em</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recibo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Declaração</th>
                        </tr>
                    </thead>
                    <tbody id="defisTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700 text-sm text-gray-700 dark:text-gray-300"></tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    const selectClienteDefis = document.getElementById('selectClienteDefis');
    const btnBuscarDefis     = document.getElementById('btnBuscarDefis');
    const defisErro          = document.getElementById('defisErro');
    const defisTabelaWrapper = document.getElementById('defisTabelaWrapper');
    const defisTabelaBody    = document.getElementById('defisTabelaBody');

    function tipoDefisLabel(tipo) {
        const mapa = { 1: 'Original', 2: 'Retificadora' };
        return mapa[tipo] ?? `Tipo ${tipo ?? '—'}`;
    }

    btnBuscarDefis.addEventListener('click', async function () {
        const clienteId = selectClienteDefis.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente.' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Buscando...';
        defisErro.classList.add('hidden');
        defisTabelaWrapper.classList.add('hidden');

        try {
            const url = new URL('{{ route('simples-nacional.defis.declaracoes') }}');
            url.searchParams.set('cliente_id', clienteId);

            const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                defisErro.textContent = data.error ?? 'Falha ao buscar DEFIS.';
                defisErro.classList.remove('hidden');
                return;
            }

            defisTabelaBody.innerHTML = '';

            if (!data.declaracoes || data.declaracoes.length === 0) {
                defisTabelaBody.innerHTML = '<tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Nenhuma DEFIS encontrada para este cliente.</td></tr>';
            }

            (data.declaracoes ?? []).forEach(decl => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(decl.anoCalendario ?? '—')}</td>
                    <td class="px-4 py-2 whitespace-nowrap font-mono text-xs">${escapeHtml(decl.idDefis ?? '—')}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(tipoDefisLabel(decl.tipo))}</td>
                    <td class="px-4 py-2 whitespace-nowrap">${escapeHtml(formatarDataHora(decl.dataHora))}</td>
                    <td class="px-4 py-2 whitespace-nowrap recibo-defis-cell"></td>
                    <td class="px-4 py-2 whitespace-nowrap declaracao-defis-cell"></td>
                `;

                const reciboCell = tr.querySelector('.recibo-defis-cell');
                const declaracaoCell = tr.querySelector('.declaracao-defis-cell');
                reciboCell.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-gray-400"></i>';

                fetch('{{ route('simples-nacional.defis.recibo') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cliente_id: clienteId, id_defis: decl.idDefis }),
                })
                    .then(r => r.json().then(d => ({ ok: r.ok, d })))
                    .then(({ ok, d }) => {
                        if (!ok || d.error) {
                            reciboCell.innerHTML = `<span class="text-xs text-red-600" title="${escapeHtml(d.error ?? 'Falha ao buscar.')}"><i class="fa-solid fa-triangle-exclamation"></i></span>`;
                            declaracaoCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                            return;
                        }

                        const arquivos = d.arquivos ?? [];
                        const recibo = arquivos.find(a => a.nomeArquivo.toLowerCase().includes('recibo'));
                        const declaracao = arquivos.find(a => a.nomeArquivo.toLowerCase().includes('declaracao'));

                        reciboCell.innerHTML = recibo
                            ? `<a href="${escapeHtml(recibo.url)}" target="_blank" class="text-brand hover:text-brand/70"><i class="fa-solid fa-file-pdf"></i></a>`
                            : '<span class="text-xs text-gray-400">—</span>';

                        declaracaoCell.innerHTML = declaracao
                            ? `<a href="${escapeHtml(declaracao.url)}" target="_blank" class="text-brand hover:text-brand/70"><i class="fa-solid fa-file-pdf"></i></a>`
                            : '<span class="text-xs text-gray-400">—</span>';
                    })
                    .catch(() => {
                        reciboCell.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-red-600" title="Erro de comunicação"></i>';
                        declaracaoCell.innerHTML = '<span class="text-xs text-gray-400">—</span>';
                    });

                defisTabelaBody.appendChild(tr);
            });

            defisTabelaWrapper.classList.remove('hidden');
        } catch (e) {
            defisErro.textContent = 'Erro de comunicação com o servidor.';
            defisErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'Buscar DEFIS';
        }
    });

    protegerComConfigSerpro('cardDefis');
    </script>
    @endpush
@endsection
