@extends('layouts.internal')

@section('title', 'ICMS-ST — Pendências — WR Assessoria')

@php
    $labelStatus = [
        'pendente_cest' => 'CEST ausente ou não localizado',
        'pendente_aliquota' => 'Alíquota interna não confirmada',
        'pendente_reducao_base' => 'Redução de base não informada',
        'pendente_fem' => 'Adicional (AMPARA/FEM) não confirmado',
        'uf_nao_suportada' => 'UF de destino sem motor cadastrado',
    ];
@endphp

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-list-check"></i> Fila de Pendências — ICMS-ST</h1>
                <p class="text-gray-700 dark:text-gray-300">Todos os itens de todos os clientes que ainda precisam de validação manual (últimos 1000).</p>
            </div>
            <a href="{{ route('icms-st.index') }}" class="text-sm text-[#0084aa] no-underline"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        </div>

        @forelse ($pendencias as $status => $itens)
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-slate-100 mb-2">
                    {{ $labelStatus[$status] ?? $status }}
                    <span class="ml-2 px-2 py-0.5 rounded-full bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-xs font-semibold align-middle">{{ $itens->count() }}</span>
                </h2>
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-600 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">NF-e</th>
                                <th class="px-4 py-3 text-left">Item / Produto</th>
                                <th class="px-4 py-3 text-left">Detalhe</th>
                                <th class="px-4 py-3 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                            @foreach ($itens as $item)
                                <tr>
                                    <td class="px-4 py-3 text-gray-800 dark:text-slate-200">{{ $item->cliente?->nome }}</td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $item->nfe_numero }}</td>
                                    <td class="px-4 py-3 text-gray-800 dark:text-slate-200">{{ $item->nfe_item }} — {{ $item->produto }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-slate-400 max-w-md whitespace-normal text-xs">{{ $item->status_detalhe }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('icms-st.detalhe', [$item->chave_acesso, 'voltar' => route('icms-st.pendencias')]) }}" class="text-[#0084aa] hover:text-[#006e8e] text-xs font-semibold no-underline">Resolver <i class="fa-solid fa-arrow-right"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="px-4 py-6 text-center text-gray-500 dark:text-slate-400 bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700">
                Nenhuma pendência no momento. 🎉
            </div>
        @endforelse
    </div>
@endsection
