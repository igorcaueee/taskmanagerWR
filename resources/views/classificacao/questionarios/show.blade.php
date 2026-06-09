@extends('layouts.internal')

@section('title', 'Respostas — ' . $questionario->titulo)

@section('content')
<div class="w-full mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('questionarios.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline no-underline mb-1 inline-flex items-center gap-1">
                <i class="fa-solid fa-arrow-left text-xs"></i> Questionários
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                <i class="fa-solid fa-list-check"></i> Respostas
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $questionario->titulo }}</p>
        </div>
        <div class="flex gap-2">
            <button onclick="copiarLink('{{ route('questionario.publico', $questionario->slug) }}')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                <i class="fa-solid fa-link"></i> Copiar link público
            </button>
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

    {{-- Filtros --}}
    <form method="GET" action="{{ route('questionarios.show', $questionario->id) }}" class="flex flex-wrap gap-3 mb-5">
        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pesquisar</label>
            <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Nome, empresa, e-mail..."
                   class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500 w-56"
                   onchange="this.form.submit()">
        </div>
        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Classificação</label>
            <select name="classificacao" onchange="this.form.submit()"
                    class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">Todas</option>
                <option value="Muito Dinheiro Escondido"     {{ request('classificacao') === 'Muito Dinheiro Escondido'     ? 'selected' : '' }}>Muito Dinheiro Escondido</option>
                <option value="Dinheiro Escondido Relevante" {{ request('classificacao') === 'Dinheiro Escondido Relevante' ? 'selected' : '' }}>Dinheiro Escondido Relevante</option>
                <option value="Boa Gestão"                  {{ request('classificacao') === 'Boa Gestão'                  ? 'selected' : '' }}>Boa Gestão</option>
                <option value="Alta Performance"             {{ request('classificacao') === 'Alta Performance'             ? 'selected' : '' }}>Alta Performance</option>
            </select>
        </div>
        @if(request()->hasAny(['busca','classificacao']))
        <div class="flex items-end">
            <a href="{{ route('questionarios.show', $questionario->id) }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 no-underline px-2">
                <i class="fa-solid fa-xmark"></i> Limpar
            </a>
        </div>
        @endif
    </form>

    <div class="bg-white dark:bg-slate-800 rounded shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                    <th class="text-left px-4 py-3 text-gray-600 dark:text-gray-300 font-medium">Respondente</th>
                    <th class="text-left px-4 py-3 text-gray-600 dark:text-gray-300 font-medium">Empresa</th>
                    <th class="text-left px-4 py-3 text-gray-600 dark:text-gray-300 font-medium">Vínculo</th>
                    <th class="text-center px-4 py-3 text-gray-600 dark:text-gray-300 font-medium">IDE</th>
                    <th class="text-left px-4 py-3 text-gray-600 dark:text-gray-300 font-medium">Classificação</th>
                    <th class="text-left px-4 py-3 text-gray-600 dark:text-gray-300 font-medium">Data</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($respostas as $r)
                @php
                    $cor = match($r->classificacao) {
                        'Muito Dinheiro Escondido'    => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300',
                        'Dinheiro Escondido Relevante' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
                        'Boa Gestão'                  => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                        'Alta Performance'             => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                        default                       => 'bg-gray-100 dark:bg-gray-700 text-gray-500',
                    };
                @endphp
                <tr class="border-b border-gray-50 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/40">
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-900 dark:text-slate-100">{{ $r->respondente_nome }}</div>
                        @if($r->respondente_email)
                        <div class="text-xs text-gray-400">{{ $r->respondente_email }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $r->respondente_empresa ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($r->lead)
                            <span class="text-xs px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full">Lead</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">{{ $r->lead->nome }}</span>
                        @elseif($r->cliente)
                            <span class="text-xs px-2 py-0.5 bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300 rounded-full">Cliente</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">{{ $r->cliente->nome }}</span>
                        @else
                            <span class="text-xs text-gray-400">Avulso</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="font-bold text-lg text-gray-900 dark:text-slate-100">{{ number_format($r->pontuacao_total, 1) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-1 rounded-full {{ $cor }}">{{ $r->classificacao }}</span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 justify-end">
                            <a href="{{ route('questionarios.respostas.detalhe', $r->id) }}"
                               class="text-blue-600 dark:text-blue-400 hover:underline text-xs no-underline">
                                <i class="fa-solid fa-eye"></i> Ver
                            </a>
                            <form id="form-del-{{ $r->id }}" method="POST" action="{{ route('questionarios.respostas.delete', $r->id) }}">
                                @csrf @method('DELETE')
                                <button type="button"
                                        onclick="confirmarExclusao({{ $r->id }})"
                                        class="text-red-500 hover:text-red-700 bg-transparent border-0 text-xs cursor-pointer">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                        <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                        Nenhuma resposta encontrada.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($respostas->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-slate-700">
            {{ $respostas->links() }}
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script type="module">
window.copiarLink = function(url) {
    navigator.clipboard.writeText(url).then(() => {
        Swal.fire({ icon: 'success', title: 'Link copiado!', confirmButtonColor: '#2563eb', timer: 1800, showConfirmButton: false });
    });
};

window.confirmarExclusao = function(id) {
    Swal.fire({
        title: 'Excluir resposta?',
        text: 'Esta ação não pode ser desfeita.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
    }).then(result => {
        if (result.isConfirmed) {
            document.getElementById('form-del-' + id).submit();
        }
    });
};
</script>
@endpush
@endsection
