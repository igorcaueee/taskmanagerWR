@extends('layouts.internal')

@section('title', 'DCTFWeb — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-file-invoice"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-invoice"></i> DCTFWeb</h1>
            <p class="text-gray-700 dark:text-gray-300">Guia (DARF), recibo, relatório completo e XML de uma declaração DCTFWeb já calculada pelo eSocial/EFD-Reinf.</p>
        </div>

        <div id="cardDctfWeb" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-invoice text-brand"></i> Consultar / emitir DCTFWeb
            </h2>
            <div class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Só consulta e emissão de guia de uma declaração já existente. <strong>Transmitir</strong> a DCTFWeb (fechar a declaração) não é feito por aqui — exige assinatura digital do XML com certificado ICP-Brasil, ainda precisa ser feito pelo e-CAC.
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                    <select id="selectClienteDctfWeb"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Categoria</label>
                    <select id="selectCategoriaDctfWeb" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        @foreach($categorias as $codigo => $nome)
                            <option value="{{ $codigo }}">{{ $codigo }} — {{ str_replace('_', ' ', $nome) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Ano (PA)</label>
                    <input type="text" id="inputAnoDctfWeb" value="{{ now()->year }}" maxlength="4"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                <div id="campoMesDctfWeb">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Mês (PA)</label>
                    <select id="selectMesDctfWeb" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($m == now()->subMonthNoOverflow()->month)>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>
                <div id="campoDiaDctfWeb" class="hidden">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Dia (PA) — só espetáculo desportivo</label>
                    <input type="text" id="inputDiaDctfWeb" maxlength="2"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div id="campoCnoDctfWeb" class="hidden">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CNO da obra — só aferição</label>
                    <input type="text" id="inputCnoDctfWeb"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div id="campoProcReclamatoriaDctfWeb" class="hidden">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Nº processo — só reclamatória trabalhista</label>
                    <input type="text" id="inputProcReclamatoriaDctfWeb"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Nº recibo de entrega (opcional)</label>
                    <input type="text" id="inputReciboDctfWeb" placeholder="Usa a mais recente se vazio"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" id="btnGerarGuiaDctfWeb" data-rota="{{ route('simples-nacional.dctfweb.guia') }}" data-label="Gerar guia (DARF)"
                        class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    Gerar guia (DARF)
                </button>
                <button type="button" id="btnGerarGuiaAndamentoDctfWeb" data-rota="{{ route('simples-nacional.dctfweb.guia-andamento') }}" data-label="Gerar guia (em andamento)"
                        class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                    Gerar guia (em andamento)
                </button>
                <button type="button" id="btnConsultarReciboDctfWeb" data-rota="{{ route('simples-nacional.dctfweb.recibo') }}" data-label="Consultar recibo"
                        class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                    Consultar recibo
                </button>
                <button type="button" id="btnConsultarCompletaDctfWeb" data-rota="{{ route('simples-nacional.dctfweb.completa') }}" data-label="Consultar declaração completa"
                        class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                    Consultar declaração completa
                </button>
                <button type="button" id="btnConsultarXmlDctfWeb" data-rota="{{ route('simples-nacional.dctfweb.xml') }}" data-label="Baixar XML"
                        class="py-2 px-4 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600">
                    Baixar XML
                </button>
            </div>

            <div id="dctfWebErro" class="hidden mt-3 text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2"></div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    const selectClienteDctfWeb   = document.getElementById('selectClienteDctfWeb');
    const selectCategoriaDctfWeb = document.getElementById('selectCategoriaDctfWeb');
    const inputAnoDctfWeb        = document.getElementById('inputAnoDctfWeb');
    const campoMesDctfWeb        = document.getElementById('campoMesDctfWeb');
    const selectMesDctfWeb       = document.getElementById('selectMesDctfWeb');
    const campoDiaDctfWeb        = document.getElementById('campoDiaDctfWeb');
    const inputDiaDctfWeb        = document.getElementById('inputDiaDctfWeb');
    const campoCnoDctfWeb        = document.getElementById('campoCnoDctfWeb');
    const inputCnoDctfWeb        = document.getElementById('inputCnoDctfWeb');
    const campoProcReclamatoriaDctfWeb = document.getElementById('campoProcReclamatoriaDctfWeb');
    const inputProcReclamatoriaDctfWeb = document.getElementById('inputProcReclamatoriaDctfWeb');
    const inputReciboDctfWeb     = document.getElementById('inputReciboDctfWeb');
    const dctfWebErro            = document.getElementById('dctfWebErro');

    const CATEGORIAS_13_SALARIO = ['41', '51'];

    function atualizarCamposDctfWeb() {
        const categoria = selectCategoriaDctfWeb.value;
        campoMesDctfWeb.classList.toggle('hidden', CATEGORIAS_13_SALARIO.includes(categoria));
        campoDiaDctfWeb.classList.toggle('hidden', categoria !== '45');
        campoCnoDctfWeb.classList.toggle('hidden', categoria !== '44');
        campoProcReclamatoriaDctfWeb.classList.toggle('hidden', categoria !== '46');
    }

    selectCategoriaDctfWeb.addEventListener('change', atualizarCamposDctfWeb);
    atualizarCamposDctfWeb();

    async function executarAcaoDctfWeb(botao) {
        const clienteId = selectClienteDctfWeb.value;
        const categoria = selectCategoriaDctfWeb.value;
        const ano = inputAnoDctfWeb.value;

        if (!clienteId || !ano) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente e informe o ano.' });
            return;
        }

        const payload = {
            cliente_id: clienteId,
            categoria: categoria,
            ano_pa: ano,
        };

        if (!campoMesDctfWeb.classList.contains('hidden')) payload.mes_pa = selectMesDctfWeb.value;
        if (!campoDiaDctfWeb.classList.contains('hidden')) payload.dia_pa = inputDiaDctfWeb.value;
        if (!campoCnoDctfWeb.classList.contains('hidden')) payload.cno_afericao = inputCnoDctfWeb.value;
        if (!campoProcReclamatoriaDctfWeb.classList.contains('hidden')) payload.num_proc_reclamatoria = inputProcReclamatoriaDctfWeb.value;
        if (inputReciboDctfWeb.value) payload.numero_recibo_entrega = inputReciboDctfWeb.value;

        const textoOriginal = botao.textContent;
        botao.disabled = true;
        botao.textContent = 'Processando...';
        dctfWebErro.classList.add('hidden');

        try {
            const resp = await fetch(botao.dataset.rota, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                dctfWebErro.textContent = data.error ?? `Falha ao executar "${botao.dataset.label}".`;
                dctfWebErro.classList.remove('hidden');
                return;
            }

            if (data.arquivo) {
                window.open(data.arquivo.url, '_blank');
            } else {
                Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Nenhum arquivo retornado.' });
            }
        } catch (e) {
            dctfWebErro.textContent = 'Erro de comunicação com o servidor.';
            dctfWebErro.classList.remove('hidden');
        } finally {
            botao.disabled = false;
            botao.textContent = textoOriginal;
        }
    }

    document.querySelectorAll('#cardDctfWeb button[data-rota]').forEach(botao => {
        botao.addEventListener('click', () => executarAcaoDctfWeb(botao));
    });

    protegerComConfigSerpro('cardDctfWeb');
    </script>
    @endpush
@endsection
