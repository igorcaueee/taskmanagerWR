@extends('layouts.internal')

@section('title', 'ICMS-ST Antecipação — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-truck-fast"></i> ICMS-ST Antecipação</h1>
                <p class="text-gray-700 dark:text-gray-300">Antecipação tributária de ICMS-ST pelo destinatário, calculada a partir do cofre de XMLs.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('icms-st.pendencias') }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors no-underline">
                    <i class="fa-solid fa-list-check"></i> Fila de pendências
                </a>
                <a href="{{ route('icms-st.guias.index') }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors no-underline">
                    <i class="fa-solid fa-file-invoice-dollar"></i> Guias
                </a>
                <a href="{{ route('icms-st.regras.index') }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors no-underline">
                    <i class="fa-solid fa-table-list"></i> Regras cadastradas
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">{{ session('status') }}</div>
        @endif
        @if (session('erro'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 text-sm">{{ session('erro') }}</div>
        @endif
        @if (session('aviso'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-sm">
                <i class="fa-solid fa-triangle-exclamation"></i> {{ session('aviso') }}
            </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6 mb-6">
            {{-- Os dois <form> abaixo NÃO podem ficar um dentro do outro (HTML inválido --
                 o navegador descarta a tag do form interno e o clique acaba reenviando o
                 form de fora). Ficam lado a lado como irmãos; o GET usa `display:contents`
                 (classe `contents`) só pra não quebrar o alinhamento em flex desta linha. --}}
            <div class="flex flex-wrap items-end gap-3 mb-2">
                <form method="GET" action="{{ route('icms-st.index') }}" autocomplete="off" class="contents">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                        <select name="cliente_id" required autocomplete="off" class="w-64 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="">Selecione...</option>
                            @foreach ($clientes as $c)
                                <option value="{{ $c->id }}" @selected((int) $clienteId === $c->id)>{{ $c->nome }} ({{ $c->estado }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Emissão de</label>
                        <input type="date" name="data_inicio" value="{{ $dataInicio }}" required autocomplete="off" class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">até</label>
                        <input type="date" name="data_fim" value="{{ $dataFim }}" required autocomplete="off" class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="py-2 px-4 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg transition-colors">
                        <i class="fa-solid fa-magnifying-glass"></i> Consultar
                    </button>
                </form>
                @if ($clienteId && $dataInicio && $dataFim)
                    <form method="POST" action="{{ route('icms-st.calcular') }}" id="form-calcular-icms-st" class="contents">
                        @csrf
                        <input type="hidden" name="cliente_id" value="{{ $clienteId }}">
                        <input type="hidden" name="data_inicio" value="{{ $dataInicio }}">
                        <input type="hidden" name="data_fim" value="{{ $dataFim }}">
                        <button type="submit" class="py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors">
                            <i class="fa-solid fa-calculator"></i> Calcular ICMS-ST do período
                        </button>
                    </form>
                @endif
            </div>
            <p class="text-xs text-gray-500 dark:text-slate-400">Filtro por data de emissão da NF-e. "Calcular" processa (ou recalcula) todas as NF-e do cofre nesse recorte.</p>
        </div>

        @if ($clienteSelecionado && ! in_array(strtoupper($clienteSelecionado->estado ?? ''), ['RS', 'MG'], true))
            <div class="mb-6 px-4 py-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-sm">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Este cliente está cadastrado em {{ $clienteSelecionado->estado ?? '?' }} — esta ferramenta só possui motor
                de cálculo para destinatários no <strong>RS</strong> e <strong>MG</strong>. Nenhum cálculo será realizado
                para este cliente até que um motor para {{ $clienteSelecionado->estado ?? 'essa UF' }} seja implementado.
            </div>
        @endif

        @if ($notas !== null)
            @if ($totais['uf_nao_suportada'] ?? false)
                <div class="mb-4 px-4 py-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-sm">
                    <i class="fa-solid fa-triangle-exclamation"></i> Existem NF-e neste período com destinatário fora de RS/MG — nenhum item dessas notas foi calculado. Veja o detalhe de cada nota.
                </div>
            @endif

            <form method="GET" action="{{ route('icms-st.index') }}" autocomplete="off" class="flex flex-wrap items-end justify-between gap-3 mb-3">
                <div class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="cliente_id" value="{{ $clienteId }}">
                    <input type="hidden" name="data_inicio" value="{{ $dataInicio }}">
                    <input type="hidden" name="data_fim" value="{{ $dataFim }}">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Buscar NF-e</label>
                        <input type="text" name="busca" value="{{ $busca }}" placeholder="Número da nota" autocomplete="off" class="w-40 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Status</label>
                        <select name="status_filtro" autocomplete="off" class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                            <option value="" @selected(! $statusFiltro)>Todas</option>
                            <option value="pendentes" @selected($statusFiltro === 'pendentes')>Com pendências</option>
                            <option value="sem_pendentes" @selected($statusFiltro === 'sem_pendentes')>Sem pendências</option>
                        </select>
                    </div>
                    <button type="submit" class="py-2 px-4 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg transition-colors">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    @if ($busca || $statusFiltro)
                        <a href="{{ route('icms-st.index', ['cliente_id' => $clienteId, 'data_inicio' => $dataInicio, 'data_fim' => $dataFim]) }}" class="text-xs text-gray-500 dark:text-slate-400 no-underline hover:text-gray-700 dark:hover:text-slate-200">Limpar</a>
                    @endif
                </div>
                <a href="{{ route('icms-st.exportar', ['cliente_id' => $clienteId, 'data_inicio' => $dataInicio, 'data_fim' => $dataFim, 'busca' => $busca, 'status_filtro' => $statusFiltro]) }}" class="flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-xs font-semibold rounded-lg transition-colors no-underline">
                    <i class="fa-solid fa-file-excel"></i> Exportar Excel
                </a>
            </form>
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-3">A exportação Excel segue exatamente o filtro acima — só as NF-e exibidas na tabela abaixo.</p>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-slate-400">ICMS-ST</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-slate-100">R$ {{ number_format($totais['icms_st'], 2, ',', '.') }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-slate-400">Adicional (AMPARA/FEM)</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-slate-100">R$ {{ number_format($totais['adicional'], 2, ',', '.') }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-slate-400">Total a recolher</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-slate-100">R$ {{ number_format($totais['total'], 2, ',', '.') }}</p>
                </div>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-slate-400">Itens pendentes</p>
                    <p class="text-xl font-bold {{ $totais['pendentes'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-slate-100' }}">{{ $totais['pendentes'] }}</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-600 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-left">NF-e</th>
                            <th class="px-4 py-3 text-left">Emissão</th>
                            <th class="px-4 py-3 text-right">ICMS-ST</th>
                            <th class="px-4 py-3 text-right">Adicional</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Pendentes</th>
                            <th class="px-4 py-3 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                        @forelse ($notas as $nota)
                            <tr>
                                <td class="px-4 py-3 text-gray-800 dark:text-slate-200">{{ $nota->nfe_numero }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ \Carbon\Carbon::parse($nota->data_emissao)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-right text-gray-800 dark:text-slate-200">R$ {{ number_format($nota->icms_st, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-gray-800 dark:text-slate-200">R$ {{ number_format($nota->adicional, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-slate-100">R$ {{ number_format($nota->total, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($nota->pendentes > 0)
                                        <span class="px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-xs font-semibold">{{ $nota->pendentes }}</span>
                                    @else
                                        <span class="text-gray-400 dark:text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('icms-st.detalhe', $nota->chave_acesso) }}" class="text-[#0084aa] hover:text-[#006e8e] text-xs font-semibold no-underline">Detalhe <i class="fa-solid fa-arrow-right"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-gray-500 dark:text-slate-400">Nenhuma NF-e calculada neste período ainda — clique em "Calcular ICMS-ST do período".</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('form-calcular-icms-st')?.addEventListener('submit', function () {
        Swal.fire({
            title: 'Calculando ICMS-ST do período...',
            text: 'Isso pode levar alguns segundos dependendo da quantidade de NF-e no cofre.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading(),
        });
    });
</script>
@endpush
