@extends('layouts.internal')

@section('title', $lead->nome . ' — Lead — WR Assessoria')

@section('content')
    <div class="flex flex-col h-full px-6">

        {{-- Top bar: back + actions --}}
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <a href="{{ route('funil') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 no-underline">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Funil
            </a>

            <div class="flex items-center gap-2">
                <button type="button"
                        data-modal-url="{{ route('leads.form.edit', $lead->id) }}"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-brand text-white rounded-lg border-0 hover:bg-brand/80 text-sm cursor-pointer">
                    <i class="fa-solid fa-pen-to-square"></i> Editar
                </button>

                @if (!$lead->convertido_cliente_id)
                    <button type="button"
                            data-modal-url="{{ route('leads.form.conversao', $lead->id) }}"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 text-white rounded-lg border-0 hover:bg-green-700 text-sm cursor-pointer">
                        <i class="fa-solid fa-user-check"></i> Converter em cliente
                    </button>
                @endif

                <form method="POST" action="{{ route('leads.delete', $lead->id) }}" id="form-delete-lead" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" onclick="confirmarDelete()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-transparent text-red-500 dark:text-red-400 rounded-lg border border-red-300 dark:border-red-700 hover:bg-red-50 dark:hover:bg-red-950/30 text-sm cursor-pointer">
                        <i class="fa-solid fa-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>

        {{-- Header card (full width) --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 px-6 py-4 mb-4">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-4 flex-wrap">
                    <div>
                        @if ($lead->convertido_cliente_id)
                            <div class="flex items-center gap-1.5 text-green-600 dark:text-green-400 text-xs font-medium mb-1">
                                <i class="fa-solid fa-circle-check"></i> Convertido em cliente
                            </div>
                        @endif
                        <h1 class="text-xl font-bold text-gray-900 dark:text-slate-100 leading-tight">{{ $lead->nome }}</h1>
                        @if ($lead->empresa)
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                <i class="fa-regular fa-building mr-1"></i>{{ $lead->empresa }}
                            </p>
                        @endif
                    </div>

                    @if ($lead->cpfcnpj)
                        <span class="text-xs text-gray-400 dark:text-gray-500 border border-gray-200 dark:border-slate-600 rounded px-2 py-1">
                            {{ $lead->tipo === 0 ? 'CPF' : 'CNPJ' }}: {{ $lead->cpfcnpj }}
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-3 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full"
                          style="background: {{ $lead->etapaFunil?->cor ?? '#6b7280' }}22; color: {{ $lead->etapaFunil?->cor ?? '#6b7280' }}">
                        <span class="w-2 h-2 rounded-full shrink-0"
                              style="background: {{ $lead->etapaFunil?->cor ?? '#6b7280' }}"></span>
                        {{ $lead->etapaFunil?->nome ?? '—' }}
                    </span>

                    @if ($lead->origem === 'formulario')
                        <span class="inline-flex items-center gap-1 text-purple-600 dark:text-purple-400 text-xs font-medium">
                            <i class="fa-solid fa-globe text-xs"></i> Formulário público
                        </span>
                    @else
                        <span class="text-xs text-gray-400 dark:text-gray-500">Manual</span>
                    @endif

                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        <i class="fa-regular fa-calendar mr-1"></i>{{ $lead->created_at->format('d/m/Y') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Two-column body --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

            {{-- Left column: possibilidade + observações --}}
            <div class="lg:col-span-8 flex flex-col gap-4">

                @if ($lead->possibilidade || $lead->observacoes)
                    <div class="grid grid-cols-1 {{ ($lead->possibilidade && $lead->observacoes) ? 'md:grid-cols-2' : '' }} gap-4">
                        @if ($lead->possibilidade)
                            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
                                <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                    <i class="fa-regular fa-lightbulb mr-1"></i> Possibilidade
                                </h2>
                                <p class="text-sm text-gray-700 dark:text-slate-200 whitespace-pre-wrap leading-relaxed">{{ $lead->possibilidade }}</p>
                            </div>
                        @endif

                        @if ($lead->observacoes)
                            <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
                                <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-2">
                                    <i class="fa-regular fa-note-sticky mr-1"></i> Observações
                                </h2>
                                <p class="text-sm text-gray-700 dark:text-slate-200 whitespace-pre-wrap leading-relaxed">{{ $lead->observacoes }}</p>
                            </div>
                        @endif
                    </div>
                @endif

            </div>

            {{-- Right sidebar: contact, financial, products --}}
            <div class="lg:col-span-4 flex flex-col gap-4">

                {{-- Contact --}}
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
                    <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                        <i class="fa-solid fa-address-card mr-1"></i> Contato
                    </h2>
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-regular fa-envelope w-4 text-gray-400 text-sm shrink-0"></i>
                            @if ($lead->email)
                                <a href="mailto:{{ $lead->email }}"
                                   class="text-sm text-brand hover:underline break-all">{{ $lead->email }}</a>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-phone w-4 text-gray-400 text-sm shrink-0"></i>
                            @if ($lead->telefone)
                                <a href="tel:{{ preg_replace('/\D/', '', $lead->telefone) }}"
                                   class="text-sm text-gray-700 dark:text-slate-200">{{ $lead->telefone }}</a>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">—</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2.5">
                            <i class="fa-regular fa-user w-4 text-gray-400 text-sm shrink-0"></i>
                            @if ($lead->responsavel)
                                <span class="text-sm text-gray-700 dark:text-slate-200">{{ $lead->responsavel->nome }}</span>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">Sem responsável</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Financial --}}
                @if (auth()->user()?->canVerFaturamento() || auth()->user()?->canVerHonorario())
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
                        <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                            <i class="fa-solid fa-dollar-sign mr-1"></i> Financeiro
                        </h2>
                        <div class="space-y-3">
                            @if (auth()->user()?->canVerFaturamento())
                                <div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Faturamento</p>
                                    <p class="text-sm font-medium text-gray-700 dark:text-slate-200">
                                        {{ $lead->faturamento ? 'R$ ' . number_format($lead->faturamento, 2, ',', '.') : '—' }}
                                    </p>
                                </div>
                            @endif
                            @if (auth()->user()?->canVerHonorario())
                                <div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Honorário estimado</p>
                                    <p class="text-base font-semibold {{ $lead->honorario ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                                        {{ $lead->honorario ? 'R$ ' . number_format($lead->honorario, 2, ',', '.') : '—' }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Products --}}
                @if ($lead->produtos->isNotEmpty())
                    <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5">
                        <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                            <i class="fa-solid fa-briefcase mr-1"></i> Serviços / Produtos
                        </h2>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($lead->produtos as $produto)
                                <span class="text-xs px-2.5 py-1 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-300 rounded-full">
                                    {{ $produto->nome }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        </div>

        {{-- Histórico de Etapas (full width, bottom) --}}
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-5 mt-4 mb-4">
            <h2 class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-3">
                <i class="fa-solid fa-clock-rotate-left mr-1"></i> Histórico de Etapas
            </h2>

            @if ($lead->historico->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">Nenhuma movimentação registrada.</p>
            @else
                <ol class="relative border-l border-gray-200 dark:border-slate-600 ml-2 space-y-4">
                    @foreach ($lead->historico->sortByDesc('created_at') as $h)
                        <li class="ml-5">
                            <p class="text-sm text-gray-700 dark:text-slate-200">
                                @if ($h->etapaAnterior)
                                    <span class="font-medium">{{ $h->etapaAnterior->nome }}</span>
                                    <i class="fa-solid fa-arrow-right mx-1.5 text-gray-400 text-xs"></i>
                                @endif
                                <span class="font-semibold" style="color: {{ $h->etapaNova?->cor ?? '#374151' }}">
                                    {{ $h->etapaNova?->nome ?? '—' }}
                                </span>
                            </p>
                            @if ($h->descricao)
                                <p class="text-xs text-gray-500 dark:text-gray-400 italic mt-0.5">"{{ $h->descricao }}"</p>
                            @endif
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ $h->created_at->format('d/m/Y H:i') }}
                                @if ($h->alteradoPor) · {{ $h->alteradoPor->nome }} @endif
                            </p>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
<script>
function confirmarDelete() {
    Swal.fire({
        title: 'Excluir lead?',
        text: 'Tem certeza que deseja excluir "{{ addslashes($lead->nome) }}"? Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
    }).then(function (result) {
        if (result.isConfirmed) {
            document.getElementById('form-delete-lead').submit();
        }
    });
}
</script>
@endpush
