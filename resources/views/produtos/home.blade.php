@extends('layouts.internal')

@section('title', 'Produtos — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fa-solid fa-box-open"></i> Produtos</h1>
                <p class="text-gray-700">Gerencie os produtos e serviços disponíveis para seus clientes.</p>
            </div>
            <div>
                <button type="button" class="inline-flex items-center px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('produtos.form.create') }}">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded shadow overflow-x-auto">
            {{-- Filters --}}
            <form method="GET" action="{{ route('produtos') }}" id="form-filtros-produtos"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Buscar por nome..."
                           onchange="document.getElementById('form-filtros-produtos').submit()"
                           class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand w-48">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select name="status" onchange="document.getElementById('form-filtros-produtos').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        <option value="ativo"   @selected(request('status') === 'ativo')>Ativo</option>
                        <option value="inativo" @selected(request('status') === 'inativo')>Inativo</option>
                    </select>
                </div>
            </form>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($produtos as $produto)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $produto->nome }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $produto->descricao ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if($produto->ativo)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <button type="button"
                                        class="text-brand hover:text-brand/80 focus:outline-none focus:ring-0 border-0 bg-transparent p-0"
                                        data-modal-url="{{ route('produtos.form.edit', $produto->id) }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>

                                <form method="POST" action="{{ route('produtos.delete', $produto->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-red-600 hover:text-red-700 ml-3 focus:outline-none focus:ring-0 border-0 bg-transparent p-0 btn-delete-produto" data-nome="{{ $produto->nome }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum produto encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    document.querySelectorAll('.btn-delete-produto').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const nome = btn.dataset.nome;
            const form = btn.closest('form');

            Swal.fire({
                title: 'Excluir produto?',
                text: `Tem certeza que deseja excluir "${nome}"? Esta ação não pode ser desfeita.`,
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
