@extends('layouts.internal')

@section('title', 'Lançar receita e transmitir — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-paper-plane"></i> Lançar receita e transmitir</h1>
            <p class="text-gray-700 dark:text-gray-300">Apuração do mês e transmissão da declaração do PGDASD.</p>
        </div>

        {{-- ─── Lançar receita do mês e transmitir declaração ───────────────────── --}}
        <div id="cardTransmitirDeclaracao" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-paper-plane text-brand"></i> Lançar receita do mês e transmitir declaração
            </h2>
            <div class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Transmitir cria uma declaração fiscal <strong>real</strong> perante a Receita Federal — não é possível desfazer. Confira os valores com atenção antes de confirmar.
            </div>

            <div class="flex flex-wrap gap-3 items-end mb-4">
                <div class="flex-1 min-w-60">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                    <select id="selectClienteTransmissao"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Período (YYYYMM)</label>
                    <input type="text" id="inputPeriodoTransmissao" maxlength="6" placeholder="{{ now()->format('Ym') }}"
                           class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm w-32">
                </div>
            </div>

            <div id="transmissaoCamposWrapper" class="hidden">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3 pb-4 border-b border-gray-100 dark:border-slate-700">
                    <div class="md:col-span-3 text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Dados fiscais do cliente (cadastro único)</div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CNAE (referência)</label>
                        <input type="text" id="inputCnaeFiscal" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Anexo</label>
                        <input type="text" id="inputAnexoFiscal" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-3">
                        <button type="button" id="btnSalvarDadosFiscais" class="py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                            Salvar dados fiscais
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                    <div class="md:col-span-3 text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Receita bruta do período</div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Receita bruta (competência) R$</label>
                        <input type="number" step="0.01" id="inputReceitaCompetencia" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Receita bruta (caixa) R$</label>
                        <input type="number" step="0.01" id="inputReceitaCaixa" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Regime de apuração</label>
                        <select id="selectRegimeApuracao" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="competencia">Competência</option>
                            <option value="caixa">Caixa</option>
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <button type="button" id="btnSalvarReceitaMensal" class="py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                            Salvar receita do mês
                        </button>
                    </div>
                </div>

                {{-- ─── Atividades e Receitas do período (réplica do assistente do e-CAC) ── --}}
                <div class="mb-4 pb-4 border-b border-gray-100 dark:border-slate-700">
                    <div class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase mb-2">Atividades e receitas do período</div>
                    <p class="text-xs text-gray-400 mb-3">Marque as atividades que tiveram receita neste período, informe o valor de cada uma e o tratamento tributário por tributo (se houver isenção/redução/substituição). A soma deve bater com a receita bruta lançada acima.</p>

                    <div class="relative mb-3" id="dropdownAtividadesWrapper">
                        <button type="button" id="btnToggleAtividades"
                                class="w-full flex items-center justify-between text-left border border-gray-300 dark:border-slate-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200">
                            <span id="resumoAtividadesSelecionadas" class="truncate">Selecionar atividades...</span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400 ml-2 transition-transform"></i>
                        </button>
                        <div id="checklistAtividades" class="hidden absolute z-20 mt-1 w-full max-h-72 overflow-y-auto bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg shadow-lg p-3 space-y-3"></div>
                    </div>

                    <div id="painelAtividades" class="space-y-4"></div>

                    <div id="somaAtividadesResumo" class="mt-3 text-xs rounded-lg px-3 py-2"></div>

                    <button type="button" id="btnSalvarAtividades" class="mt-3 py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                        Salvar atividades e receitas
                    </button>
                </div>

                <button type="button" id="btnTransmitirDeclaracao"
                        class="py-2 px-4 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    <i class="fa-solid fa-triangle-exclamation"></i> Transmitir declaração (real)
                </button>

                <div id="transmitirResultado" class="hidden mt-3 text-sm rounded-lg px-3 py-2"></div>
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
    const selectClienteTransmissao   = document.getElementById('selectClienteTransmissao');
    const inputPeriodoTransmissao    = document.getElementById('inputPeriodoTransmissao');
    const transmissaoCamposWrapper   = document.getElementById('transmissaoCamposWrapper');
    const inputCnaeFiscal            = document.getElementById('inputCnaeFiscal');
    const inputAnexoFiscal           = document.getElementById('inputAnexoFiscal');
    const btnSalvarDadosFiscais      = document.getElementById('btnSalvarDadosFiscais');
    const inputReceitaCompetencia    = document.getElementById('inputReceitaCompetencia');
    const inputReceitaCaixa          = document.getElementById('inputReceitaCaixa');
    const selectRegimeApuracao       = document.getElementById('selectRegimeApuracao');
    const btnSalvarReceitaMensal     = document.getElementById('btnSalvarReceitaMensal');
    const btnTransmitirDeclaracao    = document.getElementById('btnTransmitirDeclaracao');
    const transmitirResultado        = document.getElementById('transmitirResultado');

    async function carregarDadosFiscais() {
        const resp = await fetch(`{{ route('simples-nacional.dados-fiscais.get') }}?cliente_id=${selectClienteTransmissao.value}`, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();
        inputCnaeFiscal.value = data.cnae_principal ?? '';
        inputAnexoFiscal.value = data.anexo_simples ?? '';

        // Só sugere automaticamente se o cliente ainda não tem CNAE cadastrado —
        // nunca sobrescreve um valor já salvo manualmente.
        if (!inputCnaeFiscal.value) {
            sugerirCnaeAutomatico();
        }
    }

    async function sugerirCnaeAutomatico() {
        try {
            const resp = await fetch(`{{ route('simples-nacional.dados-fiscais.sugerir-cnae') }}?cliente_id=${selectClienteTransmissao.value}`, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            if (data.cnae && !inputCnaeFiscal.value) {
                inputCnaeFiscal.value = `${data.cnae.codigo} - ${data.cnae.descricao}`;
            }
        } catch (e) {
            // Falha silenciosa — é só uma sugestão, não bloqueia o cadastro manual.
        }
    }

    async function carregarReceitaMensal() {
        const url = new URL('{{ route('simples-nacional.receita-mensal.get') }}');
        url.searchParams.set('cliente_id', selectClienteTransmissao.value);
        url.searchParams.set('periodo_apuracao', inputPeriodoTransmissao.value);

        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();
        inputReceitaCompetencia.value = data.receita_bruta_competencia ?? '';
        inputReceitaCaixa.value = data.receita_bruta_caixa ?? '';
        selectRegimeApuracao.value = data.regime_apuracao ?? 'competencia';
    }

    function atualizarCamposTransmissao() {
        const pronto = !!selectClienteTransmissao.value && /^\d{6}$/.test(inputPeriodoTransmissao.value);
        transmissaoCamposWrapper.classList.toggle('hidden', !pronto);
        transmitirResultado.classList.add('hidden');

        if (pronto) {
            carregarDadosFiscais();
            carregarReceitaMensal();
            carregarReceitasAtividades();
        }
    }

    selectClienteTransmissao.addEventListener('change', atualizarCamposTransmissao);
    inputPeriodoTransmissao.addEventListener('change', atualizarCamposTransmissao);

    btnSalvarDadosFiscais.addEventListener('click', async function () {
        this.disabled = true;

        try {
            const resp = await fetch('{{ route('simples-nacional.dados-fiscais.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente_id: selectClienteTransmissao.value,
                    cnae_principal: inputCnaeFiscal.value,
                    anexo_simples: inputAnexoFiscal.value,
                }),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
        }
    });

    btnSalvarReceitaMensal.addEventListener('click', async function () {
        if (!inputReceitaCompetencia.value) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe a receita bruta (competência).' });
            return;
        }

        this.disabled = true;

        try {
            const resp = await fetch('{{ route('simples-nacional.receita-mensal.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente_id: selectClienteTransmissao.value,
                    periodo_apuracao: inputPeriodoTransmissao.value,
                    receita_bruta_competencia: inputReceitaCompetencia.value,
                    receita_bruta_caixa: inputReceitaCaixa.value || null,
                    regime_apuracao: selectRegimeApuracao.value,
                }),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
        }
    });

    btnTransmitirDeclaracao.addEventListener('click', async function () {
        const nomeCliente = selectClienteTransmissao.options[selectClienteTransmissao.selectedIndex]?.text ?? '';
        const periodo = inputPeriodoTransmissao.value;

        const valorTributavel = selectRegimeApuracao.value === 'caixa'
            ? parseFloat(inputReceitaCaixa.value || '0')
            : parseFloat(inputReceitaCompetencia.value || '0');

        let confirmarReceitaZerada = false;

        if (!valorTributavel || valorTributavel <= 0) {
            const resultZerada = await Swal.fire({
                title: 'Receita zerada?',
                html: `A receita bruta do regime selecionado (${selectRegimeApuracao.value}) está <strong>zero</strong>. Confirma que é uma declaração <strong>sem movimento</strong>?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, é sem movimento mesmo',
                cancelButtonText: 'Cancelar, vou corrigir o valor',
            });

            if (!resultZerada.isConfirmed) return;
            confirmarReceitaZerada = true;
        }

        const result = await Swal.fire({
            title: 'Transmitir declaração?',
            html: `Isso vai transmitir a declaração REAL do PGDASD de <strong>${escapeHtml(nomeCliente)}</strong> para o período <strong>${escapeHtml(periodo)}</strong> perante a Receita Federal. Não pode ser desfeito. Tem certeza?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, transmitir',
            cancelButtonText: 'Cancelar',
        });

        {
            if (!result.isConfirmed) return;

            btnTransmitirDeclaracao.disabled = true;
            btnTransmitirDeclaracao.textContent = 'Transmitindo...';
            transmitirResultado.classList.add('hidden');

            try {
                const resp = await fetch('{{ route('simples-nacional.transmitir') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        cliente_id: selectClienteTransmissao.value,
                        periodo_apuracao: periodo,
                        confirmar_receita_zerada: confirmarReceitaZerada,
                    }),
                });
                const data = await resp.json();

                transmitirResultado.classList.remove('hidden');
                transmitirResultado.classList.remove(
                    'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                    'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
                );

                if (!resp.ok || data.error) {
                    transmitirResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                    transmitirResultado.textContent = data.error ?? 'Falha ao transmitir.';
                } else {
                    transmitirResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
                    transmitirResultado.textContent = data.message;
                }
            } catch (e) {
                transmitirResultado.classList.remove('hidden');
                transmitirResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                transmitirResultado.textContent = 'Erro de comunicação com o servidor.';
            } finally {
                btnTransmitirDeclaracao.disabled = false;
                btnTransmitirDeclaracao.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Transmitir declaração (real)';
            }
        }
    });

    // ─── Atividades e receitas do período (réplica do assistente do e-CAC) ─────
    const dropdownAtividadesWrapper = document.getElementById('dropdownAtividadesWrapper');
    const btnToggleAtividades = document.getElementById('btnToggleAtividades');
    const resumoAtividadesSelecionadas = document.getElementById('resumoAtividadesSelecionadas');
    const checklistAtividades = document.getElementById('checklistAtividades');
    const painelAtividades = document.getElementById('painelAtividades');
    const somaAtividadesResumo = document.getElementById('somaAtividadesResumo');
    const btnSalvarAtividades = document.getElementById('btnSalvarAtividades');

    function abrirDropdownAtividades() {
        checklistAtividades.classList.remove('hidden');
        btnToggleAtividades.querySelector('i').classList.add('rotate-180');
    }

    function fecharDropdownAtividades() {
        checklistAtividades.classList.add('hidden');
        btnToggleAtividades.querySelector('i').classList.remove('rotate-180');
        atualizarResumoAtividadesSelecionadas();
    }

    function atualizarResumoAtividadesSelecionadas() {
        const selecionadas = Array.from(checklistAtividades.querySelectorAll('.checkbox-atividade:checked'));

        resumoAtividadesSelecionadas.textContent = selecionadas.length === 0
            ? 'Selecionar atividades...'
            : selecionadas.map(cb => cb.value).join(', ') + (selecionadas.length > 1 ? ' atividades' : ' atividade');
    }

    btnToggleAtividades.addEventListener('click', () => {
        checklistAtividades.classList.contains('hidden') ? abrirDropdownAtividades() : fecharDropdownAtividades();
    });

    document.addEventListener('click', (event) => {
        if (!dropdownAtividadesWrapper.contains(event.target) && !checklistAtividades.classList.contains('hidden')) {
            fecharDropdownAtividades();
        }
    });

    const MOTIVOS_SUSPENSAO = { 1: 'Liminar em MS', 2: 'Depósito Judicial', 3: 'Antecipação de Tutela', 4: 'Liminar em Medida Cautelar', 5: 'Depósito Administrativo', 6: 'Outros' };
    const OPCOES_AJUSTE = {
        normal: 'Normal',
        imunidade: 'Imunidade',
        lancamento_oficio: 'Lançamento de Ofício',
        substituicao_tributaria: 'Substituição Tributária',
        tributacao_monofasica: 'Tributação Monofásica',
        antecipacao_encerramento: 'Antecipação c/ Encerramento',
        retencao_iss: 'Retenção de ISS',
        isencao: 'Isenção',
        reducao: 'Redução',
        exigibilidade_suspensa: 'Exigibilidade Suspensa',
    };

    function renderChecklistAtividades() {
        const grupos = {};
        Object.entries(window.PGDASD_ATIVIDADES).forEach(([id, a]) => {
            grupos[a.categoria] = grupos[a.categoria] || [];
            grupos[a.categoria].push({ id, ...a });
        });

        checklistAtividades.innerHTML = Object.entries(grupos).map(([categoria, itens]) => `
            <div>
                <div class="text-xs font-semibold text-gray-500 dark:text-slate-400 mb-1">${escapeHtml(categoria)}</div>
                ${itens.map(it => `
                    <label class="flex items-start gap-2 text-xs py-0.5 cursor-pointer">
                        <input type="checkbox" class="checkbox-atividade mt-0.5" value="${it.id}">
                        <span>${it.id} — ${escapeHtml(it.descricao)}</span>
                    </label>
                `).join('')}
            </div>
        `).join('');

        checklistAtividades.querySelectorAll('.checkbox-atividade').forEach(cb => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    adicionarPainelAtividade(cb.value);
                } else {
                    removerPainelAtividade(cb.value);
                }
                atualizarSomaAtividades();
                atualizarResumoAtividadesSelecionadas();
            });
        });
    }

    function renderTributoCell(codTributo, dadosExistentes) {
        const tipoAjuste = dadosExistentes?.tipo_ajuste ?? 'normal';
        const identificador = dadosExistentes?.identificador_isencao ?? 1;
        const percentual = dadosExistentes?.percentual_reducao ?? '';
        const motivo = dadosExistentes?.motivo_suspensao ?? 1;

        const cell = document.createElement('td');
        cell.className = 'align-top px-2 py-2 tributo-row';
        cell.dataset.codTributo = codTributo;

        cell.innerHTML = `
            <select class="select-tipo-ajuste w-full text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-1.5 py-1">
                ${Object.entries(OPCOES_AJUSTE).map(([v, l]) => `<option value="${v}" ${v === tipoAjuste ? 'selected' : ''}>${escapeHtml(l)}</option>`).join('')}
            </select>
            <select class="select-identificador hidden w-full mt-1 text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-1.5 py-1">
                <option value="1" ${identificador == 1 ? 'selected' : ''}>Normal</option>
                <option value="2" ${identificador == 2 ? 'selected' : ''}>Cesta básica</option>
            </select>
            <input type="number" step="0.01" placeholder="% redução" value="${percentual}" class="input-percentual hidden w-full mt-1 text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-1.5 py-1">
            <select class="select-motivo hidden w-full mt-1 text-xs rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-1.5 py-1">
                ${Object.entries(MOTIVOS_SUSPENSAO).map(([v, l]) => `<option value="${v}" ${v == motivo ? 'selected' : ''}>${escapeHtml(l)}</option>`).join('')}
            </select>
        `;

        const selectTipo = cell.querySelector('.select-tipo-ajuste');
        const selectIdent = cell.querySelector('.select-identificador');
        const inputPercentual = cell.querySelector('.input-percentual');
        const selectMotivo = cell.querySelector('.select-motivo');

        function atualizarVisibilidadeAjuste() {
            const v = selectTipo.value;
            selectIdent.classList.toggle('hidden', !(v === 'isencao' || v === 'reducao'));
            inputPercentual.classList.toggle('hidden', v !== 'reducao');
            selectMotivo.classList.toggle('hidden', v !== 'exigibilidade_suspensa');
        }
        atualizarVisibilidadeAjuste();
        selectTipo.addEventListener('change', atualizarVisibilidadeAjuste);

        return cell;
    }

    function adicionarPainelAtividade(idAtividade, dadosExistentes) {
        if (painelAtividades.querySelector(`[data-id-atividade="${idAtividade}"]`)) return;

        const atividade = window.PGDASD_ATIVIDADES[idAtividade];
        if (!atividade) return;

        const div = document.createElement('div');
        div.className = 'atividade-panel rounded-lg overflow-hidden border border-gray-200 dark:border-slate-700';
        div.dataset.idAtividade = idAtividade;

        div.innerHTML = `
            <div class="bg-green-600 text-white text-xs font-medium px-3 py-2">${idAtividade} — ${escapeHtml(atividade.descricao)}</div>
            <div class="p-3 overflow-x-auto">
                <table class="text-xs border-separate" style="border-spacing: 0.5rem 0;">
                    <thead>
                        <tr>
                            <th class="text-left text-gray-500 dark:text-slate-400 font-medium pb-1">Receita (R$)</th>
                            <th class="tributos-thead-cells"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="align-top py-2 pr-2">
                                <input type="number" step="0.01" class="input-valor-atividade w-32 rounded border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-2 py-1 text-sm" value="${dadosExistentes?.valor ?? ''}">
                            </td>
                            <td class="tributos-tbody-cells"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;

        const tributosExistentesMap = {};
        (dadosExistentes?.tributos ?? []).forEach(t => { tributosExistentesMap[t.cod_tributo] = t; });

        // As células de tributo vêm como <td> soltos (um por tributo) — inserimos cada
        // um como irmão do <td> placeholder da receita, e no cabeçalho o nome correspondente.
        const linhaCabecalho = div.querySelector('thead tr');
        const linhaCorpo = div.querySelector('tbody tr');
        const thPlaceholder = div.querySelector('.tributos-thead-cells');
        const tdPlaceholder = div.querySelector('.tributos-tbody-cells');
        thPlaceholder.remove();
        tdPlaceholder.remove();

        atividade.tributos.forEach(codTributo => {
            const th = document.createElement('th');
            th.className = 'text-left text-gray-500 dark:text-slate-400 font-medium pb-1';
            th.textContent = window.PGDASD_TRIBUTOS[codTributo] ?? codTributo;
            linhaCabecalho.appendChild(th);

            linhaCorpo.appendChild(renderTributoCell(codTributo, tributosExistentesMap[codTributo]));
        });

        div.querySelector('.input-valor-atividade').addEventListener('input', atualizarSomaAtividades);

        painelAtividades.appendChild(div);
    }

    function removerPainelAtividade(idAtividade) {
        const el = painelAtividades.querySelector(`[data-id-atividade="${idAtividade}"]`);
        if (el) el.remove();
        atualizarSomaAtividades();
    }

    function atualizarSomaAtividades() {
        let soma = 0;
        painelAtividades.querySelectorAll('.atividade-panel').forEach(p => {
            soma += parseFloat(p.querySelector('.input-valor-atividade').value || '0');
        });

        const alvo = selectRegimeApuracao.value === 'caixa'
            ? parseFloat(inputReceitaCaixa.value || '0')
            : parseFloat(inputReceitaCompetencia.value || '0');

        const bate = Math.abs(soma - alvo) < 0.01;

        somaAtividadesResumo.textContent = `Soma das atividades: R$ ${soma.toFixed(2)} — Receita bruta lançada (${selectRegimeApuracao.value}): R$ ${alvo.toFixed(2)} ${bate ? '✓ bate' : '✗ não bate'}`;
        somaAtividadesResumo.classList.remove('bg-green-50', 'text-green-700', 'dark:bg-green-900/20', 'dark:text-green-400', 'bg-yellow-50', 'text-yellow-700', 'dark:bg-yellow-900/20', 'dark:text-yellow-400');
        somaAtividadesResumo.classList.add(...(bate
            ? ['bg-green-50', 'text-green-700', 'dark:bg-green-900/20', 'dark:text-green-400']
            : ['bg-yellow-50', 'text-yellow-700', 'dark:bg-yellow-900/20', 'dark:text-yellow-400']));
    }

    inputReceitaCompetencia.addEventListener('input', atualizarSomaAtividades);
    inputReceitaCaixa.addEventListener('input', atualizarSomaAtividades);
    selectRegimeApuracao.addEventListener('change', atualizarSomaAtividades);

    async function carregarReceitasAtividades() {
        painelAtividades.innerHTML = '';
        checklistAtividades.querySelectorAll('.checkbox-atividade').forEach(cb => cb.checked = false);

        const url = new URL('{{ route('simples-nacional.receitas-atividades.get') }}');
        url.searchParams.set('cliente_id', selectClienteTransmissao.value);
        url.searchParams.set('periodo_apuracao', inputPeriodoTransmissao.value);

        const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await resp.json();

        (data.atividades ?? []).forEach(a => {
            const cb = checklistAtividades.querySelector(`.checkbox-atividade[value="${a.id_atividade}"]`);
            if (cb) cb.checked = true;
            adicionarPainelAtividade(a.id_atividade, a);
        });

        atualizarSomaAtividades();
        atualizarResumoAtividadesSelecionadas();
    }

    btnSalvarAtividades.addEventListener('click', async function () {
        const atividades = [];

        painelAtividades.querySelectorAll('.atividade-panel').forEach(p => {
            const idAtividade = parseInt(p.dataset.idAtividade, 10);
            const valor = parseFloat(p.querySelector('.input-valor-atividade').value || '0');
            const tributos = [];

            p.querySelectorAll('.tributo-row').forEach(row => {
                const tipoAjuste = row.querySelector('.select-tipo-ajuste').value;
                if (tipoAjuste === 'normal') return;

                const identificadorEl = row.querySelector('.select-identificador');
                const percentualEl = row.querySelector('.input-percentual');
                const motivoEl = row.querySelector('.select-motivo');

                tributos.push({
                    cod_tributo: parseInt(row.dataset.codTributo, 10),
                    tipo_ajuste: tipoAjuste,
                    identificador_isencao: (tipoAjuste === 'isencao' || tipoAjuste === 'reducao') ? parseInt(identificadorEl.value, 10) : null,
                    percentual_reducao: tipoAjuste === 'reducao' ? parseFloat(percentualEl.value || '0') : null,
                    motivo_suspensao: tipoAjuste === 'exigibilidade_suspensa' ? parseInt(motivoEl.value, 10) : null,
                    valor: valor,
                });
            });

            atividades.push({ id_atividade: idAtividade, valor, tributos });
        });

        if (atividades.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione ao menos uma atividade.' });
            return;
        }

        this.disabled = true;

        try {
            const resp = await fetch('{{ route('simples-nacional.receitas-atividades.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente_id: selectClienteTransmissao.value,
                    periodo_apuracao: inputPeriodoTransmissao.value,
                    atividades,
                }),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, timer: 1500, showConfirmButton: false });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
        }
    });

    renderChecklistAtividades();
    protegerComConfigSerpro('cardTransmitirDeclaracao');
    </script>
    @endpush
@endsection
