@php
    $isEditing = !is_null($lead);
    $action = $isEditing ? route('leads.update', $lead->id) : route('leads.save');
    $title = $isEditing ? 'Editar Lead' : 'Novo Lead';
    $etapaDefault = $etapaDefault ?? null;
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        @if($isEditing)
            <i class="fa-solid fa-pen-to-square mr-2"></i>
        @else
            <i class="fa-solid fa-filter-circle-plus mr-2"></i>
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
        {{-- Nome --}}
        <div>
            <label class="block text-sm font-medium text-gray-700">Nome <span class="text-red-500">*</span></label>
            <input name="nome" type="text"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('nome', $isEditing ? $lead->nome : '') }}"
                   required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">E-mail</label>
                <input name="email" type="email"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('email', $isEditing ? $lead->email : '') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Telefone</label>
                <input name="telefone" type="text"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('telefone', $isEditing ? $lead->telefone : '') }}">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Empresa</label>
            <input name="empresa" type="text"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('empresa', $isEditing ? $lead->empresa : '') }}">
        </div>

        @php
            $tipoInicialLead = old('tipo', $isEditing ? (string) $lead->tipo : '1');
            $isPJLead = $tipoInicialLead === '1';
        @endphp
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                <select name="tipo" id="lead-select-tipo" class="mt-1 block w-full border rounded px-3 py-2">
                    <option value="1" {{ $tipoInicialLead === '1' ? 'selected' : '' }}>Pessoa Jurídica (CNPJ)</option>
                    <option value="0" {{ $tipoInicialLead === '0' ? 'selected' : '' }}>Pessoa Física (CPF)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700" id="lead-label-cpfcnpj">{{ $isPJLead ? 'CNPJ' : 'CPF' }}</label>
                <input name="cpfcnpj" id="lead-input-cpfcnpj" type="text"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       placeholder="{{ $isPJLead ? '00.000.000/0000-00' : '000.000.000-00' }}"
                       maxlength="{{ $isPJLead ? 18 : 14 }}"
                       value="{{ old('cpfcnpj', $isEditing ? $lead->cpfcnpj : '') }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Etapa <span class="text-red-500">*</span></label>
                <select name="etapa_funil_id" class="mt-1 block w-full border rounded px-3 py-2" required>
                    @foreach($etapas as $etapa)
                        <option value="{{ $etapa->id }}"
                            {{ old('etapa_funil_id', $isEditing ? $lead->etapa_funil_id : $etapaDefault) == $etapa->id ? 'selected' : '' }}>
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
                            {{ old('responsavel_id', $isEditing ? $lead->responsavel_id : '') == $usuario->id ? 'selected' : '' }}>
                            {{ $usuario->nome }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Faturamento (R$)</label>
                <input name="faturamento" type="number" step="0.01" min="0"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('faturamento', $isEditing ? $lead->faturamento : '') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Honorário (R$)</label>
                <input name="honorario" type="number" step="0.01" min="0"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('honorario', $isEditing ? $lead->honorario : '') }}">
            </div>
        </div>

        @if(isset($produtos) && $produtos->isNotEmpty())
            @php
                $produtosSelecionados = old('produtos', $isEditing ? $lead->produtos->pluck('id')->toArray() : []);
            @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Serviços / Produtos</label>
                <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border rounded p-3">
                    @foreach($produtos as $produto)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" name="produtos[]" value="{{ $produto->id }}"
                                   class="rounded border-gray-300"
                                   {{ in_array($produto->id, $produtosSelecionados) ? 'checked' : '' }}>
                            {{ $produto->nome }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700">Possibilidade</label>
            <textarea name="possibilidade" rows="2"
                      class="mt-1 block w-full border rounded px-3 py-2"
                      placeholder="O que você poderia oferecer a este cliente?">{{ old('possibilidade', $isEditing ? $lead->possibilidade : '') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Observações</label>
            <textarea name="observacoes" rows="3"
                      class="mt-1 block w-full border rounded px-3 py-2"
                      placeholder="Notas internas sobre o lead...">{{ old('observacoes', $isEditing ? $lead->observacoes : '') }}</textarea>
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
