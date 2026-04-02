@php
    $isEditing = !is_null($tarefa);
    $action = $isEditing ? route('tarefas.update', $tarefa->id) : route('tarefas.save');
    $title = $isEditing ? 'Editar Tarefa' : 'Nova Tarefa';
    $etapaDefault = $etapaDefault ?? null;
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        @if($isEditing)
            <i class="fa-solid fa-pen-to-square mr-2"></i>
        @else
            <i class="fa-solid fa-plus mr-2"></i>
        @endif
        {{ $title }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<form method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Título</label>
            <input name="titulo" type="text"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('titulo', $isEditing ? $tarefa->titulo : '') }}"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Descrição</label>
            <textarea name="descricao" rows="3"
                      class="mt-1 block w-full border rounded px-3 py-2">{{ old('descricao', $isEditing ? $tarefa->descricao : '') }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Cliente</label>
                <select name="cliente_id" class="mt-1 block w-full border rounded px-3 py-2" required>
                    <option value="">— Selecione —</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}"
                            {{ old('cliente_id', $isEditing ? $tarefa->cliente_id : '') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Departamento</label>
                <select name="departamento_id" class="mt-1 block w-full border rounded px-3 py-2" required>
                    <option value="">— Selecione —</option>
                    @foreach($departamentos as $dep)
                        <option value="{{ $dep->id }}"
                            {{ old('departamento_id', $isEditing ? $tarefa->departamento_id : '') == $dep->id ? 'selected' : '' }}>
                            {{ $dep->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Etapa</label>
                <select name="etapa_id" class="mt-1 block w-full border rounded px-3 py-2" required>
                    <option value="">— Selecione —</option>
                    @foreach($etapas as $etapa)
                        <option value="{{ $etapa->id }}"
                            {{ old('etapa_id', $isEditing ? $tarefa->etapa_id : $etapaDefault) == $etapa->id ? 'selected' : '' }}>
                            {{ $etapa->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Responsável</label>
                <select name="responsavel_id" class="mt-1 block w-full border rounded px-3 py-2">
                    <option value="">— Sem responsável —</option>
                    @foreach($usuarios as $usuario)
                        <option value="{{ $usuario->id }}"
                            {{ old('responsavel_id', $isEditing ? $tarefa->responsavel_id : '') == $usuario->id ? 'selected' : '' }}>
                            {{ $usuario->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Supervisor</label>
            <select name="supervisor_id" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="">— Sem supervisor —</option>
                @foreach($usuarios as $usuario)
                    <option value="{{ $usuario->id }}"
                        {{ old('supervisor_id', $isEditing ? $tarefa->supervisor_id : '') == $usuario->id ? 'selected' : '' }}>
                        {{ $usuario->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Data de Vencimento</label>
                <input name="data_vencimento" type="date"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('data_vencimento', $isEditing ? $tarefa->data_vencimento->format('Y-m-d') : '') }}"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Prioridade</label>
                <select name="prioridade" class="mt-1 block w-full border rounded px-3 py-2" required>
                    @foreach([1 => 'Baixa', 2 => 'Normal', 3 => 'Alta', 4 => 'Urgente', 5 => 'Crítica'] as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('prioridade', $isEditing ? $tarefa->prioridade : 1) == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                <i class="fa-solid fa-rotate mr-1"></i> Recorrência
            </label>
            <select name="frequencia" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="nenhuma" {{ old('frequencia', $isEditing ? $tarefa->frequencia : 'nenhuma') === 'nenhuma' ? 'selected' : '' }}>
                    Não se repete
                </option>
                <option value="semanal" {{ old('frequencia', $isEditing ? $tarefa->frequencia : 'nenhuma') === 'semanal' ? 'selected' : '' }}>
                    Semanal (toda semana)
                </option>
                <option value="mensal" {{ old('frequencia', $isEditing ? $tarefa->frequencia : 'nenhuma') === 'mensal' ? 'selected' : '' }}>
                    Mensal (todo mês)
                </option>
                <option value="trimestral" {{ old('frequencia', $isEditing ? $tarefa->frequencia : 'nenhuma') === 'trimestral' ? 'selected' : '' }}>
                    Trimestral (a cada 3 meses)
                </option>
                <option value="semestral" {{ old('frequencia', $isEditing ? $tarefa->frequencia : 'nenhuma') === 'semestral' ? 'selected' : '' }}>
                    Semestral (a cada 6 meses)
                </option>
                <option value="anual" {{ old('frequencia', $isEditing ? $tarefa->frequencia : 'nenhuma') === 'anual' ? 'selected' : '' }}>
                    Anual (todo ano)
                </option>
            </select>
            @if($isEditing && $tarefa->recorrente && $tarefa->tarefa_original_id)
                <p class="text-xs text-blue-600 mt-1">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    Esta tarefa foi gerada automaticamente por recorrência.
                </p>
            @endif
        </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 bg-transparent">
            Cancelar
        </button>
        <button type="submit" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
            Salvar
        </button>
    </div>
</form>

@if($isEditing && $tarefa->historico->isNotEmpty())
    <div class="mt-6 pt-5 border-t border-gray-200">
        <h6 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
            <i class="fa-solid fa-clock-rotate-left mr-1"></i> Histórico de Etapas
        </h6>
        <ol reversed class="relative border-l border-gray-200 ml-2 space-y-3">
            @foreach($tarefa->historico->sortByDesc('created_at') as $reg)
                <li class="ml-4">
                    <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-gray-300 border border-white"></span>
                    <p class="text-xs text-gray-700">
                        @if($reg->etapaAnterior)
                            <span class="font-medium">{{ $reg->etapaAnterior->nome }}</span>
                            <i class="fa-solid fa-arrow-right mx-1 text-gray-400"></i>
                        @else
                            <span class="text-gray-400 italic">Criada em </span>
                        @endif
                        <span class="font-medium text-brand">{{ $reg->etapaNova->nome ?? '—' }}</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $reg->created_at->format('d/m/Y H:i') }}
                        @if($reg->alteradoPor)
                            &bull; {{ $reg->alteradoPor->nome }}
                        @endif
                    </p>
                </li>
            @endforeach
        </ol>
    </div>
@endif
