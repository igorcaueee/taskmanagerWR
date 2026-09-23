@extends('layouts.internal')

@section('title', 'ICMS-ST — Editar Regra '.$regra->uf.'/'.$regra->cest.' — WR Assessoria')

@section('content')
    <div class="max-w-2xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-pen"></i> Editar regra {{ $regra->uf }}/{{ $regra->cest }}</h1>
            <a href="{{ route('icms-st.regras.index', ['uf' => $regra->uf]) }}" class="text-sm text-[#0084aa] no-underline"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        </div>

        @if ($regra->editado_por)
            <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">Última edição: {{ $regra->editado_por }} em {{ $regra->editado_em?->format('d/m/Y H:i') }}</p>
        @endif

        <form method="POST" action="{{ route('icms-st.regras.atualizar', $regra) }}" class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Segmento</label>
                <input type="text" name="segmento" value="{{ old('segmento', $regra->segmento) }}" required class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Descrição</label>
                <textarea name="descricao" required rows="2" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">{{ old('descricao', $regra->descricao) }}</textarea>
            </div>

            @if ($regra->uf === 'RS')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">MVA interestadual 12%</label>
                        <input type="number" step="0.01" name="mva_12_pct" value="{{ old('mva_12_pct', $regra->mva_12_pct) }}" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">MVA interestadual 4%</label>
                        <input type="number" step="0.01" name="mva_4_pct" value="{{ old('mva_4_pct', $regra->mva_4_pct) }}" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    </div>
                </div>
            @else
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">MVA (única)</label>
                    <input type="number" step="0.01" name="mva_pct" value="{{ old('mva_pct', $regra->mva_pct) }}" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Alíquota interna (%)</label>
                <input type="number" step="0.01" name="aliquota_interna_pct" value="{{ old('aliquota_interna_pct', $regra->aliquota_interna_pct) }}" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                @if ($regra->situacao === 'revogada')
                    <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Regra revogada — sem alíquota aplicável (não há mais ST).</p>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Adicional</label>
                    <select name="adicional_tipo" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                        <option value="nenhum" @selected(old('adicional_tipo', $regra->adicional_tipo) === 'nenhum')>Nenhum</option>
                        <option value="AMPARA_RS" @selected(old('adicional_tipo', $regra->adicional_tipo) === 'AMPARA_RS')>AMPARA/RS</option>
                        <option value="FEM_MG" @selected(old('adicional_tipo', $regra->adicional_tipo) === 'FEM_MG')>FEM/MG</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Adicional (%)</label>
                    <input type="number" step="0.01" name="adicional_pct" value="{{ old('adicional_pct', $regra->adicional_pct) }}" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                </div>
            </div>

            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                    <input type="checkbox" name="adicional_confirmado" value="1" @checked(old('adicional_confirmado', $regra->adicional_confirmado))>
                    Adicional confirmado (soma automaticamente)
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-slate-300">
                    <input type="checkbox" name="aliquota_confirmada" value="1" @checked(old('aliquota_confirmada', $regra->aliquota_confirmada))>
                    Alíquota interna confirmada (senão bloqueia o cálculo)
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Fonte legal</label>
                <textarea name="fonte_legal" rows="2" class="w-full rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">{{ old('fonte_legal', $regra->fonte_legal) }}</textarea>
            </div>

            <button type="submit" class="py-2 px-4 bg-[#0084aa] hover:bg-[#006e8e] text-white text-sm font-semibold rounded-lg transition-colors">Salvar</button>
        </form>

        <div class="mt-8">
            <h2 class="text-sm font-semibold text-gray-600 dark:text-slate-400 mb-3">Histórico de alterações</h2>
            @if ($historico->isEmpty())
                <p class="text-sm text-gray-500 dark:text-slate-400">Nenhuma edição registrada ainda — esta regra está como veio do seeder.</p>
            @else
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-600 dark:text-slate-400">
                            <tr>
                                <th class="px-3 py-2 text-left">Quando</th>
                                <th class="px-3 py-2 text-left">Quem</th>
                                <th class="px-3 py-2 text-left">Campo</th>
                                <th class="px-3 py-2 text-left">De</th>
                                <th class="px-3 py-2 text-left">Para</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($historico as $h)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600 dark:text-slate-400 whitespace-nowrap">{{ $h->editado_em->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-slate-400">{{ $h->editado_por ?? '—' }}</td>
                                    <td class="px-3 py-2 text-gray-800 dark:text-slate-200">{{ $h->campo }}</td>
                                    <td class="px-3 py-2 text-red-600 dark:text-red-400 max-w-xs whitespace-normal">{{ $h->valor_anterior ?? '—' }}</td>
                                    <td class="px-3 py-2 text-green-600 dark:text-green-400 max-w-xs whitespace-normal">{{ $h->valor_novo ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
