@extends('layouts.internal')

@section('title', 'Funil de Vendas — WR Assessoria')

@section('content')
    <div class="flex flex-col h-full">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><i class="fa-solid fa-filter"></i> Funil de Vendas</h1>
                <p class="text-sm text-gray-500">Gerencie seus leads e acompanhe a jornada de prospecção até o pós-venda.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('funil.captura') }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded border border-gray-300 hover:bg-gray-200 text-sm no-underline">
                    <i class="fa-solid fa-globe"></i> Form. Público
                </a>
                <button type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80 text-sm"
                        data-modal-url="{{ route('leads.form.create') }}">
                    <i class="fa-solid fa-plus"></i> Novo Lead
                </button>
            </div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('funil') }}" id="form-filtros-funil" class="flex flex-wrap gap-3 mb-5">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Buscar</label>
                <input type="text" name="busca" value="{{ request('busca') }}"
                       placeholder="Nome ou empresa..."
                       onchange="document.getElementById('form-filtros-funil').submit()"
                       class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand w-48">
            </div>
            @if($podeVerTodos)
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Responsável</label>
                    <select name="responsavel_id" onchange="document.getElementById('form-filtros-funil').submit()"
                            class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($usuarios as $usr)
                            <option value="{{ $usr->id }}" @selected(request('responsavel_id') == $usr->id)>{{ $usr->nome }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </form>

        {{-- Kanban Board + Detail Panel --}}
        <div class="flex flex-1 overflow-hidden gap-0">

            {{-- Kanban Board --}}
            <div class="flex gap-4 overflow-x-auto pb-4 flex-1" id="kanban-board">
                @foreach ($etapas as $etapa)
                    <div class="flex-shrink-0 w-64 flex flex-col bg-gray-100 rounded-xl">
                        {{-- Column header --}}
                        <div class="flex items-center gap-2 px-3 py-2.5 rounded-t-xl"
                             style="background-color: {{ $etapa->cor }}1a; border-bottom: 2px solid {{ $etapa->cor }}">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $etapa->cor }}"></span>
                            <span class="font-semibold text-sm text-gray-800">{{ $etapa->nome }}</span>
                            <span class="ml-auto text-xs font-medium text-gray-500 bg-white rounded-full px-2 py-0.5 kanban-count" data-etapa="{{ $etapa->id }}">
                                {{ ($leads[$etapa->id] ?? collect())->count() }}
                            </span>
                        </div>

                        {{-- Drop zone --}}
                        <div class="kanban-column flex-1 min-h-[200px] p-2 space-y-2 overflow-y-auto"
                             data-etapa-id="{{ $etapa->id }}"
                             ondragover="event.preventDefault()"
                             ondrop="handleDrop(event, {{ $etapa->id }})">

                            @foreach (($leads[$etapa->id] ?? collect()) as $lead)
                                @include('funil.partials.kanbanCard', ['lead' => $lead])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            {{-- /Kanban Board --}}

        </div>
        {{-- /Board --}}
    </div>

    @if(session('cpfcnpj_duplicado') !== null)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'warning',
                title: 'CPF/CNPJ já cadastrado',
                html: 'Já existe um cliente com o CPF/CNPJ <strong>{{ session("cpfcnpj_duplicado") }}</strong>.<br>Verifique os clientes cadastrados.',
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#0084aa',
            });
        });
    </script>
    @endif

    @if(session('validation_error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'Dados incompletos',
                text: '{{ session("validation_error") }}',
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#0084aa',
            });
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: '{{ session("error") }}',
                confirmButtonText: 'Entendi',
                confirmButtonColor: '#0084aa',
            });
        });
    </script>
    @endif

    {{-- Toast notification --}}
    <div id="kanban-toast"
         class="hidden fixed bottom-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white transition-opacity duration-300">
    </div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';
    const updateEtapaUrl = (id) => `/leads/${id}/etapa`;

    let draggedCard = null;
    let draggedOriginalEtapa = null;

    function handleDragStart(event, leadId, etapaId) {
        draggedCard = event.currentTarget;
        draggedOriginalEtapa = etapaId;
        event.dataTransfer.setData('leadId', leadId);
        event.dataTransfer.setData('etapaOrigem', etapaId);
        event.dataTransfer.effectAllowed = 'move';
        setTimeout(() => { draggedCard.classList.add('opacity-40'); }, 0);
    }

    function handleDragEnd() {
        if (draggedCard) {
            draggedCard.classList.remove('opacity-40');
            draggedCard = null;
        }
    }

    function handleDragOver(event) {
        event.preventDefault();
        event.currentTarget.classList.add('bg-blue-50');
    }

    function handleDragLeave(event) {
        event.currentTarget.classList.remove('bg-blue-50');
    }

    async function handleDrop(event, novaEtapaId) {
        event.preventDefault();
        const col = event.currentTarget;
        col.classList.remove('bg-blue-50');

        const leadId = event.dataTransfer.getData('leadId');
        const etapaOrigem = parseInt(event.dataTransfer.getData('etapaOrigem'));
        const cardToMove = draggedCard; // capture before Swal nulls it via handleDragEnd

        if (!leadId || novaEtapaId === etapaOrigem) {
            return;
        }

        // Ask for optional description
        const { value: descricao, isConfirmed } = await Swal.fire({
            title: 'Mover lead de etapa',
            input: 'textarea',
            inputLabel: 'Descrição da movimentação (opcional)',
            inputPlaceholder: 'Ex: Cliente demonstrou interesse no serviço X...',
            showCancelButton: true,
            confirmButtonText: 'Mover',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2563eb',
        });

        if (!isConfirmed) {
            return;
        }

        // Optimistic UI: move card
        if (cardToMove) {
            col.appendChild(cardToMove);
        }
        updateCount(etapaOrigem, -1);
        updateCount(novaEtapaId, 1);

        try {
            const response = await fetch(updateEtapaUrl(leadId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ etapa_funil_id: novaEtapaId, descricao: descricao || null }),
            });

            if (!response.ok) {
                throw new Error('Erro ao atualizar etapa.');
            }

            const result = await response.json();

            if (cardToMove) {
                cardToMove.dataset.etapaId = novaEtapaId;
            }

            showToast('Etapa atualizada com sucesso!', 'green');

            if (result.sugerir_conversao) {
                const conv = await Swal.fire({
                    title: 'Lead chegou em "Cliente"!',
                    text: 'Deseja preencher os dados do cliente agora?',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Sim, preencher',
                    cancelButtonText: 'Depois',
                    confirmButtonColor: '#16a34a',
                });

                if (conv.isConfirmed) {
                    await confirmarConversaoModal(leadId, '');
                }
            }
        } catch (err) {
            // Rollback
            const originalCol = document.querySelector(`.kanban-column[data-etapa-id="${etapaOrigem}"]`);
            if (originalCol && cardToMove) {
                originalCol.appendChild(cardToMove);
            }
            updateCount(novaEtapaId, -1);
            updateCount(etapaOrigem, 1);
            showToast('Erro ao atualizar etapa. Tente novamente.', 'red');
        }
    }

    function updateCount(etapaId, delta) {
        const badge = document.querySelector(`.kanban-count[data-etapa="${etapaId}"]`);
        if (badge) {
            badge.textContent = Math.max(0, parseInt(badge.textContent) + delta);
        }
    }

    function showToast(message, color) {
        const toast = document.getElementById('kanban-toast');
        toast.textContent = message;
        const colorMap = { green: 'bg-green-600', red: 'bg-red-600', amber: 'bg-amber-500' };
        toast.className = `fixed bottom-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white transition-opacity duration-300 ${colorMap[color] ?? 'bg-gray-700'}`;
        toast.classList.remove('hidden');
        setTimeout(() => { toast.classList.add('hidden'); }, 3000);
    }

    // Wire up dragover/dragleave on columns
    document.querySelectorAll('.kanban-column').forEach(col => {
        col.addEventListener('dragover', handleDragOver);
        col.addEventListener('dragleave', handleDragLeave);
    });

    // ── Detail Modal (Swal) ───────────────────────────────────────────────────
    let wasDragged = false;
    let activeLeadId = null;

    document.addEventListener('dragstart', () => { wasDragged = true; });
    document.addEventListener('dragend',   () => { setTimeout(() => { wasDragged = false; }, 50); });

    document.addEventListener('click', async function (e) {
        if (wasDragged) { return; }

        const card = e.target.closest('.kanban-card');
        if (!card) { return; }
        if (e.target.closest('button, a, form')) { return; }

        const leadId = card.dataset.leadId;
        if (!leadId) { return; }

        activeLeadId = leadId;
        await openDetailModal(leadId);
    });

    async function openDetailModal(leadId) {
        Swal.fire({
            title: '<span style="font-size:1rem;font-weight:600">Carregando...</span>',
            html: '<div class="flex justify-center py-6"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i></div>',
            showConfirmButton: false,
            width: 640,
            padding: '1.5rem',
            customClass: { popup: 'text-left' },
        });

        try {
            const res = await fetch(`/leads/${leadId}/detalhe`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!res.ok) { throw new Error(); }
            const l = await res.json();
            renderDetailModal(l);
        } catch {
            Swal.fire({ title: 'Erro', text: 'Não foi possível carregar os dados do lead.', icon: 'error' });
        }
    }

    function fmt(val) {
        return val ? 'R$ ' + parseFloat(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '—';
    }

    function field(label, value, extra = '') {
        return `<div>
            <p class="text-xs text-gray-400 mb-0.5">${label}</p>
            <p class="text-sm text-gray-800 font-medium ${extra}">${value || '—'}</p>
        </div>`;
    }

    function renderDetailModal(l) {
        const origemHtml = l.origem === 'formulario'
            ? '<span class="inline-flex items-center gap-1 text-purple-700 text-sm font-medium"><i class="fa-solid fa-globe text-xs"></i> Formulário público</span>'
            : '<span class="text-sm text-gray-600">Manual</span>';

        const etapaHtml = `<span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full" style="background:${l.etapa.cor}22;color:${l.etapa.cor}">
            <span style="width:7px;height:7px;border-radius:50%;background:${l.etapa.cor};display:inline-block;flex-shrink:0"></span>
            ${l.etapa.nome}
        </span>`;

        // Historico
        let historicoHtml = '';
        if (l.historico.length === 0) {
            historicoHtml = '<p class="text-xs text-gray-400 italic ml-4">Nenhuma movimentação registrada.</p>';
        } else {
            historicoHtml = '<ol class="relative border-l border-gray-200 ml-2 space-y-3">';
            l.historico.forEach(h => {
                let item = `<li class="ml-4 relative">
                    <span style="position:absolute;left:-1.15rem;top:4px;width:10px;height:10px;border-radius:50%;background:${h.etapa_nova_cor ?? '#9ca3af'};border:2px solid white;display:block"></span>`;

                if (h.etapa_nova) {
                    item += `<p class="text-xs text-gray-700">
                        ${h.etapa_anterior ? `<span class="font-semibold">${h.etapa_anterior}</span> <i class="fa-solid fa-arrow-right text-gray-400" style="font-size:0.6rem"></i> ` : ''}
                        <span class="font-semibold" style="color:${h.etapa_nova_cor}">${h.etapa_nova}</span>
                    </p>`;
                }
                if (h.descricao) {
                    item += `<p class="text-xs text-gray-500 italic mt-0.5">"${h.descricao}"</p>`;
                }
                item += `<p class="text-[11px] text-gray-400 mt-0.5">${h.data}${h.alterado_por ? ' · ' + h.alterado_por : ''}</p></li>`;
                historicoHtml += item;
            });
            historicoHtml += '</ol>';
        }

        // Optional fields
        const optionals = [
            l.servico      ? `<div class="col-span-2">${field('Serviço', l.servico)}</div>` : '',
            l.possibilidade ? `<div class="col-span-2">${field('Como podemos ajudar', l.possibilidade)}</div>` : '',
            l.observacoes  ? `<div class="col-span-2">${field('Observações', l.observacoes)}</div>` : '',
        ].join('');

        // Converter button
        const converterBtn = l.convertido
            ? `<button disabled class="flex-1 text-sm px-4 py-2 bg-gray-200 text-gray-400 rounded-lg border-0 cursor-not-allowed"><i class="fa-solid fa-check mr-1"></i> Já convertido</button>`
            : `<button onclick="confirmarConversaoModal(${l.id}, '${l.nome.replace(/'/g, "\\'")}')" class="flex-1 text-sm px-4 py-2 bg-green-600 text-white rounded-lg border-0 hover:bg-green-700 cursor-pointer"><i class="fa-solid fa-user-check mr-1"></i> Converter em cliente</button>`;

        // Voltar etapa button — show only if most recent historico has a previous stage
        const ultimoHistorico = l.historico[0] ?? null;
        const voltarBtn = (ultimoHistorico && ultimoHistorico.etapa_anterior_id)
            ? `<button onclick="voltarEtapaLead(${l.id}, ${ultimoHistorico.etapa_anterior_id}, '${(ultimoHistorico.etapa_anterior ?? '').replace(/'/g, "\\'")}')" title="Voltar para: ${ultimoHistorico.etapa_anterior ?? ''}" class="text-sm px-3 py-2 bg-amber-100 text-amber-700 rounded-lg border-0 hover:bg-amber-200 cursor-pointer"><i class="fa-solid fa-rotate-left"></i></button>`
            : '';

        const html = `
            <div class="space-y-4 text-left">
                {{-- Etapa + Origem --}}
                <div class="flex items-center justify-between flex-wrap gap-2">
                    ${etapaHtml}
                    ${origemHtml}
                </div>

                {{-- Fields grid --}}
                <div class="grid grid-cols-2 gap-x-6 gap-y-3 bg-gray-50 rounded-xl p-4">
                    ${field('Empresa', l.empresa)}
                    ${field('Responsável', l.responsavel)}
                    ${field('E-mail', l.email)}
                    ${field('Telefone', l.telefone)}
                    ${field('Faturamento', fmt(l.faturamento))}
                    ${field('Honorário estimado', fmt(l.honorario), 'text-green-700')}
                    ${optionals}
                </div>

                {{-- Histórico --}}
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i> Histórico de etapas
                    </p>
                    ${historicoHtml}
                </div>

                {{-- Actions --}}
                <div class="flex gap-2 pt-2 border-t border-gray-100">
                    ${voltarBtn}
                    <button onclick="window.openModal('/leads/${l.id}/form'); Swal.close();" class="flex-1 text-sm px-4 py-2 bg-brand text-white rounded-lg border-0 hover:bg-brand/80 cursor-pointer">
                        <i class="fa-solid fa-pen-to-square mr-1"></i> Editar
                    </button>
                    ${converterBtn}
                </div>
            </div>`;

        Swal.fire({
            title: `<span style="font-size:1.05rem;font-weight:700">${l.nome}</span>`,
            html: html,
            showConfirmButton: false,
            showCloseButton: true,
            width: 640,
            padding: '1.5rem',
            customClass: { popup: 'text-left' },
        });
    }

    async function voltarEtapaLead(leadId, etapaAnteriorId, etapaAnteriorNome) {
        Swal.close();
        await new Promise(r => setTimeout(r, 200));

        const result = await Swal.fire({
            title: 'Voltar etapa?',
            text: `O lead será movido de volta para "${etapaAnteriorNome}".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, voltar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d97706',
        });

        if (!result.isConfirmed) { return; }

        try {
            const res = await fetch(updateEtapaUrl(leadId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ etapa_funil_id: etapaAnteriorId, descricao: null }),
            });

            if (!res.ok) { throw new Error(); }

            // Move card visually
            const card = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
            const novaCol = document.querySelector(`.kanban-column[data-etapa-id="${etapaAnteriorId}"]`);
            if (card && novaCol) {
                const oldEtapaId = parseInt(card.dataset.etapaId);
                novaCol.appendChild(card);
                card.dataset.etapaId = etapaAnteriorId;
                updateCount(oldEtapaId, -1);
                updateCount(etapaAnteriorId, 1);
            }

            showToast(`Voltou para "${etapaAnteriorNome}"`, 'amber');
        } catch {
            showToast('Erro ao voltar etapa. Tente novamente.', 'red');
        }
    }

    async function confirmarConversaoModal(leadId, nome) {
        Swal.close();
        await new Promise(r => setTimeout(r, 200));
        window.openModal(`/leads/${leadId}/form-conversao`);
    }

    async function confirmarConversao(leadId, nome) {
        await confirmarConversaoModal(leadId, nome);
    }

    async function converterLead(leadId) {
        try {
            const res = await fetch(`/leads/${leadId}/converter`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) {
                const err = await res.json();
                throw new Error(err.error ?? 'Erro ao converter.');
            }

            const data = await res.json();

            showToast(`"${data.cliente_nome}" convertido em cliente!`, 'green');

            // Update card visually
            const card = document.querySelector(`.kanban-card[data-lead-id="${leadId}"]`);
            if (card) {
                card.classList.remove('bg-white', 'border-gray-200');
                card.classList.add('bg-green-50', 'border-green-300');
                const convertBtn = card.querySelector('.btn-converter-lead');
                if (convertBtn) { convertBtn.remove(); }
            }

            // Refresh modal if open
            if (activeLeadId == leadId) {
                await openDetailModal(leadId);
            }
        } catch (err) {
            Swal.fire({ title: 'Erro', text: err.message, icon: 'error' });
        }
    }

    // ── Delete lead ───────────────────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-lead');
        if (!btn) { return; }
        e.stopPropagation();

        const nome = btn.dataset.leadNome;
        const form = btn.closest('form');

        Swal.fire({
            title: 'Excluir lead?',
            text: `Tem certeza que deseja excluir "${nome}"? Esta ação não pode ser desfeita.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // ── Convert from card button ──────────────────────────────────────────────
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btn-converter-lead');
        if (!btn) { return; }
        e.stopPropagation();

        const leadId = btn.dataset.leadId;
        const nome   = btn.dataset.leadNome;
        await confirmarConversao(leadId, nome);
    });
</script>
@endpush
