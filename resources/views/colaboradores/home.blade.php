@extends('layouts.internal')

@section('title', 'Painel — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fa-regular fa-user"></i> Colaboradores</h1>
                <p class="text-gray-700">Aqui você pode visualizar e gerenciar seus colaboradores.</p>
            </div>
            <div>
                <button type="button" class="inline-flex items-center px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('colaboradores.form.create') }}">
                    <i class="fa-solid fa-user-plus"></i>
                </button>
            </div>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            {{-- Filters --}}
            <form method="GET" action="{{ route('colaboradores') }}" id="form-filtros-colabs"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Buscar por nome ou e-mail..."
                           onchange="document.getElementById('form-filtros-colabs').submit()"
                           class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand w-56">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Cargo</label>
                    <select name="cargo" onchange="document.getElementById('form-filtros-colabs').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        <option value="diretor"    @selected(request('cargo') === 'diretor')>Diretor</option>
                        <option value="supervisor" @selected(request('cargo') === 'supervisor')>Supervisor</option>
                        <option value="analista"   @selected(request('cargo') === 'analista')>Analista</option>
                        <option value="assistente" @selected(request('cargo') === 'assistente')>Assistente</option>
                        <option value="auxiliar"   @selected(request('cargo') === 'auxiliar')>Auxiliar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select name="status" onchange="document.getElementById('form-filtros-colabs').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        <option value="1" @selected(request('status') === '1')>Ativo</option>
                        <option value="0" @selected(request('status') === '0')>Inativo</option>
                    </select>
                </div>
            </form>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">E-mail</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nascimento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registro</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cargo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($colaboradores as $colab)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $colab->nome }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $colab->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $colab->telefone ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $colab->data_nascimento ? \Illuminate\Support\Carbon::parse($colab->data_nascimento)->format('d/m/Y') : '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $colab->data_registro ? \Illuminate\Support\Carbon::parse($colab->data_registro)->format('d/m/Y') : '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $colab->departamento?->nome ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 capitalize whitespace-nowrap">{{ $colab->cargo }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if($colab->status)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <button type="button"
                                        class="text-brand hover:text-brand/80 focus:outline-none focus:ring-0 border-0 bg-transparent p-0"
                                        data-modal-url="{{ route('colaboradores.form.edit', $colab->id) }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>

                                <form method="POST" action="{{ route('colaboradores.delete', $colab->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-red-600 hover:text-red-700 ml-3 focus:outline-none focus:ring-0 border-0 bg-transparent p-0 btn-delete-colab" data-nome="{{ $colab->nome }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum colaborador encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    document.querySelectorAll('.btn-delete-colab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const nome = btn.dataset.nome;
            const form = btn.closest('form');

            Swal.fire({
                title: 'Excluir colaborador?',
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