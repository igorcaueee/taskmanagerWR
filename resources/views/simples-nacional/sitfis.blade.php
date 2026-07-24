@extends('layouts.internal')

@section('title', 'SITFIS — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-shield"></i> SITFIS</h1>
            <p class="text-gray-700 dark:text-gray-300">Relatório de Situação Fiscal — visão geral de pendências perante RFB/PGFN.</p>
        </div>

        <div id="cardSitfis" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-file-shield text-brand"></i> Relatório de Situação Fiscal do cliente
            </h2>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">
                Gera o relatório de situação fiscal completo do cliente perante a Receita Federal e a PGFN — pode levar alguns segundos (fluxo assíncrono da própria SERPRO), aguarde sem fechar a página.
            </p>

            <div class="flex flex-wrap gap-3 items-end mb-4">
                <div class="flex-1 min-w-60">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                    <select id="selectClienteSitfis"
                            class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" id="btnGerarSitfis"
                        class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    Gerar relatório
                </button>
            </div>

            <div id="sitfisStatus" class="hidden text-sm rounded-lg px-3 py-2"></div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    const selectClienteSitfis = document.getElementById('selectClienteSitfis');
    const btnGerarSitfis      = document.getElementById('btnGerarSitfis');
    const sitfisStatus        = document.getElementById('sitfisStatus');

    const MAX_TENTATIVAS_SITFIS = 20;

    function definirStatusSitfis(texto, tipo) {
        sitfisStatus.classList.remove('hidden');
        sitfisStatus.classList.remove(
            'text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20',
            'text-blue-700', 'dark:text-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20',
            'text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'
        );

        const cores = {
            info: ['text-blue-700', 'dark:text-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20'],
            sucesso: ['text-green-700', 'dark:text-green-400', 'bg-green-50', 'dark:bg-green-900/20'],
            erro: ['text-red-700', 'dark:text-red-400', 'bg-red-50', 'dark:bg-red-900/20'],
        };
        sitfisStatus.classList.add(...(cores[tipo] ?? cores.info));
        sitfisStatus.textContent = texto;
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function tentarEmitir(clienteId, protocolo, tentativa) {
        if (tentativa > MAX_TENTATIVAS_SITFIS) {
            definirStatusSitfis('O relatório demorou mais que o esperado para ficar pronto. Tente novamente em alguns instantes.', 'erro');
            return;
        }

        const resp = await fetch('{{ route('simples-nacional.sitfis.emitir') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ cliente_id: clienteId, protocolo }),
        });
        const data = await resp.json();

        if (!resp.ok || data.error) {
            definirStatusSitfis(data.error ?? 'Falha ao emitir o relatório.', 'erro');
            return;
        }

        if (data.pronto) {
            definirStatusSitfis('Relatório gerado com sucesso.', 'sucesso');
            if (data.arquivo) {
                window.open(data.arquivo.url, '_blank');
            }
            return;
        }

        const espera = data.tempo_espera_ms ?? 4000;
        definirStatusSitfis(`Gerando relatório... aguardando ${Math.round(espera / 1000)}s (tentativa ${tentativa}/${MAX_TENTATIVAS_SITFIS})`, 'info');
        await sleep(espera);
        await tentarEmitir(clienteId, protocolo, tentativa + 1);
    }

    btnGerarSitfis.addEventListener('click', async function () {
        const clienteId = selectClienteSitfis.value;

        if (!clienteId) {
            Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione o cliente.' });
            return;
        }

        this.disabled = true;
        definirStatusSitfis('Solicitando protocolo do relatório...', 'info');

        try {
            const resp = await fetch('{{ route('simples-nacional.sitfis.solicitar') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ cliente_id: clienteId }),
            });
            const data = await resp.json();

            if (!resp.ok || data.error) {
                definirStatusSitfis(data.error ?? 'Falha ao solicitar o relatório.', 'erro');
                return;
            }

            if (!data.protocolo) {
                definirStatusSitfis('A API não retornou um protocolo válido.', 'erro');
                return;
            }

            const espera = data.tempo_espera_ms ?? 4000;
            definirStatusSitfis(`Protocolo obtido, gerando relatório... aguardando ${Math.round(espera / 1000)}s`, 'info');
            await sleep(espera);
            await tentarEmitir(clienteId, data.protocolo, 1);
        } catch (e) {
            definirStatusSitfis('Erro de comunicação com o servidor.', 'erro');
        } finally {
            this.disabled = false;
        }
    });

    protegerComConfigSerpro('cardSitfis');
    </script>
    @endpush
@endsection
