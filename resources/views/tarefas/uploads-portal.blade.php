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
                Histórico de arquivos enviados para clientes pelo portal.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="abrirUploadAvulso()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 text-sm hover:bg-brand/80 cursor-pointer">
                <i class="fa-solid fa-cloud-arrow-up"></i> Enviar arquivo
            </button>
            <a href="{{ route('tarefas.list') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-slate-600 text-sm hover:bg-gray-200 dark:hover:bg-slate-600 no-underline">
                <i class="fa-solid fa-arrow-left"></i> Voltar ao Pipeline
            </a>
        </div>
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
            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Origem</label>
            <select name="origem"
                    class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-brand">
                <option value="">Todas</option>
                <option value="empresa" @selected(request('origem') === 'empresa')>Enviado pela empresa</option>
                <option value="cliente" @selected(request('origem') === 'cliente')>Enviado pelo cliente</option>
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
        @if(request()->hasAny(['cliente_id', 'origem', 'status']))
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
            <p class="text-xs mt-1">Os arquivos enviados ao portal (por tarefa ou pelo botão "Enviar arquivo") aparecerão aqui.</p>
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
                                        @if($upload->descricao_documento)
                                            <p class="text-xs text-gray-600 dark:text-slate-300 truncate max-w-[200px]" title="{{ $upload->descricao_documento }}">{{ $upload->descricao_documento }}</p>
                                        @endif
                                        <p class="text-xs text-gray-400 dark:text-slate-500">{{ $upload->tamanhoFormatado() }}</p>
                                        @if($upload->foiEnviadoPeloCliente())
                                            <span class="inline-flex items-center gap-1 mt-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400">
                                                <i class="fa-solid fa-arrow-up text-[9px]"></i> Enviado pelo cliente
                                            </span>
                                        @endif
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
                                {{ $upload->foiEnviadoPeloCliente() ? ($upload->enviadoPorPortalUsuario?->nome ?? 'Cliente') : ($upload->enviadoPor?->nome ?? '—') }}
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
                                <div class="flex items-center gap-1 justify-end">
                                    @if(!$upload->foiEnviadoPeloCliente() && $upload->cliente?->recebe_arquivos_whatsapp)
                                        @php
                                            $telefoneWhats = $upload->cliente->contatoClientes->first(fn ($c) => filled($c->telefone))?->telefone;
                                            $telefoneLimpo = $telefoneWhats ? preg_replace('/\D/', '', $telefoneWhats) : null;
                                            $mensagemWhats = "Olá! Um novo arquivo (\"{$upload->arquivo_nome}\") foi disponibilizado no seu Portal WR Assessoria: ".route('portal.login');
                                        @endphp
                                        @if($telefoneLimpo)
                                            <a href="https://wa.me/55{{ $telefoneLimpo }}?text={{ urlencode($mensagemWhats) }}"
                                               target="_blank" rel="noopener"
                                               class="p-1.5 text-gray-400 hover:text-green-600 rounded hover:bg-green-50 dark:hover:bg-green-900/30 bg-transparent border-0 transition"
                                               title="Avisar por WhatsApp">
                                                <i class="fa-brands fa-whatsapp text-sm"></i>
                                            </a>
                                        @endif
                                    @endif
                                    <button onclick="excluirUpload({{ $upload->id }}, '{{ addslashes($upload->arquivo_nome) }}')"
                                            class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded hover:bg-red-50 dark:hover:bg-red-900/30 bg-transparent border-0 cursor-pointer transition"
                                            title="Excluir do hist&oacute;rico">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
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

// ── Upload avulso (sem tarefa) ───────────────────────────────────────────────
@php
    $clientesUpload = $clientes->map(fn ($c) => ['id' => $c->id, 'nome' => $c->nome, 'temPasta' => filled($c->pasta_arquivos), 'doc' => preg_replace('/\D/', '', $c->cpfcnpj ?? ''), 'recebeEmail' => (bool) $c->recebe_arquivos_email])->values();
    $clienteFiltroUpload = request('cliente_id') ? (int) request('cliente_id') : null;
@endphp
const clientesUpload = @json($clientesUpload);

function gerarPeriodoPadrao() {
    const now = new Date();
    const nomesMeses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
    return `${String(now.getMonth() + 1).padStart(2, '0')} - ${nomesMeses[now.getMonth()]} ${now.getFullYear()}`;
}

function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function onTipoArquivoChange() {
    const tipo = document.getElementById('swal-tipo-arquivo')?.value;
    document.getElementById('swal-pagamento-fields')?.classList.toggle('hidden', tipo !== 'pagamento');
}

function onArquivoSelecionado(input) {
    const nameEl = document.getElementById('file-selected-name');
    if (input.files.length && nameEl) {
        nameEl.textContent = '📎 ' + input.files[0].name;
        nameEl.classList.remove('hidden');
        document.getElementById('upload-area').classList.add('border-blue-400', 'bg-blue-50');
        iniciarAnaliseDocumento(input.files[0]);
    }
}

@include('tarefas.partials.analise-documento-js')

function onArquivoDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    document.getElementById('upload-area')?.classList.remove('border-blue-400', 'bg-blue-50');
    const files = event.dataTransfer.files;
    if (!files.length) { return; }
    const input = document.getElementById('swal-file-input');
    const dt = new DataTransfer();
    dt.items.add(files[0]);
    input.files = dt.files;
    onArquivoSelecionado(input);
}

function onClienteUploadChange() {
    const cliente = clientesUpload.find(c => String(c.id) === document.getElementById('swal-cliente')?.value);
    const linkEmail = document.getElementById('swal-enviar-link-email');
    if (linkEmail) { linkEmail.checked = !!cliente?.recebeEmail; }
    renderAnaliseDocumento();
}

function abrirUploadAvulso() {
    const clienteFiltro = @json($clienteFiltroUpload);
    const inputClass = 'w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400';
    const opcoesClientes = clientesUpload.map(c =>
        `<option value="${c.id}" ${c.temPasta ? '' : 'disabled'} ${c.id === clienteFiltro && c.temPasta ? 'selected' : ''}>${escHtml(c.nome)}${c.temPasta ? '' : ' (sem pasta configurada)'}</option>`
    ).join('');

    const clienteInicial = clientesUpload.find(c => c.id === clienteFiltro && c.temPasta) ?? null;

    configurarAnaliseDocumento({
        obterCliente: () => clientesUpload.find(c => String(c.id) === document.getElementById('swal-cliente')?.value) ?? null,
        selecionarCliente: id => { document.getElementById('swal-cliente').value = id; onClienteUploadChange(); },
    });

    Swal.fire({
        title: '<span style="font-size:1rem;font-weight:600"><i class="fa-solid fa-file-arrow-up mr-2 text-blue-500"></i>Enviar arquivo ao portal do cliente</span>',
        width: 560,
        html: `
            <div id="upload-area"
                 class="mb-3 border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition"
                 onclick="document.getElementById('swal-file-input').click()"
                 ondragover="event.preventDefault(); this.classList.add('border-blue-400','bg-blue-50')"
                 ondragleave="this.classList.remove('border-blue-400','bg-blue-50')"
                 ondrop="onArquivoDrop(event)">
                <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2 block"></i>
                <p class="text-sm text-gray-600 font-medium">Clique para selecionar ou arraste o arquivo aqui</p>
                <p class="text-xs text-gray-400 mt-1">A IA identifica a guia, o cliente e preenche os campos abaixo</p>
                <p id="file-selected-name" class="text-xs text-blue-600 font-semibold mt-2 hidden"></p>
            </div>
            <input type="file" id="swal-file-input" class="hidden" onchange="onArquivoSelecionado(this)">
            <div id="swal-analise-ia" class="hidden mb-3 text-left"></div>

            <div class="mb-3 text-left">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Cliente <span class="text-red-500">*</span></label>
                <select id="swal-cliente" onchange="onClienteUploadChange()" class="${inputClass}">
                    <option value="">Selecione o cliente...</option>
                    ${opcoesClientes}
                </select>
            </div>

            <div class="mb-3 text-left">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de arquivo <span class="text-red-500">*</span></label>
                <select id="swal-tipo-arquivo" onchange="onTipoArquivoChange()" class="${inputClass}">
                    <option value="">Selecione o tipo...</option>
                    <option value="pagamento">💳 Arquivo de Pagamento</option>
                    <option value="contrato_social">📜 Contrato Social</option>
                    <option value="informacao">ℹ️ Informação</option>
                </select>
            </div>

            <div id="swal-pagamento-fields" class="mb-3 hidden">
                <div class="grid grid-cols-2 gap-3">
                    <div class="text-left">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Data de vencimento</label>
                        <input type="date" id="swal-data-vencimento" class="${inputClass}">
                    </div>
                    <div class="text-left">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Valor (R$)</label>
                        <input type="number" id="swal-valor" step="0.01" min="0" placeholder="0,00" class="${inputClass}">
                    </div>
                </div>
            </div>

            <div class="mb-3 text-left">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Pasta / Categoria <span class="text-red-500">*</span></label>
                <select id="swal-pasta-categoria" class="${inputClass}">
                    <option value="">Selecione a pasta...</option>
                    <option value="Contabilidade">📂 Contabilidade</option>
                    <option value="Financeiro">📂 Financeiro</option>
                    <option value="Fiscal">📂 Fiscal</option>
                    <option value="Patrimônio">📂 Patrimônio</option>
                    <option value="Pessoal">📂 Pessoal</option>
                </select>
            </div>

            <div class="mb-4 text-left">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Período <span class="text-red-500">*</span></label>
                <input type="text" id="swal-pasta-periodo" value="${gerarPeriodoPadrao()}" placeholder="Ex: 05 - Maio 2026" class="${inputClass}">
                <p class="text-xs text-gray-400 mt-1">A subpasta de período será criada automaticamente se não existir.</p>
            </div>

            <div class="mt-4 border-t border-gray-200 pt-4">${htmlOpcoesEmailPortal(clienteInicial?.recebeEmail)}</div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-paper-plane mr-1"></i> Enviar arquivo',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0084AA',
        preConfirm: async () => {
            // Espera a IA terminar de preencher antes de ler os campos
            const bloqueioAnalise = await validarAnaliseDocumento();
            if (bloqueioAnalise) { Swal.showValidationMessage(bloqueioAnalise); return false; }

            const clienteId = document.getElementById('swal-cliente').value;
            const tipoArquivo = document.getElementById('swal-tipo-arquivo').value;
            const categoria = document.getElementById('swal-pasta-categoria').value;
            const periodo = document.getElementById('swal-pasta-periodo').value.trim();
            const dataVencimento = document.getElementById('swal-data-vencimento').value;
            const valor = document.getElementById('swal-valor').value;
            const fileInput = document.getElementById('swal-file-input');

            if (!clienteId) { Swal.showValidationMessage('Selecione o cliente.'); return false; }
            if (!tipoArquivo) { Swal.showValidationMessage('Selecione o tipo de arquivo.'); return false; }
            if (!categoria) { Swal.showValidationMessage('Selecione a pasta / categoria.'); return false; }
            if (!periodo) { Swal.showValidationMessage('Informe o período.'); return false; }
            if (!fileInput.files.length) { Swal.showValidationMessage('Selecione um arquivo para enviar.'); return false; }

            const formData = new FormData();
            formData.append('cliente_id', clienteId);
            formData.append('descricao_documento', descricaoAnaliseDocumento());
            formData.append('enviar_link_email', document.getElementById('swal-enviar-link-email').checked ? '1' : '0');
            formData.append('arquivo', fileInput.files[0]);
            formData.append('tipo_arquivo', tipoArquivo);
            formData.append('pasta_categoria', categoria);
            formData.append('pasta_periodo', periodo);
            if (tipoArquivo === 'pagamento' && dataVencimento) formData.append('data_vencimento', dataVencimento);
            if (tipoArquivo === 'pagamento' && valor) formData.append('valor', valor);

            Swal.showLoading();

            try {
                const res = await fetch('{{ route('tarefas.uploads-portal.store') }}', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    Swal.showValidationMessage(data.error ?? (res.status === 413 ? 'Arquivo muito grande.' : 'Erro ao enviar o arquivo.'));
                    return false;
                }
                return data;
            } catch {
                Swal.showValidationMessage('Erro de conexão ao enviar o arquivo.');
                return false;
            }
        },
    }).then(result => {
        if (!result.isConfirmed || !result.value) { return; }
        const avisoOk = result.value.aviso_email === 'enviado';
        Swal.fire({
            icon: avisoOk ? 'success' : 'warning',
            title: 'Arquivo enviado!',
            text: `"${result.value.nome}" já está no portal do cliente. ${mensagemAvisoEmail(result.value)}`,
            confirmButtonColor: '#0084AA',
        }).then(() => window.location.reload());
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
