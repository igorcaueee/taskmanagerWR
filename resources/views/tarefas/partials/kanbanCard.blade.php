@php
    $prioridadeColors = [
        1 => 'bg-gray-100 text-gray-500',
        2 => 'bg-blue-100 text-blue-600',
        3 => 'bg-yellow-100 text-yellow-700',
        4 => 'bg-orange-100 text-orange-700',
        5 => 'bg-red-100 text-red-600',
    ];
    $prioridadeLabels = [1 => 'Baixa', 2 => 'Normal', 3 => 'Média', 4 => 'Alta', 5 => 'Urgente'];
    $colorClass = $prioridadeColors[$tarefa->prioridade] ?? 'bg-gray-100 text-gray-500';
    $prioridadeLabel = $prioridadeLabels[$tarefa->prioridade] ?? $tarefa->prioridade;
    $estaAtrasada = is_null($tarefa->data_conclusao) && $tarefa->data_vencimento->lt(now()->startOfDay());
    $estaConcluida = ! is_null($tarefa->data_conclusao);
@endphp

<div class="kanban-card rounded-lg shadow-sm border p-3 cursor-grab active:cursor-grabbing select-none
    {{ $estaConcluida ? 'bg-green-50 border-green-300' : 'bg-white' }}
    {{ $tarefa->passou_ciclo && ! $estaConcluida ? 'border-amber-400 border-l-4' : '' }}
    {{ ! $estaConcluida && ! $tarefa->passou_ciclo ? 'border-gray-200' : '' }}"
     draggable="true"
     data-tarefa-id="{{ $tarefa->id }}"
     data-etapa-id="{{ $tarefa->etapa_id }}"
     ondragstart="handleDragStart(event, {{ $tarefa->id }}, {{ $tarefa->etapa_id }})"
     ondragend="handleDragEnd()">

    @if ($tarefa->passou_ciclo && ! $estaConcluida)
        <div class="flex items-center gap-1 text-amber-600 text-xs font-medium mb-2">
            <i class="fa-solid fa-forward-step"></i>
            <span>Vinda do ciclo anterior</span>
        </div>
    @endif

    @if ($estaConcluida)
        <div class="flex items-center gap-1 text-green-600 text-xs font-medium mb-2">
            <i class="fa-solid fa-circle-check"></i>
            <span>Concluída em {{ $tarefa->data_conclusao->format('d/m/Y') }}</span>
        </div>
    @endif

    <div class="flex items-start justify-between gap-1 mb-2">
        <p class="text-sm font-semibold text-gray-800 leading-tight line-clamp-2">{{ $tarefa->titulo }}</p>
        <span class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0 {{ $colorClass }}">{{ $prioridadeLabel }}</span>
    </div>

    @if ($tarefa->cliente)
        <p class="text-xs text-gray-500 mb-1"><i class="fa-regular fa-building w-3"></i> {{ $tarefa->cliente->nome }}</p>
    @endif

    <div class="flex items-center justify-between mt-2">
        @if ($tarefa->responsavel)
            <span class="text-xs text-gray-500"><i class="fa-regular fa-user w-3"></i> {{ $tarefa->responsavel->nome }}</span>
        @else
            <span></span>
        @endif

        <span class="text-xs {{ $estaAtrasada ? 'text-red-600 font-semibold' : 'text-gray-400' }}">
            <i class="fa-regular fa-calendar w-3"></i>
            {{ $tarefa->data_vencimento->format('d/m/Y') }}
        </span>
    </div>

    {{-- Quick actions --}}
    <div class="flex gap-2 mt-2 pt-2 border-t border-gray-100 items-center">
        <button type="button"
                class="text-xs text-gray-400 hover:text-brand border-0 bg-transparent p-0 cursor-pointer"
                data-modal-url="{{ route('tarefas.form.edit', $tarefa->id) }}"
                ondragstart="event.stopPropagation()">
            <i class="fa-solid fa-pen-to-square"></i>
        </button>
        <form method="POST" action="{{ route('tarefas.delete', $tarefa->id) }}" class="inline"
              ondragstart="event.stopPropagation()">
            @csrf
            @method('DELETE')
            <button type="button"
                    class="text-xs text-gray-400 hover:text-red-500 border-0 bg-transparent p-0 cursor-pointer btn-delete-kanban"
                    data-tarefa-titulo="{{ $tarefa->titulo }}"
                    ondragstart="event.stopPropagation()">
                <i class="fa-solid fa-trash"></i>
            </button>
        </form>

        <button type="button"
                class="ml-auto text-xs text-gray-400 hover:text-amber-600 border-0 bg-transparent p-0 cursor-pointer btn-proximo-ciclo"
                title="Passar para o próximo ciclo"
                data-tarefa-id="{{ $tarefa->id }}"
                data-tarefa-titulo="{{ $tarefa->titulo }}"
                ondragstart="event.stopPropagation()">
            <i class="fa-solid fa-forward"></i>
        </button>
    </div>
</div>
