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
            $selectedClienteIds = $selectedClienteIds ?? old('cliente_ids', $isEditing ? $tarefa->clientes->pluck('id')->toArray() : []);
        @endphp

        {{-- Multi-select clientes (create & edit) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Clientes <span class="text-gray-400 font-normal">(pode selecionar mais de um)</span>
            </label>
            <div class="relative mt-1" id="cliente-multi-wrapper">
                <button type="button" id="cliente-multi-trigger"
                    class="w-full flex items-center justify-between border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-left text-sm focus:outline-none focus:ring-2 focus:ring-brand/50"
                    onclick="toggleClienteMultiDropdown()">
                    <span id="cliente-multi-display" class="text-gray-400 truncate">— Selecione os clientes —</span>
                    <i class="fa-solid fa-chevron-down text-gray-400 text-xs ml-2 flex-shrink-0"></i>
                </button>

                <div id="cliente-multi-dropdown"
                    class="absolute z-50 mt-1 w-full bg-white dark:bg-slate-700 border dark:border-slate-600 rounded shadow-lg hidden"
                    style="max-height: 280px;">
                    <div class="p-2 border-b dark:border-slate-600">
                        <input type="text" id="cliente-multi-search"
                            placeholder="Buscar cliente..."
                            class="w-full px-3 py-1.5 text-sm border dark:border-slate-600 rounded bg-white dark:bg-slate-600 text-gray-900 dark:text-slate-200 focus:outline-none"
                            oninput="filtrarClientesMulti(this.value)">
                    </div>
                    <ul id="cliente-multi-list" class="overflow-y-auto" style="max-height: 220px;">
                        @foreach($clientes as $cliente)
                            <li data-label="{{ $cliente->nome }}"
                                class="cliente-multi-option flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-slate-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-600"
                                onclick="toggleClienteCheck({{ $cliente->id }}, this)">
                                <input type="checkbox" name="cliente_ids[]" value="{{ $cliente->id }}"
                                    id="chk_cli_{{ $cliente->id }}"
                                    class="rounded border-gray-300 text-brand focus:ring-brand"
                                    {{ in_array($cliente->id, $selectedClienteIds) ? 'checked' : '' }}
                                    onclick="event.stopPropagation()">
                                <label for="chk_cli_{{ $cliente->id }}" class="cursor-pointer flex-1" onclick="event.stopPropagation(); toggleClienteCheck({{ $cliente->id }}, this.closest('li'))">
                                    {{ $cliente->nome }}
                                </label>
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
// --- Searchable cliente dropdown (edit mode) ---
function toggleClienteDropdown() {
    const dropdown = document.getElementById('cliente-dropdown');
    if (!dropdown) return;
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

    document.querySelectorAll('#cliente-list .cliente-option').forEach(function (li) {
        li.classList.toggle('bg-brand/10', li.dataset.value === value);
        li.classList.toggle('font-medium', li.dataset.value === value);
    });
}

// Close single dropdown when clicking outside
document.addEventListener('click', function (e) {
    const wrapper = document.getElementById('cliente-dropdown-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        const dd = document.getElementById('cliente-dropdown');
        if (dd) dd.classList.add('hidden');
    }
});

// --- Multi-select cliente dropdown (create mode) ---
function toggleClienteMultiDropdown() {
    const dropdown = document.getElementById('cliente-multi-dropdown');
    if (!dropdown) return;
    const isHidden = dropdown.classList.toggle('hidden');
    if (!isHidden) {
        const search = document.getElementById('cliente-multi-search');
        search.value = '';
        filtrarClientesMulti('');
        search.focus();
    }
}

function filtrarClientesMulti(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('#cliente-multi-list .cliente-multi-option').forEach(function (li) {
        const label = li.dataset.label.toLowerCase();
        li.style.display = (!q || label.includes(q)) ? '' : 'none';
    });
}

function toggleClienteCheck(id, li) {
    const chk = document.getElementById('chk_cli_' + id);
    if (!chk) return;
    chk.checked = !chk.checked;
    atualizarDisplayMulti();
}

function atualizarDisplayMulti() {
    const checked = Array.from(document.querySelectorAll('#cliente-multi-list input[type="checkbox"]:checked'));
    const display = document.getElementById('cliente-multi-display');
    if (!display) return;
    if (checked.length === 0) {
        display.textContent = '— Selecione os clientes —';
        display.className = 'text-gray-400 truncate';
    } else {
        const nomes = checked.map(function (c) {
            return c.closest('li').dataset.label;
        });
        display.textContent = nomes.join(', ');
        display.className = 'text-gray-900 dark:text-slate-200 truncate';
    }
}

// Re-sync display after checkbox change via direct click
document.addEventListener('change', function (e) {
    if (e.target.name === 'cliente_ids[]') {
        atualizarDisplayMulti();
    }
});

// Close multi dropdown when clicking outside
document.addEventListener('click', function (e) {
    const wrapper = document.getElementById('cliente-multi-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        const dd = document.getElementById('cliente-multi-dropdown');
        if (dd) dd.classList.add('hidden');
    }
});

// Init multi display on load
(function () {
    if (document.getElementById('cliente-multi-list')) {
        atualizarDisplayMulti();
    }
}());

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
}());
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
