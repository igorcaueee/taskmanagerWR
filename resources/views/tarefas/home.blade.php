@extends('layouts.internal')

@section('title', 'Tarefas — WR Assessoria')

@section('content')
    <div class="py-6 px-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fa-solid fa-list-check"></i> Tarefas</h1>
                <p class="text-gray-700">Aqui você pode visualizar e gerenciar suas tarefas.</p>
            </div>
            <div>
                <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('tarefas.form.create') }}">
                    <i class="fa-solid fa-plus"></i> Nova Tarefa
                </button>
            </div>
        </div>

        <div class="bg-white rounded shadow">
            {{-- Filters --}}
            <form method="GET" action="{{ route('tarefas') }}" id="form-filtros-home"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Buscar por título..."
                           onchange="document.getElementById('form-filtros-home').submit()"
                           class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand w-48">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Cliente</label>
                    <select name="cliente_id" onchange="document.getElementById('form-filtros-home').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(request('cliente_id') == $cliente->id)>{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Etapa</label>
                    <select name="etapa_id" onchange="document.getElementById('form-filtros-home').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todas</option>
                        @foreach($etapas as $etapa)
                            <option value="{{ $etapa->id }}" @selected(request('etapa_id') == $etapa->id)>{{ $etapa->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Recorrência</label>
                    <select name="frequencia" onchange="document.getElementById('form-filtros-home').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todas</option>
                        <option value="nenhuma"   @selected(request('frequencia') === 'nenhuma')>Não se repete</option>
                        <option value="semanal"   @selected(request('frequencia') === 'semanal')>Semanal</option>
                        <option value="mensal"    @selected(request('frequencia') === 'mensal')>Mensal</option>
                        <option value="trimestral" @selected(request('frequencia') === 'trimestral')>Trimestral</option>
                        <option value="semestral" @selected(request('frequencia') === 'semestral')>Semestral</option>
                        <option value="anual"     @selected(request('frequencia') === 'anual')>Anual</option>
                    </select>
                </div>
                @if($podeVerTodas)
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Responsável</label>
                    <select name="responsavel_id" onchange="document.getElementById('form-filtros-home').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($usuarios as $usr)
                            <option value="{{ $usr->id }}" @selected(request('responsavel_id') == $usr->id)>{{ $usr->nome }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </form>

            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Departamento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Etapa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Responsável</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioridade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorrência</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tarefas as $tarefa)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900 font-medium whitespace-nowrap">{{ $tarefa->titulo }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $tarefa->cliente->nome ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $tarefa->departamento->nome ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $tarefa->etapa->nome ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ $tarefa->responsavel->nome ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap {{ (is_null($tarefa->data_conclusao) && $tarefa->data_vencimento->lt(now()->startOfDay())) ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                {{ $tarefa->data_vencimento->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                @php
                                    $prioridadeMap = [
                                        1 => ['label' => 'Baixa', 'class' => 'bg-gray-100 text-gray-600'],
                                        2 => ['label' => 'Normal', 'class' => 'bg-blue-100 text-blue-700'],
                                        3 => ['label' => 'Alta', 'class' => 'bg-yellow-100 text-yellow-700'],
                                        4 => ['label' => 'Urgente', 'class' => 'bg-orange-100 text-orange-700'],
                                        5 => ['label' => 'Crítica', 'class' => 'bg-red-100 text-red-700'],
                                    ];
                                    $p = $prioridadeMap[$tarefa->prioridade] ?? $prioridadeMap[1];
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $p['class'] }}">
                                    {{ $p['label'] }}
                                </span>
                            </td>
                            @php
                                $frequenciaLabels = [
                                    'semanal'    => ['label' => 'Semanal',    'class' => 'bg-blue-100 text-blue-700'],
                                    'mensal'     => ['label' => 'Mensal',     'class' => 'bg-purple-100 text-purple-700'],
                                    'trimestral' => ['label' => 'Trimestral', 'class' => 'bg-indigo-100 text-indigo-700'],
                                    'semestral'  => ['label' => 'Semestral',  'class' => 'bg-cyan-100 text-cyan-700'],
                                    'anual'      => ['label' => 'Anual',      'class' => 'bg-teal-100 text-teal-700'],
                                ];
                                $freq = $frequenciaLabels[$tarefa->frequencia] ?? null;
                            @endphp
                            <td class="px-6 py-4 text-sm whitespace-nowrap">
                                @if($freq)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium {{ $freq['class'] }}">
                                        <i class="fa-solid fa-rotate text-[10px]"></i> {{ $freq['label'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                <button type="button"
                                        class="text-brand hover:text-brand/80 focus:outline-none border-0 bg-transparent p-0"
                                        data-modal-url="{{ route('tarefas.form.edit', $tarefa->id) }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>

                                <form method="POST" action="{{ route('tarefas.delete', $tarefa->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button"
                                            class="text-red-600 hover:text-red-700 ml-3 focus:outline-none border-0 bg-transparent p-0 btn-delete-tarefa"
                                            data-titulo="{{ $tarefa->titulo }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">Nenhuma tarefa encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    document.querySelectorAll('.btn-delete-tarefa').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const titulo = btn.dataset.titulo;
            const form = btn.closest('form');

            Swal.fire({
                title: 'Excluir tarefa?',
                text: `Tem certeza que deseja excluir "${titulo}"? Esta ação não pode ser desfeita.`,
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
