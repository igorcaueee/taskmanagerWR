@extends('layouts.portal')

@section('title', 'Precificação de Produtos')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Precificação de Produtos</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Custo de compra e preço de venda sugerido dos seus produtos.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('portal.precificacao.relatorio') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-[#1e293b] border border-gray-300 dark:border-[#334155] text-gray-700 dark:text-slate-200 rounded-lg text-sm no-underline hover:bg-gray-50 dark:hover:bg-[#334155]">
                <i class="fa-solid fa-file-excel text-green-600"></i> Relatório
            </a>
            <a href="{{ route('portal.precificacao.produtos.import.form') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-[#1e293b] border border-gray-300 dark:border-[#334155] text-gray-700 dark:text-slate-200 rounded-lg text-sm no-underline hover:bg-gray-50 dark:hover:bg-[#334155]">
                <i class="fa-solid fa-file-import"></i> Importar planilha
            </a>
            <a href="{{ route('portal.precificacao.produtos.form.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#0084AA] text-white rounded-lg text-sm no-underline hover:bg-[#006884]">
                <i class="fa-solid fa-plus"></i> Novo produto
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="px-4 py-3 bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('portal.precificacao.index') }}" class="flex">
        <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar por nome, NCM ou CEST..."
               class="w-full max-w-sm rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
    </form>

    @if($resumo->isEmpty())
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-10 text-center text-gray-400 dark:text-slate-500 shadow-sm">
            <p class="text-5xl mb-3">📦</p>
            <p class="font-medium">Nenhum produto cadastrado ainda.</p>
            <p class="text-xs mt-1">Cadastre manualmente ou importe uma planilha para começar.</p>
        </div>
    @else
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-[#334155]">
                <thead class="bg-gray-50 dark:bg-[#334155]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Produto</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">NCM / CEST</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">UF compra</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">UF venda</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Custo unitário</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Preço sugerido</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Margem</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#334155]">
                    @foreach($resumo as $item)
                        @php $produto = $item['produto']; $cenario = $item['cenario']; $resultado = $item['resultado']; @endphp
                        <tr onclick="window.location='{{ route('portal.precificacao.show', $produto->id) }}'" class="cursor-pointer hover:bg-gray-50 dark:hover:bg-[#25334a]">
                            <td class="px-5 py-4 text-sm text-gray-800 dark:text-slate-100 whitespace-nowrap">
                                {{ $produto->nome }}
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-slate-300 whitespace-nowrap">{{ $produto->ncm }} @if($produto->cest) / {{ $produto->cest }} @endif</td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-slate-300 whitespace-nowrap">{{ $cenario ? ($cenario->uf_compra === 'EX' ? 'Exterior' : $cenario->uf_compra) : '—' }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-slate-300 whitespace-nowrap">{{ $cenario->uf_venda ?? '—' }}</td>
                            @if($resultado)
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-slate-100 whitespace-nowrap">R$ {{ number_format($resultado->custoUnitario, 2, ',', '.') }}</td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-slate-100 whitespace-nowrap">R$ {{ number_format($resultado->precoVenda, 2, ',', '.') }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">
                                    <span class="{{ $resultado->margemContribuicaoValor >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ number_format($resultado->margemContribuicaoPercentual, 2, ',', '.') }}%
                                    </span>
                                </td>
                            @else
                                <td class="px-5 py-4 text-sm text-gray-400 dark:text-slate-500" colspan="3">Nenhum cenário de compra cadastrado ainda.</td>
                            @endif
                            <td class="px-5 py-4 text-sm text-right whitespace-nowrap" onclick="event.stopPropagation()">
                                <form method="POST" action="{{ route('portal.precificacao.produtos.delete', $produto->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="bg-transparent border-0 p-0 text-red-500 hover:text-red-600 btn-delete-produto mr-3" data-nome="{{ $produto->nome }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                <span class="text-gray-400 dark:text-slate-500"><i class="fa-solid fa-chevron-right"></i></span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('scripts')
<script type="module">
document.querySelectorAll('.btn-delete-produto').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const nome = btn.dataset.nome;
        const form = btn.closest('form');

        Swal.fire({
            title: 'Excluir produto?',
            text: `Tem certeza que deseja excluir "${nome}"? Todos os cenários dele também serão excluídos. Esta ação não pode ser desfeita.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
@endsection
