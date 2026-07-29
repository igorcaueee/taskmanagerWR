@extends('layouts.internal')

@section('title', 'Consulta CND — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-shield"></i> Consulta CND</h1>
            <p class="text-gray-700 dark:text-gray-300">Certidão negativa de débito federal (PGFN/RFB) de PJ, PF ou imóvel rural. Produto contratado à parte do Integra Contador.</p>
        </div>

        {{-- ─── Configuração da API Consulta CND ────────────────────────────────── --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2">
                        <i class="fa-solid fa-key text-brand"></i> Configuração da API Consulta CND
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">
                        Consumer Key/Secret do contrato "Consulta CND" (separado do Integra Contador) — obtidos na Área do Cliente SERPRO.
                    </p>
                </div>
                <button type="button" id="btnAbrirConfigCnd" class="underline text-brand bg-transparent border-0 text-sm whitespace-nowrap">Editar configuração</button>
            </div>

            <div id="configCndStatus" class="mt-3 space-y-2">
                <div id="configCndOk" class="hidden items-center gap-2 text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Configurado — ambiente <strong id="configCndAmbiente"></strong></span>
                </div>
                <div id="configCndNone" class="hidden items-center gap-2 text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-slate-700 rounded-lg px-3 py-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Nenhuma configuração cadastrada ainda — cadastre o Consumer Key/Secret para habilitar consultas.</span>
                </div>
            </div>

            <div id="formConfigCnd" class="hidden mt-4 pt-4 border-t border-gray-100 dark:border-slate-700 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Consumer Key</label>
                    <input type="text" id="inputConsumerKeyCnd"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Consumer Secret</label>
                    <input type="password" id="inputConsumerSecretCnd"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ambiente</label>
                    <select id="selectAmbienteCnd"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="trial">Trial (testes)</option>
                        <option value="producao">Produção</option>
                    </select>
                </div>
                <div class="md:col-span-3 flex gap-3">
                    <button type="button" id="btnSalvarConfigCnd"
                            class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                        Salvar configuração
                    </button>
                    <button type="button" id="btnTestarConexaoCnd"
                            class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                        Testar conexão
                    </button>
                </div>
                <div id="configCndResultado" class="hidden md:col-span-3 text-sm rounded-lg px-3 py-2"></div>
            </div>
        </div>

        {{-- ─── Consultar certidão ───────────────────────────────────────────────── --}}
        <div id="cardConsultarCnd" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-shield text-brand"></i> Consultar/emitir certidão
            </h2>
            <div class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Cada consulta pode gerar cobrança real pela SERPRO, independente do resultado (certidão encontrada, emitida ou não emitida).
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente (preenche o número automaticamente)</label>
                    <select id="selectClienteCnd"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Preencher manualmente...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}" data-cpfcnpj="{{ preg_replace('/\D/', '', $cli->cpfcnpj ?? '') }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Tipo</label>
                    <select id="selectTipoContribuinteCnd" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="1">Pessoa Jurídica</option>
                        <option value="2">Pessoa Física</option>
                        <option value="3">Imóvel Rural (NIRF)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Nº inscrição (CNPJ/CPF/NIRF)</label>
                    <input type="text" id="inputNumeroInscricaoCnd" placeholder="Só números"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4 mb-4">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                    <input type="checkbox" id="chkGerarPdfCnd" checked class="rounded border-gray-300 dark:border-slate-600">
                    Gerar certidão em PDF
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                    <input type="checkbox" id="chkCarimboTempoCnd" class="rounded border-gray-300 dark:border-slate-600">
                    Assinar com carimbo do tempo (contrato "Check Time Stamp")
                </label>
            </div>

            <button type="button" id="btnConsultarCnd"
                    class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                Consultar certidão
            </button>

            <div id="cndResultado" class="hidden mt-4"></div>
        </div>
    </div>

    @push('scripts')
    <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str ?? '';
        return div.innerHTML;
    }

    // ─── Configuração ───────────────────────────────────────────────────────────
    const btnAbrirConfigCnd     = document.getElementById('btnAbrirConfigCnd');
    const configCndOk           = document.getElementById('configCndOk');
    const configCndNone         = document.getElementById('configCndNone');
    const configCndAmbiente     = document.getElementById('configCndAmbiente');
    const formConfigCnd         = document.getElementById('formConfigCnd');
    const inputConsumerKeyCnd   = document.getElementById('inputConsumerKeyCnd');
    const inputConsumerSecretCnd = document.getElementById('inputConsumerSecretCnd');
    const selectAmbienteCnd     = document.getElementById('selectAmbienteCnd');
    const configCndResultado    = document.getElementById('configCndResultado');
    const cardConsultarCnd      = document.getElementById('cardConsultarCnd');

    async function carregarConfigCnd() {
        try {
            const resp = await fetch('{{ route('cnd.configuracao.get') }}', { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();

            configCndOk.classList.toggle('hidden', !data.configurado);
            configCndOk.classList.toggle('flex', data.configurado);
            configCndNone.classList.toggle('hidden', data.configurado);
            configCndNone.classList.toggle('flex', !data.configurado);

            if (data.configurado) {
                configCndAmbiente.textContent = data.ambiente;
                selectAmbienteCnd.value = data.ambiente;
            } else {
                formConfigCnd.classList.remove('hidden');
            }

            cardConsultarCnd.classList.toggle('hidden', !data.configurado);
        } catch (e) {
            configCndNone.classList.remove('hidden');
            configCndNone.classList.add('flex');
        }
    }

    btnAbrirConfigCnd.addEventListener('click', () => formConfigCnd.classList.toggle('hidden'));

    document.getElementById('btnSalvarConfigCnd').addEventListener('click', async function () {
        const consumerKey = inputConsumerKeyCnd.value.trim();
        const consumerSecret = inputConsumerSecretCnd.value.trim();

        if (!consumerKey || !consumerSecret) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe o Consumer Key e o Consumer Secret.' });
            return;
        }

        this.disabled = true;

        try {
            const resp = await fetch('{{ route('cnd.configuracao.salvar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    consumer_key: consumerKey,
                    consumer_secret: consumerSecret,
                    ambiente: selectAmbienteCnd.value,
                }),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao salvar configuração.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Salvo', text: data.message, timer: 1500, showConfirmButton: false });
            formConfigCnd.classList.add('hidden');
            await carregarConfigCnd();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('btnTestarConexaoCnd').addEventListener('click', async function () {
        this.disabled = true;
        configCndResultado.classList.add('hidden');

        try {
            const resp = await fetch('{{ route('cnd.configuracao.testar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            const data = await resp.json();

            configCndResultado.classList.remove('hidden');
            configCndResultado.classList.remove(
                'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
                'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
            );

            if (!resp.ok || data.error) {
                configCndResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
                configCndResultado.textContent = data.error ?? 'Falha no teste de conexão.';
                return;
            }

            configCndResultado.classList.add('text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20');
            configCndResultado.textContent = data.message;
        } catch (e) {
            configCndResultado.classList.remove('hidden');
            configCndResultado.classList.add('text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20');
            configCndResultado.textContent = 'Erro de comunicação com o servidor.';
        } finally {
            this.disabled = false;
        }
    });

    carregarConfigCnd();

    // ─── Consultar certidão ──────────────────────────────────────────────────────
    const selectClienteCnd = document.getElementById('selectClienteCnd');
    const inputNumeroInscricaoCnd = document.getElementById('inputNumeroInscricaoCnd');

    selectClienteCnd.addEventListener('change', () => {
        const option = selectClienteCnd.selectedOptions[0];
        const cpfcnpj = option?.dataset.cpfcnpj;

        if (cpfcnpj) {
            inputNumeroInscricaoCnd.value = cpfcnpj;
            document.getElementById('selectTipoContribuinteCnd').value = cpfcnpj.length === 11 ? '2' : '1';
        }
    });

    const MENSAGENS_STATUS_CND = {
        1: { texto: 'Certidão válida encontrada.', tipo: 'success' },
        2: { texto: 'Certidão emitida agora.', tipo: 'success' },
        3: { texto: 'Processamento OK — certidão não emitida.', tipo: 'warning' },
        4: { texto: 'Certidão não emitida — situação cadastral impeditiva.', tipo: 'warning' },
        5: { texto: 'Base de dados sendo atualizada — tente novamente em instantes.', tipo: 'warning' },
        6: { texto: 'Sistema de apoio indisponível — tente novamente em instantes.', tipo: 'warning' },
        7: { texto: 'Em processamento — clique em "Verificar novamente" em alguns segundos.', tipo: 'info' },
        8: { texto: 'Contribuinte não cadastrado.', tipo: 'error' },
        9: { texto: 'Parâmetros ausentes ou inválidos.', tipo: 'error' },
        99: { texto: 'Erro no servidor da SERPRO.', tipo: 'error' },
    };

    const CORES_TIPO_CND = {
        success: ['text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20'],
        warning: ['text-amber-700', 'dark:text-amber-400', 'bg-amber-50', 'dark:bg-amber-900/20'],
        info: ['text-blue-700', 'dark:text-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20'],
        error: ['text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'],
    };

    const cndResultado = document.getElementById('cndResultado');

    async function executarConsultaCnd(chave) {
        const tipo = document.getElementById('selectTipoContribuinteCnd').value;
        const numero = inputNumeroInscricaoCnd.value.replace(/\D/g, '');
        const gerarPdf = document.getElementById('chkGerarPdfCnd').checked;
        const carimboTempo = document.getElementById('chkCarimboTempoCnd').checked;

        if (!numero) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe o número de inscrição (ou selecione um cliente).' });
            return;
        }

        const btn = document.getElementById('btnConsultarCnd');
        btn.disabled = true;
        const textoOriginal = btn.textContent;
        btn.textContent = 'Consultando...';

        try {
            const resp = await fetch('{{ route('cnd.consultar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tipo_contribuinte: tipo,
                    numero_inscricao: numero,
                    gerar_pdf: gerarPdf,
                    carimbo_tempo: carimboTempo,
                    chave: chave ?? null,
                }),
            });
            const data = await resp.json();

            cndResultado.classList.remove('hidden');

            if (!resp.ok || data.error) {
                cndResultado.innerHTML = `<div class="text-sm rounded-lg px-3 py-2 text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20">${escapeHtml(data.error ?? 'Falha ao consultar certidão.')}</div>`;
                return;
            }

            const info = MENSAGENS_STATUS_CND[data.status] ?? { texto: `Status ${data.status}`, tipo: 'error' };
            const cores = CORES_TIPO_CND[info.tipo].join(' ');

            let html = `<div class="text-sm rounded-lg px-3 py-2 ${cores} mb-2">${escapeHtml(info.texto)}${data.mensagem ? ' — ' + escapeHtml(data.mensagem) : ''}</div>`;

            if (data.status === 7 && data.chave) {
                html += `<button type="button" id="btnVerificarNovamenteCnd" class="py-1.5 px-3 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">Verificar novamente</button>`;
            }

            if (data.arquivo) {
                html += `<div class="mt-2"><a href="${data.arquivo.url}" target="_blank" class="text-brand underline text-sm"><i class="fa-solid fa-file-pdf"></i> Baixar certidão em PDF</a></div>`;
            }

            cndResultado.innerHTML = html;

            const btnVerificar = document.getElementById('btnVerificarNovamenteCnd');
            if (btnVerificar) {
                btnVerificar.addEventListener('click', () => executarConsultaCnd(data.chave));
            }
        } catch (e) {
            cndResultado.classList.remove('hidden');
            cndResultado.innerHTML = `<div class="text-sm rounded-lg px-3 py-2 text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20">Erro de comunicação com o servidor.</div>`;
        } finally {
            btn.disabled = false;
            btn.textContent = textoOriginal;
        }
    }

    document.getElementById('btnConsultarCnd').addEventListener('click', () => executarConsultaCnd(null));
    </script>
    @endpush
@endsection
