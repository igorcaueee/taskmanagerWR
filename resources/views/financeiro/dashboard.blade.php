@extends('layouts.internal')

@section('title', 'Dashboard Financeiro — WR Assessoria')

@section('content')
<div class="w-full mx-auto py-6 px-4">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                <i class="fa-solid fa-chart-pie text-blue-600"></i> Dashboard Financeiro
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Visão consolidada das empresas conectadas à Conta Azul</p>
        </div>

        {{-- Filtro por empresa --}}
        <form method="GET" action="{{ route('financeiro.dashboard') }}" class="flex items-center gap-2">
            <select name="cliente_id"
                    onchange="this.form.submit()"
                    class="text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
                <option value="">Todas as empresas</option>
                @foreach ($empresas as $emp)
                    <option value="{{ $emp->id }}" {{ $clienteId == $emp->id ? 'selected' : '' }}>
                        {{ $emp->nome }}
                        @if (!$emp->conta_azul_conectada) (desconectado) @endif
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Cards de totais --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Saldo total</p>
            <p class="text-2xl font-bold {{ $saldoTotal >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} mt-1">
                R$ {{ number_format($saldoTotal, 2, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Recebido</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                R$ {{ number_format($totalCreditos, 2, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Pago</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                R$ {{ number_format($totalDebitos, 2, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">A Receber</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1">
                R$ {{ number_format($contasAReceber, 2, ',', '.') }}
            </p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">A Pagar</p>
            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">
                R$ {{ number_format($contasAPagar, 2, ',', '.') }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

        {{-- Gráfico Fluxo de Caixa --}}
        <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">
                <i class="fa-solid fa-chart-line text-blue-500"></i> Fluxo de Caixa — últimos 6 meses
            </h2>
            <canvas id="fluxoCaixaChart" height="120"></canvas>
        </div>

        {{-- Contas financeiras --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 mb-3">
                <i class="fa-solid fa-building-columns text-blue-500"></i> Contas Financeiras
            </h2>
            @forelse ($contas as $conta)
                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-slate-700 last:border-0">
                    <div>
                        <p class="text-sm font-medium text-gray-800 dark:text-slate-200">{{ $conta->nome }}</p>
                        <p class="text-xs text-gray-400">{{ $conta->tipo ?? 'Conta' }}</p>
                    </div>
                    <span class="text-sm font-semibold {{ $conta->saldo_atual >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        R$ {{ number_format($conta->saldo_atual, 2, ',', '.') }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">Nenhuma conta financeira sincronizada.</p>
            @endforelse
            <a href="{{ route('financeiro.contas', $clienteId ? ['cliente_id' => $clienteId] : []) }}"
               class="text-xs text-blue-600 dark:text-blue-400 hover:underline mt-2 inline-block">
               Ver todas →
            </a>
        </div>
    </div>

    {{-- Últimos lançamentos --}}
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200">
                <i class="fa-solid fa-list text-blue-500"></i> Últimos Lançamentos
            </h2>
            <a href="{{ route('financeiro.lancamentos', $clienteId ? ['cliente_id' => $clienteId] : []) }}"
               class="text-xs text-blue-600 dark:text-blue-400 hover:underline">Ver todos →</a>
        </div>

        @if ($lancamentos->isEmpty())
            <p class="text-sm text-gray-400 text-center py-6">Nenhum lançamento encontrado. Conecte uma empresa à Conta Azul e sincronize.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-slate-700">
                            <th class="text-left pb-2">Empresa</th>
                            <th class="text-left pb-2">Descrição</th>
                            <th class="text-left pb-2">Vencimento</th>
                            <th class="text-left pb-2">Tipo</th>
                            <th class="text-right pb-2">Valor</th>
                            <th class="text-left pb-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lancamentos as $l)
                        <tr class="border-b border-gray-100 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                            <td class="py-2 text-gray-600 dark:text-gray-400 text-xs">{{ $l->cliente->nome ?? '—' }}</td>
                            <td class="py-2 text-gray-900 dark:text-slate-100">{{ Str::limit($l->descricao ?? '—', 40) }}</td>
                            <td class="py-2 text-gray-600 dark:text-gray-400">{{ $l->data_vencimento?->format('d/m/Y') ?? '—' }}</td>
                            <td class="py-2">
                                @if ($l->tipo === 'credito')
                                    <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 px-1.5 py-0.5 rounded">Crédito</span>
                                @else
                                    <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 px-1.5 py-0.5 rounded">Débito</span>
                                @endif
                            </td>
                            <td class="py-2 text-right font-semibold {{ $l->tipo === 'credito' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                R$ {{ number_format($l->valor, 2, ',', '.') }}
                            </td>
                            <td class="py-2">
                                @php
                                    $sc = match($l->status) {
                                        'pago'      => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                        'cancelado' => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                        'atrasado'  => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                                        default     => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                    };
                                @endphp
                                <span class="text-xs px-1.5 py-0.5 rounded {{ $sc }}">{{ ucfirst($l->status) }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script type="module">
const labels   = @json($meses->pluck('label'));
const creditos = @json($meses->pluck('creditos'));
const debitos  = @json($meses->pluck('debitos'));
const isDark   = document.getElementById('html-root')?.classList.contains('dark');

const ctx = document.getElementById('fluxoCaixaChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Créditos',
                    data: creditos,
                    backgroundColor: 'rgba(22,163,74,0.7)',
                    borderRadius: 4,
                },
                {
                    label: 'Débitos',
                    data: debitos,
                    backgroundColor: 'rgba(220,38,38,0.7)',
                    borderRadius: 4,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: isDark ? '#94a3b8' : '#374151', font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ` R$ ${ctx.raw.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`,
                    },
                },
            },
            scales: {
                x: { ticks: { color: isDark ? '#94a3b8' : '#6b7280' }, grid: { color: isDark ? '#334155' : '#f3f4f6' } },
                y: {
                    ticks: {
                        color: isDark ? '#94a3b8' : '#6b7280',
                        callback: v => 'R$ ' + v.toLocaleString('pt-BR'),
                    },
                    grid: { color: isDark ? '#334155' : '#f3f4f6' },
                },
            },
        },
    });
}
</script>
@endpush
