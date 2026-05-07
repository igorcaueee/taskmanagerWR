<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900">
        <i class="fa-solid fa-building-circle-xmark mr-2 text-red-600"></i>
        Encerrar Cliente
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
    <i class="fa-solid fa-triangle-exclamation mr-1"></i>
    Você está prestes a encerrar o cliente <strong>{{ $cliente->nome }}</strong>. O status será alterado para <strong>Inativo</strong> e a data de encerramento será registrada automaticamente.
</div>

<form method="POST" action="{{ route('clientes.encerrar', $cliente->id) }}">
    @csrf

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">
                Motivo do Encerramento <span class="text-red-500">*</span>
            </label>
            <textarea name="motivo_encerramento" rows="4"
                      class="mt-1 block w-full border rounded px-3 py-2 @error('motivo_encerramento') border-red-500 @enderror"
                      placeholder="Descreva o motivo do encerramento..." required>{{ old('motivo_encerramento') }}</textarea>
            @error('motivo_encerramento')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" onclick="closeModal()"
                    class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 focus:outline-none">
                Cancelar
            </button>
            <button type="submit"
                    class="px-4 py-2 text-sm text-white bg-red-600 border border-red-600 rounded hover:bg-red-700 focus:outline-none">
                <i class="fa-solid fa-building-circle-xmark mr-1"></i>
                Confirmar Encerramento
            </button>
        </div>
    </div>
</form>
