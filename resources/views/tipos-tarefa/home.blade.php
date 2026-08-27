@extends('layouts.internal')

@section('title', 'Tipos de Tarefa — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-tags"></i> Tipos de Tarefa</h1>
                <p class="text-gray-700 dark:text-gray-300">Cadastre os tipos com pré-definições que serão usados ao criar tarefas.</p>
            </div>
            <div>
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('tipos-tarefa.form.create') }}">
                    <i class="fa-solid fa-plus"></i> Novo Tipo
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded">{{ session('error') }}</div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded shadow overflow-x-auto">
            {{-- Filtro --}}
            <form method="GET" action="{{ route('tipos-tarefa.index') }}" id="form-filtros-tipos"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Buscar por nome..."
                           onchange="document.getElementById('form-filtros-tipos').submit()"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-52">
                </div>
            </form>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Título Padrão</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data Vencimento Padrão</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Regimes</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tarefas</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($tipos as $tipo)
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-100 whitespace-nowrap">{{ $tipo->nome }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $tipo->titulo_padrao ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                @if($tipo->data_vencimento)
                                    <span class="inline-flex items-center gap-1">
                                        <i class="fa-regular fa-calendar text-brand"></i>
                                        {{ $tipo->data_vencimento->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                @forelse($tipo->regras as $regra)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 mr-1 mb-1">
                                        {{ $regra->regime_tributario ?? 'Qualquer regime' }}
                                        @if($regra->cnae_prefixos)
                                            <span class="ml-1 opacity-70">· CNAE {{ implode(', ', $regra->cnae_prefixos) }}</span>
                                        @endif
                                    </span>
                                @empty
                                    <span class="text-gray-400 dark:text-slate-500">—</span>
                                @endforelse
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    {{ $tipo->tarefas_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                <button type="button"
                                        class="text-brand hover:text-brand/80 focus:outline-none focus:ring-0 border-0 bg-transparent p-0"
                                        data-modal-url="{{ route('tipos-tarefa.form.edit', $tipo->id) }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>

                                <form method="POST" action="{{ route('tipos-tarefa.delete', $tipo->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="text-red-600 hover:text-red-700 ml-3 focus:outline-none focus:ring-0 border-0 bg-transparent p-0 btn-delete-tipo"
                                            data-nome="{{ $tipo->nome }}"
                                            data-count="{{ $tipo->tarefas_count }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400">Nenhum tipo de tarefa cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    document.querySelectorAll('.btn-delete-tipo').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const nome = btn.dataset.nome;
            const count = parseInt(btn.dataset.count, 10);
            const form = btn.closest('form');

            if (count > 0) {
                Swal.fire({
                    title: 'Não é possível excluir',
                    text: `O tipo "${nome}" possui ${count} tarefa(s) vinculada(s) e não pode ser excluído.`,
                    icon: 'warning',
                    confirmButtonColor: '#6b7280',
                    confirmButtonText: 'Entendi',
                });
                return;
            }

            Swal.fire({
                title: 'Excluir tipo de tarefa?',
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
