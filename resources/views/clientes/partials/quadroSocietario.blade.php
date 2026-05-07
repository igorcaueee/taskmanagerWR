<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        <i class="fa-solid fa-scale-balanced mr-2"></i> Quadro Societário — {{ $cliente->nome }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

@if(session('success'))
    <div class="mb-3 px-3 py-2 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
@endif

{{-- Capital Social --}}
<form id="form-capital" method="POST" action="{{ route('clientes.socios.save', $cliente->id) }}"
      class="bg-gray-50 rounded border border-gray-100 px-4 py-3 mb-4 flex flex-wrap items-end gap-4">
    @csrf
    {{-- campo dummy para não adicionar sócio ao salvar só o capital --}}
    <input type="hidden" name="_only_capital" value="1">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Capital Social Total (R$)</label>
        <input type="number" name="capital_social" step="0.01" min="0"
               id="input-capital-social"
               value="{{ number_format((float) $cliente->capital_social, 2, '.', '') }}"
               class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand w-48">
    </div>
    <p class="text-xs text-gray-400 self-end pb-2">
        Total participação: <span id="total-participacao" class="font-semibold text-gray-700">{{ $cliente->socios->sum('participacao') }}%</span>
    </p>
</form>

{{-- Tabela de sócios --}}
@if($cliente->socios->isEmpty())
    <div class="py-6 text-center text-gray-400 text-sm italic">Nenhum sócio cadastrado.</div>
@else
<div class="overflow-x-auto mb-4">
    <table class="min-w-full text-sm" id="tabela-socios">
        <thead>
            <tr class="border-b border-gray-100 text-xs text-gray-500 uppercase">
                <th class="py-2 pr-3 text-left w-10">Ordem</th>
                <th class="py-2 pr-3 text-left">Nome</th>
                <th class="py-2 pr-3 text-left">Telefone</th>
                <th class="py-2 pr-3 text-left">E-mail</th>
                <th class="py-2 pr-3 text-right">Participação %</th>
                <th class="py-2 pr-3 text-right">Quotas Integralizadas</th>
                @if(auth()->user()?->canEditarClientes())
                <th class="py-2 text-right w-16"></th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($cliente->socios as $socio)
            <tr class="hover:bg-gray-50" id="row-socio-{{ $socio->id }}">
                <td class="py-2 pr-3 text-gray-400 text-xs font-medium">{{ $socio->ordem }}ª</td>
                <td class="py-2 pr-3 font-medium text-gray-800">{{ $socio->nome }}</td>
                <td class="py-2 pr-3 text-gray-600 text-xs">{{ $socio->telefone ?: '—' }}</td>
                <td class="py-2 pr-3 text-gray-600 text-xs">{{ $socio->gmail ?: '—' }}</td>
                <td class="py-2 pr-3 text-right text-gray-700">{{ number_format($socio->participacao, 2, ',', '') }}%</td>
                <td class="py-2 pr-3 text-right text-gray-700">
                    R$ {{ number_format($socio->quotas_integralizadas, 2, ',', '.') }}
                </td>
                @if(auth()->user()?->canEditarClientes())
                <td class="py-2 text-right">
                    <div class="flex justify-end gap-1">
                        <button type="button"
                                class="p-1.5 text-gray-400 hover:text-blue-600 bg-transparent border-0"
                                onclick="abrirEdicaoSocio({{ $socio->id }})"
                                title="Editar">
                            <i class="fa-solid fa-pencil text-xs"></i>
                        </button>
                        <button type="button"
                                class="p-1.5 text-gray-400 hover:text-red-600 bg-transparent border-0"
                                onclick="deletarSocio({{ $socio->id }}, '{{ addslashes($socio->nome) }}')"
                                title="Remover">
                            <i class="fa-solid fa-trash text-xs"></i>
                        </button>
                    </div>
                </td>
                @endif
            </tr>
            {{-- Linha de edição inline (oculta por padrão) --}}
            <tr id="edit-row-{{ $socio->id }}" class="hidden bg-blue-50">
                <td colspan="{{ auth()->user()?->canEditarClientes() ? 7 : 6 }}" class="py-3 px-2">
                    <form id="form-edit-socio-{{ $socio->id }}" method="POST"
                          action="{{ route('clientes.socios.update', $socio->id) }}"
                          class="flex flex-wrap gap-3 items-end">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="capital_social"
                               id="hidden-capital-{{ $socio->id }}"
                               value="{{ number_format((float) $cliente->capital_social, 2, '.', '') }}">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Nome *</label>
                            <input type="text" name="nome" value="{{ $socio->nome }}" required
                                   class="border border-gray-300 rounded px-2 py-1 text-sm w-48">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Telefone</label>
                            <input type="text" name="telefone" value="{{ $socio->telefone }}"
                                   class="border border-gray-300 rounded px-2 py-1 text-sm w-32">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">E-mail</label>
                            <input type="email" name="gmail" value="{{ $socio->gmail }}"
                                   class="border border-gray-300 rounded px-2 py-1 text-sm w-44">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Participação % *</label>
                            <input type="number" name="participacao" step="0.01" min="0" max="100"
                                   value="{{ $socio->participacao }}" required
                                   class="border border-gray-300 rounded px-2 py-1 text-sm w-24">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="px-3 py-1 text-xs bg-brand text-white rounded hover:bg-brand/80 border-0">
                                Salvar
                            </button>
                            <button type="button"
                                    onclick="fecharEdicaoSocio({{ $socio->id }})"
                                    class="px-3 py-1 text-xs border border-gray-300 rounded text-gray-600 hover:bg-gray-50">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Formulário adicionar sócio --}}
@if(auth()->user()?->canEditarClientes())
<div class="border-t border-gray-100 pt-4">
    <p class="text-xs font-medium text-gray-500 uppercase mb-3">Adicionar Sócio</p>
    <form id="form-add-socio" method="POST" action="{{ route('clientes.socios.save', $cliente->id) }}"
          class="flex flex-wrap gap-3 items-end">
        @csrf
        <input type="hidden" name="capital_social" id="add-capital-hidden"
               value="{{ number_format((float) $cliente->capital_social, 2, '.', '') }}">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Nome *</label>
            <input type="text" name="nome" required
                   class="border border-gray-300 rounded px-2 py-1.5 text-sm w-48"
                   placeholder="Nome completo">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Telefone</label>
            <input type="text" name="telefone"
                   class="border border-gray-300 rounded px-2 py-1.5 text-sm w-32"
                   placeholder="(00) 00000-0000">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">E-mail</label>
            <input type="email" name="gmail"
                   class="border border-gray-300 rounded px-2 py-1.5 text-sm w-44"
                   placeholder="email@exemplo.com">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Participação % *</label>
            <input type="number" name="participacao" step="0.01" min="0" max="100" required
                   class="border border-gray-300 rounded px-2 py-1.5 text-sm w-24"
                   placeholder="0,00">
        </div>
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-brand text-white rounded hover:bg-brand/80 border-0">
            <i class="fa-solid fa-plus"></i> Adicionar
        </button>
    </form>
</div>
@endif

<script>
(function () {
    const capitalInput = document.getElementById('input-capital-social');

    // Sincronizar capital nos hidden fields ao alterar
    if (capitalInput) {
        capitalInput.addEventListener('input', function () {
            document.getElementById('add-capital-hidden').value = this.value;
            document.querySelectorAll('[id^="hidden-capital-"]').forEach(el => {
                el.value = this.value;
            });
        });

        // Salvar capital automaticamente ao sair do campo (blur)
        capitalInput.addEventListener('change', async function () {
            const val = this.value;
            if (!val) { return; }
            const fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            fd.append('capital_social', val);
            fd.append('_only_capital', '1');
            fd.append('nome', '__skip__'); // dummy — o controller checa _only_capital
            await fetch('{{ route('clientes.socios.save', $cliente->id) }}', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
        });
    }

    // Interceptar formulário de adição
    const addForm = document.getElementById('form-add-socio');
    if (addForm) {
        addForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = addForm.querySelector('[type="submit"]');
            btn.disabled = true;

            // Atualizar capital antes
            if (capitalInput) {
                document.getElementById('add-capital-hidden').value = capitalInput.value;
            }

            const resp = await fetch(addForm.action, {
                method: 'POST',
                body: new FormData(addForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (resp.ok || resp.redirected) {
                window.openModal('{{ route('clientes.quadro.modal', $cliente->id) }}');
            } else {
                btn.disabled = false;
            }
        });
    }

    // Interceptar formulários de edição
    document.querySelectorAll('[id^="form-edit-socio-"]').forEach(function (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = form.querySelector('[type="submit"]');
            btn.disabled = true;

            if (capitalInput) {
                const hiddenId = form.querySelector('[name="capital_social"]');
                if (hiddenId) { hiddenId.value = capitalInput.value; }
            }

            const resp = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (resp.ok || resp.redirected) {
                window.openModal('{{ route('clientes.quadro.modal', $cliente->id) }}');
            } else {
                btn.disabled = false;
            }
        });
    });
})();

function abrirEdicaoSocio(id) {
    document.getElementById('edit-row-' + id).classList.remove('hidden');
    document.getElementById('row-socio-' + id).classList.add('opacity-40');
}

function fecharEdicaoSocio(id) {
    document.getElementById('edit-row-' + id).classList.add('hidden');
    document.getElementById('row-socio-' + id).classList.remove('opacity-40');
}

async function deletarSocio(id, nome) {
    const confirmed = await Swal.fire({
        title: `Remover "${nome}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });
    if (!confirmed.isConfirmed) { return; }

    const fd = new FormData();
    fd.append('_token', '{{ csrf_token() }}');
    fd.append('_method', 'DELETE');
    await fetch('{{ url('clientes/socios') }}/' + id, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    window.openModal('{{ route('clientes.quadro.modal', $cliente->id) }}');
}
</script>
