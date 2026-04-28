<div class="kanban-card rounded-lg shadow-sm border border-gray-200 bg-white p-3 cursor-grab active:cursor-grabbing select-none
    {{ $lead->convertido_cliente_id ? 'bg-green-50 border-green-300' : 'bg-white border-gray-200' }}"
     draggable="true"
     data-lead-id="{{ $lead->id }}"
     data-etapa-id="{{ $lead->etapa_funil_id }}"
     ondragstart="handleDragStart(event, {{ $lead->id }}, {{ $lead->etapa_funil_id }})"
     ondragend="handleDragEnd()">

    @if ($lead->convertido_cliente_id)
        <div class="flex items-center gap-1 text-green-600 text-xs font-medium mb-2">
            <i class="fa-solid fa-circle-check"></i>
            <span>Convertido em cliente</span>
        </div>
    @endif

    <div class="flex items-start justify-between gap-1 mb-1">
        <p class="text-sm font-semibold text-gray-800 leading-tight line-clamp-2">{{ $lead->nome }}</p>
        @if ($lead->origem === 'formulario')
            <span class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0 bg-purple-100 text-purple-700" title="Veio do formulário público">
                <i class="fa-solid fa-globe"></i>
            </span>
        @endif
    </div>

    @if ($lead->empresa)
        <p class="text-xs text-gray-500 mb-1"><i class="fa-regular fa-building w-3"></i> {{ $lead->empresa }}</p>
    @endif

    @if ($lead->servico)
        <p class="text-xs text-gray-400 mb-1 truncate"><i class="fa-solid fa-briefcase w-3"></i> {{ $lead->servico }}</p>
    @endif

    <div class="flex items-center justify-between mt-2">
        @if ($lead->responsavel)
            <span class="text-xs text-gray-500"><i class="fa-regular fa-user w-3"></i> {{ $lead->responsavel->nome }}</span>
        @else
            <span></span>
        @endif

        @if ($lead->honorario)
            <span class="text-xs text-green-700 font-medium">
                R$ {{ number_format($lead->honorario, 2, ',', '.') }}
            </span>
        @endif
    </div>

    {{-- Quick actions --}}
    <div class="flex gap-2 mt-2 pt-2 border-t border-gray-100 items-center">
        <button type="button"
                class="text-xs text-gray-400 hover:text-brand border-0 bg-transparent p-0 cursor-pointer"
                data-modal-url="{{ route('leads.form.edit', $lead->id) }}"
                ondragstart="event.stopPropagation()">
            <i class="fa-solid fa-pen-to-square"></i>
        </button>
        <form method="POST" action="{{ route('leads.delete', $lead->id) }}" class="inline"
              ondragstart="event.stopPropagation()">
            @csrf
            @method('DELETE')
            <button type="button"
                    class="text-xs text-gray-400 hover:text-red-500 border-0 bg-transparent p-0 cursor-pointer btn-delete-lead"
                    data-lead-nome="{{ $lead->nome }}"
                    ondragstart="event.stopPropagation()">
                <i class="fa-solid fa-trash"></i>
            </button>
        </form>

        @if (!$lead->convertido_cliente_id)
            <button type="button"
                    class="ml-auto text-xs text-gray-400 hover:text-green-600 border-0 bg-transparent p-0 cursor-pointer btn-converter-lead"
                    title="Converter em cliente"
                    data-lead-id="{{ $lead->id }}"
                    data-lead-nome="{{ $lead->nome }}"
                    ondragstart="event.stopPropagation()">
                <i class="fa-solid fa-user-check"></i>
            </button>
        @endif
    </div>
</div>
