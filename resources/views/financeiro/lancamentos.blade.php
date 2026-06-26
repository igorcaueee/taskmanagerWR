@extends('layouts.internal')

@section('title', 'Lançamentos Financeiros — WR Assessoria')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
            <i class="fa-solid fa-file-invoice-dollar text-blue-600"></i> Lançamentos Financeiros
        </h1>
        <a href="{{ route('financeiro.dashboard') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
            ← Dashboard
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('financeiro.lancamentos') }}"
          class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4 mb-4 flex flex-wrap gap-3 items-end">

        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Empresa</label>
            <select name="cliente_id"
                    class="text-sm border border-gray-300 dark:border-slate-600 rounded px-2 py-1.5 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
                <option value="">Todas</option>
                @foreach ($empresas as $emp)
                    <option value="{{ $emp->id }}" {{ $clienteId == $emp->id ? 'selected' : '' }}>{{ $emp->nome }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tipo</label>
            <select name="tipo"
                    class="text-sm border border-gray-300 dark:border-slate-600 rounded px-2 py-1.5 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
                <option value="">Todos</option>
                <option value="credito" {{ $tipo === 'credito' ? 'selected' : '' }}>Crédito</option>
                <option value="debito"  {{ $tipo === 'debito'  ? 'selected' : '' }}>Débito</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Status</label>
            <select name="status"
                    class="text-sm border border-gray-300 dark:border-slate-600 rounded px-2 py-1.5 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
                <option value="">Todos</option>
                <option value="pendente"  {{ $status === 'pendente'  ? 'selected' : '' }}>Pendente</option>
                <option value="pago"      {{ $status === 'pago'      ? 'selected' : '' }}>Pago</option>
                <option value="atrasado"  {{ $status === 'atrasado'  ? 'selected' : '' }}>Atrasado</option>
                <option value="cancelado" {{ $status === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Vencimento de</label>
            <input type="date" name="de" value="{{ $de }}"
                   class="text-sm border border-gray-300 dark:border-slate-600 rounded px-2 py-1.5 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
        </div>

        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">até</label>
            <input type="date" name="ate" value="{{ $ate }}"
                   class="text-sm border border-gray-300 dark:border-slate-600 rounded px-2 py-1.5 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
        </div>

        <button type="submit"
                class="px-4 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 border-0">
            <i class="fa-solid fa-filter"></i> Filtrar
        </button>

        <a href="{{ route('financeiro.lancamentos') }}"
           class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white no-underline">
            Limpar
        </a>
    </form>

    {{-- Tabela --}}
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 overflow-hidden">
        @if ($lancamentos->isEmpty())
            <p class="text-sm text-gray-400 text-center py-12">Nenhum lançamento encontrado para os filtros selecionados.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-slate-700/50">
                        <tr class="text-xs text-gray-500 dark:text-gray-400">
                            <th class="text-left px-4 py-3">Empresa</th>
                            <th class="text-left px-4 py-3">Descrição</th>
                            <th class="text-left px-4 py-3">Categoria</th>
                            <th class="text-left px-4 py-3">Vencimento</th>
                            <th class="text-left px-4 py-3">Pagamento</th>
                            <th class="text-left px-4 py-3">Tipo</th>
                            <th class="text-right px-4 py-3">Valor</th>
                            <th class="text-left px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50">
                        @foreach ($lancamentos as $l)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                {{ $l->cliente->nome ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-gray-900 dark:text-slate-100 max-w-xs truncate">
                                {{ $l->descricao ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $l->categoria->nome ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                {{ $l->data_vencimento?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap text-gray-600 dark:text-gray-400">
                                {{ $l->data_pagamento?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-2.5">
                                @if ($l->tipo === 'credito')
                                    <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 px-1.5 py-0.5 rounded">Crédito</span>
                                @else
                                    <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 px-1.5 py-0.5 rounded">Débito</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right font-semibold whitespace-nowrap {{ $l->tipo === 'credito' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                R$ {{ number_format($l->valor, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-2.5">
                                @php
                                    $sc = match($l->status) {
                                        'pago'      => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                        'cancelado' => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                        'atrasado'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                        default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                    };
                                @endphp
                                <span class="text-xs px-1.5 py-0.5 rounded {{ $sc }}">{{ ucfirst($l->status) }}</span>
                                @if ($l->conciliado)
                                    <i class="fa-solid fa-check-double text-blue-500 text-xs ml-1" title="Conciliado"></i>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
                {{ $lancamentos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
