@php
    $isEditing = !is_null($tarefa);
    $action = $isEditing ? route('tarefas.update', $tarefa->id) : route('tarefas.save');
    $title = $isEditing ? 'Editar Tarefa' : 'Nova Tarefa';
    $etapaDefault = $etapaDefault ?? null;
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        @if($isEditing)
            <i class="fa-solid fa-pen-to-square mr-2"></i>
        @else
            <i class="fa-solid fa-plus mr-2"></i>
        @endif
        {{ $title }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-transparent border-0 p-0">
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
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
            <input name="titulo" type="text"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('titulo', $isEditing ? $tarefa->titulo : '') }}"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
            <textarea name="descricao" rows="3"
                      class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">{{ old('descricao', $isEditing ? $tarefa->descricao : '') }}</textarea>
        </div>

        @php
            $selectedClienteId = old('cliente_id', $isEditing ? $tarefa->cliente_id : '');
            $selectedClienteNome = $isEditing && $tarefa->cliente ? $tarefa->cliente->nome : '';
        @endphp

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente</label>
            <div class="relative mt-1" id="cliente-dropdown-wrapper">
                {{-- Hidden select for form submission --}}
                <select name="cliente_id" id="cliente_id_hidden" class="hidden" required>
                    <option value="">— Selecione —</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}"
                            {{ $selectedClienteId == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nome }}
                        </option>
                    @endforeach
                </select>

                {{-- Visible trigger --}}
                <button type="button" id="cliente-trigger"
                    class="w-full flex items-center justify-between border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-left text-sm focus:outline-none focus:ring-2 focus:ring-brand/50"
                    onclick="toggleClienteDropdown()">
                    <span id="cliente-display-text" class="{{ $selectedClienteId ? 'text-gray-900' : 'text-gray-400' }}">
                        {{ $selectedClienteId ? $selectedClienteNome : '— Selecione —' }}
                    </span>
                    <i class="fa-solid fa-chevron-down text-gray-400 text-xs ml-2"></i>
                </button>

                {{-- Dropdown --}}
                <div id="cliente-dropdown"
                    class="absolute z-50 mt-1 w-full bg-white dark:bg-slate-700 border dark:border-slate-600 rounded shadow-lg hidden"
                    style="max-height: 260px;">
                    <div class="p-2 border-b">
                        <input type="text" id="cliente-search"
                            placeholder="Buscar cliente..."
                            class="w-full px-3 py-1.5 text-sm border dark:border-slate-600 rounded bg-white dark:bg-slate-600 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-brand/50"
                            oninput="filtrarClientes(this.value)">
                    </div>
                    <ul id="cliente-list" class="overflow-y-auto" style="max-height: 200px;">
                        <li data-value="" data-label="— Selecione —"
                            class="cliente-option px-3 py-2 text-sm text-gray-400 dark:text-slate-500 cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-600"
                            onclick="selecionarCliente('', '— Selecione —')">
                            — Selecione —
                        </li>
                        @foreach($clientes as $cliente)
                            <li data-value="{{ $cliente->id }}" data-label="{{ $cliente->nome }}"
                                class="cliente-option px-3 py-2 text-sm text-gray-700 dark:text-slate-200 cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-600 {{ $selectedClienteId == $cliente->id ? 'bg-brand/10 font-medium' : '' }}"
                                onclick="selecionarCliente('{{ $cliente->id }}', '{{ addslashes($cliente->nome) }}')">
                                {{ $cliente->nome }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="hidden">
            <p id="display-departamento">{{ $isEditing ? ($tarefa->departamento?->nome ?? '—') : '—' }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Etapa</label>
                <select name="etapa_id" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200" required>
                    <option value="">— Selecione —</option>
                    @foreach($etapas as $etapa)
                        <option value="{{ $etapa->id }}"
                            {{ old('etapa_id', $isEditing ? $tarefa->etapa_id : $etapaDefault) == $etapa->id ? 'selected' : '' }}>
                            {{ $etapa->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            @php
                $podeMudarResponsavel = $podeMudarResponsavel ?? true;
            @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Responsável</label>
                <select name="responsavel_id" class="mt-1 block w-full border rounded px-3 py-2 {{ $isEditing && !$podeMudarResponsavel ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isEditing && !$podeMudarResponsavel ? 'disabled' : '' }}>
                    <option value="">— Sem responsável —</option>
                    @foreach($usuarios as $usuario)
                        <option value="{{ $usuario->id }}"
                            {{ old('responsavel_id', $isEditing ? $tarefa->responsavel_id : '') == $usuario->id ? 'selected' : '' }}>
                            {{ $usuario->nome }}
                        </option>
                    @endforeach
                </select>
                @if ($isEditing && !$podeMudarResponsavel)
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Apenas o supervisor da tarefa pode alterar o responsável.</p>
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Supervisor</label>
            <select name="supervisor_id" class="mt-1 block w-full border rounded px-3 py-2 {{ $isEditing && !$podeMudarResponsavel ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isEditing && !$podeMudarResponsavel ? 'disabled' : '' }}>
                <option value="">— Sem supervisor —</option>
                @foreach($usuarios as $usuario)
                    <option value="{{ $usuario->id }}"
                        {{ old('supervisor_id', $isEditing ? $tarefa->supervisor_id : '') == $usuario->id ? 'selected' : '' }}>
                        {{ $usuario->nome }}
                    </option>
                @endforeach
            </select>
            @if ($isEditing && !$podeMudarResponsavel)
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Apenas o supervisor da tarefa pode alterar este campo.</p>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Vencimento</label>
                <input name="data_vencimento" type="date"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('data_vencimento', $isEditing ? $tarefa->data_vencimento->format('Y-m-d') : '') }}"
                       required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Prioridade</label>
                <select name="prioridade" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200" required>
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
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fa-solid fa-rotate mr-1"></i> Recorrência
            </label>
            <select name="frequencia" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
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
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent dark:bg-transparent">
            Cancelar
        </button>
        <button type="submit" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
            Salvar
        </button>
    </div>
</form>

<script>
// --- Searchable cliente dropdown ---
function toggleClienteDropdown() {
    const dropdown = document.getElementById('cliente-dropdown');
    const search = document.getElementById('cliente-search');
    const isHidden = dropdown.classList.toggle('hidden');
    if (!isHidden) {
        search.value = '';
        filtrarClientes('');
        search.focus();
    }
}

function filtrarClientes(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('#cliente-list .cliente-option').forEach(function (li) {
        const label = li.dataset.label.toLowerCase();
        li.style.display = (!q || label.includes(q)) ? '' : 'none';
    });
}

function selecionarCliente(value, label) {
    const hidden = document.getElementById('cliente_id_hidden');
    const displayText = document.getElementById('cliente-display-text');
    const dropdown = document.getElementById('cliente-dropdown');

    hidden.value = value;
    displayText.textContent = label;
    displayText.className = value ? 'text-gray-900' : 'text-gray-400';
    dropdown.classList.add('hidden');

    // Highlight selected item
    document.querySelectorAll('#cliente-list .cliente-option').forEach(function (li) {
        li.classList.toggle('bg-brand/10', li.dataset.value === value);
        li.classList.toggle('font-medium', li.dataset.value === value);
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function (e) {
    const wrapper = document.getElementById('cliente-dropdown-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('cliente-dropdown').classList.add('hidden');
    }
});

// --- Departamento por responsável ---
(function () {
    const depMap = @json($usuariosDepartamentos ?? []);
    const selectResponsavel = document.querySelector('[name="responsavel_id"]');
    const displayDep = document.getElementById('display-departamento');

    function atualizarDepartamento() {
        const dep = depMap[selectResponsavel.value];
        displayDep.textContent = dep?.nome ?? '—';
    }

    if (selectResponsavel) {
        selectResponsavel.addEventListener('change', atualizarDepartamento);
        atualizarDepartamento();
    }
})();
</script>

@if($isEditing && $tarefa->historico->isNotEmpty())
    <div class="mt-6 pt-5 border-t border-gray-200 dark:border-slate-700">
        <h6 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
            <i class="fa-solid fa-clock-rotate-left mr-1"></i> Histórico de Etapas
        </h6>
        <ol reversed class="relative border-l border-gray-200 dark:border-slate-700 ml-2 space-y-3">
            @foreach($tarefa->historico->sortByDesc('created_at') as $reg)
                <li class="ml-4">
                    <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-gray-300 dark:bg-slate-600 border border-white dark:border-slate-800"></span>
                    <p class="text-xs text-gray-700 dark:text-gray-300">
                        @if($reg->etapaAnterior)
                            <span class="font-medium">{{ $reg->etapaAnterior->nome }}</span>
                            <i class="fa-solid fa-arrow-right mx-1 text-gray-400"></i>
                        @else
                            <span class="text-gray-400 italic">Criada em </span>
                        @endif
                        <span class="font-medium text-brand">{{ $reg->etapaNova->nome ?? '—' }}</span>
                    </p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
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
