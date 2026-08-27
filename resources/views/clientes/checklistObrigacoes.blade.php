@extends('layouts.internal')

@section('title', 'Checklist de obrigações — ' . $cliente->nome)

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('clientes.show', $cliente->id) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <i class="fa-solid fa-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-slate-100">Checklist de obrigações</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400">
                {{ $cliente->nome }}
                @if($cliente->regime_tributario) · <span class="font-medium">{{ $cliente->regime_tributario }}</span> @endif
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 rounded text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded text-sm">{{ session('error') }}</div>
    @endif

    {{-- CNAE --}}
    <div class="border dark:border-slate-700 rounded-lg p-4 mb-6 bg-gray-50 dark:bg-slate-800/50">
        <div class="flex items-start justify-between gap-4">
            <div class="text-sm text-gray-700 dark:text-slate-300">
                <div class="font-medium text-gray-900 dark:text-slate-100 mb-1">
                    <i class="fa-solid fa-briefcase mr-1 text-brand"></i> Atividade (CNAE)
                </div>
                @if($cliente->cnae_principal)
                    <div>Principal: <span class="font-mono">{{ $cliente->cnae_principal }}</span></div>
                    @if(!empty($cliente->cnae_secundarios))
                        <div class="text-gray-500 dark:text-slate-400">Secundários: <span class="font-mono">{{ implode(', ', $cliente->cnae_secundarios) }}</span></div>
                    @endif
                @else
                    <div class="text-gray-500 dark:text-slate-400">Nenhum CNAE registrado. As regras por atividade não são aplicadas.</div>
                @endif
            </div>
            @if(preg_replace('/\D/', '', (string) $cliente->cpfcnpj) && strlen(preg_replace('/\D/', '', (string) $cliente->cpfcnpj)) === 14)
                <form method="POST" action="{{ route('clientes.cnae.atualizar', $cliente->id) }}">
                    @csrf
                    <button type="submit" class="whitespace-nowrap px-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 bg-transparent">
                        <i class="fa-solid fa-rotate mr-1"></i> Atualizar da Receita
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if($sugestoes->isEmpty())
        <div class="text-center py-10 text-gray-500 dark:text-slate-400">
            <i class="fa-solid fa-circle-info text-2xl mb-2"></i>
            <p>Nenhuma obrigação cadastrada casa com o regime/atividade deste cliente.</p>
            <a href="{{ route('clientes.show', $cliente->id) }}" class="inline-block mt-4 text-brand hover:underline">Voltar para o cliente</a>
        </div>
    @else
        <form method="POST" action="{{ route('clientes.checklist.save', $cliente->id) }}">
            @csrf

            <div class="space-y-3">
                @foreach($sugestoes as $s)
                    @php $tid = $s['tipo_tarefa_id']; @endphp
                    <div class="border dark:border-slate-600 rounded-lg px-4 py-3 obrigacao-item">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-800 dark:text-slate-200 cursor-pointer">
                            <input type="checkbox" name="obrigacoes[{{ $tid }}][ativo]" value="1"
                                   class="obrigacao-toggle rounded border-gray-300 dark:border-slate-600 text-brand focus:ring-brand"
                                   {{ $s['ja_existe'] ? 'disabled' : 'checked' }}>
                            {{ $s['nome'] }}
                            @if($s['ja_existe'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-slate-300">já existe</span>
                            @endif
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                {{ $s['regime'] ?? 'Qualquer regime' }}
                            </span>
                        </label>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3 obrigacao-campos {{ $s['ja_existe'] ? 'hidden' : '' }}">
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Frequência</label>
                                <select name="obrigacoes[{{ $tid }}][frequencia]"
                                        class="block w-full border dark:border-slate-600 rounded px-2 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                                    @foreach(['mensal' => 'Mensal', 'trimestral' => 'Trimestral', 'semestral' => 'Semestral', 'anual' => 'Anual', 'semanal' => 'Semanal', 'diaria' => 'Diária', 'nenhuma' => 'Não se repete'] as $value => $label)
                                        <option value="{{ $value }}" {{ $s['frequencia'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Dia de vencimento</label>
                                <input type="number" min="1" max="31" name="obrigacoes[{{ $tid }}][dia_vencimento]"
                                       value="{{ $s['dia_vencimento'] }}"
                                       class="block w-full border dark:border-slate-600 rounded px-2 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Departamento</label>
                                <select name="obrigacoes[{{ $tid }}][departamento_id]"
                                        class="block w-full border dark:border-slate-600 rounded px-2 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                                    <option value="">—</option>
                                    @foreach($departamentos as $departamento)
                                        <option value="{{ $departamento->id }}" {{ (string) $s['departamento_id'] === (string) $departamento->id ? 'selected' : '' }}>{{ $departamento->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Responsável</label>
                                <select name="obrigacoes[{{ $tid }}][responsavel_id]"
                                        class="block w-full border dark:border-slate-600 rounded px-2 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200">
                                    <option value="">—</option>
                                    @foreach($usuarios as $usuarioOpcao)
                                        <option value="{{ $usuarioOpcao->id }}" {{ (string) $s['responsavel_id'] === (string) $usuarioOpcao->id ? 'selected' : '' }}>{{ $usuarioOpcao->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <a href="{{ route('clientes.show', $cliente->id) }}" class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-700">
                    Pular
                </a>
                <button type="submit" class="px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80">
                    Gerar tarefas selecionadas
                </button>
            </div>
        </form>
    @endif
</div>

<script>
document.querySelectorAll('.obrigacao-toggle').forEach(function (toggle) {
    toggle.addEventListener('change', function () {
        const campos = toggle.closest('.obrigacao-item').querySelector('.obrigacao-campos');
        campos.classList.toggle('hidden', ! toggle.checked);
    });
});
</script>
@endsection
