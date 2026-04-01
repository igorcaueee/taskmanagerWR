@extends('layouts.internal')

@section('title', 'Pipeline de Tarefas — WR Assessoria')

@section('content')
    <div class="flex flex-col h-full">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><i class="fa-solid fa-chart-gantt"></i> Pipeline</h1>
                <p class="text-sm text-gray-500">Arraste as tarefas entre as etapas para atualizar o status.</p>
            </div>
            <button type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80 text-sm"
                    data-modal-url="{{ route('tarefas.form.create') }}">
                <i class="fa-solid fa-plus"></i> Nova Tarefa
            </button>
        </div>

        {{-- Cycle Navigator --}}
        @php
            $statusLabels = ['passado' => 'Passado', 'atual' => 'Atual', 'proximo' => 'Futuro'];
            $statusColors = [
                'passado' => 'bg-gray-100 text-gray-500',
                'atual'   => 'bg-green-100 text-green-700',
                'proximo' => 'bg-blue-100 text-blue-700',
            ];
            $cicloAtual = \App\Models\Ciclo::current();
        @endphp
        <div class="flex items-center justify-between mb-4 bg-white border border-gray-200 rounded-xl px-4 py-3 shadow-sm">
            {{-- Prev --}}
            <a href="{{ route('tarefas.list', array_merge(request()->except(['ciclo_id', 'page']), ['ciclo_id' => $cicloPrev->id])) }}"
               class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand no-underline group">
                <i class="fa-solid fa-chevron-left group-hover:translate-x-[-2px] transition-transform"></i>
                <span class="hidden sm:inline text-xs truncate max-w-[140px]">{{ $cicloPrev->nome }}</span>
            </a>

            {{-- Current selected --}}
            <div class="flex flex-col items-center gap-1">
                <span class="text-sm font-bold text-gray-900">{{ $cicloSelecionado->nome }}</span>
                <div class="flex items-center gap-2">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusColors[$cicloSelecionado->status] }}">
                        {{ $statusLabels[$cicloSelecionado->status] }}
                    </span>
                    @if ($cicloSelecionado->id !== $cicloAtual->id)
                        <a href="{{ route('tarefas.list', array_merge(request()->except(['ciclo_id', 'page']), ['ciclo_id' => $cicloAtual->id])) }}"
                           class="text-xs text-brand hover:underline no-underline">
                            <i class="fa-regular fa-calendar-check mr-0.5"></i>Ir para hoje
                        </a>
                    @endif
                </div>
            </div>

            {{-- Next --}}
            <a href="{{ route('tarefas.list', array_merge(request()->except(['ciclo_id', 'page']), ['ciclo_id' => $cicloNext->id])) }}"
               class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand no-underline group">
                <span class="hidden sm:inline text-xs truncate max-w-[140px]">{{ $cicloNext->nome }}</span>
                <i class="fa-solid fa-chevron-right group-hover:translate-x-[2px] transition-transform"></i>
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('tarefas.list') }}" id="form-filtros" class="flex flex-wrap gap-3 mb-5">
            <input type="hidden" name="ciclo_id" value="{{ $cicloSelecionado->id }}">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Departamento</label>
                <select name="departamento_id" onchange="document.getElementById('form-filtros').submit()"
                        class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos</option>
                    @foreach ($departamentos as $dep)
                        <option value="{{ $dep->id }}" @selected(request('departamento_id') == $dep->id)>{{ $dep->nome }}</option>
                    @endforeach
                </select>
            </div>
            @if ($podeVerTodas)
            <div>
                <label class="block text-xs text-gray-500 mb-1">Responsável</label>
                <select name="responsavel_id" onchange="document.getElementById('form-filtros').submit()"
                        class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $usr)
                        <option value="{{ $usr->id }}" @selected(request('responsavel_id') == $usr->id)>{{ $usr->nome }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>

        {{-- Kanban Board + Detail Panel --}}
        <div class="flex flex-1 overflow-hidden gap-0">

            {{-- Kanban Board --}}
            <div class="flex gap-4 overflow-x-auto pb-4 flex-1 min-w-0" id="kanban-board">
            @foreach ($etapas as $etapa)
                <div class="flex-shrink-0 w-64 flex flex-col bg-gray-100 rounded-xl">
                    {{-- Column header --}}
                    <div class="flex items-center gap-2 px-3 py-2.5 rounded-t-xl"
                         style="background-color: {{ $etapa->cor ?? '#6b7280' }}1a; border-bottom: 2px solid {{ $etapa->cor ?? '#6b7280' }}">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $etapa->cor ?? '#6b7280' }}"></span>
                        <span class="font-semibold text-sm text-gray-800">{{ $etapa->nome }}</span>
                        <span class="ml-auto text-xs font-medium text-gray-500 bg-white rounded-full px-2 py-0.5 kanban-count" data-etapa="{{ $etapa->id }}">
                            {{ ($tarefas[$etapa->id] ?? collect())->count() }}
                        </span>
                    </div>

                    {{-- Drop zone --}}
                    <div class="kanban-column flex-1 min-h-[200px] p-2 space-y-2 overflow-y-auto"
                         data-etapa-id="{{ $etapa->id }}"
                         ondragover="event.preventDefault()"
                         ondrop="handleDrop(event, {{ $etapa->id }})">

                        @foreach (($tarefas[$etapa->id] ?? collect()) as $tarefa)
                            @include('tarefas.partials.kanbanCard', ['tarefa' => $tarefa])
                        @endforeach
                    </div>
                </div>
            @endforeach
            </div>
            {{-- /Kanban Board --}}

            {{-- Detail Panel --}}
            <div id="detail-panel"
                 class="hidden w-120 flex-shrink-0 bg-white border-l border-gray-200 flex flex-col overflow-y-auto">

                {{-- Panel header --}}
                <div class="flex items-start justify-between p-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <h2 id="dp-titulo" class="text-sm font-semibold text-gray-900 leading-snug pr-2"></h2>
                    <button type="button" onclick="closeDetailPanel()"
                            class="text-gray-400 hover:text-gray-600 bg-transparent border-0 p-0 flex-shrink-0">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                {{-- Panel body --}}
                <div class="p-4 space-y-4 flex-1">

                    {{-- Meta grid --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Etapa</p>
                            <span id="dp-etapa"
                                  class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Prioridade</p>
                            <span id="dp-prioridade" class="text-xs font-medium text-gray-700"></span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Cliente</p>
                            <p id="dp-cliente" class="text-xs text-gray-700"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Departamento</p>
                            <p id="dp-departamento" class="text-xs text-gray-700"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Responsável</p>
                            <p id="dp-responsavel" class="text-xs text-gray-700"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Vencimento</p>
                            <p id="dp-vencimento" class="text-xs font-medium"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 mb-0.5">Recorrência</p>
                            <p id="dp-recorrencia" class="text-xs text-gray-700"></p>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div id="dp-descricao-block" class="hidden">
                        <p class="text-xs text-gray-400 mb-1">Descrição</p>
                        <p id="dp-descricao" class="text-xs text-gray-700 whitespace-pre-line leading-relaxed"></p>
                    </div>

                    {{-- History --}}
                    <div>
                        <p class="text-xs text-gray-400 mb-2"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Histórico de etapas</p>
                        <ol id="dp-historico" reversed class="relative border-l border-gray-200 ml-2 space-y-3 text-xs">
                            <li class="ml-4 text-gray-400 italic" id="dp-historico-empty">Nenhuma movimentação registrada.</li>
                        </ol>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-2 border-t border-gray-100 flex gap-2">
                        <button type="button" id="dp-btn-editar"
                                class="flex-1 text-xs px-3 py-1.5 bg-brand text-white rounded border-0 hover:bg-brand/80">
                            <i class="fa-solid fa-pen-to-square mr-1"></i> Editar
                        </button>
                    </div>
                </div>
            </div>
            {{-- /Detail Panel --}}

        </div>
        {{-- /Board + Panel --}}
    </div>

    {{-- Toast notification --}}
    <div id="kanban-toast"
         class="hidden fixed bottom-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white transition-opacity duration-300">
    </div>
@endsection

@push('scripts')
<script>
    const updateEtapaUrl = (id) => `/tarefas/${id}/etapa`;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        ?? '{{ csrf_token() }}';

    let draggedCard = null;
    let draggedOriginalEtapa = null;

    function handleDragStart(event, tarefaId, etapaId) {
        draggedCard = event.currentTarget;
        draggedOriginalEtapa = etapaId;
        event.dataTransfer.setData('tarefaId', tarefaId);
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

        const tarefaId = event.dataTransfer.getData('tarefaId');
        const etapaOrigem = parseInt(event.dataTransfer.getData('etapaOrigem'));

        if (!tarefaId || novaEtapaId === etapaOrigem) {
            return;
        }

        // Optimistic UI: move card
        if (draggedCard) {
            col.appendChild(draggedCard);
        }

        updateCount(etapaOrigem, -1);
        updateCount(novaEtapaId, 1);

        try {
            const response = await fetch(updateEtapaUrl(tarefaId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ etapa_id: novaEtapaId }),
            });

            if (!response.ok) {
                throw new Error('Erro ao atualizar etapa.');
            }

            const result = await response.json();

            // Update the card's data attribute and visual state
            if (draggedCard) {
                draggedCard.dataset.etapaId = novaEtapaId;

                // Apply/remove green concluded style
                if (result.finalizado) {
                    draggedCard.classList.remove('bg-white', 'border-gray-200', 'border-amber-400', 'border-l-4');
                    draggedCard.classList.add('bg-green-50', 'border-green-300');
                } else {
                    draggedCard.classList.remove('bg-green-50', 'border-green-300');
                    draggedCard.classList.add('bg-white', 'border-gray-200');
                }
            }

            showToast('Etapa atualizada com sucesso!', 'green');
        } catch (err) {
            // Rollback: move card back
            const originalCol = document.querySelector(`.kanban-column[data-etapa-id="${etapaOrigem}"]`);
            if (originalCol && draggedCard) {
                originalCol.appendChild(draggedCard);
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
        const colorMap = {
            green: 'bg-green-600',
            red: 'bg-red-600',
            amber: 'bg-amber-500',
        };
        toast.className = `fixed bottom-5 right-5 z-50 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white transition-opacity duration-300 ${colorMap[color] ?? 'bg-gray-700'}`;
        toast.classList.remove('hidden');
        setTimeout(() => { toast.classList.add('hidden'); }, 3000);
    }

    // Wire up dragover/dragleave on columns
    document.querySelectorAll('.kanban-column').forEach(col => {
        col.addEventListener('dragover', handleDragOver);
        col.addEventListener('dragleave', handleDragLeave);
    });

    // ── Detail Panel ──────────────────────────────────────────────────────────
    let wasDragged = false;
    let activeTarefaId = null;

    document.addEventListener('dragstart', () => { wasDragged = true; });
    document.addEventListener('dragend',   () => { setTimeout(() => { wasDragged = false; }, 50); });

    const prioridadeColors = {
        'Baixa': 'text-gray-500', 'Normal': 'text-blue-600',
        'Média': 'text-yellow-600', 'Alta': 'text-orange-600', 'Urgente': 'text-red-600',
    };

    document.addEventListener('click', async function (e) {
        if (wasDragged) { return; }

        const card = e.target.closest('.kanban-card');
        if (!card) { return; }

        // Ignore clicks on action buttons inside the card
        if (e.target.closest('button, a, form')) { return; }

        const tarefaId = card.dataset.tarefaId;
        if (!tarefaId) { return; }

        // Highlight selected card
        document.querySelectorAll('.kanban-card').forEach(c => c.classList.remove('ring-2', 'ring-brand'));
        card.classList.add('ring-2', 'ring-brand');

        activeTarefaId = tarefaId;
        await openDetailPanel(tarefaId);
    });

    async function openDetailPanel(tarefaId) {
        const panel = document.getElementById('detail-panel');
        panel.classList.remove('hidden');

        // Show loading state
        document.getElementById('dp-titulo').textContent = 'Carregando…';

        try {
            const res = await fetch(`/tarefas/${tarefaId}/detalhe`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!res.ok) { throw new Error(); }
            const t = await res.json();
            renderDetailPanel(t);
        } catch {
            document.getElementById('dp-titulo').textContent = 'Erro ao carregar.';
        }
    }

    function renderDetailPanel(t) {
        document.getElementById('dp-titulo').textContent = t.titulo;

        // Etapa
        const etapaEl = document.getElementById('dp-etapa');
        etapaEl.innerHTML = `<span style="width:8px;height:8px;border-radius:50%;background:${t.etapa.cor};display:inline-block;flex-shrink:0"></span>${t.etapa.nome}`;

        // Prioridade
        const prioEl = document.getElementById('dp-prioridade');
        prioEl.textContent = t.prioridade;
        prioEl.className = `text-xs font-medium ${prioridadeColors[t.prioridade] ?? 'text-gray-700'}`;

        document.getElementById('dp-cliente').textContent      = t.cliente      ?? '—';
        document.getElementById('dp-departamento').textContent = t.departamento ?? '—';
        document.getElementById('dp-responsavel').textContent  = t.responsavel  ?? '—';

        // Vencimento
        const vencEl = document.getElementById('dp-vencimento');
        vencEl.textContent = t.data_vencimento ?? '—';
        vencEl.className   = `text-xs font-medium ${t.atrasada ? 'text-red-600' : 'text-gray-700'}`;

        const recEl = document.getElementById('dp-recorrencia');
        const frequenciaLabels = {
            'nenhuma':    'Não se repete',
            'semanal':    'Semanal',
            'mensal':     'Mensal',
            'trimestral': 'Trimestral',
            'semestral':  'Semestral (6 meses)',
            'anual':      'Anual',
        };
        if (t.recorrente && t.frequencia && t.frequencia !== 'nenhuma') {
            recEl.innerHTML = `<i class="fa-solid fa-rotate mr-1 text-blue-500"></i>${frequenciaLabels[t.frequencia] ?? t.frequencia}`;
            recEl.className = 'text-xs font-medium text-blue-600';
        } else {
            recEl.textContent = 'Não se repete';
            recEl.className = 'text-xs text-gray-400';
        }

        // Description
        const descBlock = document.getElementById('dp-descricao-block');
        if (t.descricao) {
            document.getElementById('dp-descricao').textContent = t.descricao;
            descBlock.classList.remove('hidden');
        } else {
            descBlock.classList.add('hidden');
        }

        // History
        const histList  = document.getElementById('dp-historico');
        const histEmpty = document.getElementById('dp-historico-empty');
        // Remove old items except the empty placeholder
        histList.querySelectorAll('li:not(#dp-historico-empty)').forEach(el => el.remove());

        if (t.historico.length === 0) {
            histEmpty.classList.remove('hidden');
        } else {
            histEmpty.classList.add('hidden');
            t.historico.forEach(h => {
                const li = document.createElement('li');
                li.className = 'ml-4';
                li.innerHTML = `
                    <span style="position:absolute;left:-5px;margin-top:6px;width:10px;height:10px;border-radius:50%;background:${h.etapa_nova_cor};border:2px solid white;display:block"></span>
                    <p style="font-size:0.7rem;color:#374151">
                        ${h.etapa_anterior ? `<span style="font-weight:600">${h.etapa_anterior}</span> <i class="fa-solid fa-arrow-right" style="color:#9ca3af;font-size:0.6rem"></i> ` : ''}
                        <span style="font-weight:600;color:${h.etapa_nova_cor}">${h.etapa_nova}</span>
                    </p>
                    <p style="font-size:0.65rem;color:#9ca3af;margin-top:1px">${h.data}${h.alterado_por ? ' · ' + h.alterado_por : ''}</p>
                `;
                histList.appendChild(li);
            });
        }

        // Edit button
        document.getElementById('dp-btn-editar').onclick = () => {
            window.openModal(`/tarefas/${t.id}/form`);
        };
    }

    function closeDetailPanel() {
        document.getElementById('detail-panel').classList.add('hidden');
        document.querySelectorAll('.kanban-card').forEach(c => c.classList.remove('ring-2', 'ring-brand'));
        activeTarefaId = null;
    }

    // ── Excluir tarefa (kanban) ───────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-delete-kanban');
        if (!btn) { return; }
        e.stopPropagation();

        const titulo = btn.dataset.tarefaTitulo;
        const form = btn.closest('form');

        Swal.fire({
            title: 'Excluir tarefa?',
            text: `Tem certeza que deseja excluir "${titulo}"? Esta ação não pode ser desfeita.`,
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

    // ── Passar para próximo ciclo ─────────────────────────────────────────────
    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btn-proximo-ciclo');
        if (!btn) { return; }
        e.stopPropagation();

        const tarefaId = btn.dataset.tarefaId;
        const titulo = btn.dataset.tarefaTitulo;

        const confirmed = await Swal.fire({
            title: 'Passar para o próximo ciclo?',
            text: `"${titulo}" será movida para a próxima semana.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, mover',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#f59e0b',
        });

        if (!confirmed.isConfirmed) { return; }

        try {
            const res = await fetch(`/tarefas/${tarefaId}/ciclo/proximo`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            if (!res.ok) { throw new Error(); }

            const data = await res.json();
            showToast(`Movida para: ${data.ciclo_nome}`, 'amber');

            // Remove the card from the board since it no longer belongs to this cycle
            const card = document.querySelector(`.kanban-card[data-tarefa-id="${tarefaId}"]`);
            if (card) {
                const col = card.closest('.kanban-column');
                const etapaId = card.dataset.etapaId;
                card.remove();
                updateCount(etapaId, -1);
            }
        } catch {
            showToast('Erro ao mover tarefa. Tente novamente.', 'red');
        }
    });
</script>
@endpush
