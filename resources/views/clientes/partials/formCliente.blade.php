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
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        @if($isEditing)
            <i class="fa-solid fa-building-circle-arrow-right mr-2"></i>
        @else
            <i class="fa-solid fa-building-circle-check mr-2"></i>
        @endif
        {{ $title }}
    </h5>
    <div class="flex items-center gap-2">
        @if($isEditing)
            <button type="button"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs border border-gray-300 dark:border-slate-600 rounded text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent"
                    data-modal-url="{{ route('clientes.quadro.modal', $cliente->id) }}">
                <i class="fa-solid fa-scale-balanced"></i> Quadro Societário
                @if($cliente->socios->isNotEmpty())
                    <span class="inline-flex items-center justify-center w-4 h-4 text-xs rounded-full bg-brand text-white">{{ $cliente->socios->count() }}</span>
                @endif
            </button>
            @if(auth()->user()?->canEditarClientes() && $cliente->status === 'ativo')
            <button type="button"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs border border-red-200 rounded text-red-600 hover:bg-red-50 bg-transparent"
                    data-modal-url="{{ route('clientes.form.encerrar', $cliente->id) }}">
                <i class="fa-solid fa-building-circle-xmark"></i> Encerrar
            </button>
            @endif
            @if(auth()->user()?->canEditarClientes() && $cliente->status === 'inativo')
            <form method="POST" action="{{ route('clientes.reativar', $cliente->id) }}" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs border border-green-200 rounded text-green-600 hover:bg-green-50 bg-transparent">
                    <i class="fa-solid fa-building-circle-check"></i> Reativar
                </button>
            </form>
            @endif
        @endif
        <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0 ml-1">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
    </div>
</div>
@endunless

@if($isEditing && $cliente->status === 'inativo' && $cliente->motivo_encerramento)
<div class="mb-4 px-4 py-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400 flex items-start gap-2">
    <i class="fa-solid fa-building-circle-xmark mt-0.5 flex-shrink-0"></i>
    <span>
        <span class="font-semibold">Empresa encerrada</span> em {{ $cliente->data_encerramento?->format('d/m/Y') ?? '—' }}
        — <span class="italic">{{ Str::limit($cliente->motivo_encerramento, 100) }}</span>
    </span>
</div>
@endif

<form @if(isset($formId)) id="{{ $formId }}" @endif method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <input type="hidden" name="cnae_principal" id="input-cnae-principal"
           value="{{ old('cnae_principal', $isEditing ? $cliente->cnae_principal : ($prefill['cnae_principal'] ?? '')) }}">
    <input type="hidden" name="cnae_secundarios" id="input-cnae-secundarios"
           value="{{ old('cnae_secundarios', $isEditing ? json_encode($cliente->cnae_secundarios ?? []) : ($prefill['cnae_secundarios'] ?? '')) }}">

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo</label>
                <select name="tipo" id="select-tipo" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                    <option value="1" {{ $tipoInicial === '1' ? 'selected' : '' }}>Pessoa Jurídica (CNPJ)</option>
                    <option value="0" {{ $tipoInicial === '0' ? 'selected' : '' }}>Pessoa Física (CPF)</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" id="label-cpfcnpj">{{ $isPJ ? 'CNPJ' : 'CPF' }}</label>
                <input name="cpfcnpj" id="input-cpfcnpj" type="text"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       placeholder="{{ $isPJ ? '00.000.000/0000-00' : '000.000.000-00' }}"
                       maxlength="{{ $isPJ ? 18 : 14 }}"
                       required
                       value="{{ old('cpfcnpj', $isEditing ? $cliente->cpfcnpj : ($prefill['cpfcnpj'] ?? '')) }}">
                <p id="status-busca-cnpj" class="hidden mt-1 text-xs"></p>
                <p id="aviso-cpfcnpj-duplicado" class="hidden mt-1 text-xs text-amber-600 dark:text-amber-400">
                    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                    Já existe um cliente cadastrado com este documento: <a href="#" id="link-cliente-duplicado" class="underline font-medium"></a>
                </p>
            </div>
        </div>

        <div id="regime-tributario-wrapper" class="{{ $isPJ ? '' : 'hidden' }}">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Regime Tributário</label>
            <select name="regime_tributario" id="select-regime" class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                    {{ $isPJ ? 'required' : '' }}>
                <option value="">— Selecione —</option>
                @php $regimeAtual = mb_strtolower(old('regime_tributario', $isEditing ? $cliente->regime_tributario : ($prefill['regime_tributario'] ?? ''))); @endphp
                @foreach(['Simples Nacional' => 'SIMPLES NACIONAL', 'Lucro Presumido' => 'LUCRO PRESUMIDO', 'Lucro Real' => 'LUCRO REAL', 'MEI' => 'MEI', 'Associação' => 'ASSOCIACAO'] as $value => $label)
                    <option value="{{ $value }}"
                        {{ $regimeAtual === mb_strtolower($value) ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome</label>
            <input name="nome" type="text"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('nome', $isEditing ? $cliente->nome : ($prefill['nome'] ?? '')) }}"
                   required>
        </div>

        @if($isEditing)
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fa-solid fa-folder-open mr-1 text-amber-500"></i> Pasta de Arquivos no Servidor
            </label>
            <input name="pasta_arquivos" type="text"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   placeholder="Deixe vazio para usar o nome do cliente"
                   value="{{ old('pasta_arquivos', $cliente->pasta_arquivos ?? '') }}">
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Nome exato da pasta no servidor. Se vazio, usa o nome do cliente.</p>
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Área</label>
            <div class="mt-1 flex gap-2">
                <select name="segmentacao_id" id="select-segmentacao" class="block w-full border rounded px-3 py-2">
                    <option value="">— Selecione —</option>
                    @foreach($segmentacoes ?? [] as $segmentacao)
                        <option value="{{ $segmentacao->id }}"
                            {{ old('segmentacao_id', $isEditing ? $cliente->segmentacao_id : ($prefill['segmentacao_id'] ?? '')) == $segmentacao->id ? 'selected' : '' }}>
                            {{ $segmentacao->nome }}
                        </option>
                    @endforeach
                </select>
                @if(auth()->user()?->canEditarClientes())
                <button type="button" id="btn-nova-segmentacao"
                        title="Nova segmentação"
                        class="flex-shrink-0 inline-flex items-center justify-center self-stretch px-3 border border-gray-300 dark:border-slate-600 rounded text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 bg-white dark:bg-slate-800">
                    <i class="fa-solid fa-plus text-sm"></i>
                </button>
                @endif
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Atividade</label>
            <input name="atividade" type="text"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   placeholder="Ex: Comércio, Indústria, Serviços..."
                   value="{{ old('atividade', $isEditing ? $cliente->atividade : ($prefill['atividade'] ?? '')) }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fa-solid fa-shield-halved mr-1 text-amber-500"></i> Vencimento do Certificado
            </label>
            <input name="vencimento_certificado" type="date"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('vencimento_certificado', $isEditing ? $cliente->vencimento_certificado?->format('Y-m-d') : ($prefill['vencimento_certificado'] ?? '')) }}">
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Tarefa criada 30 dias antes do vencimento.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cidade</label>
                <input name="cidade" type="text"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('cidade', $isEditing ? $cliente->cidade : ($prefill['cidade'] ?? '')) }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado (UF)</label>
                <input name="estado" type="text"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       maxlength="2"
                       placeholder="SP"
                       value="{{ old('estado', $isEditing ? $cliente->estado : ($prefill['estado'] ?? '')) }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Abertura</label>
                <input name="dataabertura" type="date"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('dataabertura', $isEditing ? $cliente->dataabertura?->format('Y-m-d') : ($prefill['dataabertura'] ?? '')) }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente Desde</label>
                <input name="cliente_desde" type="date"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                       value="{{ old('cliente_desde', $isEditing ? $cliente->cliente_desde?->format('Y-m-d') : ($prefill['cliente_desde'] ?? now()->toDateString())) }}">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descrição</label>
            <textarea name="descricao" rows="3"
                      class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                      placeholder="Observações sobre o cliente...">{{ old('descricao', $isEditing ? $cliente->descricao : ($prefill['descricao'] ?? '')) }}</textarea>
        </div>

        {{-- CRM Fields — faturamento e honorário só para Diretor e TI --}}
        @if(auth()->user()?->canVerInfoComercialCliente())
        <div class="border-t border-gray-100 dark:border-slate-700 pt-4">
            <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-3">Informações Comerciais</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Faturamento (R$)</label>
                    <input name="faturamento" type="number" step="0.01" min="0"
                           class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                           value="{{ old('faturamento', $isEditing ? $cliente->faturamento : ($prefill['faturamento'] ?? '')) }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Honorário (R$)</label>
                    <input name="honorario" type="number" step="0.01" min="0"
                           class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                           value="{{ old('honorario', $isEditing ? $cliente->honorario : ($prefill['honorario'] ?? '')) }}">
                </div>
            </div>
        </div>
        @endif

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                <input name="fator_r" type="checkbox" class="rounded border-gray-300"
                       {{ old('fator_r', $isEditing ? $cliente->fator_r : ($prefill['fator_r'] ?? false)) ? 'checked' : '' }}>
                Fator R
            </label>
        </div>

        <div>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Importar notas (NF-e/NFC-e/CT-e via SEFAZ-RS)</span>
            @php
                $importarNotasFiscaisAtual = old('importar_notas_fiscais', $isEditing ? $cliente->importar_notas_fiscais : ($prefill['importar_notas_fiscais'] ?? false));
            @endphp
            <div class="flex items-center gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                    <input name="importar_notas_fiscais" type="radio" value="1" class="border-gray-300"
                           {{ $importarNotasFiscaisAtual ? 'checked' : '' }}>
                    Sim
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                    <input name="importar_notas_fiscais" type="radio" value="0" class="border-gray-300"
                           {{ ! $importarNotasFiscaisAtual ? 'checked' : '' }}>
                    Não
                </label>
            </div>
        </div>

        @if(isset($produtos) && $produtos->isNotEmpty())
            @php
                $produtosSelecionados = old('produtos', $isEditing ? $cliente->produtos->pluck('id')->toArray() : ($prefill['produtos'] ?? []));
            @endphp
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Produtos / Serviços</label>
                    @if(auth()->user()?->canGerenciarProdutos())
                    <button type="button" id="btn-novo-produto"
                            title="Novo produto"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs border border-gray-300 dark:border-slate-600 rounded text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 bg-white dark:bg-slate-800">
                        <i class="fa-solid fa-plus"></i> Novo
                    </button>
                    @endif
                </div>
                <div id="lista-produtos" class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border dark:border-slate-600 rounded p-3 bg-white dark:bg-slate-700">
                    @foreach($produtos as $produto)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" name="produtos[]" value="{{ $produto->id }}"
                                   class="rounded border-gray-300"
                                   {{ in_array($produto->id, $produtosSelecionados) ? 'checked' : '' }}>
                            {{ $produto->nome }}
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Selecione os produtos/serviços que este cliente contrata ou pode contratar.</p>
            </div>
        @endif

        @if(isset($possibilidades) && $possibilidades->isNotEmpty())
            @php
                $possibilidadesSelecionadas = old('possibilidades', $isEditing ? $cliente->possibilidades->pluck('id')->toArray() : ($prefill['possibilidades'] ?? []));
            @endphp
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Possibilidades</label>
                    @if(auth()->user()?->canGerenciarPossibilidades())
                    <button type="button" id="btn-nova-possibilidade"
                            title="Nova possibilidade"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-xs border border-gray-300 dark:border-slate-600 rounded text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 bg-white dark:bg-slate-800">
                        <i class="fa-solid fa-plus"></i> Novo
                    </button>
                    @endif
                </div>
                <div id="lista-possibilidades" class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border dark:border-slate-600 rounded p-3 bg-white dark:bg-slate-700">
                    @foreach($possibilidades as $possibilidade)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" name="possibilidades[]" value="{{ $possibilidade->id }}"
                                   class="rounded border-gray-300"
                                   {{ in_array($possibilidade->id, $possibilidadesSelecionadas) ? 'checked' : '' }}>
                            {{ $possibilidade->nome }}
                        </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">Selecione as possibilidades/oportunidades para este cliente.</p>
            </div>
        @endif

    </div>

    @unless(isset($hideShell) && $hideShell)
    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent dark:bg-transparent">
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
    const btnNovoProduto = document.getElementById('btn-novo-produto');
    if (btnNovoProduto) {
        btnNovoProduto.addEventListener('click', function () {
            Swal.fire({
                title: 'Novo Produto / Serviço',
                html: `
                    <div class="text-left space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome <span class="text-red-500">*</span></label>
                            <input id="swal-produto-nome" class="swal2-input mt-0 w-full" placeholder="Nome do produto/serviço">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                            <input id="swal-produto-descricao" class="swal2-input mt-0 w-full" placeholder="Breve descrição (opcional)">
                        </div>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                preConfirm: function () {
                    const nome = document.getElementById('swal-produto-nome').value.trim();
                    if (!nome) {
                        Swal.showValidationMessage('O nome é obrigatório.');
                        return false;
                    }
                    return { nome: nome, descricao: document.getElementById('swal-produto-descricao').value.trim() };
                },
            }).then(function (result) {
                if (!result.isConfirmed) { return; }

                $.ajax({
                    url: '{{ route('produtos.store.inline') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    data: result.value,
                    success: function (data) {
                        const lista = document.getElementById('lista-produtos');
                        const label = document.createElement('label');
                        label.className = 'inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer';
                        label.innerHTML = `<input type="checkbox" name="produtos[]" value="${data.id}" class="rounded border-gray-300" checked> ${data.nome}`;
                        lista.appendChild(label);
                        Swal.fire({ icon: 'success', title: 'Produto criado!', timer: 1500, showConfirmButton: false });
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.error ?? 'Erro ao criar produto.';
                        Swal.fire({ icon: 'error', title: 'Erro', text: msg, confirmButtonColor: '#dc2626' });
                    },
                });
            });
        });
    }
})();
</script>

<script>
(function () {
    const btnNovaPossibilidade = document.getElementById('btn-nova-possibilidade');
    if (btnNovaPossibilidade) {
        btnNovaPossibilidade.addEventListener('click', function () {
            Swal.fire({
                title: 'Nova Possibilidade',
                html: `
                    <div class="text-left space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome <span class="text-red-500">*</span></label>
                            <input id="swal-possibilidade-nome" class="swal2-input mt-0 w-full" placeholder="Nome da possibilidade">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                            <input id="swal-possibilidade-descricao" class="swal2-input mt-0 w-full" placeholder="Breve descrição (opcional)">
                        </div>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                preConfirm: function () {
                    const nome = document.getElementById('swal-possibilidade-nome').value.trim();
                    if (!nome) {
                        Swal.showValidationMessage('O nome é obrigatório.');
                        return false;
                    }
                    return { nome: nome, descricao: document.getElementById('swal-possibilidade-descricao').value.trim() };
                },
            }).then(function (result) {
                if (!result.isConfirmed) { return; }

                $.ajax({
                    url: '{{ route('possibilidades.store.inline') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    data: result.value,
                    success: function (data) {
                        const lista = document.getElementById('lista-possibilidades');
                        const label = document.createElement('label');
                        label.className = 'inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer';
                        label.innerHTML = `<input type="checkbox" name="possibilidades[]" value="${data.id}" class="rounded border-gray-300" checked> ${data.nome}`;
                        lista.appendChild(label);
                        Swal.fire({ icon: 'success', title: 'Possibilidade criada!', timer: 1500, showConfirmButton: false });
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.error ?? 'Erro ao criar possibilidade.';
                        Swal.fire({ icon: 'error', title: 'Erro', text: msg, confirmButtonColor: '#dc2626' });
                    },
                });
            });
        });
    }
})();
</script>

<script>
(function () {
    const btnNovaSegmentacao = document.getElementById('btn-nova-segmentacao');
    if (btnNovaSegmentacao) {
        btnNovaSegmentacao.addEventListener('click', function () {
            Swal.fire({
                title: 'Nova Segmentação',
                input: 'text',
                inputLabel: 'Nome da segmentação',
                inputPlaceholder: 'Ex: Indústria, Comércio, Serviços...',
                showCancelButton: true,
                confirmButtonText: 'Salvar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#2563eb',
                inputValidator: function (value) {
                    if (!value || !value.trim()) {
                        return 'O nome é obrigatório.';
                    }
                },
            }).then(function (result) {
                if (!result.isConfirmed) { return; }

                $.ajax({
                    url: '{{ route('segmentacoes.store') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    data: { nome: result.value.trim() },
                    success: function (data) {
                        const select = document.getElementById('select-segmentacao');
                        const option = document.createElement('option');
                        option.value = data.id;
                        option.textContent = data.nome;
                        option.selected = true;
                        select.appendChild(option);
                        Swal.fire({ icon: 'success', title: 'Segmentação criada!', timer: 1500, showConfirmButton: false });
                    },
                    error: function (xhr) {
                        const msg = xhr.responseJSON?.error ?? 'Erro ao criar segmentação.';
                        Swal.fire({ icon: 'error', title: 'Erro', text: msg, confirmButtonColor: '#dc2626' });
                    },
                });
            });
        });
    }
})();
</script>

<script>
(function () {
    const selectTipo = document.getElementById('select-tipo');
    const inputCpfCnpj = document.getElementById('input-cpfcnpj');
    const labelCpfCnpj = document.getElementById('label-cpfcnpj');

    function applyMask(value, tipo) {
        if (tipo === '1') {
            // CNPJ alfanumérico: AA.AAA.AAA/AAAA-AA
            const chars = value.replace(/[^A-Z0-9]/gi, '').toUpperCase().slice(0, 14);
            return chars
                .replace(/^([A-Z0-9]{2})([A-Z0-9])/, '$1.$2')
                .replace(/^([A-Z0-9]{2})\.([A-Z0-9]{3})([A-Z0-9])/, '$1.$2.$3')
                .replace(/\.([A-Z0-9]{3})([A-Z0-9])/, '.$1/$2')
                .replace(/([A-Z0-9]{4})([A-Z0-9])/, '$1-$2');
        } else {
            // CPF: 000.000.000-00
            const digits = value.replace(/\D/g, '');
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
            inputCpfCnpj.placeholder = '00.000.000/0000-00 ou AA.AAA.AAA/AAAA-AA';
            inputCpfCnpj.maxLength = 18;
            regimeWrapper.classList.remove('hidden');
            if (selectRegime) { selectRegime.required = true; }
        } else {
            labelCpfCnpj.textContent = 'CPF';
            inputCpfCnpj.placeholder = '000.000.000-00';
            inputCpfCnpj.maxLength = 14;
            regimeWrapper.classList.add('hidden');
            if (selectRegime) { selectRegime.required = false; selectRegime.value = ''; }
        }

        inputCpfCnpj.value = applyMask(inputCpfCnpj.value, tipo);
    }

    selectTipo.addEventListener('change', updateField);

    inputCpfCnpj.addEventListener('input', function () {
        // Count alphanumeric chars before cursor so we can restore position after masking
        const charsBeforeCursor = this.value.slice(0, this.selectionStart).replace(/[^A-Z0-9]/gi, '').length;
        this.value = applyMask(this.value, selectTipo.value);

        // Walk the masked value and find the position after the same number of alphanumeric chars
        let count = 0;
        let newPos = this.value.length;
        for (let i = 0; i < this.value.length; i++) {
            if (/[A-Z0-9]/i.test(this.value[i])) {
                count++;
            }
            if (count === charsBeforeCursor) {
                newPos = i + 1;
                break;
            }
        }
        if (charsBeforeCursor === 0) { newPos = 0; }
        this.setSelectionRange(newPos, newPos);
    });

    // Verifica se já existe cliente cadastrado com o mesmo CPF/CNPJ
    const avisoDuplicado = document.getElementById('aviso-cpfcnpj-duplicado');
    const linkClienteDuplicado = document.getElementById('link-cliente-duplicado');
    const excluirId = {{ $isEditing ? $cliente->id : 'null' }};
    let timerVerificacao = null;

    function esconderAvisoDuplicado() {
        avisoDuplicado.classList.add('hidden');
    }

    inputCpfCnpj.addEventListener('input', function () {
        esconderAvisoDuplicado();
        clearTimeout(timerVerificacao);

        const documento = this.value.replace(/[^A-Z0-9]/gi, '');
        if (documento.length < 11) { return; }

        timerVerificacao = setTimeout(function () {
            const params = new URLSearchParams({ cpfcnpj: documento });
            if (excluirId) { params.set('excluir_id', excluirId); }

            fetch('{{ route('clientes.verificar-documento') }}?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.json())
                .then(function (data) {
                    if (data.existe && data.cliente) {
                        linkClienteDuplicado.textContent = data.cliente.nome;
                        linkClienteDuplicado.href = data.cliente.url;
                        avisoDuplicado.classList.remove('hidden');
                    } else {
                        esconderAvisoDuplicado();
                    }
                })
                .catch(function () {});
        }, 400);
    });

    // Initialize on load
    updateField();
})();
</script>

<script>
(function () {
    const inputCpfCnpj = document.getElementById('input-cpfcnpj');
    const selectTipo = document.getElementById('select-tipo');
    const status = document.getElementById('status-busca-cnpj');
    if (!inputCpfCnpj || !status) { return; }

    function setStatus(msg, tone) {
        if (!msg) { status.classList.add('hidden'); return; }
        status.textContent = msg;
        status.className = 'mt-1 text-xs ' + ({
            ok: 'text-green-600 dark:text-green-400',
            erro: 'text-red-600 dark:text-red-400',
            carregando: 'text-gray-500 dark:text-slate-400',
        }[tone] || 'text-gray-500 dark:text-slate-400');
        status.classList.remove('hidden');
    }

    const form = inputCpfCnpj.closest('form');

    // Guarda o que a consulta preencheu. Numa nova consulta, sobrescreve os
    // campos que ainda estão como a consulta anterior deixou (ou vazios),
    // mas preserva o que o usuário digitou à mão.
    const autoPreenchido = {};

    function aplicar(name, valor) {
        if (!form) { return; }
        const el = form.querySelector(`[name="${name}"]`);
        if (!el) { return; }

        const intocado = el.value === '' || el.value === autoPreenchido[name];
        if (!intocado) { return; }

        el.value = valor || '';
        autoPreenchido[name] = el.value;
    }

    let timer = null;
    let ultimoConsultado = inputCpfCnpj.value.replace(/\D/g, '');

    function consultar(cnpj) {
        setStatus('Consultando Receita Federal...', 'carregando');

        fetch(`{{ url('clientes/consultar-cnpj') }}/${cnpj}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(r => r.ok ? r.json() : r.json().then(e => Promise.reject(e)))
            .then(function (d) {
                aplicar('nome', d.razao_social || d.nome_fantasia);
                aplicar('atividade', d.cnae_descricao);
                aplicar('cidade', d.cidade);
                aplicar('estado', d.estado);
                aplicar('dataabertura', d.data_abertura);

                document.getElementById('input-cnae-principal').value = d.cnae_principal || '';
                document.getElementById('input-cnae-secundarios').value = JSON.stringify(d.cnae_secundarios || []);

                const selectRegime = document.getElementById('select-regime');
                if (selectRegime && (!selectRegime.value || selectRegime.value === autoPreenchido['regime_tributario'])) {
                    selectRegime.value = d.regime_sugerido || '';
                    autoPreenchido['regime_tributario'] = selectRegime.value;
                }

                const ativa = (d.situacao || '').toUpperCase() === 'ATIVA';
                setStatus(
                    (ativa ? '✓ ' : '⚠ ') + [d.razao_social, d.situacao].filter(Boolean).join(' — '),
                    ativa ? 'ok' : 'erro'
                );
            })
            .catch(function (e) {
                ultimoConsultado = '';
                setStatus(e?.error || 'CNPJ não encontrado ou consulta indisponível.', 'erro');
            });
    }

    inputCpfCnpj.addEventListener('input', function () {
        clearTimeout(timer);

        if (selectTipo && selectTipo.value !== '1') { return; }

        const cnpj = this.value.replace(/\D/g, '');
        if (cnpj.length !== 14) { return; }
        if (cnpj === ultimoConsultado) { return; }

        timer = setTimeout(function () {
            ultimoConsultado = cnpj;
            consultar(cnpj);
        }, 600);
    });
})();
</script>
