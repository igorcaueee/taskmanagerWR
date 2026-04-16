@php
    $prioridadeLabels = [1 => 'Baixa', 2 => 'Normal', 3 => 'Média', 4 => 'Alta', 5 => 'Urgente'];
    $prioridadeColors = [
        1 => 'text-gray-500',
        2 => 'text-blue-600',
        3 => 'text-yellow-700',
        4 => 'text-orange-700',
        5 => 'text-red-600',
    ];
    $estaAtrasada = is_null($tarefa->data_conclusao) && $tarefa->data_vencimento->lt(now()->startOfDay());
    $estaConcluida = ! is_null($tarefa->data_conclusao);
    $frequenciaLabels = [
        'nenhuma'    => null,
        'diaria'     => 'Diária',
        'semanal'    => 'Semanal',
        'mensal'     => 'Mensal',
        'trimestral' => 'Trimestral',
        'semestral'  => 'Semestral',
        'anual'      => 'Anual',
    ];
@endphp

<div class="space-y-4">
    {{-- Header --}}
    <div class="flex items-start justify-between gap-3">
        <h2 class="text-sm font-semibold text-gray-900 leading-snug pr-2">{{ $tarefa->titulo }}</h2>
        <button type="button" onclick="closeModal()"
                class="text-gray-400 hover:text-gray-600 border-0 bg-transparent p-0 focus:outline-none flex-shrink-0">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>

    {{-- Meta grid --}}
    <div class="grid grid-cols-2 gap-3">
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Etapa</p>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full text-white"
                  style="background-color: {{ $tarefa->etapa->cor ?? '#9ca3af' }};">
                <span class="w-1.5 h-1.5 rounded-full bg-white/70 inline-block"></span>
                {{ $tarefa->etapa->nome ?? '—' }}
            </span>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Prioridade</p>
            <p class="text-xs font-medium {{ $prioridadeColors[$tarefa->prioridade] ?? 'text-gray-700' }}">
                {{ $prioridadeLabels[$tarefa->prioridade] ?? $tarefa->prioridade }}
            </p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Cliente</p>
            <p class="text-xs text-gray-700">{{ $tarefa->cliente->nome ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Departamento</p>
            <p class="text-xs text-gray-700">{{ $tarefa->departamento->nome ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Responsável</p>
            <p class="text-xs text-gray-700">{{ $tarefa->responsavel->nome ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Supervisor</p>
            <p class="text-xs text-gray-700">{{ $tarefa->supervisor->nome ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Vencimento</p>
            <p class="text-xs font-medium {{ $estaAtrasada ? 'text-red-600' : 'text-gray-700' }}">
                {{ $tarefa->data_vencimento->format('d/m/Y') }}
                @if ($estaAtrasada)
                    <i class="fa-solid fa-triangle-exclamation text-red-500 ml-1"></i>
                @elseif ($estaConcluida)
                    <i class="fa-solid fa-circle-check text-green-600 ml-1"></i>
                @endif
            </p>
        </div>
        @if ($tarefa->recorrente && ($frequenciaLabels[$tarefa->frequencia] ?? null))
            <div class="col-span-2">
                <p class="text-xs text-gray-400 mb-0.5">Recorrência</p>
                <p class="text-xs text-gray-700">
                    <i class="fa-solid fa-rotate text-brand mr-1"></i>
                    {{ $frequenciaLabels[$tarefa->frequencia] }}
                </p>
            </div>
        @endif
    </div>

    {{-- Description --}}
    @if ($tarefa->descricao)
        <div class="border-t border-gray-100 pt-3">
            <p class="text-xs text-gray-400 mb-1">Descrição</p>
            <p class="text-xs text-gray-700 whitespace-pre-line leading-relaxed">{{ $tarefa->descricao }}</p>
        </div>
    @endif

    {{-- History --}}
    <div class="border-t border-gray-100 pt-3">
        <p class="text-xs text-gray-400 mb-2"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Histórico</p>
        @if ($tarefa->historico->isNotEmpty())
            <ol reversed class="relative border-l border-gray-200 ml-2 space-y-3 text-xs">
                @foreach ($tarefa->historico->sortByDesc('created_at') as $reg)
                    <li class="ml-4">
                        <span class="absolute -left-1.5 mt-0.5 w-3 h-3 rounded-full border border-white"
                              style="background-color: {{ $reg->etapaNova->cor ?? '#9ca3af' }};"></span>
                        @if ($reg->etapa_nova_id)
                            <p class="text-gray-700">
                                <span class="font-medium">{{ $reg->etapaAnterior->nome ?? '—' }}</span>
                                <i class="fa-solid fa-arrow-right mx-1 text-gray-400"></i>
                                <span class="font-medium">{{ $reg->etapaNova->nome ?? '—' }}</span>
                            </p>
                        @endif
                        @if ($reg->responsavel_novo_id)
                            <p class="text-gray-700">
                                <i class="fa-solid fa-user-pen mr-1 text-gray-400"></i>
                                <span class="font-medium">{{ $reg->responsavelAnterior->nome ?? 'Nenhum' }}</span>
                                <i class="fa-solid fa-arrow-right mx-1 text-gray-400"></i>
                                <span class="font-medium">{{ $reg->responsavelNovo->nome ?? 'Nenhum' }}</span>
                            </p>
                        @endif
                        <p class="text-gray-400 mt-0.5">
                            {{ $reg->alteradoPor->nome ?? '—' }} · {{ $reg->created_at->format('d/m/Y H:i') }}
                        </p>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="text-xs text-gray-400 italic ml-2">Nenhuma movimentação registrada.</p>
        @endif
    </div>

    {{-- Actions --}}
    <div class="pt-2 border-t border-gray-100 flex gap-2">
        <button type="button"
                data-modal-url="{{ route('tarefas.form.edit', $tarefa->id) }}"
                class="flex-1 text-xs px-3 py-1.5 bg-brand text-white rounded border-0 hover:bg-brand/80 focus:outline-none">
            <i class="fa-solid fa-pen-to-square mr-1"></i> Editar
        </button>
    </div>
</div>
