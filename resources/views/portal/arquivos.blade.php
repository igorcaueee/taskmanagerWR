@extends('layouts.portal')

@section('title', 'Meus Arquivos')

@section('content')
<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Meus Arquivos</h1>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Documentos e guias disponibilizados pela WR Assessoria para {{ $cliente->nome }}.</p>
    </div>

    @if (empty($arvore))
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-10 text-center text-gray-400 dark:text-slate-500 shadow-sm">
            <p class="text-5xl mb-3">📂</p>
            <p class="font-medium">Nenhum arquivo disponível no momento.</p>
            <p class="text-xs mt-1">Em breve novos documentos serão adicionados aqui.</p>
        </div>
    @else
        @foreach ($arvore as $categoria => $periodos)
        @php
            $iconeCategoria = match($categoria) {
                'Contabilidade' => '🧾',
                'Financeiro'    => '💰',
                'Fiscal'        => '📋',
                'Patrimônio'    => '🏢',
                'Pessoal'       => '👥',
                default         => '📂',
            };
        @endphp
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm overflow-hidden">
            {{-- Cabeçalho da categoria --}}
            <button
                onclick="toggleCategoria('cat-{{ Str::slug($categoria) }}')"
                class="w-full flex items-center justify-between px-5 py-4 bg-gray-50 dark:bg-[#334155] border-b border-gray-200 dark:border-[#475569] cursor-pointer hover:bg-gray-100 dark:hover:bg-[#3e5068] transition text-left"
            >
                <div class="flex items-center gap-3">
                    <span class="text-xl">{{ $iconeCategoria }}</span>
                    <span class="font-semibold text-gray-800 dark:text-slate-100 text-base">{{ $categoria }}</span>
                    <span class="text-xs text-gray-400 dark:text-slate-500">
                        {{ collect($periodos)->flatten(1)->count() }} {{ collect($periodos)->flatten(1)->count() === 1 ? 'arquivo' : 'arquivos' }}
                    </span>
                </div>
                <i class="fa-solid fa-chevron-down text-gray-400 dark:text-slate-500 transition-transform cat-chevron-{{ Str::slug($categoria) }}"></i>
            </button>

            {{-- Períodos dentro da categoria --}}
            <div id="cat-{{ Str::slug($categoria) }}" class="divide-y divide-gray-100 dark:divide-[#334155]">
                @foreach ($periodos as $periodo => $arquivos)
                <div>
                    {{-- Cabeçalho do período --}}
                    <button
                        onclick="togglePeriodo('per-{{ Str::slug($categoria) }}-{{ Str::slug($periodo) }}')"
                        class="w-full flex items-center justify-between px-5 py-3 bg-gray-50/50 dark:bg-[#1e293b]/50 hover:bg-gray-50 dark:hover:bg-[#334155]/30 transition text-left cursor-pointer border-0"
                    >
                        <div class="flex items-center gap-2 pl-6">
                            <i class="fa-regular fa-calendar text-[#0084AA] text-sm"></i>
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">{{ $periodo }}</span>
                            <span class="text-xs text-gray-400 dark:text-slate-500">{{ count($arquivos) }} {{ count($arquivos) === 1 ? 'arquivo' : 'arquivos' }}</span>
                        </div>
                        <i class="fa-solid fa-chevron-down text-gray-400 dark:text-slate-500 text-xs transition-transform per-chevron-{{ Str::slug($categoria) }}-{{ Str::slug($periodo) }}"></i>
                    </button>

                    {{-- Arquivos do período --}}
                    <div id="per-{{ Str::slug($categoria) }}-{{ Str::slug($periodo) }}">
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-50 dark:divide-[#334155]">
                                @foreach ($arquivos as $arquivo)
                                @php
                                    $meta          = $uploads[$arquivo['nome']] ?? null;
                                    $isImagem      = in_array($arquivo['extensao'], ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $isPdf         = $arquivo['extensao'] === 'pdf';
                                    $isOffice      = in_array($arquivo['extensao'], ['doc', 'docx', 'xls', 'xlsx', 'csv']);
                                    $podeAbrirOlho = $isImagem || $isPdf || $isOffice;
                                    $tipoViewer    = $isPdf ? 'pdf' : ($isImagem ? 'imagem' : ($isOffice ? 'outro' : null));
                                    $urlVisualizar = route('portal.arquivos.visualizar', ['file' => $arquivo['path']]);
                                    $urlDownload   = route('portal.arquivos.download',   ['file' => $arquivo['path']]);
                                    $ehPagamento   = $meta && $meta->tipo_arquivo === 'pagamento';
                                    $foiPago       = $meta && $meta->foiPago();
                                    $estaVencido   = $meta && $meta->estaVencido();
                                    $venceHoje     = $meta && $meta->venceHoje();
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-[#334155]/50 transition {{ $estaVencido ? 'bg-red-50/50 dark:bg-red-900/10' : '' }}" data-upload-id="{{ $meta?->id }}">
                                    <td class="px-5 py-3 pl-14">
                                        <div class="flex items-center gap-3">
                                            <span class="text-lg">{{ match($arquivo['extensao']) {
                                                'pdf'  => '📄',
                                                'xls', 'xlsx' => '📊',
                                                'doc', 'docx' => '📝',
                                                'jpg', 'jpeg', 'png', 'gif', 'webp' => '🖼️',
                                                'zip', 'rar', '7z' => '🗜️',
                                                default => '📎',
                                            } }}</span>
                                            <div>
                                                <span class="font-medium text-gray-800 dark:text-slate-100">{{ $arquivo['nome'] }}</span>
                                                {{-- Badges de tipo e status --}}
                                                <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                                    @if($meta && $meta->tipo_arquivo)
                                                        @php
                                                            $tipoBadge = match($meta->tipo_arquivo) {
                                                                'pagamento'      => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                                                                'contrato_social'=> 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                                                'informacao'     => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
                                                                default          => 'bg-gray-100 text-gray-600',
                                                            };
                                                        @endphp
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $tipoBadge }}">
                                                            {{ $meta->labelTipoArquivo() }}
                                                        </span>
                                                    @endif
                                                    @if($ehPagamento && $meta->valor)
                                                        <span class="text-[10px] font-semibold text-gray-600 dark:text-slate-400">
                                                            R$ {{ number_format($meta->valor, 2, ',', '.') }}
                                                        </span>
                                                    @endif
                                                    @if($ehPagamento && $meta->data_vencimento)
                                                        <span class="text-[10px] {{ $estaVencido ? 'text-red-600 dark:text-red-400 font-semibold' : ($venceHoje ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-gray-500 dark:text-slate-400') }}">
                                                            Venc. {{ $meta->data_vencimento->format('d/m/Y') }}
                                                        </span>
                                                    @endif
                                                    @if($foiPago)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                                            <i class="fa-solid fa-circle-check text-[9px]"></i> Pago
                                                        </span>
                                                    @elseif($estaVencido)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                            <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Vencido
                                                        </span>
                                                    @elseif($venceHoje)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                                            <i class="fa-solid fa-bell text-[9px]"></i> Hoje
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-gray-500 dark:text-slate-400 text-xs hidden sm:table-cell w-24">{{ $arquivo['tamanho'] }}</td>
                                    <td class="px-5 py-3 text-gray-500 dark:text-slate-400 text-xs hidden md:table-cell w-36">{{ $arquivo['modificado'] }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="inline-flex items-center gap-3">
                                            @if($ehPagamento && !$foiPago && $meta)
                                            <button
                                                onclick="marcarComoPago({{ $meta->id }}, this)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition border-0 cursor-pointer whitespace-nowrap"
                                                title="Marcar como pago">
                                                <i class="fa-solid fa-check text-xs"></i> Pago
                                            </button>
                                            @endif
                                            @if($podeAbrirOlho)
                                            <button
                                                onclick="abrirVisualizador('{{ $urlVisualizar }}', '{{ $urlDownload }}', '{{ addslashes($arquivo['nome']) }}', '{{ $tipoViewer }}')"
                                                class="inline-flex items-center gap-1 text-[#0084AA] hover:text-[#006e8e] font-medium text-xs transition border-0 bg-transparent cursor-pointer"
                                                title="Visualizar"
                                            >
                                                <i class="fa-regular fa-eye text-base"></i>
                                            </button>
                                            @endif
                                            <a
                                                href="{{ $urlDownload }}"
                                                class="inline-flex items-center gap-1 text-gray-400 hover:text-[#0084AA] font-medium text-xs transition"
                                                title="Baixar"
                                            >
                                                <i class="fa-solid fa-download text-base"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    @endif

</div>

{{-- Modal Visualizador --}}
<div id="viewer-overlay" class="fixed inset-0 z-50 hidden flex-col bg-black/80 backdrop-blur-sm">
    <div class="flex items-center justify-between px-5 py-3 bg-[#0f172a] border-b border-white/10">
        <span id="viewer-title" class="text-sm font-medium text-slate-200 truncate max-w-xs sm:max-w-md"></span>
        <div class="flex items-center gap-3">
            <a id="viewer-download-btn" href="#" class="no-underline text-xs text-[#0084AA] hover:text-[#38bdf8] flex items-center gap-1 transition">
                <i class="fa-solid fa-download"></i> Baixar
            </a>
            <button onclick="fecharVisualizador()" class="text-slate-400 hover:text-white transition border-0 bg-transparent cursor-pointer text-lg leading-none">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
    <div id="viewer-body" class="flex-1 overflow-auto flex items-center justify-center p-4"></div>
</div>

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';

function toggleCategoria(id) {
    const el = document.getElementById(id);
    const chevrons = document.querySelectorAll('.cat-chevron-' + id.replace('cat-', ''));
    if (el) {
        el.classList.toggle('hidden');
        chevrons.forEach(c => c.style.transform = el.classList.contains('hidden') ? 'rotate(-90deg)' : '');
    }
}

function togglePeriodo(id) {
    const el = document.getElementById(id);
    const slug = id.replace('per-', '');
    const chevrons = document.querySelectorAll('.per-chevron-' + slug);
    if (el) {
        el.classList.toggle('hidden');
        chevrons.forEach(c => c.style.transform = el.classList.contains('hidden') ? 'rotate(-90deg)' : '');
    }
}

function abrirVisualizador(urlVisualizar, urlDownload, nome, tipo) {
    const overlay = document.getElementById('viewer-overlay');
    const body    = document.getElementById('viewer-body');
    const title   = document.getElementById('viewer-title');
    const dlBtn   = document.getElementById('viewer-download-btn');

    title.textContent = nome;
    dlBtn.href = urlDownload;
    body.innerHTML = '';
    body.className = 'flex-1 overflow-auto flex items-center justify-center p-4';

    if (tipo === 'imagem') {
        const img = document.createElement('img');
        img.src = urlVisualizar;
        img.alt = nome;
        img.className = 'max-w-full max-h-full rounded shadow-lg object-contain';
        body.appendChild(img);
    } else if (tipo === 'pdf') {
        const iframe = document.createElement('iframe');
        iframe.src = urlVisualizar;
        iframe.className = 'w-full rounded';
        iframe.style.height = 'calc(100vh - 56px)';
        body.className = 'flex-1 overflow-hidden p-0';
        body.appendChild(iframe);
    } else {
        fetch(urlVisualizar).catch(() => {});

        const ext = nome.split('.').pop().toLowerCase();
        const info = {
            xls:  { icone: '📊', label: 'Planilha Excel', cor: 'text-green-600' },
            xlsx: { icone: '📊', label: 'Planilha Excel', cor: 'text-green-600' },
            csv:  { icone: '📊', label: 'Arquivo CSV',    cor: 'text-green-600' },
            doc:  { icone: '📝', label: 'Documento Word', cor: 'text-blue-600'  },
            docx: { icone: '📝', label: 'Documento Word', cor: 'text-blue-600'  },
        }[ext] ?? { icone: '📎', label: 'Arquivo', cor: 'text-gray-500' };

        body.innerHTML = `
            <div class="text-center max-w-sm">
                <div class="text-7xl mb-4">${info.icone}</div>
                <p class="font-semibold text-slate-200 text-lg mb-1">${nome}</p>
                <p class="text-slate-400 text-sm mb-6">Este tipo de arquivo não pode ser visualizado diretamente no navegador.</p>
                <a href="${urlDownload}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#0084AA] hover:bg-[#006e8e] text-white rounded-lg text-sm font-medium transition no-underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Baixar para abrir
                </a>
            </div>`;
    }

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharVisualizador() {
    const overlay = document.getElementById('viewer-overlay');
    const body    = document.getElementById('viewer-body');
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
    body.innerHTML = '';
    body.className = 'flex-1 overflow-auto flex items-center justify-center p-4';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { fecharVisualizador(); }
});

async function marcarComoPago(uploadId, btn) {
    const result = await Swal.fire({
        title: 'Confirmar pagamento?',
        text: 'Esta ação registrará o pagamento com a data e hora atuais.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fa-solid fa-check mr-1"></i> Confirmar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    try {
        const res = await fetch(`/portal/arquivos/${uploadId}/marcar-pago`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (res.ok) {
            const row = btn.closest('tr');
            if (row) {
                // Remove botão e adiciona badge pago
                btn.remove();
                const badgesContainer = row.querySelector('.flex.items-center.gap-1\\.5');
                if (badgesContainer) {
                    const badge = document.createElement('span');
                    badge.className = 'inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                    badge.innerHTML = '<i class="fa-solid fa-circle-check text-[9px]"></i> Pago';
                    badgesContainer.appendChild(badge);
                }
            }
            Swal.fire({ icon: 'success', title: 'Pagamento registrado!', text: `Pago em ${data.pago_em}`, timer: 3000, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Erro', text: data.error ?? 'Não foi possível registrar o pagamento.' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'Erro de conexão', text: 'Tente novamente.' });
    }
}
</script>
@endpush

@endsection
