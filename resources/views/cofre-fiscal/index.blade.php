@extends('layouts.internal')

@section('title', 'Cofre de Notas Fiscais — WR Assessoria')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    <div class="flex items-center justify-between mb-2 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-box-archive text-[#0084aa]"></i>
                Cofre de Notas Fiscais
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Todas as NF-e, NFC-e e CT-e já sincronizadas ficam guardadas aqui, organizadas por
                Cliente → Ano → Mês → Tipo — esta tela só lê o que já foi baixado, sem consultar a Sefaz.
                Para trazer notas novas, use a <a href="{{ route('nfe.index') }}" class="underline text-[#0084aa]">busca de NF-e/CT-e</a>.
            </p>
        </div>
        <div class="flex items-center gap-2">
            @if(in_array($nivel, ['tipos', 'documentos'], true))
            <button type="button" id="btnExportarRelatorioCofre"
                    data-cliente-id="{{ request('cliente_id') }}"
                    data-ano="{{ request('ano') }}"
                    data-mes="{{ request('mes') }}"
                    data-tipo-atual="{{ $nivel === 'documentos' ? request('tipo') : '' }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg transition-colors">
                <i class="fa-solid fa-file-excel text-green-600"></i>
                Exportar relatório mensal (Excel)
            </button>
            @endif
            @if($nivel === 'documentos')
            <a href="{{ route('cofre-fiscal.zip', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors"
               title="Baixar .zip com os XMLs que batem com os filtros atuais (máx. {{ $maxZip }})">
                <i class="fa-solid fa-file-zipper"></i>
                Baixar ZIP (filtro atual)
            </a>
            @endif
        </div>
    </div>

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 mb-4 flex-wrap">
        <a href="{{ route('cofre-fiscal.index') }}" class="hover:text-[#0084aa] no-underline text-gray-600 dark:text-gray-400">
            <i class="fa-solid fa-house-chimney"></i> Cofre Fiscal
        </a>
        @foreach($breadcrumbs as $crumb)
            <span class="text-gray-400">/</span>
            @if($crumb['url'])
                <a href="{{ $crumb['url'] }}" class="hover:text-[#0084aa] no-underline text-gray-600 dark:text-gray-400">{{ $crumb['label'] }}</a>
            @else
                <span class="text-gray-800 dark:text-slate-200 font-medium">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">

        @if($nivel !== 'documentos')

            {{-- ─── Níveis de pasta: clientes / anos / meses / tipos ──────────────── --}}
            @if($nivel === 'clientes')
            <form method="GET" action="{{ route('cofre-fiscal.index') }}"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar cliente..."
                           onchange="this.form.submit()"
                           class="pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-[#0084aa] w-64">
                </div>
            </form>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @if($urlVoltar)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                <td class="px-4 py-3" colspan="2">
                                    <a href="{{ $urlVoltar }}" class="inline-flex items-center gap-2 text-gray-600 dark:text-gray-300 hover:text-[#0084aa] no-underline">
                                        <i class="fa-solid fa-arrow-turn-up fa-flip-horizontal text-gray-400"></i>
                                        <span>..</span>
                                    </a>
                                </td>
                            </tr>
                        @endif

                        @forelse($pastas as $pasta)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-4 py-3">
                                    <a href="{{ $pasta['url'] }}" class="inline-flex items-center gap-2 text-gray-800 dark:text-slate-100 hover:text-[#0084aa] no-underline font-medium">
                                        <i class="fa-solid fa-folder {{ $pasta['icon_class'] }}"></i>
                                        {{ $pasta['label'] }}
                                        @if($pasta['sublabel'])
                                            <span class="text-xs font-normal text-gray-400 dark:text-slate-500">{{ $pasta['sublabel'] }}</span>
                                        @endif
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-right text-xs text-gray-400 dark:text-slate-500 whitespace-nowrap">
                                    {{ $pasta['total'] }} {{ $pasta['total'] == 1 ? 'nota' : 'notas' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-slate-600">
                                    Nenhum documento sincronizado {{ $nivel === 'clientes' ? 'ainda' : 'nesta pasta' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($paginador && $paginador->hasPages())
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-slate-700 flex-wrap gap-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Exibindo {{ $paginador->firstItem() }}–{{ $paginador->lastItem() }} de {{ $paginador->total() }} clientes
                </p>
                <div class="flex items-center gap-1">
                    @if($paginador->onFirstPage())
                        <span class="px-2 py-1 text-xs text-gray-400 dark:text-gray-600 cursor-default">&laquo;</span>
                    @else
                        <a href="{{ $paginador->previousPageUrl() }}" class="px-2 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded">&laquo;</a>
                    @endif

                    @foreach($paginador->getUrlRange(max(1, $paginador->currentPage() - 2), min($paginador->lastPage(), $paginador->currentPage() + 2)) as $page => $url)
                        @if($page === $paginador->currentPage())
                            <span class="px-2 py-1 text-xs bg-[#0084aa] text-white rounded">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-2 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($paginador->hasMorePages())
                        <a href="{{ $paginador->nextPageUrl() }}" class="px-2 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded">&raquo;</a>
                    @else
                        <span class="px-2 py-1 text-xs text-gray-400 dark:text-gray-600 cursor-default">&raquo;</span>
                    @endif
                </div>
            </div>
            @endif

        @else

            {{-- ─── Nível final: documentos dentro da pasta Cliente/Ano/Mês/Tipo ──── --}}

            {{-- Filtros finos --}}
            <form method="GET" action="{{ route('cofre-fiscal.index') }}" id="form-filtros-cofre"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <input type="hidden" name="cliente_id" value="{{ request('cliente_id') }}">
                <input type="hidden" name="ano" value="{{ request('ano') }}">
                <input type="hidden" name="mes" value="{{ request('mes') }}">
                <input type="hidden" name="tipo" value="{{ request('tipo') }}">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Direção</label>
                    <select name="direcao" onchange="document.getElementById('form-filtros-cofre').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                        <option value="">Entradas e saídas</option>
                        <option value="saida"   @selected(request('direcao') === 'saida')>Somente saídas</option>
                        <option value="entrada" @selected(request('direcao') === 'entrada')>Somente entradas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Origem</label>
                    <select name="origem" onchange="document.getElementById('form-filtros-cofre').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                        <option value="">Todas</option>
                        <option value="nacional" @selected(request('origem') === 'nacional')>Nacional</option>
                        <option value="rs"       @selected(request('origem') === 'rs')>Contabilidade (RS)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Situação</label>
                    <select name="situacao" onchange="document.getElementById('form-filtros-cofre').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                        <option value="">Normais e canceladas</option>
                        <option value="normal"    @selected(request('situacao') === 'normal')>Somente normais</option>
                        <option value="cancelada" @selected(request('situacao') === 'cancelada')>Somente canceladas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Buscar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Chave, número ou emitente..."
                           onchange="document.getElementById('form-filtros-cofre').submit()"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa] w-56">
                </div>
            </form>

            {{-- Linha "voltar" --}}
            <div class="px-4 py-2 border-b border-gray-100 dark:border-slate-700">
                <a href="{{ $urlVoltar }}" class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 hover:text-[#0084aa] no-underline">
                    <i class="fa-solid fa-arrow-turn-up fa-flip-horizontal text-gray-400"></i>
                    <span>Voltar para a pasta</span>
                </a>
            </div>

            {{-- Tabela --}}
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-slate-700 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">
                            <th class="px-4 py-3 text-left">Número</th>
                            <th class="px-4 py-3 text-left">Data Emissão</th>
                            <th class="px-4 py-3 text-left">Emitente</th>
                            <th class="px-4 py-3 text-right">Valor</th>
                            <th class="px-4 py-3 text-left">Origem</th>
                            <th class="px-4 py-3 text-left">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                        @forelse($documentos as $documento)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-slate-200">
                                    {{ $documento->numero ?: '-' }}
                                    @if($documento->situacao === 'cancelada')
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 ml-1">Cancelada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ $documento->data_emissao?->format('d/m/Y') ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-slate-300 max-w-[260px] truncate" title="{{ $documento->emitente_nome }}">
                                    {{ $documento->emitente_nome ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-800 dark:text-slate-200">
                                    {{ $documento->valor !== null ? 'R$ '.number_format($documento->valor, 2, ',', '.') : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-slate-700/60 dark:text-slate-400"
                                          title="Sincronizado em {{ $documento->updated_at?->format('d/m/Y H:i') }}">
                                        {{ $documento->origem === 'rs' ? 'Contabilidade (RS)' : 'Nacional' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <button type="button"
                                            class="btn-ver-pdf p-1.5 text-gray-400 dark:text-slate-500 hover:text-red-600 dark:hover:text-red-400 bg-transparent border-0 transition-colors"
                                            title="Ver PDF (DANFE/DACTE)"
                                            data-chave="{{ $documento->chave_acesso }}">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </button>
                                    <a href="{{ route('cofre-fiscal.xml', $documento->chave_acesso) }}"
                                       class="p-1.5 text-gray-400 dark:text-slate-500 hover:text-blue-600 dark:hover:text-blue-400 inline-block"
                                       title="Baixar XML">
                                        <i class="fa-solid fa-file-code"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-slate-600">
                                    Nenhum documento encontrado para os filtros atuais.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginação --}}
            <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-slate-700 flex-wrap gap-2">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @if($documentos->hasPages())
                        Exibindo {{ $documentos->firstItem() }}–{{ $documentos->lastItem() }} de {{ $documentos->total() }} documentos
                    @else
                        {{ $documentos->total() }} {{ $documentos->total() === 1 ? 'documento' : 'documentos' }}
                    @endif
                </p>
                @if($documentos->hasPages())
                <div class="flex items-center gap-1">
                    @if($documentos->onFirstPage())
                        <span class="px-2 py-1 text-xs text-gray-400 dark:text-gray-600 cursor-default">&laquo;</span>
                    @else
                        <a href="{{ $documentos->previousPageUrl() }}" class="px-2 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded">&laquo;</a>
                    @endif

                    @foreach($documentos->getUrlRange(max(1, $documentos->currentPage() - 2), min($documentos->lastPage(), $documentos->currentPage() + 2)) as $page => $url)
                        @if($page === $documentos->currentPage())
                            <span class="px-2 py-1 text-xs bg-[#0084aa] text-white rounded">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="px-2 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($documentos->hasMorePages())
                        <a href="{{ $documentos->nextPageUrl() }}" class="px-2 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 rounded">&raquo;</a>
                    @else
                        <span class="px-2 py-1 text-xs text-gray-400 dark:text-gray-600 cursor-default">&raquo;</span>
                    @endif
                </div>
                @endif
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const btnExportarRelatorioCofre = document.getElementById('btnExportarRelatorioCofre');

    if (btnExportarRelatorioCofre) {
        btnExportarRelatorioCofre.addEventListener('click', async function () {
            const clienteId = this.dataset.clienteId;
            const ano = this.dataset.ano;
            const mes = this.dataset.mes;
            const tipoAtual = this.dataset.tipoAtual;

            const { value: tipos } = await Swal.fire({
                title: 'Exportar relatório mensal',
                html: `
                    <div class="text-left text-sm flex flex-col gap-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="swalTipoNfe" ${tipoAtual === '' || tipoAtual === 'nfe' ? 'checked' : ''}>
                            NF-e (com IBS/CBS por item)
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="swalTipoNfce" ${tipoAtual === '' || tipoAtual === 'nfce' ? 'checked' : ''}>
                            NFC-e (com IBS/CBS por item)
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" id="swalTipoCte" ${tipoAtual === '' || tipoAtual === 'cte' ? 'checked' : ''}>
                            CT-e (com IBS/CBS a nível de documento)
                        </label>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Gerar Excel',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0084aa',
                preConfirm: () => {
                    const tipos = [];
                    if (document.getElementById('swalTipoNfe').checked) tipos.push('nfe');
                    if (document.getElementById('swalTipoNfce').checked) tipos.push('nfce');
                    if (document.getElementById('swalTipoCte').checked) tipos.push('cte');

                    if (tipos.length === 0) {
                        Swal.showValidationMessage('Selecione ao menos um tipo de nota.');
                        return false;
                    }

                    return tipos;
                },
            });

            if (!tipos) {
                return;
            }

            const labelOriginal = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Gerando...';

            try {
                const resp = await fetch('{{ route('cofre-fiscal.relatorio') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                    },
                    body: JSON.stringify({
                        cliente_id: clienteId,
                        ano: ano,
                        mes: mes,
                        tipos: tipos,
                    }),
                });

                if (!resp.ok) {
                    const data = await resp.json().catch(() => ({}));
                    Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Falha ao gerar o relatório.' });
                    return;
                }

                const blob = await resp.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Relatorio_Cofre_${ano}_${mes}.xlsx`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(() => URL.revokeObjectURL(url), 60_000);
            } catch {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
            } finally {
                this.disabled = false;
                this.innerHTML = labelOriginal;
            }
        });
    }

    document.querySelectorAll('.btn-ver-pdf').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const chave = this.dataset.chave;
            const iconOrig = this.innerHTML;

            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                const resp = await fetch(`/nfe/danfe?chave_acesso=${encodeURIComponent(chave)}`, {
                    headers: { 'Accept': 'application/pdf,application/json' },
                });

                if (!resp.ok) {
                    const data = await resp.json().catch(() => ({}));
                    Swal.fire({ icon: 'warning', title: 'PDF indisponível', text: data.error ?? 'Falha ao gerar o PDF.' });
                    return;
                }

                const blob = await resp.blob();
                const url  = URL.createObjectURL(blob);
                window.open(url, '_blank');
                setTimeout(() => URL.revokeObjectURL(url), 60_000);
            } catch {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro de comunicação com o servidor.' });
            } finally {
                this.disabled = false;
                this.innerHTML = iconOrig;
            }
        });
    });
})();
</script>
@endpush
@endsection
