@extends('layouts.internal')

@section('title', 'Cofre de Notas Fiscais — WR Assessoria')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                <i class="fa-solid fa-box-archive text-[#0084aa]"></i>
                Cofre de Notas Fiscais
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Todas as NF-e, NFC-e e CT-e já sincronizadas ficam guardadas aqui — esta tela só lê o que já foi
                baixado, sem consultar a Sefaz. Para trazer notas novas, use a
                <a href="{{ route('nfe.index') }}" class="underline text-[#0084aa]">busca de NF-e/CT-e</a>.
            </p>
        </div>
        <a href="{{ route('cofre-fiscal.zip', request()->query()) }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors"
           title="Baixar .zip com os XMLs que batem com os filtros atuais (máx. {{ $maxZip }})">
            <i class="fa-solid fa-file-zipper"></i>
            Baixar ZIP (filtro atual)
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">

        {{-- Filtros --}}
        <form method="GET" action="{{ route('cofre-fiscal.index') }}" id="form-filtros-cofre"
              class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Empresa</label>
                <select name="cliente_id" onchange="document.getElementById('form-filtros-cofre').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa] w-56">
                    <option value="">Todas</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(request('cliente_id') == $cliente->id)>{{ $cliente->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Direção</label>
                <select name="direcao" onchange="document.getElementById('form-filtros-cofre').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa]"
                        @if(!request('cliente_id')) disabled title="Selecione uma empresa para filtrar por direção" @endif>
                    <option value="">Entradas e saídas</option>
                    <option value="saida"   @selected(request('direcao') === 'saida')>Somente saídas</option>
                    <option value="entrada" @selected(request('direcao') === 'entrada')>Somente entradas</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tipo</label>
                <select name="tipo" onchange="document.getElementById('form-filtros-cofre').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
                    <option value="">Todos</option>
                    <option value="nfe"  @selected(request('tipo') === 'nfe')>NF-e</option>
                    <option value="nfce" @selected(request('tipo') === 'nfce')>NFC-e</option>
                    <option value="cte"  @selected(request('tipo') === 'cte')>CT-e</option>
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
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Data inicial</label>
                <input type="date" name="data_inicio" value="{{ request('data_inicio') }}"
                       onchange="document.getElementById('form-filtros-cofre').submit()"
                       class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Data final</label>
                <input type="date" name="data_fim" value="{{ request('data_fim') }}"
                       onchange="document.getElementById('form-filtros-cofre').submit()"
                       class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa]">
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Buscar</label>
                <input type="text" name="busca" value="{{ request('busca') }}"
                       placeholder="Chave, número ou emitente..."
                       onchange="document.getElementById('form-filtros-cofre').submit()"
                       class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-[#0084aa] w-56">
            </div>
        </form>

        {{-- Tabela --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-slate-700 text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">
                        <th class="px-4 py-3 text-left">Empresa</th>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-left">Número</th>
                        <th class="px-4 py-3 text-left">Data Emissão</th>
                        <th class="px-4 py-3 text-left">Emitente</th>
                        <th class="px-4 py-3 text-right">Valor</th>
                        <th class="px-4 py-3 text-left">Origem</th>
                        <th class="px-4 py-3 text-left">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/50">
                    @php
                        $badgesPorTipo = [
                            'cte'  => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                            'nfce' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            'nfe'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        ];
                        $labelPorTipo = ['cte' => 'CT-e', 'nfce' => 'NFC-e', 'nfe' => 'NF-e'];
                    @endphp
                    @forelse($documentos as $documento)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300 max-w-[220px] truncate" title="{{ $documento->cliente?->nome }}">
                                {{ $documento->cliente?->nome ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $badgesPorTipo[$documento->tipo] ?? $badgesPorTipo['nfe'] }}">
                                    {{ $labelPorTipo[$documento->tipo] ?? $documento->tipo }}
                                </span>
                                @if($documento->situacao === 'cancelada')
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 ml-1">Cancelada</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-slate-200">{{ $documento->numero ?: '-' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400 whitespace-nowrap">
                                {{ $documento->data_emissao?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-slate-300 max-w-[220px] truncate" title="{{ $documento->emitente_nome }}">
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
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-slate-600">
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
    </div>
</div>

@push('scripts')
<script>
(function () {
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
