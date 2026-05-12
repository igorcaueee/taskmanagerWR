@extends('layouts.internal')

@section('title', $cliente->nome . ' — WR Assessoria')

@section('content')
    <div class="w-full mx-auto py-6 px-4">

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('clientes') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fa-solid fa-arrow-left text-lg"></i>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $cliente->nome }}</h1>
                        @if($cliente->status === 'ativo')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Ativo</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">Inativo</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        @if((string) $cliente->tipo === '1')
                            <i class="fa-solid fa-building-user mr-1"></i>Pessoa Jurídica
                        @elseif((string) $cliente->tipo === '0')
                            <i class="fa-solid fa-user mr-1"></i>Pessoa Física
                        @endif
                        @if($cliente->segmentacao)
                            &mdash; {{ $cliente->segmentacao->nome }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="https://assessoriawr.com/arquivos?path={{ rawurlencode($cliente->pasta_arquivos ?? $cliente->nome) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600 focus:outline-none">
                    <i class="fa-solid fa-folder-open"></i>
                    Pasta de Arquivos
                </a>
                @if(auth()->user()?->canEditarClientes())
                    <button type="button"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand text-white rounded text-sm hover:bg-brand/80 focus:outline-none border-0"
                            data-modal-url="{{ route('clientes.form.edit', $cliente->id) }}">
                        <i class="fa-solid fa-pencil"></i>
                        Editar
                    </button>
                @endif
            </div>
        </div>

        @if($cliente->status === 'inativo' && $cliente->motivo_encerramento)
            <div class="mb-6 px-4 py-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-400 flex items-start gap-2">
                <i class="fa-solid fa-building-circle-xmark mt-0.5 flex-shrink-0"></i>
                <span>
                    <span class="font-semibold">Empresa encerrada</span> em {{ $cliente->data_encerramento?->format('d/m/Y') ?? '—' }}
                    — <span class="italic">{{ $cliente->motivo_encerramento }}</span>
                </span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Informações Gerais --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white dark:bg-slate-800 rounded shadow p-4">
                    <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                        <i class="fa-solid fa-circle-info mr-1"></i> Informações Gerais
                    </h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">CPF / CNPJ</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">{{ $cliente->cpfcnpj ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Cidade / UF</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">
                                {{ $cliente->cidade ?? '—' }}{{ $cliente->estado ? ' / ' . $cliente->estado : '' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Cliente Desde</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">
                                {{ $cliente->cliente_desde?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Data de Abertura</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">
                                {{ $cliente->dataabertura?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Venc. Certificado Digital</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">
                                {{ $cliente->vencimento_certificado?->format('d/m/Y') ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Regime Tributário</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">
                                {{ $cliente->regime_tributario ? mb_strtoupper($cliente->regime_tributario) : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Fator R</dt>
                            <dd class="mt-0.5">
                                @if($cliente->fator_r)
                                    <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-medium">
                                        <i class="fa-solid fa-check"></i> Sim
                                    </span>
                                @else
                                    <span class="text-gray-400">Não</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Serviço</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">{{ $cliente->servico ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Descrição --}}
                @if($cliente->descricao)
                    <div class="bg-white dark:bg-slate-800 rounded shadow p-4">
                        <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            <i class="fa-solid fa-file-lines mr-1"></i> Descrição
                        </h2>
                        <p class="text-sm text-gray-700 dark:text-slate-300 whitespace-pre-wrap">{{ $cliente->descricao }}</p>
                    </div>
                @endif

                {{-- Financeiro (somente quem tem permissão) --}}
                @if(auth()->user()?->canVerFaturamento() || auth()->user()?->canVerHonorario())
                <div class="bg-white dark:bg-slate-800 rounded shadow p-4">
                    <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                        <i class="fa-solid fa-dollar-sign mr-1"></i> Financeiro
                    </h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                        @if(auth()->user()?->canVerFaturamento())
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Faturamento</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">
                                @if($cliente->faturamento)
                                    R$ {{ number_format((float) $cliente->faturamento, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        @endif
                        @if(auth()->user()?->canVerHonorario())
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Honorário</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">
                                @if($cliente->honorario)
                                    R$ {{ number_format((float) $cliente->honorario, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Capital Social</dt>
                            <dd class="mt-0.5 text-gray-900 dark:text-slate-100">
                                @if($cliente->capital_social)
                                    R$ {{ number_format((float) $cliente->capital_social, 2, ',', '.') }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        @endif
                    </dl>
                    @if($cliente->possibilidade && auth()->user()?->canVerHonorario())
                        <div class="mt-4">
                            <dt class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Possibilidade / Observação</dt>
                            <dd class="mt-1 text-sm text-gray-700 dark:text-slate-300 whitespace-pre-wrap">{{ $cliente->possibilidade }}</dd>
                        </div>
                    @endif
                </div>
                @endif

                {{-- Produtos contratados --}}
                @if($cliente->produtos->isNotEmpty())
                    <div class="bg-white dark:bg-slate-800 rounded shadow p-4">
                        <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            <i class="fa-solid fa-box mr-1"></i> Produtos / Serviços Contratados
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach($cliente->produtos as $produto)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-brand/10 text-brand dark:bg-brand/20 dark:text-blue-300">
                                    {{ $produto->nome }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Sidebar: Sócios --}}
            <div class="space-y-4">

                {{-- Sócios --}}
                @if($cliente->socios->isNotEmpty())
                    <div class="bg-white dark:bg-slate-800 rounded shadow p-4">
                        <h2 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                            <i class="fa-solid fa-scale-balanced mr-1"></i> Quadro Societário
                        </h2>
                        <ul class="space-y-3">
                            @foreach($cliente->socios as $socio)
                                <li class="text-sm border-b border-gray-100 dark:border-slate-700 pb-3 last:border-0 last:pb-0">
                                    <p class="font-medium text-gray-900 dark:text-slate-100">{{ $socio->nome }}</p>
                                    <div class="flex gap-4 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        @if($socio->participacao)
                                            <span><i class="fa-solid fa-percent mr-1"></i>{{ $socio->participacao }}%</span>
                                        @endif
                                        @if($socio->telefone)
                                            <span><i class="fa-solid fa-phone mr-1"></i>{{ $socio->telefone }}</span>
                                        @endif
                                    </div>
                                    @if($socio->email)
                                        <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                                            <i class="fa-solid fa-envelope mr-1"></i>{{ $socio->email }}
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

            </div>
        </div>
    </div>

    @if(session('success') || session('error'))
        @push('scripts')
        <script type="module">
        @if(session('success'))
        Swal.fire({ icon: 'success', title: 'Sucesso', text: '{{ session('success') }}', confirmButtonColor: '#2563eb' });
        @endif
        @if(session('error'))
        Swal.fire({ icon: 'error', title: 'Erro', text: '{{ session('error') }}', confirmButtonColor: '#dc2626' });
        @endif
        </script>
        @endpush
    @endif
@endsection
