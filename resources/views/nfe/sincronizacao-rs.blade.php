@extends('layouts.internal')

@section('title', 'Acompanhamento da Sincronização Fiscal (SEFAZ-RS)')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    {{-- Cabeçalho --}}
    <div class="mb-6">
        <a href="{{ route('nfe.index') }}" title="Voltar" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-brand hover:bg-brand/10 no-underline"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 flex items-center gap-2 mt-1">
            <i class="fa-solid fa-list-check text-[#0084aa]"></i>
            Acompanhamento da Sincronização Fiscal (SEFAZ-RS)
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Log de execução do cron <code class="text-xs bg-gray-100 dark:bg-slate-700 rounded px-1 py-0.5">fiscal:sincronizar-notas-rs</code>,
            que roda diariamente às 18:30 (parando às 07:00) buscando NF-e, NFC-e e CT-e via SEFAZ-RS dos clientes com a flag "Importar notas" ativada.
        </p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded shadow overflow-x-auto">
        <form method="GET" action="{{ route('nfe.rs.sincronizacao.tela') }}" id="form-filtros-sincronizacao-rs"
              class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Cliente</label>
                <select name="cliente_id" onchange="document.getElementById('form-filtros-sincronizacao-rs').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-56">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected((string) request('cliente_id') === (string) $cliente->id)>{{ $cliente->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Status</label>
                <select name="status" onchange="document.getElementById('form-filtros-sincronizacao-rs').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos</option>
                    <option value="sucesso" @selected(request('status') === 'sucesso')>Sucesso</option>
                    <option value="erro" @selected(request('status') === 'erro')>Erro</option>
                </select>
            </div>
        </form>

        <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
            <thead class="bg-gray-50 dark:bg-slate-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fase</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Erro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Executado em</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                @forelse($sincronizacoes as $s)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $s->cliente->nome ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap uppercase">{{ $s->fase }}</td>
                        <td class="px-6 py-4 text-sm whitespace-nowrap">
                            @php
                                $badges = [
                                    'sucesso' => 'bg-green-100 text-green-800',
                                    'erro' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="px-2 py-1 rounded text-xs font-medium {{ $badges[$s->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($s->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-red-700 dark:text-red-400">{{ $s->mensagem_erro ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $s->executado_em?->format('d/m/Y H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Nenhuma sincronização registrada ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4">
            {{ $sincronizacoes->links() }}
        </div>
    </div>
</div>
@endsection
