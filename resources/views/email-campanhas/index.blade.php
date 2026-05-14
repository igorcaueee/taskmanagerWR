@extends('layouts.internal')

@section('title', 'Campanhas de E-mail — WR Assessoria')

@section('content')
    <div class="w-full mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-envelope-open-text"></i> Campanhas de E-mail</h1>
                <p class="text-gray-700 dark:text-gray-300">Disparo de e-mails para clientes com suporte de IA.</p>
            </div>
            <a href="{{ route('email-campanhas.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80 text-sm">
                <i class="fa-solid fa-plus"></i> Nova Campanha
            </a>
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

        <div class="bg-white dark:bg-slate-800 rounded shadow">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-xs">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Título</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Assunto</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Destinatários</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Criado por</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($campanhas as $campanha)
                        <tr>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 font-medium">{{ $campanha->titulo }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ $campanha->assunto }}</td>
                            <td class="px-4 py-2 text-xs">
                                @if($campanha->status === 'enviada')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Enviada</span>
                                @elseif($campanha->status === 'falha_parcial')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">Falha parcial</span>
                                @elseif($campanha->status === 'enviando')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Enviando...</span>
                                @elseif($campanha->status === 'agendada')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" title="{{ $campanha->enviar_em?->format('d/m/Y H:i') }}">
                                        <i class="fa-solid fa-clock mr-1"></i> Agendada
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">Rascunho</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">
                                {{ $campanha->total_enviados }}/{{ $campanha->total_destinatarios }}
                            </td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300">{{ $campanha->criador?->nome ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $campanha->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2 text-xs text-right">
                                <div class="inline-flex items-center gap-3">
                                    <a href="{{ route('email-campanhas.show', $campanha->id) }}"
                                       class="text-brand hover:text-brand/80" title="Ver">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    @if($campanha->status !== 'enviando')
                                    <form method="POST" action="{{ route('email-campanhas.destroy', $campanha->id) }}" class="form-excluir-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn-excluir-inline text-red-400 hover:text-red-600 bg-transparent border-0 p-0" title="Excluir">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                                Nenhuma campanha encontrada.
                                <a href="{{ route('email-campanhas.create') }}" class="text-brand hover:underline ml-1">Criar primeira campanha</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($campanhas->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
                    {{ $campanhas->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script type="module">
document.querySelectorAll('.btn-excluir-inline').forEach(btn => {
    btn.addEventListener('click', () => {
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
                btn.closest('form').submit();
            }
        });
    });
});
</script>
@endpush
