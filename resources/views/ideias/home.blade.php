@extends('layouts.internal')

@section('title', 'Ideias & Correções — WR Assessoria')

@php
    $statusLabels = [
        'pendente'    => 'Pendente',
        'em_analise'  => 'Em análise',
        'aprovada'    => 'Aprovada',
        'concluida'   => 'Concluída',
        'descartada'  => 'Descartada',
    ];
    $statusColors = [
        'pendente'    => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        'em_analise'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'aprovada'    => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'concluida'   => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-300',
        'descartada'  => 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    ];
@endphp

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                    <i class="fa-solid fa-lightbulb"></i> Ideias &amp; Correções
                </h1>
                <p class="text-gray-700 dark:text-gray-300">Registre suas ideias e sugestões de melhoria.</p>
            </div>
            <button type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80 text-sm"
                    data-modal-url="{{ route('ideias.form') }}">
                <i class="fa-solid fa-plus"></i> Nova Ideia
            </button>
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

        <div class="bg-white dark:bg-slate-800 rounded shadow overflow-x-auto">
            {{-- Filters --}}
            <form method="GET" action="{{ route('ideias.index') }}" id="form-filtros-ideias"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Status</label>
                    <select name="status" onchange="document.getElementById('form-filtros-ideias').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Colaborador</label>
                    <select name="colaborador_id" onchange="document.getElementById('form-filtros-ideias').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($colaboradores as $colab)
                            <option value="{{ $colab->id }}" @selected(request('colaborador_id') == $colab->id)>{{ $colab->nome }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ideia / Descrição</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Colaborador</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Criada em</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Conclusão</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @forelse($ideias as $ideia)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/40">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-slate-200 max-w-xs">
                                <span class="line-clamp-2">{{ $ideia->descricao }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $ideia->colaborador?->nome ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$ideia->status] }}">
                                    {{ $statusLabels[$ideia->status] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $ideia->created_at->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $ideia->data_conclusao ? $ideia->data_conclusao->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button"
                                        class="text-xs text-brand hover:underline bg-transparent border-0 p-0 mr-3"
                                        data-modal-url="{{ route('ideias.form.edit', $ideia->id) }}">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <form method="POST" action="{{ route('ideias.destroy', $ideia->id) }}" class="inline"
                                      onsubmit="return confirm('Tem certeza que deseja excluir esta ideia?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:underline bg-transparent border-0 p-0">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                                Nenhuma ideia registrada.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($ideias->hasPages())
                <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
                    {{ $ideias->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
