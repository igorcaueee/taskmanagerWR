@extends('layouts.internal')

@section('title', 'Grupos de NCM — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-layer-group"></i> Grupos de NCM</h1>
                <p class="text-gray-700 dark:text-gray-300">Agrupe NCMs para dividir e filtrar os produtos de precificação por categoria.</p>
            </div>
            @if (auth()->user()?->canGerenciarPrecificacao())
            <div class="flex gap-2">
                <button type="button" class="inline-flex items-center px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('precificacao.ncmGrupos.form.create') }}">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            @endif
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded shadow overflow-x-auto">
            <form method="GET" action="{{ route('precificacao.ncmGrupos') }}" id="form-filtros-ncm-grupos"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Nome do grupo ou NCM..."
                           onchange="document.getElementById('form-filtros-ncm-grupos').submit()"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-64">
                </div>
            </form>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Grupo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">NCMs</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($grupos as $grupo)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-100 whitespace-nowrap">{{ $grupo->nome }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $grupo->itens->pluck('ncm')->join(', ') }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                @if($grupo->ativo)
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Ativo</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-600">Inativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                @if (auth()->user()?->canGerenciarPrecificacao())
                                <button type="button"
                                        class="text-brand hover:text-brand/80 focus:outline-none focus:ring-0 border-0 bg-transparent p-0"
                                        data-modal-url="{{ route('precificacao.ncmGrupos.form.edit', $grupo->id) }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>

                                <form method="POST" action="{{ route('precificacao.ncmGrupos.delete', $grupo->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-red-600 hover:text-red-700 ml-3 focus:outline-none focus:ring-0 border-0 bg-transparent p-0 btn-delete-ncm-grupo" data-nome="{{ $grupo->nome }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum grupo de NCM cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-6 py-4">
                {{ $grupos->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    document.querySelectorAll('.btn-delete-ncm-grupo').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const nome = btn.dataset.nome;
            const form = btn.closest('form');

            Swal.fire({
                title: 'Excluir grupo?',
                text: `Tem certeza que deseja excluir o grupo "${nome}"? Esta ação não pode ser desfeita.`,
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
