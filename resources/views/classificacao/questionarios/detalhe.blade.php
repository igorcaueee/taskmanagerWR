@extends('layouts.internal')

@section('title', 'Detalhe da Resposta — ' . $resposta->respondente_nome)

@section('content')
<div class="w-full mx-auto py-6 px-4 max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('questionarios.show', $resposta->questionario_id) }}"
           class="text-sm text-blue-600 dark:text-blue-400 hover:underline no-underline inline-flex items-center gap-1 mb-2">
            <i class="fa-solid fa-arrow-left text-xs"></i> Voltar às respostas
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
            Diagnóstico — {{ $resposta->respondente_nome }}
        </h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $resposta->questionario->titulo }}</p>
    </div>

    {{-- Dados da empresa --}}
    <div class="bg-white dark:bg-slate-800 rounded shadow p-5 mb-5">
        <h2 class="font-semibold text-gray-800 dark:text-slate-200 mb-3 text-sm uppercase tracking-wide">
            <i class="fa-solid fa-building mr-1"></i> Dados da Empresa
        </h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-xs text-gray-400 block">Responsável</span>
                <span class="text-gray-800 dark:text-slate-200 font-medium">{{ $resposta->respondente_nome }}</span>
            </div>
            @if($resposta->respondente_telefone)
            <div>
                <span class="text-xs text-gray-400 block">Telefone</span>
                <span class="text-gray-800 dark:text-slate-200 font-medium">{{ $resposta->respondente_telefone }}</span>
            </div>
            @endif
            @if($resposta->respondente_empresa)
            <div>
                <span class="text-xs text-gray-400 block">Empresa</span>
                <span class="text-gray-800 dark:text-slate-200 font-medium">{{ $resposta->respondente_empresa }}</span>
            </div>
            @endif
            @if($resposta->respondente_segmento)
            <div>
                <span class="text-xs text-gray-400 block">Segmento</span>
                <span class="text-gray-800 dark:text-slate-200 font-medium">{{ $resposta->respondente_segmento }}</span>
            </div>
            @endif
            @if($resposta->faturamento_mensal)
            <div>
                <span class="text-xs text-gray-400 block">Faturamento Mensal</span>
                <span class="text-gray-800 dark:text-slate-200 font-medium">R$ {{ number_format($resposta->faturamento_mensal, 2, ',', '.') }}</span>
            </div>
            @endif
            @if($resposta->num_colaboradores)
            <div>
                <span class="text-xs text-gray-400 block">Colaboradores</span>
                <span class="text-gray-800 dark:text-slate-200 font-medium">{{ $resposta->num_colaboradores }}</span>
            </div>
            @endif
            <div>
                <span class="text-xs text-gray-400 block">Data</span>
                <span class="text-gray-800 dark:text-slate-200 font-medium">{{ $resposta->created_at->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    {{-- Pontuação IDE --}}
    @php
        $corBadge = match($resposta->classificacao) {
            'Muito Dinheiro Escondido'    => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800',
            'Dinheiro Escondido Relevante' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800',
            'Boa Gestão'                  => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
            'Alta Performance'             => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800',
            default                       => 'bg-gray-100 dark:bg-gray-700 text-gray-500 border-gray-200',
        };
        $pesos = [
            'financeiro'    => ['label' => 'Financeiro',    'peso' => 25],
            'tributario'    => ['label' => 'Tributário',    'peso' => 25],
            'lucratividade' => ['label' => 'Lucratividade', 'peso' => 20],
            'endividamento' => ['label' => 'Endividamento', 'peso' => 15],
            'gestao'        => ['label' => 'Gestão',        'peso' => 15],
        ];
    @endphp
    <div class="bg-white dark:bg-slate-800 rounded shadow p-5 mb-5 flex flex-col md:flex-row gap-6 items-start">
        <div class="text-center shrink-0">
            <div class="text-6xl font-extrabold text-gray-900 dark:text-slate-100">
                {{ number_format($resposta->pontuacao_total, 1) }}
            </div>
            <div class="text-xs text-gray-400 mt-1 mb-2">Índice IDE</div>
            <span class="text-sm px-3 py-1 rounded-full border {{ $corBadge }} font-medium">
                {{ $resposta->classificacao }}
            </span>
        </div>
        <div class="flex-1 w-full">
            @foreach($pesos as $cat => $info)
            @php
                $itens = $porCategoria->get($cat, collect());
                $max   = $itens->count() * 10;
                $soma  = $itens->sum('pontos');
                $nota  = $max > 0 ? round(($soma / $max) * 100, 1) : 0;
                $barW  = min(100, $nota);
                $barCor = $nota >= 80 ? 'bg-green-500' : ($nota >= 60 ? 'bg-blue-500' : ($nota >= 40 ? 'bg-orange-500' : 'bg-red-500'));
            @endphp
            <div class="mb-3">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <span>{{ $info['label'] }} <span class="text-gray-400">({{ $info['peso'] }}%)</span></span>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $nota }}</span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-slate-700 rounded-full h-2">
                    <div class="{{ $barCor }} h-2 rounded-full transition-all" style="width: {{ $barW }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Respostas por categoria --}}
    @foreach($pesos as $cat => $info)
    @php $itens = $porCategoria->get($cat, collect()); @endphp
    @if($itens->count())
    <div class="bg-white dark:bg-slate-800 rounded shadow mb-4 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
            <span class="font-semibold text-gray-800 dark:text-slate-200 text-sm">{{ $info['label'] }}</span>
            <span class="text-xs text-gray-400 ml-2">Peso {{ $info['peso'] }}%</span>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700">
            @foreach($itens->sortBy('pergunta.ordem') as $item)
            @php
                $corPontos = match($item->pontos) {
                    0  => 'text-red-600 dark:text-red-400',
                    5  => 'text-orange-600 dark:text-orange-400',
                    10 => 'text-green-600 dark:text-green-400',
                    default => 'text-gray-500',
                };
            @endphp
            <div class="px-5 py-3 flex items-center justify-between gap-3">
                <p class="text-sm text-gray-700 dark:text-gray-300 flex-1">{{ $item->pergunta->texto }}</p>
                <div class="text-right shrink-0">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block">{{ $item->opcao->texto }}</span>
                    <span class="font-bold {{ $corPontos }}">{{ $item->pontos }} pts</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    @endforeach

    {{-- Vínculo --}}
    @if($resposta->lead || $resposta->cliente)
    <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
        @if($resposta->lead)
            <i class="fa-solid fa-user-plus mr-1"></i> Resposta vinculada ao lead
            <a href="{{ route('leads.detalhe', $resposta->lead_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline no-underline">{{ $resposta->lead->nome }}</a>
        @elseif($resposta->cliente)
            <i class="fa-regular fa-building mr-1"></i> Resposta vinculada ao cliente
            <a href="{{ route('clientes.show', $resposta->cliente_id) }}" class="text-blue-600 dark:text-blue-400 hover:underline no-underline">{{ $resposta->cliente->nome }}</a>
        @endif
    </div>
    @elseif(auth()->user()?->canVerFunil())
    @php
        $observacoesLead = "Diagnóstico IDE: {$resposta->classificacao} ({$resposta->pontuacao_total} pts)";
        $urlConverterLead = route('leads.form.create', [
            'resposta_id' => $resposta->id,
            'nome' => $resposta->respondente_nome,
            'email' => $resposta->respondente_email,
            'telefone' => $resposta->respondente_telefone,
            'empresa' => $resposta->respondente_empresa,
            'faturamento' => $resposta->faturamento_mensal,
            'observacoes' => $observacoesLead,
        ]);
    @endphp
    <div class="mt-4">
        <button type="button"
                class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 hover:bg-brand/80 text-sm"
                data-modal-url="{{ $urlConverterLead }}">
            <i class="fa-solid fa-user-plus"></i> Converter em Lead
        </button>
    </div>
    @endif
</div>
@endsection
