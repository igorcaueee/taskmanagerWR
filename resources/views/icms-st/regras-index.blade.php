@extends('layouts.internal')

@section('title', 'ICMS-ST — Regras Cadastradas — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-table-list"></i> Regras Cadastradas — ICMS-ST</h1>
                <p class="text-gray-700 dark:text-gray-300">MVA, alíquota interna e adicional por UF+CEST — fonte de verdade do motor de cálculo.</p>
            </div>
            <a href="{{ route('icms-st.index') }}" class="text-sm text-[#0084aa] no-underline"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        </div>

        <div class="flex gap-2 mb-6">
            <a href="{{ route('icms-st.regras.index') }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold no-underline {{ ! $uf ? 'bg-[#0084aa] text-white' : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 border border-gray-300 dark:border-slate-600' }}">Todas</a>
            <a href="{{ route('icms-st.regras.index', ['uf' => 'RS']) }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold no-underline {{ $uf === 'RS' ? 'bg-[#0084aa] text-white' : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 border border-gray-300 dark:border-slate-600' }}">RS</a>
            <a href="{{ route('icms-st.regras.index', ['uf' => 'MG']) }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold no-underline {{ $uf === 'MG' ? 'bg-[#0084aa] text-white' : 'bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 border border-gray-300 dark:border-slate-600' }}">MG</a>
        </div>

        @if (session('status'))
            <div class="mb-4 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-sm">{{ session('status') }}</div>
        @endif

        @forelse ($regras as $segmento => $itens)
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-slate-100 mb-2">{{ $segmento }}</h2>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-x-auto">
                    <table class="w-full text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-600 dark:text-slate-400">
                            <tr>
                                <th class="px-3 py-3 text-left">UF</th>
                                <th class="px-3 py-3 text-left">CEST</th>
                                <th class="px-3 py-3 text-left">Situação</th>
                                <th class="px-3 py-3 text-left">Descrição</th>
                                <th class="px-3 py-3 text-right">MVA</th>
                                <th class="px-3 py-3 text-right">Alíq. interna</th>
                                <th class="px-3 py-3 text-left">Adicional</th>
                                <th class="px-3 py-3 text-left">Confirmações</th>
                                <th class="px-3 py-3 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($itens as $regra)
                                <tr>
                                    <td class="px-3 py-3 text-gray-800 dark:text-slate-200">{{ $regra->uf }}</td>
                                    <td class="px-3 py-3 text-gray-800 dark:text-slate-200">{{ $regra->cest }}</td>
                                    <td class="px-3 py-3">
                                        @if ($regra->situacao === 'revogada')
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300">Revogada{{ $regra->revogada_desde ? ' desde '.$regra->revogada_desde->format('d/m/Y') : '' }}</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400">Vigente</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-gray-600 dark:text-slate-400 max-w-sm whitespace-normal text-xs">{{ $regra->descricao }}</td>
                                    <td class="px-3 py-3 text-right text-gray-800 dark:text-slate-200">
                                        {{ $regra->mva_pct !== null ? number_format($regra->mva_pct, 2, ',', '.').'%' : (($regra->mva_12_pct !== null || $regra->mva_4_pct !== null) ? number_format($regra->mva_12_pct, 2, ',', '.').'% / '.number_format($regra->mva_4_pct, 2, ',', '.').'%' : '—') }}
                                        @if ($regra->mva_pct !== null && ($regra->mva_12_pct !== null || $regra->mva_4_pct !== null))
                                            <div class="text-[10px] text-gray-500 dark:text-slate-400">12%: {{ number_format($regra->mva_12_pct, 2, ',', '.') }}% · 4%: {{ number_format($regra->mva_4_pct, 2, ',', '.') }}%</div>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right text-gray-800 dark:text-slate-200">{{ $regra->aliquota_interna_pct !== null ? number_format($regra->aliquota_interna_pct, 2, ',', '.').'%' : '—' }}</td>
                                    <td class="px-3 py-3 text-gray-600 dark:text-slate-400">
                                        @if ($regra->adicional_tipo !== 'nenhum')
                                            {{ $regra->adicional_tipo }} ({{ number_format($regra->adicional_pct, 2, ',', '.') }}%)
                                            @unless ($regra->adicional_confirmado)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400">pendente</span>
                                            @endunless
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        @unless ($regra->aliquota_confirmada)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400">alíquota não confirmada</span>
                                        @endunless
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <a href="{{ route('icms-st.regras.editar', $regra) }}" class="text-[#0084aa] hover:text-[#006e8e] text-xs font-semibold no-underline"><i class="fa-solid fa-pen"></i> Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="px-4 py-6 text-center text-gray-500 dark:text-slate-400 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                Nenhuma regra cadastrada — rode o seeder StRegrasCestSeeder.
            </div>
        @endforelse
    </div>
@endsection
