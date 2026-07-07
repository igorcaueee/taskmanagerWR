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
        @if($isEditing)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fa-solid fa-tag mr-1 text-brand"></i> Tipo de Tarefa
            </label>
            <select name="tipo_tarefa_id" id="tipo_tarefa_id"
                    class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                <option value="">— Sem tipo —</option>
                @foreach($tiposTarefa as $tipo)
                    <option value="{{ $tipo->id }}"
                            data-data-vencimento="{{ $tipo->data_vencimento ? $tipo->data_vencimento->format('Y-m-d') : '' }}"
                            data-titulo-padrao="{{ $tipo->titulo_padrao ?? '' }}"
                            {{ old('tipo_tarefa_id', $tarefa->tipo_tarefa_id) == $tipo->id ? 'selected' : '' }}>
                        {{ $tipo->nome }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Ao selecionar um tipo com data padrão, a data de vencimento será preenchida automaticamente.
            </p>
        </div>
        @else
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fa-solid fa-tag mr-1 text-brand"></i> Tipo de Tarefa
                <span class="text-gray-400 font-normal">(pode selecionar mais de um)</span>
            </label>
            <div class="relative mt-1" id="tipo-multi-wrapper">
                <button type="button" id="tipo-multi-trigger"
                    class="w-full flex items-center justify-between border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-left text-sm focus:outline-none focus:ring-2 focus:ring-brand/50"
                    onclick="toggleTipoMultiDropdown()">
                    <span id="tipo-multi-display" class="text-gray-400 truncate">— Sem tipo —</span>
                    <i class="fa-solid fa-chevron-down text-gray-400 text-xs ml-2 flex-shrink-0"></i>
                </button>

                <div id="tipo-multi-dropdown"
                    class="absolute z-50 mt-1 w-full bg-white dark:bg-slate-700 border dark:border-slate-600 rounded shadow-lg hidden"
                    style="max-height: 280px;">
                    <div class="p-2 border-b dark:border-slate-600">
                        <input type="text" id="tipo-multi-search"
                            placeholder="Buscar tipo..."
                            class="w-full px-3 py-1.5 text-sm border dark:border-slate-600 rounded bg-white dark:bg-slate-600 text-gray-900 dark:text-slate-200 focus:outline-none"
                            oninput="filtrarTiposMulti(this.value)">
                    </div>
                    <ul id="tipo-multi-list" class="overflow-y-auto" style="max-height: 220px;">
                        @foreach($tiposTarefa as $tipo)
                            <li data-label="{{ $tipo->nome }}"
                                data-data-vencimento="{{ $tipo->data_vencimento ? $tipo->data_vencimento->format('Y-m-d') : '' }}"
                                data-titulo-padrao="{{ $tipo->titulo_padrao ?? '' }}"
                                class="tipo-multi-option flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-slate-200 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-600"
                                onclick="toggleTipoCheck({{ $tipo->id }}, this)">
                                <input type="checkbox" name="tipo_tarefa_ids[]" value="{{ $tipo->id }}"
                                    id="chk_tipo_{{ $tipo->id }}"
                                    class="rounded border-gray-300 text-brand focus:ring-brand"
                                    onclick="event.stopPropagation()">
                                <label for="chk_tipo_{{ $tipo->id }}" class="cursor-pointer flex-1" onclick="event.stopPropagation(); toggleTipoCheck({{ $tipo->id }}, this.closest('li'))">
                                    {{ $tipo->nome }}
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Ao selecionar um tipo, a data e título serão preenchidos automaticamente. Com múltiplos tipos, será criada uma tarefa por tipo.
            </p>
        </div>
        @endif

        <div id="titulo-descricao-wrapper">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título <span class="text-red-500">*</span></label>
                <input name="titulo" id="input-titulo" type="text"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('titulo', $isEditing ? $tarefa->titulo : '') }}"
                       {{ $isEditing ? 'required' : '' }}>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
                <textarea name="descricao" rows="3"
                          class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">{{ old('descricao', $isEditing ? $tarefa->descricao : '') }}</textarea>
            </div>
        </div>

        @if(!$isEditing)
        <div id="titulo-descricao-tipo-hint" class="hidden p-3 rounded-lg border border-brand/30 bg-brand/5 dark:bg-brand/10">
            <p class="text-sm text-brand dark:text-brand font-medium">
                <i class="fa-solid fa-tag mr-1"></i> Título e descrição serão preenchidos automaticamente pelo tipo de cada tarefa.
            </p>
        </div>
        @endif

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
                $podeTransferirNoDepartamento = $podeTransferirNoDepartamento ?? false;
                $podeAlterarResponsavel = $podeMudarResponsavel || $podeTransferirNoDepartamento;
                $listaResponsaveis = ($isEditing && $podeTransferirNoDepartamento) ? $responsaveisDepartamento : $usuarios;
            @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Responsável @if(!$isEditing)<span class="text-red-500">*</span>@endif
                </label>
                <select name="responsavel_id" class="mt-1 block w-full border rounded px-3 py-2 {{ $isEditing && !$podeAlterarResponsavel ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isEditing && !$podeAlterarResponsavel ? 'disabled' : '' }} {{ !$isEditing ? 'required' : '' }}>
                    <option value="">— Selecione o responsável —</option>
                    @foreach($listaResponsaveis as $usuarioOpt)
                        <option value="{{ $usuarioOpt->id }}"
                            {{ old('responsavel_id', $isEditing ? $tarefa->responsavel_id : '') == $usuarioOpt->id ? 'selected' : '' }}>
                            {{ $usuarioOpt->nome }}
                        </option>
                    @endforeach
                </select>
                @if ($isEditing && !$podeAlterarResponsavel)
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Apenas o supervisor da tarefa pode alterar o responsável.</p>
                @elseif ($isEditing && $podeTransferirNoDepartamento)
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Você pode transferir para colaboradores do seu departamento.</p>
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Supervisor @if(!$isEditing)<span class="text-red-500">*</span>@endif
            </label>
            <select name="supervisor_id" class="mt-1 block w-full border rounded px-3 py-2 {{ $isEditing && !$podeMudarResponsavel ? 'bg-gray-100 cursor-not-allowed' : '' }}" {{ $isEditing && !$podeMudarResponsavel ? 'disabled' : '' }} {{ !$isEditing ? 'required' : '' }}>
                <option value="">— Selecione o supervisor —</option>
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
            <div id="data-vencimento-wrapper">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Data de Vencimento
                    @if($isEditing)<span class="text-red-500">*</span>@else<span id="data-venc-asterisk" class="text-red-500">*</span>@endif
                </label>
                <input name="data_vencimento" id="input-data-vencimento" type="date"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('data_vencimento', $isEditing ? $tarefa->data_vencimento->format('Y-m-d') : '') }}"
                       {{ $isEditing ? 'required' : '' }}>
                @if(!$isEditing)
                <p id="data-venc-tipo-hint" class="text-xs text-blue-500 dark:text-blue-400 mt-1 hidden">
                    <i class="fa-solid fa-circle-info mr-1"></i>
                    Cada tarefa usará a data de vencimento do seu tipo.
                </p>
                @endif
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

        @if(!$isEditing)
        <div id="primeira-execucao-wrapper" class="hidden">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Primeira execução
            </label>
            <select name="primeira_execucao" id="input-primeira-execucao" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                <option value="este_mes" {{ old('primeira_execucao', 'este_mes') === 'este_mes' ? 'selected' : '' }}>
                    Este mês
                </option>
                <option value="proximo_mes" {{ old('primeira_execucao') === 'proximo_mes' ? 'selected' : '' }}>
                    Mês que vem
                </option>
            </select>
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                Use "Mês que vem" quando o cliente estiver sendo cadastrado agora, mas a primeira cobrança/tarefa só deve ocorrer no próximo ciclo.
            </p>
        </div>
        @endif
    </div>

    {{-- Envio de arquivo ao finalizar --}}
    <div class="flex items-start gap-3 p-3 mt-4 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30">
        <input type="checkbox" name="requer_envio_arquivo" id="requer_envio_arquivo" value="1"
               class="mt-0.5 rounded border-gray-300 text-brand focus:ring-brand"
               {{ old('requer_envio_arquivo', $isEditing ? $tarefa->requer_envio_arquivo : false) ? 'checked' : '' }}>
        <label for="requer_envio_arquivo" class="text-sm text-blue-800 dark:text-blue-300 cursor-pointer">
            <span class="font-medium"><i class="fa-solid fa-file-arrow-up mr-1"></i>Esta tarefa necessita de envio de arquivo</span>
            <span class="block text-xs text-blue-600 dark:text-blue-400 mt-0.5">Ao finalizar a tarefa, será solicitado o upload de um arquivo para o portal do cliente.</span>
        </label>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent dark:bg-transparent">
            Cancelar
        </button>
        <button type="submit" id="formTarefaSubmitBtn" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
            Salvar
        </button>
    </div>
</form>

<script>
(function () {
    const container = document.getElementById('modalContent');
    const form = container ? container.querySelector('form') : null;
    if (!form) { return; }
    const markDirty = function () { window._modalHasChanges = true; };
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);

    @if(!$isEditing)
    const selectFrequencia = form.querySelector('[name="frequencia"]');
    const primeiraExecucaoWrapper = document.getElementById('primeira-execucao-wrapper');
    if (selectFrequencia && primeiraExecucaoWrapper) {
        const togglePrimeiraExecucao = function () {
            primeiraExecucaoWrapper.classList.toggle('hidden', selectFrequencia.value === 'nenhuma');
        };
        togglePrimeiraExecucao();
        selectFrequencia.addEventListener('change', togglePrimeiraExecucao);
    }
    @endif

    @if(!$isEditing)
    let _confirmedSubmit = false;

    form.addEventListener('submit', async function (e) {
        if (_confirmedSubmit) {
            _confirmedSubmit = false;
            window._modalHasChanges = false;
            const btn = document.getElementById('formTarefaSubmitBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Salvando...';
                btn.classList.add('opacity-60', 'cursor-not-allowed');
            }
            return;
        }

        e.preventDefault();

        const responsavelId  = form.querySelector('[name="responsavel_id"]')?.value ?? '';
        const titulo         = form.querySelector('[name="titulo"]')?.value ?? '';
        const clienteIds     = Array.from(form.querySelectorAll('[name="cliente_ids[]"]:checked')).map(c => c.value);
        const tipoIds        = Array.from(form.querySelectorAll('[name="tipo_tarefa_ids[]"]:checked')).map(t => t.value);

        if (!responsavelId) {
            _confirmedSubmit = true;
            form.submit();
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const body = new URLSearchParams();
            body.append('_token', csrfToken);
            body.append('responsavel_id', responsavelId);
            body.append('titulo', titulo);
            clienteIds.forEach(id => body.append('cliente_ids[]', id));
            tipoIds.forEach(id => body.append('tipo_tarefa_ids[]', id));

            const res  = await fetch('{{ route("tarefas.check-duplicata") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrfToken },
                body: body.toString(),
            });
            const data = await res.json();

            if (data.duplicatas && data.duplicatas.length > 0) {
                const linhas = data.duplicatas.map(d =>
                    `<tr>
                        <td class="py-1 pr-3 text-sm font-medium text-gray-800">${d.titulo}</td>
                        <td class="py-1 pr-3 text-sm text-gray-600">${d.cliente}</td>
                        <td class="py-1 pr-3 text-sm text-gray-600">${d.data_vencimento}</td>
                        <td class="py-1 text-sm"><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">${d.etapa}</span></td>
                    </tr>`
                ).join('');

                const result = await Swal.fire({
                    icon: 'warning',
                    title: 'Tarefa já existe!',
                    html: `<p class="text-sm text-gray-600 mb-3">Já existe${data.duplicatas.length > 1 ? 'm' : ''} <strong>${data.duplicatas.length}</strong> tarefa${data.duplicatas.length > 1 ? 's' : ''} ativa${data.duplicatas.length > 1 ? 's' : ''} com o mesmo título, responsável e empresa:</p>
                           <div class="overflow-x-auto">
                             <table class="w-full text-left">
                               <thead><tr class="text-xs text-gray-400 uppercase border-b">
                                 <th class="pb-1 pr-3">Título</th><th class="pb-1 pr-3">Empresa</th><th class="pb-1 pr-3">Vencimento</th><th class="pb-1">Etapa</th>
                               </tr></thead>
                               <tbody>${linhas}</tbody>
                             </table>
                           </div>`,
                    showCancelButton: true,
                    confirmButtonText: 'Criar mesmo assim',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#f59e0b',
                    cancelButtonColor: '#6b7280',
                    width: 600,
                });

                if (!result.isConfirmed) { return; }
            }
        } catch (_) {
            // Se a verificação falhar, permite o submit normalmente
        }

        _confirmedSubmit = true;
        form.submit();
    });
    @else
    form.addEventListener('submit', function () {
        window._modalHasChanges = false;
        const btn = document.getElementById('formTarefaSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Salvando...';
            btn.classList.add('opacity-60', 'cursor-not-allowed');
        }
    });
    @endif
})();
</script>

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

// --- Auto-fill título e data de vencimento pelo tipo de tarefa ---
(function () {
    const inputData = document.querySelector('[name="data_vencimento"]');
    const inputTitulo = document.querySelector('[name="titulo"]');

    // Modo edição: single select
    const selectTipo = document.getElementById('tipo_tarefa_id');
    if (selectTipo) {
        selectTipo.addEventListener('change', function () {
            const selected = selectTipo.options[selectTipo.selectedIndex];
            const dataVenc = selected.dataset.dataVencimento;
            const tituloPadrao = selected.dataset.tituloPadrao;
            if (dataVenc && inputData) inputData.value = dataVenc;
            if (tituloPadrao && inputTitulo && !inputTitulo.value.trim()) inputTitulo.value = tituloPadrao;
        });
        return;
    }

    // Modo criação: multi-select — auto-fill somente quando exatamente 1 tipo estiver selecionado
    document.addEventListener('change', function (e) {
        if (!e.target.name || e.target.name !== 'tipo_tarefa_ids[]') return;
        const checados = Array.from(document.querySelectorAll('#tipo-multi-list input[type="checkbox"]:checked'));
        if (checados.length !== 1) return;
        const li = checados[0].closest('li');
        if (!li) return;
        const dataVenc = li.dataset.dataVencimento;
        const tituloPadrao = li.dataset.tituloPadrao;
        if (dataVenc && inputData) inputData.value = dataVenc;
        if (tituloPadrao && inputTitulo && !inputTitulo.value.trim()) inputTitulo.value = tituloPadrao;
    });
}());

// --- Multi-select tipo de tarefa (create mode) ---
function toggleTipoMultiDropdown() {
    const dropdown = document.getElementById('tipo-multi-dropdown');
    if (!dropdown) return;
    const isHidden = dropdown.classList.toggle('hidden');
    if (!isHidden) {
        const search = document.getElementById('tipo-multi-search');
        search.value = '';
        filtrarTiposMulti('');
        search.focus();
    }
}

function filtrarTiposMulti(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('#tipo-multi-list .tipo-multi-option').forEach(function (li) {
        const label = li.dataset.label.toLowerCase();
        li.style.display = (!q || label.includes(q)) ? '' : 'none';
    });
}

function toggleTipoCheck(id, li) {
    const chk = document.getElementById('chk_tipo_' + id);
    if (!chk) return;
    chk.checked = !chk.checked;
    atualizarDisplayTipoMulti();
}

function atualizarDisplayTipoMulti() {
    const checked = Array.from(document.querySelectorAll('#tipo-multi-list input[type="checkbox"]:checked'));
    const display = document.getElementById('tipo-multi-display');
    if (!display) return;
    if (checked.length === 0) {
        display.textContent = '— Sem tipo —';
        display.className = 'text-gray-400 truncate';
    } else {
        const nomes = checked.map(function (c) { return c.closest('li').dataset.label; });
        display.textContent = nomes.join(', ');
        display.className = 'text-gray-900 dark:text-slate-200 truncate';
    }
    atualizarVisibilidadeCamposTipo(checked);
}

function atualizarVisibilidadeCamposTipo(checked) {
    // --- Título e Descrição ---
    const titDescWrapper = document.getElementById('titulo-descricao-wrapper');
    const titDescHint = document.getElementById('titulo-descricao-tipo-hint');
    const inputTitulo = document.getElementById('input-titulo');

    if (titDescWrapper) {
        if (checked.length > 0) {
            titDescWrapper.style.display = 'none';
            if (titDescHint) titDescHint.classList.remove('hidden');
            if (inputTitulo) inputTitulo.removeAttribute('required');
        } else {
            titDescWrapper.style.display = '';
            if (titDescHint) titDescHint.classList.add('hidden');
            if (inputTitulo) inputTitulo.setAttribute('required', '');
        }
    }

    // --- Data de Vencimento ---
    const dataWrapper = document.getElementById('data-vencimento-wrapper');
    const dataInput = document.getElementById('input-data-vencimento');
    const hint = document.getElementById('data-venc-tipo-hint');
    if (!dataWrapper || !dataInput) return;

    const algumSemData = checked.some(function (c) { return !c.closest('li').dataset.dataVencimento; });

    if (checked.length > 0 && !algumSemData) {
        dataWrapper.querySelector('input').style.display = 'none';
        dataWrapper.querySelector('label').style.display = 'none';
        if (hint) hint.classList.remove('hidden');
        dataInput.removeAttribute('required');
    } else {
        dataWrapper.querySelector('input').style.display = '';
        dataWrapper.querySelector('label').style.display = '';
        if (hint) hint.classList.add('hidden');
        if (checked.length === 0) dataInput.setAttribute('required', '');
    }
}

document.addEventListener('change', function (e) {
    if (e.target.name === 'tipo_tarefa_ids[]') {
        atualizarDisplayTipoMulti();
    }
});

document.addEventListener('click', function (e) {
    const wrapper = document.getElementById('tipo-multi-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        const dd = document.getElementById('tipo-multi-dropdown');
        if (dd) dd.classList.add('hidden');
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
                    <p class="text-xs text-gray-700 dark:text-gray-300">
                        @if($reg->etapaAnterior)
                            <span class="font-medium">{{ $reg->etapaAnterior->nome }}</span>
                            <i class="fa-solid fa-arrow-right mx-1 text-gray-400"></i>
                        @else
                            <span class="text-gray-400 italic">Criada em </span>
                        @endif
                        <span class="font-medium text-brand">{{ $reg->etapaNova->nome ?? '—' }}</span>
                    </p>
                    @if($reg->observacao)
                        <p class="text-xs text-red-600 dark:text-red-400 mt-0.5 italic">
                            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ $reg->observacao }}
                        </p>
                    @endif
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
