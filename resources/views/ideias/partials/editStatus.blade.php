@php
    $statusLabels = [
        'pendente'    => 'Pendente',
        'em_analise'  => 'Em análise',
        'aprovada'    => 'Aprovada',
        'concluida'   => 'Concluída',
        'descartada'  => 'Descartada',
    ];
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        <i class="fa-solid fa-pen-to-square mr-2"></i> Editar Status
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<div class="mb-4 p-3 bg-gray-50 dark:bg-slate-700 rounded text-sm text-gray-700 dark:text-gray-300">
    {{ $ideia->descricao }}
</div>

<form method="POST" action="{{ route('ideias.update-status', $ideia->id) }}">
    @csrf
    @method('PATCH')

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status"
                        class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" {{ $ideia->status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data de Conclusão</label>
                <input type="date" name="data_conclusao"
                       value="{{ $ideia->data_conclusao?->format('Y-m-d') }}"
                       class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()"
                class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-slate-700 rounded hover:bg-gray-200 dark:hover:bg-slate-600 border-0">
            Cancelar
        </button>
        <button type="submit"
                class="px-4 py-2 text-sm text-white bg-brand rounded hover:bg-brand/80 border-0">
            <i class="fa-solid fa-floppy-disk mr-1"></i> Salvar
        </button>
    </div>
</form>
