<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        <i class="fa-solid fa-address-card mr-2"></i> Contatos — {{ $cliente->nome }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

@if(session('success'))
    <div class="mb-3 px-3 py-2 bg-green-100 text-green-800 rounded text-sm">{{ session('success') }}</div>
@endif

@if(auth()->user()?->canEditarClientes())
<div class="flex justify-end mb-3">
    <button type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-brand text-white rounded hover:bg-brand/80 border-0"
            data-modal-url="{{ route('clientes.contatos.form.create', $cliente->id) }}">
        <i class="fa-solid fa-plus"></i> Novo Contato
    </button>
</div>
@endif

@if($cliente->contatos->isEmpty())
    <div class="py-8 text-center text-gray-400 dark:text-slate-500">
        <i class="fa-solid fa-address-book text-3xl mb-2 block"></i>
        <p class="text-sm">Nenhum contato cadastrado.</p>
    </div>
@else
    <div class="space-y-2">
        @foreach($cliente->contatos as $contato)
        <div class="flex items-center justify-between bg-gray-50 dark:bg-slate-700 border border-gray-100 dark:border-slate-600 rounded px-3 py-2.5">
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 dark:text-slate-200 truncate">{{ $contato->nome }}</p>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-0.5 mt-0.5">

                    @if($contato->telefone)
                        <span class="text-xs text-gray-500 dark:text-gray-400"><i class="fa-solid fa-phone mr-1"></i>{{ $contato->telefone }}</span>
                    @endif
                    @if($contato->gmail)
                        <span class="text-xs text-gray-500 dark:text-gray-400"><i class="fa-solid fa-envelope mr-1"></i>{{ $contato->gmail }}</span>
                    @endif
                </div>
            </div>
            @if(auth()->user()?->canEditarClientes())
            <div class="flex items-center gap-1 ml-2 shrink-0">
                <button type="button"
                        class="p-1.5 text-gray-400 dark:text-slate-500 hover:text-blue-600 bg-transparent border-0"
                        data-modal-url="{{ route('clientes.contatos.form.edit', $contato->id) }}"
                        title="Editar">
                    <i class="fa-solid fa-pencil text-xs"></i>
                </button>
                <form method="POST" action="{{ route('clientes.contatos.delete', $contato->id) }}"
                      data-delete-contato="{{ $cliente->id }}"
                      onsubmit="handleDeleteContato(event, this)">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-1.5 text-gray-400 dark:text-slate-500 hover:text-red-600 bg-transparent border-0" title="Remover">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                </form>
            </div>
            @endif
        </div>
        @endforeach
    </div>
@endif

<script>
async function handleDeleteContato(e, form) {
    e.preventDefault();
    const confirmed = await Swal.fire({
        title: 'Remover contato?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });
    if (!confirmed.isConfirmed) { return; }

    const data = new FormData(form);
    await fetch(form.action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } });

    const clienteId = form.dataset.deleteContato;
    window.openModal('{{ url('clientes') }}/' + clienteId + '/contatos');
}
</script>
