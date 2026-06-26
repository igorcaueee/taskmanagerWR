@extends('layouts.internal')

@section('title', 'Contas Financeiras — WR Assessoria')

@section('content')
<div class="w-full mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
            <i class="fa-solid fa-building-columns text-blue-600"></i> Contas Financeiras
        </h1>
        <a href="{{ route('financeiro.dashboard') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
            ← Dashboard
        </a>
    </div>

    <form method="GET" action="{{ route('financeiro.contas') }}" class="mb-4">
        <select name="cliente_id" onchange="this.form.submit()"
                class="text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
            <option value="">Todas as empresas</option>
            @foreach ($empresas as $emp)
                <option value="{{ $emp->id }}" {{ $clienteId == $emp->id ? 'selected' : '' }}>{{ $emp->nome }}</option>
            @endforeach
        </select>
    </form>

    <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 overflow-hidden">
        @if ($contas->isEmpty())
            <p class="text-sm text-gray-400 text-center py-12">Nenhuma conta financeira. Conecte e sincronize uma empresa.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-700/50">
                    <tr class="text-xs text-gray-500 dark:text-gray-400">
                        <th class="text-left px-4 py-3">Empresa</th>
                        <th class="text-left px-4 py-3">Conta</th>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-right px-4 py-3">Saldo Atual</th>
                        <th class="text-left px-4 py-3">Atualizado em</th>
                        <th class="text-left px-4 py-3">Ativa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700/50">
                    @foreach ($contas as $c)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30">
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $c->cliente->nome ?? '—' }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-slate-100">{{ $c->nome }}</td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $c->tipo ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $c->saldo_atual >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            R$ {{ number_format($c->saldo_atual, 2, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ $c->atualizado_em?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($c->ativa)
                                <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 px-2 py-0.5 rounded">Sim</span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded">Não</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
