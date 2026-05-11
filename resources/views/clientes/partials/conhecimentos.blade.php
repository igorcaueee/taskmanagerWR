<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        <i class="fa-solid fa-brain mr-2"></i> Conhecimento IA — {{ $cliente->nome }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<p class="text-xs text-gray-500 dark:text-slate-400 mb-4">
    Informações específicas sobre este cliente que a Liri usará para responder perguntas com mais precisão.
</p>

<div class="mb-4 flex justify-end">
    <button type="button"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-brand text-white rounded text-sm hover:bg-brand/80 border-0"
            data-modal-url="{{ route('clientes.conhecimentos.form.create', $cliente->id) }}">
        <i class="fa-solid fa-plus text-xs"></i> Adicionar
    </button>
</div>

@if($conhecimentos->isEmpty())
    <div class="py-8 text-center text-gray-400 dark:text-slate-500 text-sm italic">
        Nenhum conhecimento cadastrado para este cliente.
    </div>
@else
    <div class="space-y-3">
        @foreach($conhecimentos as $conhecimento)
        <div class="bg-gray-50 dark:bg-slate-700 border border-gray-100 dark:border-slate-600 rounded-lg px-4 py-3">
            <div class="flex items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-slate-200 mb-1">
                        <i class="fa-solid fa-bookmark text-brand mr-1"></i>{{ $conhecimento->titulo }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap break-words leading-relaxed">{{ $conhecimento->conteudo }}</p>
                    <p class="text-xs text-gray-400 dark:text-slate-500 mt-2">
                        Adicionado em {{ $conhecimento->created_at->format('d/m/Y \à\s H:i') }}
                    </p>
                </div>
                @if(auth()->user()?->canEditarClientes())
                <div class="flex gap-1 flex-shrink-0">
                    <button type="button"
                            class="p-1.5 text-gray-400 dark:text-slate-500 hover:text-blue-600 bg-transparent border-0"
                            data-modal-url="{{ route('conhecimentos.form.edit', $conhecimento->id) }}"
                            title="Editar">
                        <i class="fa-solid fa-pencil text-xs"></i>
                    </button>
                    <button type="button"
                            class="p-1.5 text-gray-400 dark:text-slate-500 hover:text-red-600 bg-transparent border-0 btn-delete-conhecimento"
                            data-id="{{ $conhecimento->id }}"
                            data-titulo="{{ $conhecimento->titulo }}"
                            title="Excluir">
                        <i class="fa-solid fa-trash text-xs"></i>
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
@endif

@push('scripts')
<script type="module">
document.querySelectorAll('.btn-delete-conhecimento').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const titulo = btn.dataset.titulo;
        const id = btn.dataset.id;

        Swal.fire({
            title: 'Excluir conhecimento?',
            text: `"${titulo}" será removido permanentemente.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/conhecimentos/${id}`;
                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>
@endpush
