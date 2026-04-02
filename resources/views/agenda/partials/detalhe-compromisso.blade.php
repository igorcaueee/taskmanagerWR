<div class="space-y-4">
    {{-- Modal header --}}
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="w-4 h-4 rounded-full flex-shrink-0 mt-0.5" style="background-color: {{ $compromisso->cor }};"></span>
            <h2 class="text-lg font-bold text-gray-900">{{ $compromisso->titulo }}</h2>
        </div>
        <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 border-0 bg-transparent p-0 focus:outline-none">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
    </div>

    {{-- Meta info --}}
    <div class="flex flex-wrap gap-4 text-sm text-gray-600">
        <span class="flex items-center gap-1.5">
            <i class="fa-regular fa-calendar text-gray-400"></i>
            {{ $compromisso->data->translatedFormat('d \d\e F \d\e Y') }}
        </span>
        @if ($compromisso->hora)
            <span class="flex items-center gap-1.5">
                <i class="fa-regular fa-clock text-gray-400"></i>
                {{ \Illuminate\Support\Carbon::parse($compromisso->hora)->format('H:i') }}
            </span>
        @endif
        @if ($compromisso->criador)
            <span class="flex items-center gap-1.5">
                <i class="fa-regular fa-user text-gray-400"></i>
                {{ $compromisso->criador->nome }}
            </span>
        @endif
    </div>

    {{-- Description --}}
    @if ($compromisso->descricao)
        <p class="text-sm text-gray-700 whitespace-pre-line border-t border-gray-100 pt-3">{{ $compromisso->descricao }}</p>
    @endif

    {{-- Actions --}}
    <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
        <button type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-white bg-brand rounded border-0 hover:bg-brand/80 focus:outline-none"
                data-modal-url="{{ route('agenda.compromisso.form', ['id' => $compromisso->id]) }}">
            <i class="fa-solid fa-pen-to-square"></i> Editar
        </button>
        <form method="POST" action="{{ route('agenda.compromisso.destroy', $compromisso->id) }}"
              onsubmit="return confirm('Excluir este compromisso?')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-white bg-red-500 rounded border-0 hover:bg-red-600 focus:outline-none">
                <i class="fa-solid fa-trash"></i> Excluir
            </button>
        </form>
    </div>
</div>
