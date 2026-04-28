@php
    $isEditing = !is_null($cliente);
    $prefill = $prefill ?? [];
    $action = $overrideAction ?? ($isEditing ? route('clientes.update', $cliente->id) : route('clientes.save'));
    $title = $formTitle ?? ($isEditing ? 'Editar Cliente' : 'Novo Cliente');
    $tipoInicial = old('tipo', $isEditing ? (string) $cliente->tipo : ($prefill['tipo'] ?? '1'));
    $isPJ = $tipoInicial === '1';
@endphp

@unless(isset($hideShell) && $hideShell)
<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        @if($isEditing)
            <i class="fa-solid fa-building-circle-arrow-right mr-2"></i>
        @else
            <i class="fa-solid fa-building-circle-check mr-2"></i>
        @endif
        {{ $title }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>
@endunless

<form @if(isset($formId)) id="{{ $formId }}" @endif method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Nome</label>
            <input name="nome" type="text"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('nome', $isEditing ? $cliente->nome : ($prefill['nome'] ?? '')) }}"
                   required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                <select name="tipo" id="select-tipo" class="mt-1 block w-full border rounded px-3 py-2">
                    <option value="1" {{ $tipoInicial === '1' ? 'selected' : '' }}>Pessoa Jurídica (CNPJ)</option>
                    <option value="0" {{ $tipoInicial === '0' ? 'selected' : '' }}>Pessoa Física (CPF)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" id="label-cpfcnpj">{{ $isPJ ? 'CNPJ' : 'CPF' }}</label>
                <input name="cpfcnpj" id="input-cpfcnpj" type="text"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       placeholder="{{ $isPJ ? '00.000.000/0000-00' : '000.000.000-00' }}"
                       maxlength="{{ $isPJ ? 18 : 14 }}"
                       required
                       value="{{ old('cpfcnpj', $isEditing ? $cliente->cpfcnpj : ($prefill['cpfcnpj'] ?? '')) }}">
            </div>
        </div>

        <div id="regime-tributario-wrapper" class="grid grid-cols-2 gap-4{{ $isPJ ? '' : ' hidden' }}">
            <div>
                <label class="block text-sm font-medium text-gray-700">Regime Tributário</label>
                <select name="regime_tributario" id="select-regime" class="mt-1 block w-full border rounded px-3 py-2"
                        {{ $isPJ ? 'required' : '' }}>
                    <option value="">— Selecione —</option>
                    @foreach(['Simples Nacional' => 'Simples Nacional', 'Lucro Presumido' => 'Lucro Presumido', 'Lucro Real' => 'Lucro Real', 'MEI' => 'MEI'] as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('regime_tributario', $isEditing ? $cliente->regime_tributario : ($prefill['regime_tributario'] ?? '')) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Cidade</label>
                <input name="cidade" type="text"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('cidade', $isEditing ? $cliente->cidade : ($prefill['cidade'] ?? '')) }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Estado (UF)</label>
                <input name="estado" type="text"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       maxlength="2"
                       placeholder="SP"
                       value="{{ old('estado', $isEditing ? $cliente->estado : ($prefill['estado'] ?? '')) }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Data de Abertura</label>
                <input name="dataabertura" type="date"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('dataabertura', $isEditing ? $cliente->dataabertura : ($prefill['dataabertura'] ?? '')) }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Cliente Desde</label>
                <input name="cliente_desde" type="date"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('cliente_desde', $isEditing ? $cliente->cliente_desde : ($prefill['cliente_desde'] ?? now()->toDateString())) }}">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Descrição</label>
            <textarea name="descricao" rows="3"
                      class="mt-1 block w-full border rounded px-3 py-2"
                      placeholder="Observações sobre o cliente...">{{ old('descricao', $isEditing ? $cliente->descricao : ($prefill['descricao'] ?? '')) }}</textarea>
        </div>

        {{-- CRM Fields --}}
        <div class="border-t border-gray-100 pt-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Informações Comerciais</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Faturamento (R$)</label>
                    <input name="faturamento" type="number" step="0.01" min="0"
                           class="mt-1 block w-full border rounded px-3 py-2"
                           required
                           value="{{ old('faturamento', $isEditing ? $cliente->faturamento : ($prefill['faturamento'] ?? '')) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Honorário (R$)</label>
                    <input name="honorario" type="number" step="0.01" min="0"
                           class="mt-1 block w-full border rounded px-3 py-2"
                           required
                           value="{{ old('honorario', $isEditing ? $cliente->honorario : ($prefill['honorario'] ?? '')) }}">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Possibilidade</label>
                <textarea name="possibilidade" rows="2"
                          class="mt-1 block w-full border rounded px-3 py-2"
                          placeholder="O que você poderia oferecer a este cliente?">{{ old('possibilidade', $isEditing ? $cliente->possibilidade : ($prefill['possibilidade'] ?? '')) }}</textarea>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select name="status" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="ativo" {{ old('status', $isEditing ? $cliente->status : ($prefill['status'] ?? 'ativo')) === 'ativo' ? 'selected' : '' }}>Ativo</option>
                <option value="inativo" {{ old('status', $isEditing ? $cliente->status : ($prefill['status'] ?? 'ativo')) === 'inativo' ? 'selected' : '' }}>Inativo</option>
            </select>
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input name="fator_r" type="checkbox" class="rounded border-gray-300"
                       {{ old('fator_r', $isEditing ? $cliente->fator_r : ($prefill['fator_r'] ?? false)) ? 'checked' : '' }}>
                Fator R
            </label>
        </div>

        @if(isset($produtos) && $produtos->isNotEmpty())
            @php
                $produtosSelecionados = old('produtos', $isEditing ? $cliente->produtos->pluck('id')->toArray() : ($prefill['produtos'] ?? []));
            @endphp
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Produtos / Serviços</label>
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
                <p class="text-xs text-gray-400 mt-1">Selecione os produtos/serviços que este cliente contrata ou pode contratar.</p>
            </div>
        @endif
    </div>

    @unless(isset($hideShell) && $hideShell)
    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 bg-transparent">
            Cancelar
        </button>
        <button type="submit" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
            Salvar
        </button>
    </div>
    @endunless
</form>

<script>
(function () {
    const selectTipo = document.getElementById('select-tipo');
    const inputCpfCnpj = document.getElementById('input-cpfcnpj');
    const labelCpfCnpj = document.getElementById('label-cpfcnpj');

    function applyMask(value, tipo) {
        const digits = value.replace(/\D/g, '');

        if (tipo === '1') {
            // CNPJ: 00.000.000/0000-00
            return digits
                .slice(0, 14)
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            // CPF: 000.000.000-00
            return digits
                .slice(0, 11)
                .replace(/^(\d{3})(\d)/, '$1.$2')
                .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1-$2');
        }
    }

    const regimeWrapper = document.getElementById('regime-tributario-wrapper');
    const selectRegime  = document.getElementById('select-regime');

    function updateField() {
        const tipo = selectTipo.value;

        if (tipo === '1') {
            labelCpfCnpj.textContent = 'CNPJ';
            inputCpfCnpj.placeholder = '00.000.000/0000-00';
            inputCpfCnpj.maxLength = 18;
            regimeWrapper.classList.remove('hidden');
            if (selectRegime) { selectRegime.required = true; }
        } else {
            labelCpfCnpj.textContent = 'CPF';
            inputCpfCnpj.placeholder = '000.000.000-00';
            inputCpfCnpj.maxLength = 14;
            regimeWrapper.classList.add('hidden');
            if (selectRegime) { selectRegime.required = false; }
        }

        inputCpfCnpj.value = applyMask(inputCpfCnpj.value, tipo);
    }

    selectTipo.addEventListener('change', updateField);

    inputCpfCnpj.addEventListener('input', function () {
        const pos = this.selectionStart;
        this.value = applyMask(this.value, selectTipo.value);
        this.setSelectionRange(pos, pos);
    });

    // Initialize on load
    updateField();
})();
</script>
