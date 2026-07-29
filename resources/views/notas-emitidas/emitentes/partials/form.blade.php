@php
    $isEditing = !is_null($emitente);
    $action = $isEditing ? route('notas-emitidas.emitentes.update', $emitente->id) : route('notas-emitidas.emitentes.save');
    $title = $isEditing ? 'Editar Cadastro' : 'Novo Cadastro';
    $modoInicial = $isEditing && $emitente->cliente_id ? 'cliente' : 'manual';
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

@if($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded text-sm">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ $action }}" id="form-emitente" data-editing="{{ $isEditing ? '1' : '0' }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif
    <input type="hidden" name="modo" id="modo-input" value="{{ $modoInicial }}">

    <div class="space-y-4">
        <div class="flex gap-4">
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="radio" name="modo_radio" value="cliente" id="modo-cliente" {{ $modoInicial === 'cliente' ? 'checked' : '' }} class="text-brand focus:ring-brand">
                Cliente{{ $isEditing ? '' : '(s)' }} já cadastrado{{ $isEditing ? '' : '(s)' }}
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input type="radio" name="modo_radio" value="manual" id="modo-manual" {{ $modoInicial === 'manual' ? 'checked' : '' }} class="text-brand focus:ring-brand">
                Não é cliente (cadastro manual)
            </label>
        </div>

        <div id="bloco-cliente" class="{{ $modoInicial === 'cliente' ? '' : 'hidden' }} relative">
            @if($isEditing)
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar cliente</label>
                <input type="text" id="busca-cliente" autocomplete="off"
                       placeholder="Digite o nome ou CPF/CNPJ..."
                       value="{{ $emitente->cliente ? $emitente->cliente->nome : '' }}"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                <input type="hidden" name="cliente_id" id="cliente_id" value="{{ $emitente->cliente_id }}">
                <ul id="lista-resultados-cliente" class="hidden absolute z-10 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded shadow max-h-56 overflow-y-auto"></ul>
            @else
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar clientes</label>
                <p class="text-xs text-gray-400 dark:text-slate-500 mb-1">Pesquise e clique para adicionar. Pode selecionar quantos precisar.</p>
                <input type="text" id="busca-cliente" autocomplete="off"
                       placeholder="Digite o nome ou CPF/CNPJ..."
                       class="block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                <ul id="lista-resultados-cliente" class="hidden absolute z-10 mt-1 w-full bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded shadow max-h-56 overflow-y-auto"></ul>
                <div id="chips-clientes" class="flex flex-wrap gap-2 mt-3"></div>
                <div id="hidden-clientes"></div>
            @endif
        </div>

        <div id="bloco-manual" class="{{ $modoInicial === 'manual' ? '' : 'hidden' }} space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-red-500">*</span></label>
                <input name="nome" type="text" id="input-nome"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('nome', $isEditing ? $emitente->nome : '') }}"
                       placeholder="Nome completo">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">CPF/CNPJ</label>
                <input name="cpfcnpj" type="text" id="input-cpfcnpj"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('cpfcnpj', $isEditing ? $emitente->cpfcnpj : '') }}"
                       placeholder="000.000.000-00">
            </div>
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

<script type="module">
(function () {
    const isEditing = document.getElementById('form-emitente').dataset.editing === '1';
    const modoCliente = document.getElementById('modo-cliente');
    const modoManual = document.getElementById('modo-manual');
    const modoInput = document.getElementById('modo-input');
    const blocoCliente = document.getElementById('bloco-cliente');
    const blocoManual = document.getElementById('bloco-manual');
    const nomeInput = document.getElementById('input-nome');
    const cpfcnpjInput = document.getElementById('input-cpfcnpj');
    const clienteIdInput = document.getElementById('cliente_id');
    const buscaInput = document.getElementById('busca-cliente');
    const lista = document.getElementById('lista-resultados-cliente');
    let timer = null;
    let selecionados = [];

    function atualizarModo() {
        const isCliente = modoCliente.checked;
        modoInput.value = isCliente ? 'cliente' : 'manual';
        blocoCliente.classList.toggle('hidden', !isCliente);
        blocoManual.classList.toggle('hidden', isCliente);
        if (isCliente) {
            nomeInput.value = '';
            cpfcnpjInput.value = '';
        } else if (clienteIdInput) {
            clienteIdInput.value = '';
        } else {
            selecionados = [];
            renderChips();
        }
    }

    modoCliente.addEventListener('change', atualizarModo);
    modoManual.addEventListener('change', atualizarModo);

    function renderChips() {
        const chipsContainer = document.getElementById('chips-clientes');
        const hiddenContainer = document.getElementById('hidden-clientes');
        chipsContainer.innerHTML = '';
        hiddenContainer.innerHTML = '';

        selecionados.forEach(function (c) {
            const chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1.5 pl-3 pr-2 py-1 rounded-full text-xs bg-brand/10 text-brand border border-brand/30';
            chip.innerHTML = '<span>' + c.nome + '</span>';
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'text-brand hover:text-brand/70 bg-transparent border-0 p-0 leading-none';
            removeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            removeBtn.addEventListener('click', function () {
                selecionados = selecionados.filter(s => s.id !== c.id);
                renderChips();
            });
            chip.appendChild(removeBtn);
            chipsContainer.appendChild(chip);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'cliente_ids[]';
            hidden.value = c.id;
            hiddenContainer.appendChild(hidden);
        });
    }

    buscaInput.addEventListener('input', function () {
        if (clienteIdInput) { clienteIdInput.value = ''; }
        const q = buscaInput.value.trim();
        clearTimeout(timer);

        if (q.length < 2) {
            lista.classList.add('hidden');
            lista.innerHTML = '';
            return;
        }

        timer = setTimeout(function () {
            fetch('{{ route('clientes.busca') }}?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(function (data) {
                    lista.innerHTML = '';
                    const disponiveis = isEditing ? data : data.filter(c => !selecionados.some(s => s.id === c.id));
                    if (!disponiveis.length) {
                        lista.classList.add('hidden');
                        return;
                    }
                    disponiveis.forEach(function (c) {
                        const li = document.createElement('li');
                        li.className = 'px-3 py-2 text-sm text-gray-800 dark:text-slate-100 hover:bg-gray-50 dark:hover:bg-slate-600 cursor-pointer';
                        li.innerHTML = '<span class="font-medium">' + c.nome + '</span>' +
                            (c.cpfcnpj ? ' <span class="text-xs text-gray-400 dark:text-slate-400">' + c.cpfcnpj + '</span>' : '');
                        li.addEventListener('click', function () {
                            if (isEditing) {
                                clienteIdInput.value = c.id;
                                buscaInput.value = c.nome;
                            } else {
                                selecionados.push({ id: c.id, nome: c.nome });
                                renderChips();
                                buscaInput.value = '';
                                buscaInput.focus();
                            }
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
        if (!lista.contains(e.target) && e.target !== buscaInput) {
            lista.classList.add('hidden');
        }
    });

    function pareceCpfCnpj(valor) {
        const digitos = valor.replace(/\D/g, '');
        if (!digitos) { return false; }
        return digitos.length === valor.trim().length && (digitos.length === 11 || digitos.length === 14);
    }

    function pareceNome(valor) {
        return /[a-zA-ZÀ-ÿ]/.test(valor);
    }

    document.getElementById('form-emitente').addEventListener('submit', function (e) {
        if (!modoCliente.checked) {
            const nome = nomeInput.value.trim();
            const cpfcnpj = cpfcnpjInput.value.trim();

            if (pareceCpfCnpj(nome)) {
                e.preventDefault();
                Swal.fire({
                    title: 'Campos trocados?',
                    text: 'O campo "Nome" parece conter um CPF/CNPJ. Verifique se os campos "Nome" e "CPF/CNPJ" não estão invertidos.',
                    icon: 'warning',
                    confirmButtonColor: '#6b7280',
                    confirmButtonText: 'Entendi',
                });
                return;
            }

            if (cpfcnpj && pareceNome(cpfcnpj)) {
                e.preventDefault();
                Swal.fire({
                    title: 'Campos trocados?',
                    text: 'O campo "CPF/CNPJ" parece conter um nome. Verifique se os campos "Nome" e "CPF/CNPJ" não estão invertidos.',
                    icon: 'warning',
                    confirmButtonColor: '#6b7280',
                    confirmButtonText: 'Entendi',
                });
                return;
            }

            return;
        }

        if (isEditing && !clienteIdInput.value) {
            e.preventDefault();
            Swal.fire({
                title: 'Selecione um cliente',
                text: 'Busque e selecione um cliente da lista antes de salvar.',
                icon: 'warning',
                confirmButtonColor: '#6b7280',
                confirmButtonText: 'Entendi',
            });
        } else if (!isEditing && selecionados.length === 0) {
            e.preventDefault();
            Swal.fire({
                title: 'Selecione ao menos um cliente',
                text: 'Busque e adicione um ou mais clientes antes de salvar.',
                icon: 'warning',
                confirmButtonColor: '#6b7280',
                confirmButtonText: 'Entendi',
            });
        }
    });
})();
</script>
