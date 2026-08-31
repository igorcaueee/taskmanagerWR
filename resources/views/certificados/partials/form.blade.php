@php
    $isEditing = ! is_null($emissao);
    $action = $isEditing ? route('certificados.update', $emissao->id) : route('certificados.store');
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        <i class="fa-solid {{ $isEditing ? 'fa-pen-to-square' : 'fa-plus' }} mr-2"></i>
        {{ $isEditing ? 'Editar Emissão' : 'Nova Emissão de Certificado' }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

@if($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded text-sm">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ $action }}" id="form-cert">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="space-y-4">
        {{-- Cliente --}}
        <div class="relative">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente <span class="text-red-500">*</span></label>
            <p class="text-xs text-gray-400 dark:text-slate-500 mb-1">Busque um cliente WR ou apenas digite o nome se não for cliente.</p>
            <input type="text" id="busca-cliente" autocomplete="off"
                   value="{{ old('cliente_nome', $isEditing ? $emissao->cliente_nome : '') }}"
                   placeholder="Digite o nome ou CPF/CNPJ..."
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
            <input type="hidden" name="cliente_nome" id="cliente_nome" value="{{ old('cliente_nome', $isEditing ? $emissao->cliente_nome : '') }}">
            <input type="hidden" name="cliente_id" id="cliente_id" value="{{ old('cliente_id', $isEditing ? $emissao->cliente_id : '') }}">
            <ul id="lista-clientes" class="hidden absolute z-10 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded shadow max-h-56 overflow-y-auto"></ul>
            <p id="tag-wr" class="mt-1 text-xs {{ ($isEditing && $emissao->cliente_id) ? '' : 'hidden' }} text-green-600 dark:text-green-400">
                <i class="fa-solid fa-check"></i> Vinculado ao cadastro (Cliente WR)
            </p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de emissão <span class="text-red-500">*</span></label>
                <input type="date" name="data_emissao" required
                       value="{{ old('data_emissao', $isEditing ? $emissao->data_emissao?->format('Y-m-d') : now()->format('Y-m-d')) }}"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vencimento</label>
                <input type="date" name="vencimento"
                       value="{{ old('vencimento', $isEditing ? $emissao->vencimento?->format('Y-m-d') : '') }}"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Modelo <span class="text-red-500">*</span></label>
                <select name="modelo" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                    @foreach($modelos as $v => $label)
                        <option value="{{ $v }}" @selected(old('modelo', $isEditing ? $emissao->modelo : 'ECNPJ') === $v)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de emissão <span class="text-red-500">*</span></label>
                <select name="forma_emissao" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                    @foreach($formas as $v => $label)
                        <option value="{{ $v }}" @selected(old('forma_emissao', $isEditing ? $emissao->forma_emissao : 'PRESENCIAL') === $v)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nº do pedido</label>
                <input type="text" name="numero_pedido"
                       value="{{ old('numero_pedido', $isEditing ? $emissao->numero_pedido : '') }}"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Documento (CPF/CNPJ)</label>
                <input type="text" name="cliente_documento" id="cliente_documento"
                       value="{{ old('cliente_documento', $isEditing ? $emissao->cliente_documento : '') }}"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor (R$)</label>
                <input type="number" step="0.01" min="0" name="valor"
                       value="{{ old('valor', $isEditing ? $emissao->valor : '') }}"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pagamento</label>
                <input type="text" name="pagamento" list="lista-pagamento"
                       value="{{ old('pagamento', $isEditing ? $emissao->pagamento : '') }}"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                <datalist id="lista-pagamento">
                    <option value="PIX"></option>
                    <option value="DINHEIRO"></option>
                    <option value="CARTÃO"></option>
                    <option value="BOLETO"></option>
                    <option value="BONIFICADO"></option>
                </datalist>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Situação <span class="text-red-500">*</span></label>
                <select name="situacao" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                    @foreach($situacoes as $s)
                        <option value="{{ $s }}" @selected(old('situacao', $isEditing ? $emissao->situacao : 'OK') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Certificadora <span class="text-red-500">*</span></label>
            <input type="text" name="certificadora"
                   value="{{ old('certificadora', $isEditing ? $emissao->certificadora : 'SOLUCAOID') }}"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observação</label>
            <textarea name="observacao" rows="2"
                      class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand resize-none">{{ old('observacao', $isEditing ? $emissao->observacao : '') }}</textarea>
        </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()"
                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 rounded hover:bg-gray-200 dark:hover:bg-slate-600 border-0">
            Cancelar
        </button>
        <button type="submit" class="px-4 py-2 text-sm text-white bg-brand rounded hover:bg-brand/80 border-0">
            <i class="fa-solid fa-floppy-disk mr-1"></i> Salvar
        </button>
    </div>
</form>

<script type="module">
(function () {
    const busca = document.getElementById('busca-cliente');
    const nomeHidden = document.getElementById('cliente_nome');
    const idHidden = document.getElementById('cliente_id');
    const docInput = document.getElementById('cliente_documento');
    const lista = document.getElementById('lista-clientes');
    const tagWr = document.getElementById('tag-wr');
    let timer = null;

    function setNome() {
        nomeHidden.value = busca.value.trim();
    }

    busca.addEventListener('input', function () {
        setNome();
        idHidden.value = '';
        tagWr.classList.add('hidden');
        const q = busca.value.trim();
        clearTimeout(timer);
        if (q.length < 2) { lista.classList.add('hidden'); lista.innerHTML = ''; return; }
        timer = setTimeout(function () {
            fetch('{{ route('clientes.busca') }}?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(function (data) {
                    lista.innerHTML = '';
                    if (!data.length) { lista.classList.add('hidden'); return; }
                    data.forEach(function (c) {
                        const li = document.createElement('li');
                        li.className = 'px-3 py-2 text-sm text-gray-800 dark:text-slate-100 hover:bg-gray-50 dark:hover:bg-slate-600 cursor-pointer';
                        li.innerHTML = '<span class="font-medium">' + c.nome + '</span>' + (c.cpfcnpj ? ' <span class="text-xs text-gray-400 dark:text-slate-400">' + c.cpfcnpj + '</span>' : '');
                        li.addEventListener('click', function () {
                            busca.value = c.nome;
                            nomeHidden.value = c.nome;
                            idHidden.value = c.id;
                            if (c.cpfcnpj && !docInput.value) { docInput.value = c.cpfcnpj; }
                            tagWr.classList.remove('hidden');
                            lista.classList.add('hidden');
                        });
                        lista.appendChild(li);
                    });
                    lista.classList.remove('hidden');
                })
                .catch(function () { lista.classList.add('hidden'); });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!lista.contains(e.target) && e.target !== busca) { lista.classList.add('hidden'); }
    });

    document.getElementById('form-cert').addEventListener('submit', setNome);

    const container = document.getElementById('modalContent');
    const form = container ? container.querySelector('form') : null;
    if (form) {
        const markDirty = function () { window._modalHasChanges = true; };
        form.addEventListener('input', markDirty);
        form.addEventListener('change', markDirty);
        form.addEventListener('submit', function () { window._modalHasChanges = false; });
    }
})();
</script>
