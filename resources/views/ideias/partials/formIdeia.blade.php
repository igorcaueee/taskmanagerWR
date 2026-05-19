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
        <i class="fa-solid fa-lightbulb mr-2"></i> Nova Ideia / Correção
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
    Enviando como: <span class="font-medium text-gray-700 dark:text-gray-300">{{ auth()->user()->nome }}</span>
</p>

<form method="POST" action="{{ route('ideias.store') }}">
    @csrf

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Ideia / Correção <span class="text-red-500">*</span></label>
            <textarea name="descricao" rows="4" required
                      class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand resize-none"
                      placeholder="Descreva sua ideia ou sugestão de correção...">{{ old('descricao') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
            <select name="status"
                    class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                @foreach($statusLabels as $value => $label)
                    <option value="{{ $value }}" {{ old('status', 'pendente') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
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
