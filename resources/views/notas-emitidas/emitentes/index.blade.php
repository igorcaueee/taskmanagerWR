@extends('layouts.internal')

@section('title', 'Cadastro de Clientes de Nota — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-regular fa-address-card"></i> Clientes de Nota</h1>
                <p class="text-gray-700 dark:text-gray-300">Cadastro de quem aparece no Contador de Notas — vincule um cliente já cadastrado ou cadastre manualmente quem não é cliente.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('notas-emitidas.index') }}" class="text-sm text-brand hover:text-brand/80 no-underline">
                    <i class="fa-solid fa-arrow-left"></i> Voltar ao contador
                </a>
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('notas-emitidas.emitentes.form.create') }}">
                    <i class="fa-solid fa-plus"></i> Novo Cadastro
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
            <form method="GET" action="{{ route('notas-emitidas.emitentes.index') }}" id="form-filtros-emitentes"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Buscar por nome ou CPF/CNPJ..."
                           onchange="document.getElementById('form-filtros-emitentes').submit()"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-64">
                </div>
            </form>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">CPF/CNPJ</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Origem</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Notas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($emitentes as $emitente)
                        <tr class="{{ $emitente->ativo ? '' : 'opacity-60' }}">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-slate-100 whitespace-nowrap">{{ $emitente->nome_exibicao }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $emitente->cpfcnpj_exibicao ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                @if($emitente->cliente_id)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                        <i class="fa-regular fa-building"></i> Cliente
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-gray-300">
                                        <i class="fa-regular fa-user"></i> Manual
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $emitente->notas_emitidas_count }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if($emitente->ativo)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 dark:bg-slate-600 text-gray-700 dark:text-gray-300">Inativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                <button type="button"
                                        class="text-brand hover:text-brand/80 focus:outline-none focus:ring-0 border-0 bg-transparent p-0"
                                        data-modal-url="{{ route('notas-emitidas.emitentes.form.edit', $emitente->id) }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>

                                <form method="POST" action="{{ route('notas-emitidas.emitentes.toggle', $emitente->id) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-amber-600 hover:text-amber-700 ml-3 focus:outline-none focus:ring-0 border-0 bg-transparent p-0" title="{{ $emitente->ativo ? 'Desativar' : 'Reativar' }}">
                                        <i class="fa-solid {{ $emitente->ativo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('notas-emitidas.emitentes.delete', $emitente->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="text-red-600 hover:text-red-700 ml-3 focus:outline-none focus:ring-0 border-0 bg-transparent p-0 btn-delete-emitente"
                                            data-nome="{{ $emitente->nome_exibicao }}"
                                            data-count="{{ $emitente->notas_emitidas_count }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-slate-400">Nenhum cadastro encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    document.querySelectorAll('.btn-delete-emitente').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const nome = btn.dataset.nome;
            const count = parseInt(btn.dataset.count, 10);
            const form = btn.closest('form');

            if (count > 0) {
                Swal.fire({
                    title: 'Não é possível excluir',
                    text: `"${nome}" possui ${count} nota(s) emitida(s) registrada(s). Desative o cadastro em vez de excluir.`,
                    icon: 'warning',
                    confirmButtonColor: '#6b7280',
                    confirmButtonText: 'Entendi',
                });
                return;
            }

            Swal.fire({
                title: 'Excluir cadastro?',
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
