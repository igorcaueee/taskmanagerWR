@extends('layouts.internal')

@section('title', 'Uploads do Portal — WR Assessoria')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                <i class="fa-solid fa-file-arrow-up mr-2 text-brand"></i>Uploads do Portal
            </h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                Histórico de arquivos enviados para clientes via tarefas.
            </p>
        </div>
        <a href="{{ route('tarefas.list') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-slate-600 text-sm hover:bg-gray-200 dark:hover:bg-slate-600 no-underline">
            <i class="fa-solid fa-arrow-left"></i> Voltar ao Pipeline
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('tarefas.uploads-portal') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Cliente</label>
            <select name="cliente_id"
                    class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                <option value="">Todos os clientes</option>
                @foreach($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected(request('cliente_id') == $cliente->id)>{{ $cliente->nome }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Status de download</label>
            <select name="status"
                    class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                <option value="">Todos</option>
                <option value="baixado" @selected(request('status') === 'baixado')>Baixado pelo cliente</option>
                <option value="visualizado" @selected(request('status') === 'visualizado')>Visualizado (sem download)</option>
                <option value="nao_baixado" @selected(request('status') === 'nao_baixado')>Não baixado</option>
            </select>
        </div>
        <button type="submit"
                class="px-4 py-1.5 bg-brand text-white rounded text-sm border-0 hover:bg-brand/80">
            <i class="fa-solid fa-magnifying-glass mr-1"></i> Filtrar
        </button>
        @if(request()->hasAny(['cliente_id', 'status']))
            <a href="{{ route('tarefas.uploads-portal') }}"
               class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200 no-underline">
                <i class="fa-solid fa-xmark mr-1"></i> Limpar
            </a>
        @endif
    </form>

    {{-- Stats rápidos --}}
    @php
        $totalUploads = $uploads->total();
        $naosBaixados = $uploads->getCollection()->where('baixado_em', null)->where('visualizado_em', null)->count();
        $baixados     = $uploads->getCollection()->whereNotNull('baixado_em')->count();
        $visualizados = $uploads->getCollection()->whereNull('baixado_em')->whereNotNull('visualizado_em')->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-gray-800 dark:text-slate-100">{{ $totalUploads }}</p>
            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Total de uploads</p>
        </div>
        <div class="bg-green-50 dark:bg-green-950/30 rounded-xl border border-green-200 dark:border-green-800 p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-green-700 dark:text-green-400">{{ $baixados }}</p>
            <p class="text-xs text-green-600 dark:text-green-500 mt-0.5">Baixados</p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-950/30 rounded-xl border border-blue-200 dark:border-blue-800 p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-400">{{ $visualizados }}</p>
            <p class="text-xs text-blue-600 dark:text-blue-500 mt-0.5">Só visualizados</p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800 p-4 text-center shadow-sm">
            <p class="text-2xl font-bold text-amber-700 dark:text-amber-400">{{ $naosBaixados }}</p>
            <p class="text-xs text-amber-600 dark:text-amber-500 mt-0.5">Não vistos</p>
        </div>
    </div>

    {{-- Tabela --}}
    @if($uploads->isEmpty())
        <div class="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-12 text-center text-gray-400 dark:text-slate-500 shadow-sm">
            <i class="fa-regular fa-folder-open text-5xl mb-3 block"></i>
            <p class="font-medium text-sm">Nenhum upload encontrado.</p>
            <p class="text-xs mt-1">Os arquivos enviados ao portal via tarefas aparecerão aqui.</p>
        </div>
    @else
        <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-slate-700">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-slate-300">Arquivo</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-slate-300 hidden md:table-cell">Cliente</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-slate-300 hidden lg:table-cell">Tarefa</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-slate-300 hidden sm:table-cell">Enviado por</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-slate-300">Enviado em</th>
                        <th class="text-left px-4 py-3 font-semibold text-gray-600 dark:text-slate-300">Status</th>
                        <th class="px-4 py-3"></th>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($uploads as $upload)
                        @php
                            $ext = strtolower(pathinfo($upload->arquivo_nome, PATHINFO_EXTENSION));
                            $icone = match(true) {
                                $ext === 'pdf' => '<i class="fa-regular fa-file-pdf text-red-500"></i>',
                                in_array($ext, ['doc', 'docx']) => '<i class="fa-regular fa-file-word text-blue-600"></i>',
                                in_array($ext, ['xls', 'xlsx', 'csv']) => '<i class="fa-regular fa-file-excel text-green-600"></i>',
                                in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => '<i class="fa-regular fa-file-image text-purple-500"></i>',
                                in_array($ext, ['zip', 'rar', '7z']) => '<i class="fa-regular fa-file-zipper text-yellow-600"></i>',
                                default => '<i class="fa-regular fa-file text-gray-400"></i>',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition cursor-pointer"
                            onclick="abrirHistorico({{ $upload->id }})"
                            title="Clique para ver o hist&oacute;rico">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">{!! $icone !!}</span>
                                    <div>
                                        <p class="font-medium text-gray-800 dark:text-slate-200 text-sm truncate max-w-[200px]" title="{{ $upload->arquivo_nome }}">
                                            {{ $upload->arquivo_nome }}
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-slate-500">{{ $upload->tamanhoFormatado() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-slate-300 hidden md:table-cell">
                                {{ $upload->cliente?->nome ?? '—' }}
                            </td>
                            <td class="px-4 py-3 hidden lg:table-cell">
                                @if($upload->tarefa)
                                    <a href="#" onclick="window.openModal('/tarefas/{{ $upload->tarefa_id }}/form'); return false;"
                                       class="text-brand hover:underline text-sm no-underline truncate block max-w-[200px]"
                                       title="{{ $upload->tarefa->titulo }}">
                                        {{ $upload->tarefa->titulo }}
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-slate-400 hidden sm:table-cell">
                                {{ $upload->enviadoPor?->nome ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-slate-400 text-xs whitespace-nowrap">
                                {{ $upload->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                @if($upload->foiBaixado())
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400"
                                          title="Baixado em {{ $upload->baixado_em->format('d/m/Y H:i') }}">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Baixado {{ $upload->baixado_em->format('d/m') }}
                                    </span>
                                @elseif($upload->foiVisualizado())
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400"
                                          title="Visualizado em {{ $upload->visualizado_em->format('d/m/Y H:i') }}">
                                        <i class="fa-regular fa-eye"></i>
                                        Visto {{ $upload->visualizado_em->format('d/m') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400">
                                        <i class="fa-solid fa-clock"></i>
                                        Não visto
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3" onclick="event.stopPropagation()">
                                <button onclick="excluirUpload({{ $upload->id }}, '{{ addslashes($upload->arquivo_nome) }}')"
                                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded hover:bg-red-50 dark:hover:bg-red-900/30 bg-transparent border-0 cursor-pointer transition"
                                        title="Excluir do hist&oacute;rico">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        @if($uploads->hasPages())
            <div class="flex justify-center mt-4">
                {{ $uploads->links() }}
            </div>
        @endif
    @endif

</div>

{{-- Modal de Histórico do Arquivo --}}
<div id="historico-overlay"
     class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm"
     onclick="fecharHistorico(event)">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden"
         onclick="event.stopPropagation()">

        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-slate-700">
            <div class="min-w-0">
                <p class="font-semibold text-gray-800 dark:text-slate-100 text-sm truncate" id="hist-arquivo">—</p>
                <p class="text-xs text-gray-400 dark:text-slate-500 mt-0.5" id="hist-meta">—</p>
                <div class="flex gap-3 mt-2" id="hist-badges"></div>
            </div>
            <button onclick="fecharHistorico()"
                    class="ml-3 flex-shrink-0 p-1 rounded border-0 bg-transparent text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 cursor-pointer transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="px-5 py-5" id="hist-timeline">
            <div class="flex justify-center py-8 text-gray-400 dark:text-slate-500">
                <i class="fa-solid fa-spinner fa-spin text-2xl"></i>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const historicoBaseUrl = "{{ url('tarefas/uploads-portal') }}";

const HIST_CONFIG = {
    enviado:     { icone: 'fa-solid fa-paper-plane',        bg: 'bg-blue-100 dark:bg-blue-900/40',   cor: 'text-blue-600 dark:text-blue-400'   },
    visualizado: { icone: 'fa-regular fa-eye',              bg: 'bg-purple-100 dark:bg-purple-900/40', cor: 'text-purple-600 dark:text-purple-400' },
    baixado:     { icone: 'fa-solid fa-circle-arrow-down',  bg: 'bg-green-100 dark:bg-green-900/40',  cor: 'text-green-600 dark:text-green-400'  },
};

function abrirHistorico(uploadId) {
    const overlay  = document.getElementById('historico-overlay');
    const timeline = document.getElementById('hist-timeline');

    document.getElementById('hist-arquivo').textContent = '—';
    document.getElementById('hist-meta').textContent    = '—';
    timeline.innerHTML = '<div class="flex justify-center py-8 text-gray-400 dark:text-slate-500"><i class="fa-solid fa-spinner fa-spin text-2xl"></i></div>';

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    document.body.style.overflow = 'hidden';

    fetch(`${historicoBaseUrl}/${uploadId}/historico`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('hist-arquivo').textContent = data.arquivo;
        document.getElementById('hist-meta').textContent    = `${data.cliente} · ${data.tarefa} · ${data.tamanho}`;

        // Contador de visualizações e downloads
        const badges = document.getElementById('hist-badges');
        badges.innerHTML = [
            { n: data.totalVisualizacoes, label: 'visualização',  plural: 'visualizações',  cor: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300' },
            { n: data.totalDownloads,     label: 'download',      plural: 'downloads',       cor: 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300' },
        ].map(b => `<span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full ${b.cor}">${b.n} ${b.n === 1 ? b.label : b.plural}</span>`).join('');

        timeline.innerHTML = data.eventos.map((ev, i) => {
            const cfg      = HIST_CONFIG[ev.tipo] ?? HIST_CONFIG.enviado;
            const isUltimo = i === data.eventos.length - 1;
            const pendente = ev.data === null;
            const corTexto = pendente ? 'text-gray-400 dark:text-slate-500' : 'text-gray-800 dark:text-slate-200';
            const bgIcone  = pendente ? 'bg-gray-100 dark:bg-slate-700' : cfg.bg;
            const corIcone = pendente ? 'text-gray-400 dark:text-slate-500' : cfg.cor;

            return `<div class="flex gap-3${isUltimo ? '' : ' mb-5'}">
                <div class="flex flex-col items-center">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0 ${bgIcone}">
                        <i class="${cfg.icone} text-sm ${corIcone}"></i>
                    </div>
                    ${!isUltimo ? '<div class="w-px flex-1 mt-1.5 bg-gray-200 dark:bg-slate-700"></div>' : ''}
                </div>
                <div class="pt-1.5 pb-1">
                    <p class="text-sm font-medium ${corTexto}${pendente ? ' italic' : ''}">${ev.label}</p>
                    ${ev.data ? `<p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5"><i class="fa-regular fa-clock mr-1"></i>${ev.data} &nbsp;·&nbsp; <i class="fa-regular fa-user mr-1"></i>${ev.por}</p>` : ''}
                </div>
            </div>`;
        }).join('');
    })
    .catch(() => {
        timeline.innerHTML = '<p class="text-sm text-red-500 py-4 text-center">Erro ao carregar histórico.</p>';
    });
}

function excluirUpload(id, nome) {
    Swal.fire({
        title: 'Excluir do histórico?',
        html: `O registro de <strong>${nome}</strong> e todo o histórico de visualizações e downloads será removido.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    }).then(result => {
        if (!result.isConfirmed) { return; }

        fetch(`{{ url('tarefas/uploads-portal') }}/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(res => {
            if (res.ok) {
                window.location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível excluir o registro.' });
            }
        });
    });
}

function fecharHistorico(event) {
    if (event && event.target !== document.getElementById('historico-overlay')) { return; }
    const overlay = document.getElementById('historico-overlay');
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') { return; }
    const overlay = document.getElementById('historico-overlay');
    if (!overlay.classList.contains('hidden')) {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        document.body.style.overflow = '';
    }
});
</script>
@endpush
