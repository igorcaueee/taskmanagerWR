<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-900">
            {{ $compromisso ? 'Editar Compromisso' : 'Novo Compromisso' }}
        </h2>
        <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 border-0 bg-transparent p-0 focus:outline-none">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
    </div>

    <form method="POST"
          action="{{ $compromisso ? route('agenda.compromisso.update', $compromisso->id) : route('agenda.compromisso.store') }}">
        @csrf
        @if ($compromisso)
            @method('PUT')
        @endif

        {{-- Título --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Título <span class="text-red-500">*</span></label>
            <input type="text" name="titulo" required maxlength="255"
                   value="{{ $compromisso?->titulo ?? '' }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand">
        </div>

        {{-- Data e hora --}}
        <div class="flex gap-3 mb-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Data <span class="text-red-500">*</span></label>
                <input type="date" name="data" required
                       value="{{ $compromisso ? $compromisso->data->format('Y-m-d') : ($dataInicial ?: '') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand">
            </div>
            <div class="w-32">
                <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                <input type="time" name="hora"
                       value="{{ $compromisso?->hora ? \Illuminate\Support\Carbon::parse($compromisso->hora)->format('H:i') : '' }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand">
            </div>
        </div>

        {{-- Cor --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Cor</label>
            <div class="flex flex-wrap gap-2">
                @php
                    $cores = [
                        '#3b82f6' => 'Azul',
                        '#10b981' => 'Verde',
                        '#f59e0b' => 'Amarelo',
                        '#ef4444' => 'Vermelho',
                        '#8b5cf6' => 'Roxo',
                        '#ec4899' => 'Rosa',
                        '#f97316' => 'Laranja',
                        '#6b7280' => 'Cinza',
                    ];
                    $corAtual = $compromisso?->cor ?? '#3b82f6';
                @endphp
                @foreach ($cores as $hex => $nome)
                    <label class="cursor-pointer" title="{{ $nome }}">
                        <input type="radio" name="cor" value="{{ $hex }}"
                               class="sr-only peer"
                               {{ $corAtual === $hex ? 'checked' : '' }}>
                        <span class="block w-7 h-7 rounded-full border-2 border-transparent peer-checked:border-gray-800 peer-checked:scale-110 transition-all"
                              style="background-color: {{ $hex }};"></span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Descrição --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
            <textarea name="descricao" rows="3"
                      class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-brand resize-none">{{ $compromisso?->descricao ?? '' }}</textarea>
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeModal()"
                    class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded border-0 hover:bg-gray-200 focus:outline-none">
                Cancelar
            </button>
            <button type="submit"
                    class="px-4 py-2 text-sm text-white bg-brand rounded border-0 hover:bg-brand/80 focus:outline-none">
                {{ $compromisso ? 'Salvar alterações' : 'Criar compromisso' }}
            </button>
        </div>
    </form>
</div>
