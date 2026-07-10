@extends('layouts.portal')

@section('title', 'Chamados')

@section('content')
<div class="space-y-8">

    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Chamados de DP</h1>
            <p class="text-gray-500 dark:text-slate-400 mt-1 text-sm">
                Abra um chamado para admissão ou demissão de colaboradores.
            </p>
        </div>
    </div>

    {{-- Ações --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('portal.chamados.create', 'admissao') }}" class="no-underline bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm hover:border-[#0084AA] hover:shadow-md transition group">
            <div class="flex items-center gap-4">
                <div class="bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-lg p-3 text-2xl group-hover:bg-green-100 dark:group-hover:bg-green-900/30 transition">🧑‍💼</div>
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-slate-100">Abrir chamado de Admissão</h2>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Solicitar admissão de um novo colaborador</p>
                </div>
            </div>
        </a>

        <a href="{{ route('portal.chamados.create', 'demissao') }}" class="no-underline bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm hover:border-[#0084AA] hover:shadow-md transition group">
            <div class="flex items-center gap-4">
                <div class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg p-3 text-2xl group-hover:bg-red-100 dark:group-hover:bg-red-900/30 transition">📄</div>
                <div>
                    <h2 class="font-semibold text-gray-800 dark:text-slate-100">Abrir chamado de Demissão</h2>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Solicitar desligamento de um colaborador</p>
                </div>
            </div>
        </a>
    </div>

    {{-- Histórico --}}
    <div>
        <h2 class="text-lg font-semibold text-gray-700 dark:text-slate-200 mb-4">Chamados abertos</h2>

        @if ($chamados->isEmpty())
            <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm text-center text-gray-500 dark:text-slate-400 text-sm">
                Nenhum chamado aberto ainda.
            </div>
        @else
            <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#0f172a] text-gray-500 dark:text-slate-400 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3 font-medium">Tipo</th>
                            <th class="text-left px-4 py-3 font-medium">Colaborador</th>
                            <th class="text-left px-4 py-3 font-medium">Data</th>
                            <th class="text-left px-4 py-3 font-medium">Status</th>
                            <th class="text-left px-4 py-3 font-medium">Aberto em</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#334155]">
                        @foreach ($chamados as $chamado)
                            <tr>
                                <td class="px-4 py-3 text-gray-800 dark:text-slate-100">{{ $chamado->labelTipo() }}</td>
                                <td class="px-4 py-3 text-gray-800 dark:text-slate-100">{{ $chamado->nome_colaborador }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ $chamado->data_evento->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-slate-300">{{ $chamado->tarefa?->etapa?->nome ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-slate-400">{{ $chamado->created_at->format('d/m/Y H:i') }}</td>
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
<script>
    @if (session('success'))
        Swal.fire({ icon: 'success', title: 'Sucesso!', text: @json(session('success')), timer: 4000, showConfirmButton: false });
    @endif
    @if (session('error'))
        Swal.fire({ icon: 'error', title: 'Erro', text: @json(session('error')) });
    @endif
</script>
@endpush
