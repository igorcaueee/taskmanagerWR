@php
    $isEditing = !is_null($contato);
    $action = $isEditing
        ? route('clientes.contatos.update', $contato->id)
        : route('clientes.contatos.save', $cliente->id);
    $title = $isEditing ? 'Editar Contato' : 'Novo Contato';
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        <i class="fa-solid fa-address-card mr-2"></i>{{ $title }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<p class="text-sm text-gray-500 mb-4">Cliente: <span class="font-medium text-gray-700">{{ $cliente->nome }}</span></p>

<form id="form-contato" method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Nome</label>
            <input name="nome" type="text"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('nome', $isEditing ? $contato->nome : '') }}"
                   required>
            @error('nome')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Telefone</label>
            <input name="telefone" type="text"
                   class="mt-1 block w-full border rounded px-3 py-2 phone-mask"
                   placeholder="(00) 00000-0000"
                   maxlength="15"
                   value="{{ old('telefone', $isEditing ? $contato->telefone : '') }}">
            @error('telefone')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">E-mail</label>
            <input name="gmail" type="email"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   placeholder="contato@exemplo.com"
                   value="{{ old('gmail', $isEditing ? $contato->gmail : '') }}">
            @error('gmail')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <button type="button" onclick="closeModal()"
                    class="px-4 py-2 text-sm border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                Cancelar
            </button>
            <button type="submit"
                    class="px-4 py-2 text-sm bg-brand text-white rounded hover:bg-brand/80">
                {{ $isEditing ? 'Salvar' : 'Adicionar' }}
            </button>
        </div>
    </div>
</form>

<script>
(function () {
    const form = document.querySelector('#form-contato');
    if (!form) { return; }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const submitBtn = form.querySelector('[type="submit"]');
        submitBtn.disabled = true;

        const data = new FormData(form);
        const resp = await fetch(form.action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } });

        // Servidor sempre redireciona para clientes.contatos.modal após salvar
        // fetch segue o redirect automaticamente — se chegou aqui, foi sucesso
        if (resp.ok || resp.redirected) {
            const contatosUrl = '{{ route('clientes.contatos.modal', $cliente->id) }}';
            window.openModal(contatosUrl);
        } else {
            // Erro de validação: recarrega o conteúdo do form com os erros
            const html = await resp.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newForm = doc.querySelector('#form-contato')?.closest('form')?.parentElement;
            if (newForm) {
                document.getElementById('modalContent').innerHTML = newForm.innerHTML;
            } else {
                submitBtn.disabled = false;
            }
        }
    });
})();
</script>
