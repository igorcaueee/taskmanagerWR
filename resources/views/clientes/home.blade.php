@extends('layouts.internal')

@section('title', 'Clientes — WR Assessoria')

@section('content')
    <div class="w-full mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fa-solid fa-users"></i> Clientes</h1>
                <p class="text-gray-700">Aqui você pode visualizar e gerenciar seus clientes.</p>
            </div>
            @if (auth()->user()?->canEditarClientes())
            <div class="flex gap-2">
                <button type="button" class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded focus:outline-none hover:bg-gray-50 text-sm"
                        data-modal-url="{{ route('clientes.import.form') }}">
                    <i class="fa-solid fa-file-import"></i>
                    Importar
                </button>
                <button type="button" class="inline-flex items-center px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('clientes.form.create') }}">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            @endif
        </div>

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

        <div class="bg-white rounded shadow">
            {{-- Filters --}}
            <form method="GET" action="{{ route('clientes') }}" id="form-filtros-clientes"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Buscar por nome..."
                           onchange="document.getElementById('form-filtros-clientes').submit()"
                           class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand w-48">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                    <select name="tipo" onchange="document.getElementById('form-filtros-clientes').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        <option value="1" @selected(request('tipo') === '1')>Pessoa Jurídica (PJ)</option>
                        <option value="0" @selected(request('tipo') === '0')>Pessoa Física (PF)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select name="status" onchange="document.getElementById('form-filtros-clientes').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        <option value="ativo"   @selected(request('status') === 'ativo')>Ativo</option>
                        <option value="inativo" @selected(request('status') === 'inativo')>Inativo</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Regime Tributário</label>
                    <select name="regime_tributario" onchange="document.getElementById('form-filtros-clientes').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        <option value="Simples Nacional" @selected(request('regime_tributario') === 'Simples Nacional')>Simples Nacional</option>
                        <option value="Lucro Presumido"  @selected(request('regime_tributario') === 'Lucro Presumido')>Lucro Presumido</option>
                        <option value="Lucro Real"       @selected(request('regime_tributario') === 'Lucro Real')>Lucro Real</option>
                        <option value="MEI"              @selected(request('regime_tributario') === 'MEI')>MEI</option>
                    </select>
                </div>
            </form>

            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">CPF/CNPJ</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Regime Tributário</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cidade/UF</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente Desde</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fator R</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($clientes as $cliente)
                        <tr>
                            <td class="px-4 py-2 text-xs text-gray-700">
                                @if((string) $cliente->tipo === '1')
                                    <i class="fa-solid fa-building-user"></i>
                                @elseif((string) $cliente->tipo === '0')
                                    <i class="fa-solid fa-user"></i>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-700">{{ $cliente->nome }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700 whitespace-nowrap">{{ $cliente->cpfcnpj ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700 whitespace-nowrap">{{ $cliente->regime_tributario ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700">
                                {{ $cliente->cidade ?? '—' }}{{ $cliente->estado ? '/' . $cliente->estado : '' }}
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-700">
                                {{ $cliente->cliente_desde ? \Illuminate\Support\Carbon::parse($cliente->cliente_desde)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-4 py-2 text-xs">
                                @if($cliente->status === 'ativo')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-center">
                                @if($cliente->fator_r)
                                    <i class="fa-solid fa-check text-green-600"></i>
                                @else
                                    <i class="fa-solid fa-xmark text-gray-300"></i>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-right whitespace-nowrap">
                                @if (auth()->user()?->canEditarClientes())
                                <button type="button"
                                        class="text-brand hover:text-brand/80 focus:outline-none focus:ring-0 border-0 bg-transparent p-0"
                                        data-modal-url="{{ route('clientes.form.edit', $cliente->id) }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>

                                @if($cliente->status === 'ativo')
                                <button type="button"
                                        class="text-orange-500 hover:text-orange-600 ml-3 focus:outline-none focus:ring-0 border-0 bg-transparent p-0"
                                        data-modal-url="{{ route('clientes.form.encerrar', $cliente->id) }}"
                                        title="Encerrar cliente">
                                    <i class="fa-solid fa-building-circle-xmark"></i>
                                </button>
                                @endif

                                <form method="POST" action="{{ route('clientes.delete', $cliente->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-red-600 hover:text-red-700 ml-3 focus:outline-none focus:ring-0 border-0 bg-transparent p-0 btn-delete-cliente" data-nome="{{ $cliente->nome }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum cliente encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    document.querySelectorAll('.btn-delete-cliente').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const nome = btn.dataset.nome;
            const form = btn.closest('form');

            Swal.fire({
                title: 'Excluir cliente?',
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
