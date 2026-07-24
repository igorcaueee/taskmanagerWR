@extends('layouts.internal')

@section('title', 'Importar do Domínio — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-import"></i> Importar do Domínio</h1>
            <p class="text-gray-700 dark:text-gray-300">Preencher receita/atividade a partir do relatório .txt exportado do Domínio.</p>
        </div>

        {{-- ─── Importar do Domínio (.txt) ──────────────────────────────────────── --}}
        <div id="cardImportarDominio" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-import text-brand"></i> Importar receita do relatório do Domínio (.txt)
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                Exporte o relatório de apuração do Simples Nacional do Domínio em .txt e suba aqui — o sistema tenta identificar o cliente pelo CNPJ e sugerir a atividade automaticamente. <strong>Confira a prévia antes de confirmar</strong>, principalmente a atividade sugerida.
            </p>

            <div class="flex items-center gap-3 mb-3">
                <input type="file" id="inputArquivoDominio" accept=".txt"
                       class="text-sm text-gray-600 dark:text-slate-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-brand file:text-white file:text-xs">
                <button type="button" id="btnPreviaDominio" class="py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                    Gerar prévia
                </button>
            </div>

            <div id="importDominioErro" class="hidden text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2 mb-3"></div>

            <div id="importDominioPreviaWrapper" class="hidden">
                <p class="text-xs text-gray-500 dark:text-slate-400 mb-2">Período identificado: <strong id="importDominioPeriodo"></strong></p>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CNPJ (relatório)</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Cliente no sistema</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">RBT12 / RBA</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Receita (competência/caixa)</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Atividade sugerida</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Receita da atividade</th>
                            </tr>
                        </thead>
                        <tbody id="importDominioTabelaBody" class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700"></tbody>
                    </table>
                </div>

                <button type="button" id="btnConfirmarImportDominio" class="mt-3 py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    Confirmar e salvar
                </button>

                <div id="importDominioResultado" class="hidden mt-3 text-sm rounded-lg px-3 py-2"></div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        window.PGDASD_ATIVIDADES = @json($atividadesCatalogo);
        window.PGDASD_TRIBUTOS = @json($nomesTributos);
    </script>
    @include('simples-nacional._shared')
    <script>
    const inputArquivoDominio = document.getElementById('inputArquivoDominio');
    const btnPreviaDominio = document.getElementById('btnPreviaDominio');
    const importDominioErro = document.getElementById('importDominioErro');
    const importDominioPreviaWrapper = document.getElementById('importDominioPreviaWrapper');
    const importDominioPeriodo = document.getElementById('importDominioPeriodo');
    const importDominioTabelaBody = document.getElementById('importDominioTabelaBody');
    const btnConfirmarImportDominio = document.getElementById('btnConfirmarImportDominio');
    const importDominioResultado = document.getElementById('importDominioResultado');

    function montarSelectAtividadesDominio(idSelecionado) {
        const grupos = {};
        Object.entries(window.PGDASD_ATIVIDADES).forEach(([id, a]) => {
            grupos[a.categoria] = grupos[a.categoria] || [];
            grupos[a.categoria].push({ id, ...a });
        });

        let html = '<option value="">Selecione...</option>';
        Object.entries(grupos).forEach(([categoria, itens]) => {
            html += `<optgroup label="${escapeHtml(categoria)}">`;
            itens.forEach(it => {
                html += `<option value="${it.id}" ${String(idSelecionado) === it.id ? 'selected' : ''}>${it.id} — ${escapeHtml(it.descricao)}</option>`;
            });
            html += '</optgroup>';
        });

        return html;
    }

    // Competência e caixa são mutuamente exclusivos (regime_apuracao só aceita um dos dois no banco),
    // mas o usuário pediu 2 checkboxes em vez de um <select> fixo — força exclusividade e impede
    // deixar as duas desmarcadas, senão não dá pra saber qual valor usar ao salvar.
    importDominioTabelaBody.addEventListener('change', function (ev) {
        const target = ev.target;
        if (!target.classList.contains('checkbox-regime-competencia') && !target.classList.contains('checkbox-regime-caixa')) {
            return;
        }

        const tr = target.closest('tr');
        const checkboxCompetencia = tr.querySelector('.checkbox-regime-competencia');
        const checkboxCaixa = tr.querySelector('.checkbox-regime-caixa');

        if (!target.checked) {
            target.checked = true; // não permite deixar as duas desmarcadas
            return;
        }

        if (target === checkboxCompetencia) {
            checkboxCaixa.checked = false;
        } else {
            checkboxCompetencia.checked = false;
        }
    });

    btnPreviaDominio.addEventListener('click', async function () {
        if (!inputArquivoDominio.files[0]) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o arquivo .txt exportado do Domínio.' });
            return;
        }

        this.disabled = true;
        this.textContent = 'Lendo...';
        importDominioErro.classList.add('hidden');
        importDominioPreviaWrapper.classList.add('hidden');

        try {
            const formData = new FormData();
            formData.append('arquivo', inputArquivoDominio.files[0]);

            const resp = await fetch('{{ route('simples-nacional.importar-dominio.previa') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: formData,
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                importDominioErro.textContent = data.error ?? 'Falha ao ler o arquivo.';
                importDominioErro.classList.remove('hidden');
                return;
            }

            importDominioPeriodo.textContent = formatarPeriodo(data.periodo_apuracao);
            importDominioTabelaBody.innerHTML = '';

            data.estabelecimentos.forEach(e => {
                const tr = document.createElement('tr');
                tr.dataset.clienteId = e.cliente_id ?? '';

                const clienteHtml = e.cliente_id
                    ? `<span class="text-green-700 dark:text-green-400">${escapeHtml(e.cliente_nome)}</span>`
                    : `<span class="text-red-600">Não encontrado (CNPJ ${escapeHtml(e.cnpj)})</span>`;

                const avisoTributos = (e.tributos_divergentes ?? []).length > 0
                    ? `<div class="text-xs text-amber-600 mt-1"><i class="fa-solid fa-triangle-exclamation"></i> Tributos com situação diferente de "Tributado" — ajuste manualmente depois em "Atividades e receitas".</div>`
                    : '';

                // sugere caixa só quando o relatório já traz um valor de caixa; senão parte de competência.
                // O valor é único e editável — o Domínio pode marcar como "competência" um cliente que na
                // prática lança como "caixa" no eCAC, então o checkbox só decide o destino, sem depender
                // de qual coluna o relatório preencheu.
                const regimeSugerido = (e.rpa_caixa !== null && e.rpa_caixa !== undefined && Number(e.rpa_caixa) > 0) ? 'caixa' : 'competencia';
                const valorInicial = regimeSugerido === 'caixa' ? (e.rpa_caixa ?? '') : (e.rpa_competencia ?? '');

                tr.innerHTML = `
                    <td class="px-3 py-2 font-mono text-xs whitespace-nowrap">${escapeHtml(e.cnpj)}</td>
                    <td class="px-3 py-2 whitespace-nowrap">${clienteHtml}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-xs">RBT12: R$ ${formatarMoeda(e.rbt12)}<br>RBA atual: R$ ${formatarMoeda(e.rba_atual)}<br>RBA anterior: R$ ${formatarMoeda(e.rba_anterior)}</td>
                    <td class="px-3 py-2 whitespace-nowrap text-xs">
                        <input type="number" step="0.01" class="input-valor-regime-dominio block mb-1.5 text-sm rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1 w-28" value="${valorInicial}">
                        <label class="flex items-center gap-1.5 mb-1 cursor-pointer">
                            <input type="checkbox" class="checkbox-regime-competencia" ${regimeSugerido === 'competencia' ? 'checked' : ''}>
                            Competência
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" class="checkbox-regime-caixa" ${regimeSugerido === 'caixa' ? 'checked' : ''}>
                            Caixa
                        </label>
                    </td>
                    <td class="px-3 py-2 select-atividade-cell">
                        <select class="select-atividade-dominio text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1 w-64">
                            ${montarSelectAtividadesDominio(e.id_atividade_sugerido)}
                        </select>
                        <div class="text-xs text-gray-400 mt-1">Confiança do match: ${Math.round((e.confianca_match ?? 0) * 100)}% — texto do relatório: "${escapeHtml(e.tabela_texto ?? '—')}"</div>
                        ${avisoTributos}
                    </td>
                    <td class="px-3 py-2">
                        <input type="number" step="0.01" class="input-receita-atividade-dominio text-sm rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1 w-28" value="${e.receita_tributada_total ?? ''}">
                    </td>
                `;

                importDominioTabelaBody.appendChild(tr);
            });

            importDominioPreviaWrapper.classList.remove('hidden');
        } catch (e) {
            importDominioErro.textContent = 'Erro de comunicação com o servidor.';
            importDominioErro.classList.remove('hidden');
        } finally {
            this.disabled = false;
            this.textContent = 'Gerar prévia';
        }
    });

    btnConfirmarImportDominio.addEventListener('click', async function () {
        const linhas = Array.from(importDominioTabelaBody.querySelectorAll('tr'));

        const estabelecimentos = [];
        let temSemCliente = false;

        linhas.forEach(tr => {
            const clienteId = tr.dataset.clienteId;
            if (!clienteId) {
                temSemCliente = true;
                return; // pula estabelecimentos sem cliente encontrado
            }

            const idAtividade = tr.querySelector('.select-atividade-dominio').value;
            const receita = tr.querySelector('.input-receita-atividade-dominio').value;
            const regimeApuracao = tr.querySelector('.checkbox-regime-caixa').checked ? 'caixa' : 'competencia';
            const valorRegime = tr.querySelector('.input-valor-regime-dominio').value || null;

            estabelecimentos.push({
                cliente_id: clienteId,
                // receita_bruta_competencia é sempre exigida pelo PGDASD (mesmo em regime caixa, ver
                // PgdasdService::montarDeclaracao) — por isso o valor escolhido aqui sempre alimenta
                // competência, e também alimenta caixa quando esse é o regime marcado.
                rpa_competencia: valorRegime,
                rpa_caixa: regimeApuracao === 'caixa' ? valorRegime : null,
                regime_apuracao: regimeApuracao,
                id_atividade: idAtividade,
                receita_tributada_total: receita,
            });
        });

        if (estabelecimentos.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum estabelecimento com cliente identificado para importar.' });
            return;
        }

        const confirmacao = await Swal.fire({
            title: 'Confirmar importação?',
            html: `Vai salvar receita/atividade de <strong>${estabelecimentos.length}</strong> estabelecimento(s)` + (temSemCliente ? ' (os sem cliente encontrado serão ignorados)' : '') + '.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, salvar',
            cancelButtonText: 'Cancelar',
        });

        if (!confirmacao.isConfirmed) return;

        this.disabled = true;

        try {
            // extrai o período no formato AAAAMM a partir do texto exibido (MM/AAAA)
            const [mm, aaaa] = importDominioPeriodo.textContent.split('/');
            const periodoApuracao = `${aaaa}${mm}`;

            const resp = await fetch('{{ route('simples-nacional.importar-dominio.confirmar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ periodo_apuracao: periodoApuracao, estabelecimentos }),
            });
            const data = await resp.json();

            importDominioResultado.classList.remove('hidden');
            importDominioResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                importDominioResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                importDominioResultado.textContent = data.error ?? 'Falha ao salvar.';
            } else {
                importDominioResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
                importDominioResultado.textContent = data.message;
            }
        } catch (e) {
            importDominioResultado.classList.remove('hidden');
            importDominioResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            importDominioResultado.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
        }
    });

    protegerComConfigSerpro('cardImportarDominio');
    </script>
    @endpush
@endsection
