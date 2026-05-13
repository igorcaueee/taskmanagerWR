@extends('layouts.internal')

@section('title', $campanha->titulo . ' — WR Assessoria')

@section('content')
<div class="w-full mx-auto py-6 px-4">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('email-campanhas.index') }}" class="text-gray-400 hover:text-brand">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">{{ $campanha->titulo }}</h1>
            <p class="text-gray-700 dark:text-gray-300">Detalhes e envio da campanha</p>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Pré-visualização --}}
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-slate-800 rounded shadow p-5">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wide mb-4">Pré-visualização do E-mail</h2>
                <div class="border border-gray-200 dark:border-slate-700 rounded p-4 prose prose-sm max-w-none dark:prose-invert">
                    {!! $campanha->conteudo_html !!}
                </div>
            </div>
        </div>

        {{-- Painel lateral --}}
        <div class="space-y-5">

            {{-- Informações --}}
            <div class="bg-white dark:bg-slate-800 rounded shadow p-5 space-y-3">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wide">Informações</h2>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Assunto</p>
                    <p class="text-sm text-gray-800 dark:text-slate-200 font-medium">{{ $campanha->assunto }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
                    @if($campanha->status === 'enviada')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                            <i class="fa-solid fa-check mr-1"></i> Enviada
                        </span>
                    @elseif($campanha->status === 'enviando')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i> Enviando...
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                            <i class="fa-solid fa-pencil mr-1"></i> Rascunho
                        </span>
                    @endif
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Destinatários</p>
                    <p class="text-sm text-gray-800 dark:text-slate-200">{{ $campanha->total_destinatarios }} cliente(s)</p>
                </div>

                @if($campanha->status === 'enviada')
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Enviados</p>
                    <p class="text-sm text-gray-800 dark:text-slate-200">{{ $campanha->total_enviados }} e-mail(s)</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Enviada em</p>
                    <p class="text-sm text-gray-800 dark:text-slate-200">{{ $campanha->enviada_em?->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
                @endif

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Criado por</p>
                    <p class="text-sm text-gray-800 dark:text-slate-200">{{ $campanha->criador?->nome ?? '—' }}</p>
                </div>

                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Criado em</p>
                    <p class="text-sm text-gray-800 dark:text-slate-200">{{ $campanha->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            {{-- Ações --}}
            @if(in_array($campanha->status, ['rascunho', 'enviada']))
            <form method="POST" action="{{ route('email-campanhas.enviar', $campanha->id) }}" id="form-enviar">
                @csrf
                @method('POST')
                <button type="button" id="btn-enviar"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 {{ $campanha->status === 'enviada' ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded border-0 text-sm font-medium">
                    <i class="fa-solid fa-paper-plane"></i> {{ $campanha->status === 'enviada' ? 'Reenviar Campanha' : 'Disparar Campanha' }}
                </button>
            </form>
            @endif

            {{-- Editar --}}
            @if($campanha->status !== 'enviando')
            <a href="{{ route('email-campanhas.edit', $campanha->id) }}"
               class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-brand hover:bg-brand/80 text-white rounded text-sm font-medium">
                <i class="fa-solid fa-pen-to-square"></i> Editar Campanha
            </a>
            @endif

            {{-- Excluir --}}
            @if($campanha->status !== 'enviando')
            <form method="POST" action="{{ route('email-campanhas.destroy', $campanha->id) }}" id="form-excluir">
                @csrf
                @method('DELETE')
                <button type="button" id="btn-excluir"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded border-0 text-sm font-medium">
                    <i class="fa-solid fa-trash"></i> Excluir Campanha
                </button>
            </form>
            @endif

        </div>
    </div>
</div>

@push('scripts')
<script type="module">
@if($campanha->status !== 'enviando')
document.getElementById('btn-excluir')?.addEventListener('click', () => {
    Swal.fire({
        icon: 'warning',
        title: 'Excluir campanha?',
        text: 'Esta ação não pode ser desfeita.',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('form-excluir').submit();
        }
    });
});
@endif

@if(in_array($campanha->status, ['rascunho', 'enviada']))
document.getElementById('btn-enviar')?.addEventListener('click', () => {
    Swal.fire({
        icon: 'question',
        title: '{{ $campanha->status === "enviada" ? "Reenviar campanha?" : "Disparar campanha?" }}',
        html: '{{ $campanha->status === "enviada" ? "Esta campanha já foi enviada. Deseja reenviar para " : "O e-mail será enviado para " }}<strong>{{ $campanha->total_destinatarios }}</strong> cliente(s)?',
        showCancelButton: true,
        confirmButtonText: '{{ $campanha->status === "enviada" ? "Sim, reenviar!" : "Sim, disparar!" }}',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '{{ $campanha->status === "enviada" ? "#ca8a04" : "#16a34a" }}',
        cancelButtonColor: '#6b7280',
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('form-enviar').submit();
        }
    });
});
@endif
</script>
@endpush
@endsection
