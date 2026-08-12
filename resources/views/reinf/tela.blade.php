@extends('layouts.internal')

@section('title', 'EFD-Reinf — Utilitários Fiscal')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="mb-6">
            <a href="{{ route('simples-nacional.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mt-1"><i class="fa-solid fa-file-shield"></i> EFD-Reinf — Fechamento</h1>
            <p class="text-gray-700 dark:text-gray-300">Envio e consulta dos eventos de fechamento R-2099 (série R-1000/R-2000) e R-4099 (série R-4000), direto pela API da Receita — usa o certificado digital cadastrado do próprio cliente.</p>
        </div>

        <div class="text-sm text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-900/40 rounded-lg px-4 py-3 mb-6">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <strong>Em desenvolvimento — não usar em produção ainda.</strong> A estrutura do XML do evento foi conferida contra o leiaute oficial, mas o envelope do lote e o formato do retorno da API ainda não foram validados contra um envio real. Acesso restrito a Diretor e TI enquanto isso não for testado em Produção Restrita.
        </div>

        <div id="cardReinf" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mb-6">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-1">
                <i class="fa-solid fa-paper-plane text-brand"></i> Enviar fechamento
            </h2>
            <div class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2 mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
                A Receita só aceita o fechamento se os eventos periódicos do período (R-2010/R-2020 etc, ou R-4010/R-4020 etc) já tiverem sido transmitidos por algum canal — isso não é feito por aqui ainda. Envie o fechamento só depois de confirmar isso.
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                    <select id="selectClienteReinf" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="">Selecione...</option>
                        @foreach($clientes as $cli)
                            <option value="{{ $cli->id }}">{{ $cli->nome }} — {{ $cli->cpfcnpj }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Evento</label>
                    <select id="selectTipoEventoReinf" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="R-2099">R-2099 (série R-1000/R-2000)</option>
                        <option value="R-4099">R-4099 (série R-4000)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Período de apuração</label>
                    <input type="month" id="inputPeriodoReinf" value="{{ now()->subMonthNoOverflow()->format('Y-m') }}"
                           class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <h3 class="text-xs font-medium text-gray-600 dark:text-slate-400 mb-2">Responsável pela informação (ideRespInf)</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Nome</label>
                    <input type="text" id="inputRespNomeReinf" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CPF</label>
                    <input type="text" id="inputRespCpfReinf" maxlength="14" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Telefone</label>
                    <input type="text" id="inputRespTelefoneReinf" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">E-mail</label>
                    <input type="email" id="inputRespEmailReinf" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <div id="blocoIndicadoresR2099">
                <h3 class="text-xs font-medium text-gray-600 dark:text-slate-400 mb-2">Indicadores do fechamento (infoFech) — marque "Sim" só se os eventos periódicos correspondentes já foram transmitidos por outro canal para este período</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Serviços tomados (R-2010)</label>
                        <select id="selectEvtServTm" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="N">Não</option>
                            <option value="S">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Serviços prestados (R-2020)</label>
                        <select id="selectEvtServPr" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="N">Não</option>
                            <option value="S">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Recursos recebidos assoc. desportiva (R-2030)</label>
                        <select id="selectEvtAssDespRec" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="N">Não</option>
                            <option value="S">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Recursos repassados assoc. desportiva (R-2040)</label>
                        <select id="selectEvtAssDespRep" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="N">Não</option>
                            <option value="S">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Comercialização de produção (R-2050)</label>
                        <select id="selectEvtComProd" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="N">Não</option>
                            <option value="S">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">CPRB (R-2060)</label>
                        <select id="selectEvtCPRB" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="N">Não</option>
                            <option value="S">Sim</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Aquisição de produção rural (R-2055)</label>
                        <select id="selectEvtAquis" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="N">Não</option>
                            <option value="S">Sim</option>
                        </select>
                    </div>
                </div>
            </div>

            <div id="blocoIndicadoresR4099" class="hidden">
                <h3 class="text-xs font-medium text-gray-600 dark:text-slate-400 mb-2">Indicador do fechamento (infoFech)</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-slate-400 mb-1">Fechamento ou reabertura</label>
                        <select id="selectFechRet" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="0">Fechamento</option>
                            <option value="1">Reabertura</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="button" id="btnEnviarReinf"
                        class="py-2 px-4 bg-brand hover:bg-brand/80 text-white text-sm font-semibold rounded-lg transition-colors border-0">
                    Enviar fechamento
                </button>
            </div>

            <div id="reinfErro" class="hidden mt-3 text-sm text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2"></div>
            <div id="reinfSucesso" class="hidden mt-3 text-sm text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2"></div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
            <h2 class="font-semibold text-gray-800 dark:text-slate-200 text-sm uppercase tracking-wide flex items-center gap-2 mb-4">
                <i class="fa-solid fa-clock-rotate-left text-brand"></i> Histórico
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-500 dark:text-slate-400 uppercase">
                            <th class="pb-2">Cliente</th>
                            <th class="pb-2">Evento</th>
                            <th class="pb-2">Período</th>
                            <th class="pb-2">Status</th>
                            <th class="pb-2">Protocolo</th>
                            <th class="pb-2">Recibo</th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody id="tbodyHistoricoReinf">
                        @foreach($historico as $item)
                            <tr class="border-t border-gray-100 dark:border-slate-700" data-id="{{ $item->id }}">
                                <td class="py-2">{{ $item->cliente->nome ?? '—' }}</td>
                                <td class="py-2">{{ $item->tipo_evento }}</td>
                                <td class="py-2">{{ $item->periodo_apuracao }}</td>
                                <td class="py-2 status-cell">{{ $item->status }}</td>
                                <td class="py-2 protocolo-cell">{{ $item->numero_protocolo ?? '—' }}</td>
                                <td class="py-2 recibo-cell">{{ $item->numero_recibo ?? '—' }}</td>
                                <td class="py-2">
                                    @if($item->numero_protocolo)
                                        <button type="button" class="btnConsultarReinf text-brand text-xs font-semibold hover:underline" data-id="{{ $item->id }}">Consultar</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    @include('simples-nacional._shared')
    <script>
    const selectClienteReinf = document.getElementById('selectClienteReinf');
    const selectTipoEventoReinf = document.getElementById('selectTipoEventoReinf');
    const inputPeriodoReinf = document.getElementById('inputPeriodoReinf');
    const inputRespNomeReinf = document.getElementById('inputRespNomeReinf');
    const inputRespCpfReinf = document.getElementById('inputRespCpfReinf');
    const inputRespTelefoneReinf = document.getElementById('inputRespTelefoneReinf');
    const inputRespEmailReinf = document.getElementById('inputRespEmailReinf');
    const blocoIndicadoresR2099 = document.getElementById('blocoIndicadoresR2099');
    const blocoIndicadoresR4099 = document.getElementById('blocoIndicadoresR4099');
    const selectEvtServTm = document.getElementById('selectEvtServTm');
    const selectEvtServPr = document.getElementById('selectEvtServPr');
    const selectEvtAssDespRec = document.getElementById('selectEvtAssDespRec');
    const selectEvtAssDespRep = document.getElementById('selectEvtAssDespRep');
    const selectEvtComProd = document.getElementById('selectEvtComProd');
    const selectEvtCPRB = document.getElementById('selectEvtCPRB');
    const selectEvtAquis = document.getElementById('selectEvtAquis');
    const selectFechRet = document.getElementById('selectFechRet');
    const reinfErro = document.getElementById('reinfErro');
    const reinfSucesso = document.getElementById('reinfSucesso');

    function alternarBlocosIndicadoresReinf() {
        const ehR2099 = selectTipoEventoReinf.value === 'R-2099';
        blocoIndicadoresR2099.classList.toggle('hidden', !ehR2099);
        blocoIndicadoresR4099.classList.toggle('hidden', ehR2099);
    }

    selectTipoEventoReinf.addEventListener('change', alternarBlocosIndicadoresReinf);
    alternarBlocosIndicadoresReinf();

    function mostrarErroReinf(mensagem) {
        reinfSucesso.classList.add('hidden');
        reinfErro.textContent = mensagem;
        reinfErro.classList.remove('hidden');
    }

    function mostrarSucessoReinf(mensagem) {
        reinfErro.classList.add('hidden');
        reinfSucesso.textContent = mensagem;
        reinfSucesso.classList.remove('hidden');
    }

    /** Atualiza só as colunas de uma linha já existente na tabela (status/protocolo/recibo). */
    function atualizarLinhaHistorico(fechamento) {
        const linha = document.querySelector(`#tbodyHistoricoReinf tr[data-id="${fechamento.id}"]`);

        if (!linha) return;

        linha.querySelector('.status-cell').textContent = fechamento.status;
        linha.querySelector('.protocolo-cell').textContent = fechamento.numero_protocolo ?? '—';
        linha.querySelector('.recibo-cell').textContent = fechamento.numero_recibo ?? '—';

        if (fechamento.numero_protocolo && !linha.querySelector('.btnConsultarReinf')) {
            const td = linha.children[linha.children.length - 1];
            td.innerHTML = `<button type="button" class="btnConsultarReinf text-brand text-xs font-semibold hover:underline" data-id="${fechamento.id}">Consultar</button>`;
            td.querySelector('.btnConsultarReinf').addEventListener('click', () => consultarFechamento(fechamento.id));
        }
    }

    document.getElementById('btnEnviarReinf').addEventListener('click', async () => {
        reinfErro.classList.add('hidden');
        reinfSucesso.classList.add('hidden');

        if (!selectClienteReinf.value || !inputPeriodoReinf.value) {
            mostrarErroReinf('Selecione o cliente e o período de apuração.');
            return;
        }

        try {
            const resp = await fetch('{{ route('reinf.enviar') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({
                    cliente_id: selectClienteReinf.value,
                    tipo_evento: selectTipoEventoReinf.value,
                    periodo_apuracao: inputPeriodoReinf.value,
                    responsavel_nome: inputRespNomeReinf.value,
                    responsavel_cpf: inputRespCpfReinf.value,
                    responsavel_telefone: inputRespTelefoneReinf.value,
                    responsavel_email: inputRespEmailReinf.value,
                    evt_serv_tm: selectEvtServTm.value,
                    evt_serv_pr: selectEvtServPr.value,
                    evt_ass_desp_rec: selectEvtAssDespRec.value,
                    evt_ass_desp_rep: selectEvtAssDespRep.value,
                    evt_com_prod: selectEvtComProd.value,
                    evt_cprb: selectEvtCPRB.value,
                    evt_aquis: selectEvtAquis.value,
                    fech_ret: selectFechRet.value,
                }),
            });

            const data = await resp.json();

            if (!resp.ok) {
                mostrarErroReinf(data.error ?? 'Erro ao enviar o fechamento.');
                return;
            }

            mostrarSucessoReinf(`Fechamento enviado — status: ${data.fechamento.status}.`);
            atualizarLinhaHistorico(data.fechamento);
        } catch (e) {
            mostrarErroReinf('Erro de rede ao enviar o fechamento.');
        }
    });

    async function consultarFechamento(id) {
        try {
            const resp = await fetch(`/reinf/${id}/consultar`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });

            const data = await resp.json();

            if (!resp.ok) {
                mostrarErroReinf(data.error ?? 'Erro ao consultar o lote.');
                return;
            }

            atualizarLinhaHistorico(data.fechamento);
        } catch (e) {
            mostrarErroReinf('Erro de rede ao consultar o lote.');
        }
    }

    document.querySelectorAll('.btnConsultarReinf').forEach((btn) => {
        btn.addEventListener('click', () => consultarFechamento(btn.dataset.id));
    });
    </script>
    @endpush
@endsection
