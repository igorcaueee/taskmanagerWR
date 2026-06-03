@extends('layouts.internal')

@section('title', 'Funil de Vendas — WR Assessoria')

@section('content')
    <div class="flex flex-col h-full">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-filter"></i> Funil de Vendas</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Gerencie seus leads e acompanhe a jornada de prospecção até o pós-venda.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('funil.captura') }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded border border-gray-300 dark:border-slate-600 hover:bg-gray-200 dark:hover:bg-slate-600 text-sm no-underline">
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
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Buscar</label>
                <input type="text" name="busca" value="{{ request('busca') }}"
                       placeholder="Nome ou empresa..."
                       onchange="document.getElementById('form-filtros-funil').submit()"
                       class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-48">
            </div>
            @if($podeVerTodos)
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Responsável</label>
                    <select name="responsavel_id" onchange="document.getElementById('form-filtros-funil').submit()"
                            class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
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
                    <div class="flex-shrink-0 w-64 flex flex-col bg-gray-100 dark:bg-slate-700 rounded-xl">
                        {{-- Column header --}}
                        <div class="flex items-center gap-2 px-3 py-2.5 rounded-t-xl"
                             style="background-color: {{ $etapa->cor }}1a; border-bottom: 2px solid {{ $etapa->cor }}">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $etapa->cor }}"></span>
                            <span class="font-semibold text-sm text-gray-800 dark:text-slate-200">{{ $etapa->nome }}</span>
                            <span class="ml-auto text-xs font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-600 rounded-full px-2 py-0.5 kanban-count" data-etapa="{{ $etapa->id }}">
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

    // ── Card click → navigate to detail page ─────────────────────────────────
    let wasDragged = false;

    document.addEventListener('dragstart', () => { wasDragged = true; });
    document.addEventListener('dragend',   () => { setTimeout(() => { wasDragged = false; }, 50); });

    document.addEventListener('click', function (e) {
        if (wasDragged) { return; }

        const card = e.target.closest('.kanban-card');
        if (!card) { return; }
        if (e.target.closest('button, a, form')) { return; }

        const leadId = card.dataset.leadId;
        if (!leadId) { return; }

        window.location.href = `/leads/${leadId}`;
    });

    async function confirmarConversaoModal(leadId, nome) {
        window.openModal(`/leads/${leadId}/form-conversao`);
    }

    async function confirmarConversao(leadId, nome) {
        await confirmarConversaoModal(leadId, nome);
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
