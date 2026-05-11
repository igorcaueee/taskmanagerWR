@php
    $isEditing = !is_null($conhecimento);
    $action = $isEditing ? route('conhecimentos.update', $conhecimento->id) : route('clientes.conhecimentos.store', $cliente->id);
    $title = $isEditing ? 'Editar Conhecimento' : 'Novo Conhecimento';
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        <i class="fa-solid fa-brain mr-2"></i> {{ $title }} — {{ $cliente->nome }}
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
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Título</label>
            <input name="titulo" type="text"
                   placeholder="Ex: Informações fiscais, Observações importantes..."
                   class="block w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 text-sm focus:outline-none focus:ring-1 focus:ring-brand"
                   value="{{ old('titulo', $isEditing ? $conhecimento->titulo : '') }}"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Conteúdo</label>
            <p class="text-xs text-gray-400 dark:text-slate-500 mb-2">
                Digite o conteúdo diretamente ou faça upload de um arquivo <strong>.txt</strong> para preencher automaticamente.
            </p>

            {{-- Upload de arquivo .txt --}}
            <div class="mb-2">
                <label class="inline-flex items-center gap-2 px-3 py-1.5 border border-gray-300 dark:border-slate-600 rounded text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer bg-white dark:bg-slate-800">
                    <i class="fa-solid fa-file-arrow-up"></i>
                    <span id="label-arquivo">Importar arquivo .txt</span>
                    <input type="file" id="input-arquivo-txt" accept=".txt" class="hidden">
                </label>
            </div>

            <textarea name="conteudo" id="textarea-conteudo" rows="10"
                      placeholder="Descreva informações relevantes sobre este cliente que a Liri deve conhecer..."
                      class="block w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 text-sm focus:outline-none focus:ring-1 focus:ring-brand resize-y"
                      required>{{ old('conteudo', $isEditing ? $conhecimento->conteudo : '') }}</textarea>
        </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()"
                class="px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 rounded hover:bg-gray-50 dark:hover:bg-slate-700 bg-white dark:bg-slate-800">
            Cancelar
        </button>
        <button type="submit"
                class="px-4 py-2 text-sm bg-brand text-white rounded hover:bg-brand/80 border-0">
            {{ $isEditing ? 'Salvar Alterações' : 'Adicionar Conhecimento' }}
        </button>
    </div>
</form>

@push('scripts')
<script type="module">
document.getElementById('input-arquivo-txt').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) {
        return;
    }

    if (!file.name.endsWith('.txt')) {
        Swal.fire({ icon: 'error', title: 'Formato inválido', text: 'Apenas arquivos .txt são aceitos.', confirmButtonColor: '#2563eb' });
        this.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('textarea-conteudo').value = e.target.result;
        document.getElementById('label-arquivo').textContent = file.name;
    };
    reader.readAsText(file, 'UTF-8');
});
</script>
@endpush
