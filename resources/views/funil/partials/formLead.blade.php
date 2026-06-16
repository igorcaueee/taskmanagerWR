@php
    $isEditing = !is_null($lead);
    $action = $isEditing ? route('leads.update', $lead->id) : route('leads.save');
    $title = $isEditing ? 'Editar Lead' : 'Novo Lead';
    $etapaDefault = $etapaDefault ?? null;
    $prefill = $prefill ?? [];
    $respostaId = $respostaId ?? null;
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        @if($isEditing)
            <i class="fa-solid fa-pen-to-square mr-2"></i>
        @else
            <i class="fa-solid fa-filter-circle-plus mr-2"></i>
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
    @if($respostaId)
        <input type="hidden" name="resposta_id" value="{{ $respostaId }}">
    @endif

    <div class="space-y-4">
        {{-- Nome --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-red-500">*</span></label>
            <input name="nome" type="text"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('nome', $isEditing ? $lead->nome : ($prefill['nome'] ?? '')) }}"
                   required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-mail</label>
                <input name="email" type="email"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('email', $isEditing ? $lead->email : ($prefill['email'] ?? '')) }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Telefone <span class="text-red-500">*</span></label>
                <input name="telefone" type="text"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('telefone', $isEditing ? $lead->telefone : '') }}"
                       required>
            </div>
        </div>

        <div class="relative" id="empresa-wrapper">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Empresa</label>
            <input name="empresa" id="empresa-input" type="text" autocomplete="off"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand"
                   value="{{ old('empresa', $isEditing ? $lead->empresa : ($prefill['empresa'] ?? '')) }}"
                   placeholder="Digite para buscar ou criar nova...">
            <ul id="empresa-dropdown"
                class="hidden absolute z-50 w-full mt-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded shadow-lg max-h-48 overflow-y-auto">
            </ul>
        </div>

        @php
            $tipoInicialLead = old('tipo', $isEditing ? (string) $lead->tipo : '1');
            $isPJLead = $tipoInicialLead === '1';
        @endphp
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <select name="tipo" id="lead-select-tipo" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                    <option value="1" {{ $tipoInicialLead === '1' ? 'selected' : '' }}>Pessoa Jurídica (CNPJ)</option>
                    <option value="0" {{ $tipoInicialLead === '0' ? 'selected' : '' }}>Pessoa Física (CPF)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" id="lead-label-cpfcnpj">{{ $isPJLead ? 'CNPJ' : 'CPF' }}</label>
                <input name="cpfcnpj" id="lead-input-cpfcnpj" type="text"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       placeholder="{{ $isPJLead ? '00.000.000/0000-00' : '000.000.000-00' }}"
                       maxlength="{{ $isPJLead ? 18 : 14 }}"
                       value="{{ old('cpfcnpj', $isEditing ? $lead->cpfcnpj : '') }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Etapa <span class="text-red-500">*</span></label>
                <select name="etapa_funil_id" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200" required>
                    @foreach($etapas as $etapa)
                        <option value="{{ $etapa->id }}"
                            {{ old('etapa_funil_id', $isEditing ? $lead->etapa_funil_id : $etapaDefault) == $etapa->id ? 'selected' : '' }}>
                            {{ $etapa->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Responsável</label>
                <select name="responsavel_id" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                    <option value="">— Sem responsável —</option>
                    @foreach($usuarios as $usuario)
                        <option value="{{ $usuario->id }}"
                            {{ old('responsavel_id', $isEditing ? $lead->responsavel_id : '') == $usuario->id ? 'selected' : '' }}>
                            {{ $usuario->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if (auth()->user()?->canVerFaturamento() || auth()->user()?->canVerHonorario())
        <div class="grid grid-cols-2 gap-4">
            @if (auth()->user()?->canVerFaturamento())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Faturamento (R$)</label>
                <input name="faturamento" type="number" step="0.01" min="0"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('faturamento', $isEditing ? $lead->faturamento : ($prefill['faturamento'] ?? '')) }}">
            </div>
            @endif
            @if (auth()->user()?->canVerHonorario())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Honorário (R$)</label>
                <input name="honorario" type="number" step="0.01" min="0"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('honorario', $isEditing ? $lead->honorario : '') }}">
            </div>
            @endif
        </div>
        @endif

        @if(isset($produtos) && $produtos->isNotEmpty())
            @php
                $produtosSelecionados = old('produtos', $isEditing ? $lead->produtos->pluck('id')->toArray() : []);
            @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Serviços / Produtos</label>
                <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border dark:border-slate-600 rounded p-3 bg-white dark:bg-slate-700">
                    @foreach($produtos as $produto)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" name="produtos[]" value="{{ $produto->id }}"
                                   class="rounded border-gray-300"
                                   {{ in_array($produto->id, $produtosSelecionados) ? 'checked' : '' }}>
                            {{ $produto->nome }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        @if(isset($possibilidades) && $possibilidades->isNotEmpty())
            @php
                $possibilidadesSelecionadas = old('possibilidades', $isEditing ? $lead->possibilidades->pluck('id')->toArray() : []);
            @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Possibilidades</label>
                <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border dark:border-slate-600 rounded p-3 bg-white dark:bg-slate-700">
                    @foreach($possibilidades as $possibilidade)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" name="possibilidades[]" value="{{ $possibilidade->id }}"
                                   class="rounded border-gray-300"
                                   {{ in_array($possibilidade->id, $possibilidadesSelecionadas) ? 'checked' : '' }}>
                            {{ $possibilidade->nome }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
            <textarea name="observacoes" rows="3"
                      class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                      placeholder="Notas internas sobre o lead...">{{ old('observacoes', $isEditing ? $lead->observacoes : ($prefill['observacoes'] ?? '')) }}</textarea>
        </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent dark:bg-transparent">
            Cancelar
        </button>
        <button type="submit" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
            Salvar
        </button>
    </div>
</form>

@if($isEditing && $lead->historico->isNotEmpty())
    <div class="mt-6 pt-5 border-t border-gray-200">
        <h6 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
            <i class="fa-solid fa-clock-rotate-left mr-1"></i> Histórico de Etapas
        </h6>
        <ol reversed class="relative border-l border-gray-200 ml-2 space-y-3">
            @foreach($lead->historico->sortByDesc('created_at') as $reg)
                <li class="ml-4">
                    <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full border border-white"
                          style="background-color: {{ $reg->etapaNova?->cor ?? '#9ca3af' }}"></span>
                    <p class="text-xs text-gray-700">
                        @if($reg->etapaAnterior)
                            <span class="font-medium">{{ $reg->etapaAnterior->nome }}</span>
                            <i class="fa-solid fa-arrow-right mx-1 text-gray-400 text-[0.6rem]"></i>
                        @endif
                        <span class="font-medium" style="color: {{ $reg->etapaNova?->cor ?? '#374151' }}">
                            {{ $reg->etapaNova?->nome }}
                        </span>
                    </p>
                    @if($reg->descricao)
                        <p class="text-xs text-gray-500 mt-0.5 italic">"{{ $reg->descricao }}"</p>
                    @endif
                    <p class="text-[0.65rem] text-gray-400 mt-0.5">
                        {{ $reg->created_at->format('d/m/Y H:i') }}
                        @if($reg->alteradoPor)
                            · {{ $reg->alteradoPor->nome }}
                        @endif
                    </p>
                </li>
            @endforeach
        </ol>
    </div>
@endif

<script>
(function () {
    const input    = document.getElementById('empresa-input');
    const dropdown = document.getElementById('empresa-dropdown');
    if (!input || !dropdown) { return; }

    let debounceTimer = null;
    let activeIndex   = -1;

    function fillClienteData(item) {
        if (item.source !== 'cliente') { return; }

        const selectTipo   = document.getElementById('lead-select-tipo');
        const inputCpfCnpj = document.getElementById('lead-input-cpfcnpj');
        const inputFat     = document.querySelector('input[name="faturamento"]');
        const inputHon     = document.querySelector('input[name="honorario"]');

        if (selectTipo && item.tipo !== null && item.tipo !== undefined) {
            selectTipo.value = String(item.tipo);
            selectTipo.dispatchEvent(new Event('change'));
        }

        if (inputCpfCnpj && item.cpfcnpj) {
            inputCpfCnpj.value = item.cpfcnpj;
        }

        if (inputFat && item.faturamento !== '' && item.faturamento !== null && item.faturamento !== undefined) {
            inputFat.value = item.faturamento;
        }

        if (inputHon && item.honorario !== '' && item.honorario !== null && item.honorario !== undefined) {
            inputHon.value = item.honorario;
        }
    }

    function showDropdown(items) {
        dropdown.innerHTML = '';
        activeIndex = -1;

        if (items.length === 0) {
            dropdown.classList.add('hidden');
            return;
        }

        items.forEach(function (item) {
            const nome = item.nome;
            const li = document.createElement('li');
            li.className = 'px-3 py-2 text-sm text-gray-800 dark:text-slate-200 cursor-pointer hover:bg-brand/10 dark:hover:bg-slate-600 flex items-center justify-between gap-2';

            const span = document.createElement('span');
            span.textContent = nome;
            li.appendChild(span);

            if (item.source === 'cliente') {
                const badge = document.createElement('span');
                badge.textContent = 'cliente';
                badge.className = 'text-[0.65rem] px-1.5 py-0.5 rounded bg-brand/10 text-brand dark:bg-brand/20 dark:text-brand-light flex-shrink-0';
                li.appendChild(badge);
            }

            li.addEventListener('mousedown', function (e) {
                e.preventDefault();
                input.value = nome;
                dropdown.classList.add('hidden');
                fillClienteData(item);
            });
            dropdown.appendChild(li);
        });

        dropdown.classList.remove('hidden');
    }

    function fetchEmpresas(q) {
        if (q.length < 1) { dropdown.classList.add('hidden'); return; }

        fetch('/leads/empresas?q=' + encodeURIComponent(q), {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { showDropdown(data); })
        .catch(function () { dropdown.classList.add('hidden'); });
    }

    input.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { fetchEmpresas(input.value.trim()); }, 220);
    });

    input.addEventListener('keydown', function (e) {
        const items = dropdown.querySelectorAll('li');
        if (!items.length || dropdown.classList.contains('hidden')) { return; }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0) {
                e.preventDefault();
                items[activeIndex].dispatchEvent(new MouseEvent('mousedown'));
                dropdown.classList.add('hidden');
            }
            return;
        } else if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
            return;
        }

        items.forEach(function (li, i) {
            li.classList.toggle('bg-brand/10', i === activeIndex);
            li.classList.toggle('dark:bg-slate-600', i === activeIndex);
        });
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('empresa-wrapper')?.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
})();
</script>

<script>
(function () {
    const selectTipo = document.getElementById('lead-select-tipo');
    const inputCpfCnpj = document.getElementById('lead-input-cpfcnpj');
    const labelCpfCnpj = document.getElementById('lead-label-cpfcnpj');

    if (!selectTipo) { return; }

    function applyMask(value, tipo) {
        const digits = value.replace(/\D/g, '');

        if (tipo === '1') {
            return digits
                .slice(0, 14)
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            return digits
                .slice(0, 11)
                .replace(/^(\d{3})(\d)/, '$1.$2')
                .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1-$2');
        }
    }

    function updateField() {
        const tipo = selectTipo.value;

        if (tipo === '1') {
            labelCpfCnpj.textContent = 'CNPJ';
            inputCpfCnpj.placeholder = '00.000.000/0000-00';
            inputCpfCnpj.maxLength = 18;
        } else {
            labelCpfCnpj.textContent = 'CPF';
            inputCpfCnpj.placeholder = '000.000.000-00';
            inputCpfCnpj.maxLength = 14;
        }

        inputCpfCnpj.value = applyMask(inputCpfCnpj.value, tipo);
    }

    selectTipo.addEventListener('change', updateField);

    inputCpfCnpj.addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = applyMask(this.value, selectTipo.value);
        this.setSelectionRange(pos, pos);
    });

    updateField();
})();
</script>

<script>
(function () {
    const container = document.getElementById('modalContent');
    const form = container ? container.querySelector('form') : null;
    if (!form) { return; }
    const markDirty = function () { window._modalHasChanges = true; };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    form.addEventListener('submit', function () { window._modalHasChanges = false; });
})();
</script>
