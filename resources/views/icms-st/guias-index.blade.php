@extends('layouts.internal')

@section('title', 'ICMS-ST — Guias — WR Assessoria')

@php
    $badgeStatus = [
        'RASCUNHO' => 'bg-gray-100 dark:bg-slate-700 text-gray-600 dark:text-slate-300',
        'PRONTA_PARA_EMISSAO' => 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400',
        'EMITIDA' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400',
        'PAGA' => 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400',
    ];
@endphp

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-file-invoice-dollar"></i> Guias ICMS-ST</h1>
                <p class="text-gray-700 dark:text-gray-300">Histórico de guias GNRE/DAE preparadas — por cliente e status.</p>
            </div>
            <a href="{{ route('icms-st.index') }}" class="text-sm text-[#0084aa] no-underline"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
        </div>

        <form method="GET" action="{{ route('icms-st.guias.index') }}" class="flex flex-wrap items-end gap-3 mb-6">
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Cliente</label>
                <select name="cliente_id" class="w-56 rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach ($clientes as $c)
                        <option value="{{ $c->id }}" @selected(request('cliente_id') == $c->id)>{{ $c->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 dark:text-slate-400 mb-1">Status</label>
                <select name="status" class="rounded-lg border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-gray-800 dark:text-slate-200 px-3 py-2 text-sm">
                    <option value="">Todos</option>
                    @foreach (['RASCUNHO', 'PRONTA_PARA_EMISSAO', 'EMITIDA', 'PAGA'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="py-2 px-4 bg-white dark:bg-slate-700 hover:bg-gray-50 dark:hover:bg-slate-600 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 text-sm font-semibold rounded-lg transition-colors">Filtrar</button>
        </form>

        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-700/50 text-gray-600 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Cliente</th>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-left">Vencimento</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse ($guias as $guia)
                        <tr>
                            <td class="px-4 py-3 text-gray-800 dark:text-slate-200">{{ $guia->cliente?->nome }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $guia->tipo === 'GNRE_RS' ? 'GNRE (RS)' : 'DAE (MG)' }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-400">{{ $guia->data_vencimento?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-slate-100">R$ {{ number_format($guia->valor_total, 2, ',', '.') }}</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeStatus[$guia->status] }}">{{ $guia->status }}</span></td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('icms-st.guias.form', $guia->chave_acesso) }}" class="text-[#0084aa] hover:text-[#006e8e] text-xs font-semibold no-underline">Abrir <i class="fa-solid fa-arrow-right"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500 dark:text-slate-400">Nenhuma guia preparada ainda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $guias->links() }}</div>
    </div>
@endsection
