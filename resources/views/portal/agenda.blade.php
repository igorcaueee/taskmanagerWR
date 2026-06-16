@extends('layouts.portal')

@section('title', 'Agenda')

@section('content')
<div class="max-w-5xl mx-auto">

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100"><i class="fa-solid fa-calendar-days"></i> Agenda</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Vencimentos e documentos de {{ $cliente->nome }}.</p>
        </div>
    </div>

    {{-- Navegação de mês --}}
    <div class="flex items-center justify-between mb-4 bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl px-4 py-3 shadow-sm">
        <a href="{{ route('portal.agenda', ['mes' => $mesAnterior->month, 'ano' => $mesAnterior->year]) }}"
           class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-slate-400 hover:text-[#0084AA] no-underline group">
            <i class="fa-solid fa-chevron-left group-hover:-translate-x-0.5 transition-transform"></i>
            <span class="hidden sm:inline text-xs">{{ ucfirst($mesAnterior->translatedFormat('F Y')) }}</span>
        </a>

        <span class="text-base font-bold text-gray-800 dark:text-slate-100 capitalize">
            {{ ucfirst($primeiroDia->translatedFormat('F Y')) }}
        </span>

        <a href="{{ route('portal.agenda', ['mes' => $proximoMes->month, 'ano' => $proximoMes->year]) }}"
           class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-slate-400 hover:text-[#0084AA] no-underline group">
            <span class="hidden sm:inline text-xs">{{ ucfirst($proximoMes->translatedFormat('F Y')) }}</span>
            <i class="fa-solid fa-chevron-right group-hover:translate-x-0.5 transition-transform"></i>
        </a>
    </div>

    {{-- Legenda --}}
    <div class="flex items-center gap-4 mb-4 text-xs text-gray-500 dark:text-slate-400 flex-wrap">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-purple-500 inline-block"></span> Pagamento</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-indigo-500 inline-block"></span> Contrato Social</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-sky-500 inline-block"></span> Informação</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span> Vencido</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span> Pago</span>
    </div>

    {{-- Grid do calendário --}}
    <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl overflow-hidden shadow-sm">
        <div class="grid grid-cols-7 border-b border-gray-200 dark:border-[#334155]">
            @foreach (['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $cabecalho)
                <div class="py-2 text-center text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wide">
                    {{ $cabecalho }}
                </div>
            @endforeach
        </div>

        @foreach ($semanas as $semana)
            <div class="grid grid-cols-7 border-b border-gray-100 dark:border-[#334155] last:border-b-0">
                @foreach ($semana as $dia)
                    @php
                        $chave = $dia->format('Y-m-d');
                        $ehMesAtual = $dia->month === $primeiroDia->month;
                        $ehHoje = $dia->isToday();
                        $eventosDoDia = $eventosPorDia[$chave] ?? collect();
                    @endphp
                    <button type="button"
                        @if($eventosDoDia->count()) onclick="abrirDiaModal('{{ $chave }}')" @endif
                        class="min-h-[90px] p-1.5 border-r border-gray-100 dark:border-[#334155] last:border-r-0 text-left align-top cursor-{{ $eventosDoDia->count() ? 'pointer' : 'default' }} border-0
                                    {{ $ehMesAtual ? 'bg-white dark:bg-[#1e293b]' : 'bg-gray-50 dark:bg-[#0f172a]' }} hover:bg-gray-50 dark:hover:bg-[#263449] transition">

                        <div class="flex justify-end mb-1">
                            <span class="w-6 h-6 flex items-center justify-center text-xs font-medium rounded-full
                                         {{ $ehHoje ? 'bg-[#0084AA] text-white' : ($ehMesAtual ? 'text-gray-700 dark:text-slate-300' : 'text-gray-300 dark:text-slate-600') }}">
                                {{ $dia->day }}
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-1">
                            @foreach ($eventosDoDia->take(4) as $evento)
                                @php
                                    $cor = $evento->foiPago() ? '#22c55e' : ($evento->estaVencido() ? '#ef4444' : match($evento->tipo_arquivo) {
                                        'pagamento' => '#a855f7',
                                        'contrato_social' => '#6366f1',
                                        'informacao' => '#0ea5e9',
                                        default => '#9ca3af',
                                    });
                                @endphp
                                <span title="{{ $evento->arquivo_nome }}"
                                      class="w-2.5 h-2.5 rounded-full flex-shrink-0 ring-1 ring-white/60"
                                      style="background-color: {{ $cor }};"></span>
                            @endforeach
                            @if ($eventosDoDia->count() > 4)
                                <span class="text-[10px] text-gray-400 dark:text-slate-500 leading-none self-end">+{{ $eventosDoDia->count() - 4 }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>
        @endforeach
    </div>

    @if(collect($eventosPorDia)->collapse()->isEmpty())
        <p class="text-center text-sm text-gray-400 dark:text-slate-500 mt-4">Nenhum evento neste mês.</p>
    @endif

</div>

{{-- Modal do dia --}}
<div id="dia-modal-overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-[#1e293b] rounded-xl shadow-xl w-full max-w-md max-h-[85vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-[#334155]">
            <span id="dia-modal-titulo" class="font-semibold text-gray-800 dark:text-slate-100 text-sm"></span>
            <button onclick="fecharDiaModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-200 border-0 bg-transparent cursor-pointer text-lg leading-none">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="dia-modal-body" class="p-5 space-y-3"></div>
    </div>
</div>

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';

const eventosPorDia = @json($eventosJson);

const urlDownloadBase = '{{ route('portal.arquivos.download') }}';
const urlVisualizarBase = '{{ route('portal.arquivos.visualizar') }}';

function formatarData(chave) {
    const [ano, mes, dia] = chave.split('-');
    return `${dia}/${mes}/${ano}`;
}

function abrirDiaModal(chave) {
    const eventos = eventosPorDia[chave] || [];
    const overlay = document.getElementById('dia-modal-overlay');
    const titulo = document.getElementById('dia-modal-titulo');
    const body = document.getElementById('dia-modal-body');

    titulo.textContent = formatarData(chave);
    body.innerHTML = '';

    if (!eventos.length) {
        body.innerHTML = '<p class="text-sm text-gray-400 dark:text-slate-500 text-center py-4">Nenhum evento neste dia.</p>';
    } else {
        eventos.forEach(ev => {
            const tipoBadge = {
                pagamento: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                contrato_social: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                informacao: 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400',
            }[ev.tipo] ?? 'bg-gray-100 text-gray-600';

            const statusBadge = ev.pago
                ? '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><i class="fa-solid fa-circle-check text-[9px]"></i> Pago</span>'
                : (ev.vencido ? '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"><i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Vencido</span>' : '');

            const card = document.createElement('div');
            card.className = 'border border-gray-200 dark:border-[#334155] rounded-lg p-3';
            card.dataset.uploadId = ev.id;
            card.innerHTML = `
                <div class="flex items-center gap-2 flex-wrap mb-1.5">
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold ${tipoBadge}">${ev.tipoLabel}</span>
                    <span class="status-slot">${statusBadge}</span>
                </div>
                <p class="font-medium text-gray-800 dark:text-slate-100 text-sm mb-1">${ev.nome}</p>
                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-slate-400 flex-wrap mb-3">
                    ${ev.valor ? `<span class="font-semibold text-gray-700 dark:text-slate-300">R$ ${Number(ev.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>` : ''}
                    ${ev.vencimento ? `<span>Venc. ${ev.vencimento}</span>` : ''}
                    ${ev.pagoEm ? `<span class="text-green-600 dark:text-green-400">Pago em ${ev.pagoEm}</span>` : ''}
                </div>
                <div class="flex items-center gap-3 action-slot">
                    <a href="${urlVisualizarBase}?file=${encodeURIComponent(ev.path)}" target="_blank" class="inline-flex items-center gap-1 text-[#0084AA] hover:text-[#006e8e] font-medium text-xs transition no-underline">
                        <i class="fa-regular fa-eye"></i> Visualizar
                    </a>
                    <a href="${urlDownloadBase}?file=${encodeURIComponent(ev.path)}" class="inline-flex items-center gap-1 text-gray-400 hover:text-[#0084AA] font-medium text-xs transition no-underline">
                        <i class="fa-solid fa-download"></i> Baixar
                    </a>
                    ${(!ev.pago && ev.tipo === 'pagamento') ? `<button onclick="marcarComoPago(${ev.id}, this)" class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded-lg transition border-0 cursor-pointer ml-auto"><i class="fa-solid fa-check text-xs"></i> Marcar pago</button>` : ''}
                </div>
            `;
            body.appendChild(card);
        });
    }

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function fecharDiaModal() {
    document.getElementById('dia-modal-overlay').classList.add('hidden');
    document.getElementById('dia-modal-overlay').classList.remove('flex');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') fecharDiaModal();
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
            const card = btn.closest('[data-upload-id]');
            if (card) {
                card.querySelector('.status-slot').innerHTML = '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><i class="fa-solid fa-circle-check text-[9px]"></i> Pago</span>';
                btn.remove();
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
