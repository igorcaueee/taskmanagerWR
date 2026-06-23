@extends('layouts.internal')

@section('title', 'Tarefas — WR Assessoria')

@section('content')
    <div class="py-6 px-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-list-check"></i> Tarefas</h1>
                <p class="text-gray-700 dark:text-gray-300">Aqui você pode visualizar e gerenciar suas tarefas.</p>
            </div>
            <div>
                <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('tarefas.form.create') }}">
                    <i class="fa-solid fa-plus"></i> Nova Tarefa
                </button>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-800 rounded shadow">
            {{-- Filters --}}
            <form method="GET" action="{{ route('tarefas') }}" id="form-filtros-home"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="Buscar por título..."
                           onchange="document.getElementById('form-filtros-home').submit()"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-48">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Cliente</label>
                    <select name="cliente_id" onchange="document.getElementById('form-filtros-home').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(request('cliente_id') == $cliente->id)>{{ $cliente->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Etapa</label>
                    <select name="etapa_id" onchange="document.getElementById('form-filtros-home').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todas</option>
                        @foreach($etapas as $etapa)
                            <option value="{{ $etapa->id }}" @selected(request('etapa_id') == $etapa->id)>{{ $etapa->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Recorrência</label>
                    <select name="frequencia" onchange="document.getElementById('form-filtros-home').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
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
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Responsável</label>
                    <select name="responsavel_id" onchange="document.getElementById('form-filtros-home').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($usuarios as $usr)
                            <option value="{{ $usr->id }}" @selected(request('responsavel_id') == $usr->id)>{{ $usr->nome }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(auth()->user()->canExcluirTarefa())
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 cursor-pointer select-none">
                        <input type="checkbox" name="mostrar_inativas" value="1"
                               @checked($mostrarInativas)
                               onchange="document.getElementById('form-filtros-home').submit()"
                               class="rounded border-gray-300 text-brand focus:ring-brand">
                        Mostrar inativas
                    </label>
                </div>
                @endif
            </form>

            <table class="w-full table-fixed divide-y divide-gray-200 dark:divide-slate-700">
                <colgroup>
                    <col class="w-[28%]">  {{-- Título --}}
                    <col class="w-[16%]">  {{-- Cliente --}}
                    <col class="w-[14%]">  {{-- Departamento --}}
                    <col class="w-[11%]">  {{-- Etapa --}}
                    <col class="w-[13%]">  {{-- Responsável --}}
                    <col class="w-[9%]">   {{-- Vencimento --}}
                    <col class="w-[6%]">   {{-- Prioridade --}}
                    <col class="w-[3%]">   {{-- Ações --}}
                </colgroup>
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Título</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Departamento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Etapa</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Responsável</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Vencimento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prioridade</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($tarefas as $tarefa)
                        <tr class="{{ ! $tarefa->ativo ? 'opacity-50' : '' }}">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-slate-100 font-medium overflow-hidden">
                                <span class="block truncate {{ ! $tarefa->ativo ? 'line-through text-gray-400' : '' }}" title="{{ $tarefa->titulo }}">{{ $tarefa->titulo }}</span>
                                @if(! $tarefa->ativo)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-200 text-gray-500 dark:bg-slate-700 dark:text-slate-400 ml-1">Inativa</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 overflow-hidden">
                                <span class="block truncate" title="{{ $tarefa->cliente->nome ?? '' }}">{{ \Str::limit($tarefa->cliente->nome ?? '—', 30, '…') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 overflow-hidden">
                                <span class="block truncate" title="{{ $tarefa->departamento->nome ?? '' }}">{{ \Str::limit($tarefa->departamento->nome ?? '—', 30, '…') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 overflow-hidden">
                                <span class="block truncate" title="{{ $tarefa->etapa->nome ?? '' }}">{{ \Str::limit($tarefa->etapa->nome ?? '—', 30, '…') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 overflow-hidden">
                                <span class="block truncate" title="{{ $tarefa->responsavel->nome ?? '' }}">{{ \Str::limit($tarefa->responsavel->nome ?? '—', 30, '…') }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap {{ (is_null($tarefa->data_conclusao) && $tarefa->data_vencimento->lt(now()->startOfDay())) ? 'text-red-600 font-semibold' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $tarefa->data_vencimento->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm whitespace-nowrap">
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
                            <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                @if ($tarefa->ativo)
                                    @if (auth()->user()->canEditarQualquerTarefa() || $tarefa->responsavel_id == auth()->id())
                                    <button type="button"
                                            class="text-brand hover:text-brand/80 focus:outline-none border-0 bg-transparent p-0"
                                            data-modal-url="{{ route('tarefas.form.edit', $tarefa->id) }}">
                                        <i class="fa-solid fa-pencil"></i>
                                    </button>

                                    <button type="button"
                                            class="text-indigo-500 hover:text-indigo-700 ml-3 focus:outline-none border-0 bg-transparent p-0 btn-duplicar-tarefa"
                                            title="Duplicar para outros clientes"
                                            data-tarefa-id="{{ $tarefa->id }}"
                                            data-tarefa-titulo="{{ $tarefa->titulo }}">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                    @endif

                                    @if (auth()->user()->canInativarTarefa($tarefa))
                                    <form method="POST" action="{{ route('tarefas.inativar', $tarefa->id) }}" class="inline inativar-tarefa-form">
                                        @csrf
                                        <input type="hidden" name="scope" value="unica" class="inativar-scope-input">
                                        <button type="button"
                                                class="text-orange-500 hover:text-orange-700 ml-3 focus:outline-none border-0 bg-transparent p-0 btn-inativar-tarefa"
                                                data-titulo="{{ $tarefa->titulo }}"
                                                data-tarefa-id="{{ $tarefa->id }}"
                                                data-recorrente="{{ $tarefa->recorrente ? '1' : '0' }}"
                                                title="Inativar tarefa">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    </form>
                                    @endif
                                @else
                                    @if (auth()->user()->canExcluirTarefa())
                                    <form method="POST" action="{{ route('tarefas.ativar', $tarefa->id) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="text-green-600 hover:text-green-700 focus:outline-none border-0 bg-transparent p-0"
                                                title="Reativar tarefa">
                                            <i class="fa-solid fa-rotate-left"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('tarefas.delete', $tarefa->id) }}" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="text-red-600 hover:text-red-700 ml-3 focus:outline-none border-0 bg-transparent p-0 btn-delete-tarefa"
                                                data-titulo="{{ $tarefa->titulo }}">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Nenhuma tarefa encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const clientesData = @json($clientes->map(fn($c) => ['id' => $c->id, 'nome' => $c->nome])->values());

    document.querySelectorAll('.btn-duplicar-tarefa').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const tarefaId = btn.dataset.tarefaId;
            const titulo = btn.dataset.tarefaTitulo;

            const clienteOptions = clientesData.map(c =>
                `<label class="flex items-center gap-2 px-2 py-1 hover:bg-gray-50 rounded cursor-pointer">
                    <input type="checkbox" name="cliente_ids" value="${c.id}" class="rounded">
                    <span class="text-sm">${c.nome}</span>
                </label>`
            ).join('');

            const { value: form, isConfirmed } = await Swal.fire({
                title: 'Duplicar tarefa',
                html: `<p class="text-sm text-gray-500 mb-3">Selecione os clientes para os quais deseja duplicar <strong>"${titulo}"</strong>:</p>
                       <div class="text-left max-h-64 overflow-y-auto border rounded p-2">
                           <input type="text" id="swal-busca-cliente" placeholder="Buscar cliente..." class="w-full border rounded px-2 py-1 text-sm mb-2 focus:outline-none focus:ring-1 focus:ring-brand">
                           <div id="swal-clientes-lista">${clienteOptions}</div>
                       </div>`,
                showCancelButton: true,
                confirmButtonText: 'Duplicar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#4f46e5',
                didOpen: () => {
                    const busca = document.getElementById('swal-busca-cliente');
                    busca.addEventListener('input', () => {
                        const q = busca.value.toLowerCase();
                        document.querySelectorAll('#swal-clientes-lista label').forEach(label => {
                            label.style.display = label.textContent.toLowerCase().includes(q) ? '' : 'none';
                        });
                    });
                },
                preConfirm: () => {
                    const checked = [...document.querySelectorAll('input[name="cliente_ids"]:checked')].map(i => i.value);
                    if (checked.length === 0) {
                        Swal.showValidationMessage('Selecione pelo menos um cliente.');
                        return false;
                    }
                    return checked;
                },
            });

            if (!isConfirmed || !form) { return; }

            try {
                const res = await fetch(`/tarefas/${tarefaId}/duplicar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ cliente_ids: form }),
                });

                if (!res.ok) { throw new Error(); }
                const data = await res.json();
                Swal.fire({
                    icon: 'success',
                    title: 'Duplicado!',
                    text: `${data.count} tarefa(s) criada(s) com sucesso.`,
                    timer: 2500,
                    showConfirmButton: false,
                });
            } catch {
                Swal.fire('Erro', 'Não foi possível duplicar a tarefa.', 'error');
            }
        });
    });

    document.querySelectorAll('.btn-inativar-tarefa').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const titulo = btn.dataset.titulo;
            const recorrente = btn.dataset.recorrente === '1';
            const form = btn.closest('form');

            if (recorrente) {
                const { value: scope } = await Swal.fire({
                    title: 'Inativar tarefa recorrente',
                    html: `<p class="text-sm text-gray-600 mb-4">A tarefa <strong>"${titulo}"</strong> é recorrente. O que deseja inativar?</p>`,
                    input: 'radio',
                    inputOptions: {
                        'unica': 'Apenas esta ocorrência',
                        'futuras': 'Esta e todas as futuras',
                    },
                    inputValue: 'unica',
                    showCancelButton: true,
                    confirmButtonColor: '#f97316',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Inativar',
                    cancelButtonText: 'Cancelar',
                    inputValidator: (value) => {
                        if (!value) return 'Selecione uma opção.';
                    },
                });
                if (!scope) return;
                form.querySelector('.inativar-scope-input').value = scope;
            } else {
                const result = await Swal.fire({
                    title: 'Inativar tarefa?',
                    text: `A tarefa "${titulo}" será inativada. Você poderá reativá-la depois.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#f97316',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sim, inativar',
                    cancelButtonText: 'Cancelar',
                });
                if (!result.isConfirmed) return;
            }

            form.submit();
        });
    });

    document.querySelectorAll('.btn-delete-tarefa').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const titulo = btn.dataset.titulo;
            const form = btn.closest('form');

            Swal.fire({
                title: 'Excluir permanentemente?',
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
