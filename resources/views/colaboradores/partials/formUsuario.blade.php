
@php
    $isEditing = !is_null($colab);
    $action = $isEditing ? route('colaboradores.update', $colab->id) : route('colaboradores.save');
    $title = $isEditing ? 'Editar Colaborador' : 'Novo Colaborador';
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        @if($isEditing)
            <i class="fa-solid fa-user-pen mr-2"></i>
        @else
            <i class="fa-solid fa-user-plus mr-2"></i>
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
                   value="{{ old('nome', $isEditing ? $colab->nome : '') }}"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">E-mail</label>
            <input name="email" type="email"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   value="{{ old('email', $isEditing ? $colab->email : '') }}"
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                Senha @if($isEditing)<span class="text-gray-400 font-normal">(deixe em branco para manter)</span>@endif
            </label>
            <input name="senha" type="password"
                   class="mt-1 block w-full border rounded px-3 py-2"
                   {{ $isEditing ? '' : 'required' }}>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Cargo</label>
            <select name="cargo" class="mt-1 block w-full border rounded px-3 py-2" required>
                @foreach(['diretor' => 'Diretor', 'supervisor' => 'Supervisor', 'analista' => 'Analista', 'assistente' => 'Assistente', 'auxiliar' => 'Auxiliar'] as $value => $label)
                    <option value="{{ $value }}"
                        {{ old('cargo', $isEditing ? $colab->cargo : '') === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Telefone</label>
                <input name="telefone" type="text"
                       class="mt-1 block w-full border rounded px-3 py-2 telefone-mask"
                       placeholder="(99) 99999-9999"
                       maxlength="15"
                       value="{{ old('telefone', $isEditing ? $colab->telefone : '') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Sexo</label>
                <select name="sexo" class="mt-1 block w-full border rounded px-3 py-2">
                    <option value="">— Selecione —</option>
                    @foreach(['masculino' => 'Masculino', 'feminino' => 'Feminino', 'outro' => 'Outro'] as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('sexo', $isEditing ? $colab->sexo : '') === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Data de Nascimento</label>
                <input name="data_nascimento" type="date"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('data_nascimento', $isEditing ? $colab->data_nascimento : '') }}">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Data de Registro</label>
                <input name="data_registro" type="date"
                       class="mt-1 block w-full border rounded px-3 py-2"
                       value="{{ old('data_registro', $isEditing ? $colab->data_registro : now()->toDateString()) }}">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Departamento</label>
            <select name="departamento_id" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="">— Selecione —</option>
                @foreach($departamentos as $dep)
                    <option value="{{ $dep->id }}"
                        {{ old('departamento_id', $isEditing ? $colab->departamento_id : '') == $dep->id ? 'selected' : '' }}>
                        {{ $dep->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select name="status" class="mt-1 block w-full border rounded px-3 py-2">
                <option value="1" {{ old('status', $isEditing ? (int) $colab->status : 1) == 1 ? 'selected' : '' }}>Ativo</option>
                <option value="0" {{ old('status', $isEditing ? (int) $colab->status : 1) == 0 ? 'selected' : '' }}>Inativo</option>
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
