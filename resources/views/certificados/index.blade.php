@extends('layouts.internal')

@section('title', 'Certificados Digitais — WR Assessoria')

@php
    $situacaoColors = [
        'OK'         => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'PENDENTE'   => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        'BONIFICADO' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'CANCELADO'  => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    ];

    $badgeVencimento = function ($data) {
        if (! $data) {
            return ['—', 'text-gray-400 dark:text-slate-500'];
        }
        $txt = $data->format('d/m/Y');
        if ($data->isPast()) {
            return [$txt.' · vencido', 'text-red-600 dark:text-red-400 font-medium'];
        }
        if (now()->diffInDays($data) <= 30) {
            return [$txt.' · vence em breve', 'text-yellow-700 dark:text-yellow-400 font-medium'];
        }
        return [$txt, 'text-gray-700 dark:text-slate-300'];
    };
@endphp

@section('content')
<div class="{{ $aba === 'emissoes' ? 'max-w-full' : 'max-w-7xl' }} mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                <i class="fa-solid fa-id-card"></i> Certificados Digitais
            </h1>
            <p class="text-gray-700 dark:text-gray-300">Emissões de certificados dos clientes e controle de vencimentos.</p>
        </div>
        @if($aba === 'emissoes')
        <button type="button"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80 text-sm"
                data-modal-url="{{ route('certificados.form') }}" data-modal-width="max-w-3xl">
            <i class="fa-solid fa-plus"></i> Nova Emissão
        </button>
        @endif
    </div>

    @if(session('success') || session('error'))
    @push('scripts')
    <script type="module">
        @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Sucesso', text: @json(session('success')), confirmButtonColor: '#2563eb' });
        @endif
        @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Erro', text: @json(session('error')), confirmButtonColor: '#dc2626' });
        @endif
    </script>
    @endpush
    @endif

    {{-- Abas --}}
    <div class="flex flex-wrap gap-2 border-b border-gray-200 dark:border-slate-700 mb-6">
        <a href="{{ route('certificados.index', ['aba' => 'emissoes']) }}"
           class="px-4 py-2 text-sm font-medium border-b-2 no-underline {{ $aba === 'emissoes' ? 'border-brand text-brand' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-brand' }}">
            <i class="fa-solid fa-file-circle-plus"></i> Emissões
        </a>
        <a href="{{ route('certificados.index', ['aba' => 'vencimentos']) }}"
           class="px-4 py-2 text-sm font-medium border-b-2 no-underline {{ $aba === 'vencimentos' ? 'border-brand text-brand' : 'border-transparent text-gray-500 dark:text-slate-400 hover:text-brand' }}">
            <i class="fa-solid fa-calendar-xmark"></i> Vencimentos por cliente
            @if($totVencidos + $totVence30 > 0)
                <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs rounded-full bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400">{{ $totVencidos + $totVence30 }}</span>
            @endif
        </a>
    </div>

    @if($aba === 'emissoes')
        {{-- ─── ABA EMISSÕES ─────────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-slate-800 rounded shadow overflow-x-auto">
            <form method="GET" action="{{ route('certificados.index') }}" id="form-filtros-cert"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <input type="hidden" name="aba" value="emissoes">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Cliente, pedido, documento..."
                           onchange="this.form.submit()"
                           class="pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand w-64">
                </div>
                <select name="modelo" onchange="this.form.submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos os modelos</option>
                    @foreach($modelos as $v => $label)
                        <option value="{{ $v }}" @selected(request('modelo') === $v)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="situacao" onchange="this.form.submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todas as situações</option>
                    @foreach($situacoes as $s)
                        <option value="{{ $s }}" @selected(request('situacao') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                <select name="vencimento_status" onchange="this.form.submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Qualquer vencimento</option>
                    <option value="vencido" @selected(request('vencimento_status') === 'vencido')>Vencidos</option>
                    <option value="vence30" @selected(request('vencimento_status') === 'vence30')>Vencem em 30 dias</option>
                </select>
                @if(request()->hasAny(['busca', 'modelo', 'situacao', 'vencimento_status']))
                    <a href="{{ route('certificados.index', ['aba' => 'emissoes']) }}" class="text-sm text-gray-500 dark:text-slate-400 hover:text-brand self-center no-underline">Limpar</a>
                @endif
            </form>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        @foreach(['Data', 'Cliente', 'Cliente WR', 'Modelo', 'Nº Pedido', 'Forma', 'Valor', 'Pagamento', 'Situação', 'Certificadora', 'Vencimento', ''] as $th)
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($emissoes as $e)
                        @php [$vencTxt, $vencCls] = $badgeVencimento($e->vencimento); @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40">
                            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300">{{ $e->data_emissao?->format('d/m/Y') }}</td>
                            <td class="px-3 py-3 text-gray-800 dark:text-slate-100 whitespace-nowrap">
                                @if($e->cliente)
                                    <a href="{{ route('clientes.show', $e->cliente_id) }}" class="hover:text-brand no-underline text-gray-800 dark:text-slate-100">{{ $e->cliente_nome }}</a>
                                @else
                                    {{ $e->cliente_nome }}
                                @endif
                                @if($e->cliente_documento)
                                    <span class="block text-xs text-gray-400 dark:text-slate-500 whitespace-nowrap">{{ $e->cliente_documento }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($e->cliente_wr)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><i class="fa-solid fa-check"></i> Sim</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-slate-400">Não</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300">{{ $modelos[$e->modelo] ?? $e->modelo }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300">{{ $e->numero_pedido ?: '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300">{{ $formas[$e->forma_emissao] ?? $e->forma_emissao }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300">{{ $e->valor !== null ? 'R$ '.number_format($e->valor, 2, ',', '.') : '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300">{{ $e->pagamento ?: '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $situacaoColors[$e->situacao] ?? 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-300' }}">{{ $e->situacao }}</span>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300">{{ $e->certificadora }}</td>
                            <td class="px-3 py-3 whitespace-nowrap {{ $vencCls }}">{{ $vencTxt }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-right">
                                <button type="button" data-modal-url="{{ route('certificados.form.edit', $e->id) }}" data-modal-width="max-w-3xl"
                                        class="text-gray-400 hover:text-brand bg-transparent border-0 p-1" title="Editar">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="POST" action="{{ route('certificados.destroy', $e->id) }}" class="inline js-excluir">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-500 bg-transparent border-0 p-1" title="Excluir">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-slate-600">Nenhuma emissão registrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($emissoes->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $emissoes->links() }}</div>
            @endif
        </div>
    @else
        {{-- ─── ABA VENCIMENTOS POR CLIENTE ──────────────────────────────── --}}
        @if($totVencidos + $totVence30 > 0)
            <div class="mb-4 px-4 py-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded text-sm text-yellow-800 dark:text-yellow-300">
                <i class="fa-solid fa-triangle-exclamation"></i>
                {{ $totVencidos }} certificado(s) vencido(s) e {{ $totVence30 }} vencendo nos próximos 30 dias.
            </div>
        @endif

        <div class="bg-white dark:bg-slate-800 rounded shadow overflow-x-auto">
            <form method="GET" action="{{ route('certificados.index') }}"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <input type="hidden" name="aba" value="vencimentos">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    <input type="text" name="busca_cliente" value="{{ request('busca_cliente') }}" placeholder="Buscar cliente..."
                           onchange="this.form.submit()"
                           class="pl-8 pr-3 py-1.5 text-sm border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand w-64">
                </div>
                <select name="filtro_vencimento" onchange="this.form.submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos</option>
                    <option value="vencido" @selected(request('filtro_vencimento') === 'vencido')>Vencidos</option>
                    <option value="vence30" @selected(request('filtro_vencimento') === 'vence30')>Vencem em 30 dias</option>
                    <option value="sem" @selected(request('filtro_vencimento') === 'sem')>Sem vencimento cadastrado</option>
                </select>
            </form>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        @foreach(['Cliente', 'CPF/CNPJ', 'Tipo', 'Vencimento do certificado', 'Situação', ''] as $th)
                            <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider whitespace-nowrap">{{ $th }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($clientes as $c)
                        @php [$vencTxt, $vencCls] = $badgeVencimento($c->vencimento_certificado); @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40">
                            <td class="px-3 py-3 text-gray-800 dark:text-slate-100">
                                <a href="{{ route('clientes.show', $c->id) }}" class="hover:text-brand no-underline text-gray-800 dark:text-slate-100">{{ $c->nome }}</a>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300">{{ $c->cpfcnpj ?: '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-gray-700 dark:text-slate-300">{{ $c->tipo ?: '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap {{ $vencCls }}">{{ $vencTxt }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-slate-400">{{ $c->status }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-right">
                                <form method="POST" action="{{ route('certificados.cliente.vencimento', $c->id) }}" class="inline-flex items-center gap-2">
                                    @csrf @method('PATCH')
                                    <input type="date" name="vencimento_certificado"
                                           value="{{ $c->vencimento_certificado?->format('Y-m-d') }}"
                                           class="border border-gray-300 dark:border-slate-600 rounded px-2 py-1 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                                    <button type="submit" class="px-2 py-1 text-xs bg-brand text-white rounded border-0 hover:bg-brand/80">Salvar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-400 dark:text-slate-600">Nenhum cliente encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($clientes->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">{{ $clientes->links() }}</div>
            @endif
        </div>
    @endif
</div>

@push('scripts')
<script type="module">
    document.querySelectorAll('form.js-excluir').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            Swal.fire({
                title: 'Excluir emissão?',
                text: 'Esta ação não pode ser desfeita.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
            }).then(function (r) {
                if (r.isConfirmed) { form.submit(); }
            });
        });
    });
</script>
@endpush
@endsection
