@php
    $totalAbertas = $comoResponsavel + $comoSupervisor;
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        <i class="fa-solid fa-people-arrows mr-2"></i>
        Transferir tarefas de {{ $colab->nome }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

<p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
    Reatribui todas as tarefas em aberto (ativas e não concluídas) deste colaborador para outro.
    Ocorrências futuras de tarefas recorrentes acompanham a transferência.
</p>

<div class="grid grid-cols-2 gap-3 mb-4">
    <div class="rounded border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 px-3 py-2">
        <div class="text-xs text-gray-500 dark:text-gray-400">Como responsável</div>
        <div class="text-xl font-semibold text-gray-900 dark:text-slate-100">{{ $comoResponsavel }}</div>
    </div>
    <div class="rounded border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 px-3 py-2">
        <div class="text-xs text-gray-500 dark:text-gray-400">Como supervisor</div>
        <div class="text-xl font-semibold text-gray-900 dark:text-slate-100">{{ $comoSupervisor }}</div>
    </div>
</div>

@if($totalAbertas === 0)
    <div class="rounded border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 text-sm px-3 py-2 mb-4">
        Este colaborador não tem tarefas em aberto. Você pode apenas desativá-lo pela tela de edição.
    </div>
@endif

<form method="POST" action="{{ route('colaboradores.transferir-tarefas', $colab->id) }}">
    @csrf

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Transferir para</label>
            <select name="novo_responsavel_id" required
                    class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                <option value="">— Selecione o colaborador —</option>
                @foreach($destinos as $destino)
                    <option value="{{ $destino->id }}">{{ $destino->nome }}{{ $destino->departamento ? ' — '.$destino->departamento->nome : '' }}</option>
                @endforeach
            </select>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="hidden" name="incluir_supervisao" value="0">
            <input type="checkbox" name="incluir_supervisao" value="1" checked
                   class="rounded border-gray-300 dark:border-slate-600 text-brand focus:ring-brand">
            Reatribuir também as tarefas em que ele é supervisor
        </label>

        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="hidden" name="desativar_colaborador" value="0">
            <input type="checkbox" name="desativar_colaborador" value="1" checked
                   class="rounded border-gray-300 dark:border-slate-600 text-brand focus:ring-brand">
            Desativar {{ $colab->nome }} após a transferência
        </label>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent dark:bg-transparent">
            Cancelar
        </button>
        <button type="submit" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
            <i class="fa-solid fa-people-arrows mr-1"></i> Transferir tarefas
        </button>
    </div>
</form>
