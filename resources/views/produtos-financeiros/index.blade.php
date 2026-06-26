@extends('layouts.internal')

@section('title', 'Produtos Financeiros — WR Assessoria')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                <i class="fa-solid fa-box-open text-blue-600"></i> Produtos Financeiros
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Produtos por empresa</p>
        </div>
        <a href="{{ route('financeiro.produtos.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm no-underline">
            <i class="fa-solid fa-plus"></i> Novo Produto
        </a>
    </div>

    <form method="GET" action="{{ route('financeiro.produtos.index') }}" class="mb-4">
        <select name="cliente_id" onchange="this.form.submit()"
                class="text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
            <option value="">Todas as empresas</option>
            @foreach ($empresas as $emp)
                <option value="{{ $emp->id }}" {{ $clienteId == $emp->id ? 'selected' : '' }}>{{ $emp->nome }}</option>
            @endforeach
        </select>
    </form>

    @if(session('success') || session('error'))
    @push('scripts')
    <script type="module">
    @if(session('success'))
    Swal.fire({ icon: 'success', title: 'Sucesso', text: '{{ session('success') }}', confirmButtonColor: '#2563eb' });
    @endif
    @if(session('error'))
    Swal.fire({ icon: 'error', title: 'Erro', text: '{{ session('error') }}', confirmButtonColor: '#dc2626' });
    @endif
    </script>
    @endpush
    @endif

    <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 overflow-hidden">
        @if ($produtos->isEmpty())
            <p class="text-sm text-gray-400 text-center py-12">Nenhum produto cadastrado.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-700/50">
                    <tr class="text-xs text-gray-500 dark:text-gray-400">
                        <th class="text-left px-4 py-3">Empresa</th>
                        <th class="text-left px-4 py-3">Nome</th>
                        <th class="text-left px-4 py-3">Código</th>
                        <th class="text-left px-4 py-3">Categoria</th>
                        <th class="text-right px-4 py-3">Preço Custo</th>
                        <th class="text-right px-4 py-3">Preço Venda</th>
                        <th class="text-right px-4 py-3">Estoque</th>
                        <th class="text-left px-4 py-3">Ativo</th>
                        <th class="text-left px-4 py-3">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50">
                    @foreach ($produtos as $p)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $p->cliente->nome ?? '—' }}</td>
                        <td class="px-4 py-2.5 font-medium text-gray-900 dark:text-slate-100">{{ $p->nome }}</td>
                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">{{ $p->codigo ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-400">{{ $p->categoria ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-right">R$ {{ number_format($p->preco_custo, 2, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right font-semibold text-green-600 dark:text-green-400">R$ {{ number_format($p->preco_venda, 2, ',', '.') }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format($p->estoque_atual, 2, ',', '.') }}</td>
                        <td class="px-4 py-2.5">
                            @if ($p->ativo)
                                <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 px-2 py-0.5 rounded">Sim</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Não</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex gap-2">
                                <a href="{{ route('financeiro.produtos.edit', $p) }}"
                                   class="text-blue-600 dark:text-blue-400 hover:underline text-xs no-underline">Editar</a>
                                <button type="button"
                                        onclick="deletarProduto({{ $p->id }}, '{{ addslashes($p->nome) }}')"
                                        class="text-red-600 dark:text-red-400 hover:underline text-xs border-0 bg-transparent p-0 cursor-pointer">
                                    Excluir
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
                {{ $produtos->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script type="module">
function deletarProduto(id, nome) {
    Swal.fire({
        icon: 'warning',
        title: 'Excluir produto?',
        text: `"${nome}" será removido permanentemente.`,
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    }).then(r => {
        if (!r.isConfirmed) return;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/financeiro/produtos/${id}`;
        form.innerHTML = `<input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}"><input type="hidden" name="_method" value="DELETE">`;
        document.body.appendChild(form);
        form.submit();
    });
}
window.deletarProduto = deletarProduto;
</script>
@endpush
@endsection
