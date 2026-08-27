@php
    $isEditing = !is_null($tipo);
    $action = $isEditing ? route('tipos-tarefa.update', $tipo->id) : route('tipos-tarefa.save');
    $title = $isEditing ? 'Editar Tipo de Tarefa' : 'Novo Tipo de Tarefa';
@endphp

<div class="flex items-center justify-between mb-4">
    <h5 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
        @if($isEditing)
            <i class="fa-solid fa-pen-to-square mr-2"></i>
        @else
            <i class="fa-solid fa-plus mr-2"></i>
        @endif
        {{ $title }}
    </h5>
    <button type="button" onclick="closeModal()" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 bg-transparent border-0 p-0">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
</div>

@if($errors->any())
    <div class="mb-4 px-4 py-3 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 rounded text-sm">
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome <span class="text-red-500">*</span></label>
            <input name="nome" type="text"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('nome', $isEditing ? $tipo->nome : '') }}"
                   placeholder="Ex: FGTS, INSS, Folha de Pagamento..."
                   required>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fa-solid fa-heading mr-1 text-brand"></i> Título Padrão da Tarefa
            </label>
            <input name="titulo_padrao" type="text"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('titulo_padrao', $isEditing ? $tipo->titulo_padrao : '') }}"
                   placeholder="Ex: Apuração FGTS — Junho/2026">
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Ao selecionar este tipo em uma tarefa, o título será preenchido automaticamente.
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                <i class="fa-regular fa-calendar mr-1 text-brand"></i> Data de Vencimento Padrão
            </label>
            <input name="data_vencimento" type="date"
                   class="mt-1 block w-full border dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200"
                   value="{{ old('data_vencimento', $isEditing && $tipo->data_vencimento ? $tipo->data_vencimento->format('Y-m-d') : '') }}">
            <p class="text-xs text-gray-400 dark:text-slate-500 mt-1">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Ao selecionar este tipo em uma tarefa, a data de vencimento será preenchida automaticamente.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-slate-700 pt-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                <i class="fa-solid fa-list-check mr-1 text-brand"></i> Regras de geração da obrigação
            </label>
            <p class="text-xs text-gray-400 dark:text-slate-500 mb-3">
                Marque os regimes em que esta obrigação se aplica. Use "Qualquer regime" + prefixos de CNAE para obrigações que dependem da atividade (ex: <code>01</code> rural → ITR, <code>86</code> saúde → DMED). Ao cadastrar o cliente ou trocar o regime, essas obrigações aparecem pré-marcadas na tela de revisão do checklist.
            </p>

            @php
                $regrasAtuais = $isEditing
                    ? $tipo->regras->keyBy(fn ($r) => $r->regime_tributario ?? $regimeQualquer)
                    : collect();
                $linhasRegras = array_merge([$regimeQualquer => 'Qualquer regime'], array_combine($regimesDisponiveis, $regimesDisponiveis));
            @endphp

            <div class="space-y-3">
                @foreach($linhasRegras as $chave => $rotulo)
                    @php
                        $regraAtual = $regrasAtuais->get($chave);
                        $ativoOld = old("regras.$chave.ativo");
                        $ativo = $ativoOld !== null ? filter_var($ativoOld, FILTER_VALIDATE_BOOLEAN) : (bool) $regraAtual;
                        $prefixosAtuais = old("regras.$chave.cnae_prefixos", $regraAtual && $regraAtual->cnae_prefixos ? implode(', ', $regraAtual->cnae_prefixos) : '');
                    @endphp
                    <div class="border dark:border-slate-600 rounded px-3 py-2 regime-obrigacao-item">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" name="regras[{{ $chave }}][ativo]" value="1"
                                   class="regime-obrigacao-toggle rounded border-gray-300 dark:border-slate-600 text-brand focus:ring-brand"
                                   {{ $ativo ? 'checked' : '' }}>
                            {{ $rotulo }}
                        </label>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2 regime-obrigacao-campos {{ $ativo ? '' : 'hidden' }}">
                            <div class="col-span-2 md:col-span-4">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Prefixos de CNAE (opcional, separados por vírgula)</label>
                                <input type="text" name="regras[{{ $chave }}][cnae_prefixos]"
                                       value="{{ $prefixosAtuais }}" placeholder="Ex: 01, 02, 03"
                                       class="block w-full border dark:border-slate-600 rounded px-2 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Frequência</label>
                                <select name="regras[{{ $chave }}][frequencia]"
                                        class="block w-full border dark:border-slate-600 rounded px-2 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                                    @foreach(['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual', 'semanal' => 'Semanal', 'diaria' => 'Diária'] as $value => $label)
                                        <option value="{{ $value }}" {{ old("regras.$chave.frequencia", $regraAtual->frequencia ?? 'mensal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Dia padrão</label>
                                <input type="number" min="1" max="31" name="regras[{{ $chave }}][dia_vencimento]"
                                       value="{{ old("regras.$chave.dia_vencimento", $regraAtual->dia_vencimento ?? '') }}"
                                       class="block w-full border dark:border-slate-600 rounded px-2 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Departamento</label>
                                <select name="regras[{{ $chave }}][departamento_id]"
                                        class="block w-full border dark:border-slate-600 rounded px-2 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                                    <option value="">—</option>
                                    @foreach($departamentos as $departamento)
                                        <option value="{{ $departamento->id }}" {{ (string) old("regras.$chave.departamento_id", $regraAtual->departamento_id ?? '') === (string) $departamento->id ? 'selected' : '' }}>{{ $departamento->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Responsável</label>
                                <select name="regras[{{ $chave }}][responsavel_id]"
                                        class="block w-full border dark:border-slate-600 rounded px-2 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                                    <option value="">—</option>
                                    @foreach($usuarios as $usuarioOpcao)
                                        <option value="{{ $usuarioOpcao->id }}" {{ (string) old("regras.$chave.responsavel_id", $regraAtual->responsavel_id ?? '') === (string) $usuarioOpcao->id ? 'selected' : '' }}>{{ $usuarioOpcao->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-2 mt-6">
        <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700 bg-transparent dark:bg-transparent">
            Cancelar
        </button>
        <button type="submit" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
            Salvar
        </button>
    </div>
</form>

<script>
document.querySelectorAll('.regime-obrigacao-toggle').forEach(function (toggle) {
    toggle.addEventListener('change', function () {
        const campos = toggle.closest('.regime-obrigacao-item').querySelector('.regime-obrigacao-campos');
        campos.classList.toggle('hidden', ! toggle.checked);
    });
});
</script>
