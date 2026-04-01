@php
    $isEditing = !is_null($cliente);
    $action = $isEditing ? route('clientes.update', $cliente->id) : route('clientes.save');
    $title = $isEditing ? 'Editar Cliente' : 'Novo Cliente';
    $tipoInicial = old('tipo', $isEditing ? (string) $cliente->tipo : '1');
    $isPJ = $tipoInicial === '1';
@endphp

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

<form method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Nome</label>
            <input name="nome" type="text"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('nome', $isEditing ? $cliente->nome : '') }}"
                   required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                <select name="tipo" id="select-tipo" class="mt-1 block w-full border rounded px-3 py-2">
                    <option value="1" {{ old('tipo', $isEditing ? (string) $cliente->tipo : '1') === '1' ? 'selected' : '' }}>Pessoa Jurídica (CNPJ)</option>
                    <option value="0" {{ old('tipo', $isEditing ? (string) $cliente->tipo : '1') === '0' ? 'selected' : '' }}>Pessoa Física (CPF)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" id="label-cpfcnpj">{{ $isPJ ? 'CNPJ' : 'CPF' }}</label>
                <input name="cpfcnpj" id="input-cpfcnpj" type="text"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       placeholder="{{ $isPJ ? '00.000.000/0000-00' : '000.000.000-00' }}"
                       maxlength="{{ $isPJ ? 18 : 14 }}"
                       value="{{ old('cpfcnpj', $isEditing ? $cliente->cpfcnpj : '') }}">
            </div>
        </div>

        <div id="regime-tributario-wrapper" class="grid grid-cols-2 gap-4{{ $isPJ ? '' : ' hidden' }}">
            <div>
                <label class="block text-sm font-medium text-gray-700">Regime Tributário</label>
                <select name="regime_tributario" class="mt-1 block w-full border rounded px-3 py-2">
                    <option value="">— Selecione —</option>
                    @foreach(['Simples Nacional' => 'Simples Nacional', 'Lucro Presumido' => 'Lucro Presumido', 'Lucro Real' => 'Lucro Real', 'MEI' => 'MEI'] as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('regime_tributario', $isEditing ? $cliente->regime_tributario : '') === $value ? 'selected' : '' }}>
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
                       value="{{ old('cidade', $isEditing ? $cliente->cidade : '') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Estado (UF)</label>
                <input name="estado" type="text"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       maxlength="2"
                       placeholder="SP"
                       value="{{ old('estado', $isEditing ? $cliente->estado : '') }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Data de Abertura</label>
                <input name="dataabertura" type="date"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('dataabertura', $isEditing ? $cliente->dataabertura : '') }}" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Cliente Desde</label>
                <input name="cliente_desde" type="date"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('cliente_desde', $isEditing ? $cliente->cliente_desde : now()->toDateString()) }}">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Descrição</label>
            <textarea name="descricao" rows="3"
                      class="mt-1 block w-full border rounded px-3 py-2"
                      placeholder="Observações sobre o cliente...">{{ old('descricao', $isEditing ? $cliente->descricao : '') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select name="status" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="ativo" {{ old('status', $isEditing ? $cliente->status : 'ativo') === 'ativo' ? 'selected' : '' }}>Ativo</option>
                <option value="inativo" {{ old('status', $isEditing ? $cliente->status : 'ativo') === 'inativo' ? 'selected' : '' }}>Inativo</option>
            </select>
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

    function updateField() {
        const tipo = selectTipo.value;

        if (tipo === '1') {
            labelCpfCnpj.textContent = 'CNPJ';
            inputCpfCnpj.placeholder = '00.000.000/0000-00';
            inputCpfCnpj.maxLength = 18;
            regimeWrapper.classList.remove('hidden');
        } else {
            labelCpfCnpj.textContent = 'CPF';
            inputCpfCnpj.placeholder = '000.000.000-00';
            inputCpfCnpj.maxLength = 14;
            regimeWrapper.classList.add('hidden');
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
