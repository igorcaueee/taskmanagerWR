@extends('layouts.internal')

@section('title', 'Pipeline de Tarefas — WR Assessoria')

@section('content')
    <div class="flex flex-col h-full">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-chart-gantt"></i> Pipeline</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Arraste as tarefas entre as etapas para atualizar o status.</p>
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
               class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-brand no-underline group">
                <i class="fa-solid fa-chevron-left group-hover:translate-x-[-2px] transition-transform"></i>
                <span class="hidden sm:inline text-xs truncate max-w-[140px]">{{ $cicloPrev->nome }}</span>
            </a>

            {{-- Current selected --}}
            <div class="flex flex-col items-center gap-1">
                <span class="text-sm font-bold text-gray-900 dark:text-slate-100">{{ $cicloSelecionado->nome }}</span>
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
               class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-brand no-underline group">
                <span class="hidden sm:inline text-xs truncate max-w-[140px]">{{ $cicloNext->nome }}</span>
                <i class="fa-solid fa-chevron-right group-hover:translate-x-[2px] transition-transform"></i>
            </a>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('tarefas.list') }}" id="form-filtros" class="flex flex-wrap gap-3 mb-5">
            <input type="hidden" name="ciclo_id" value="{{ $cicloSelecionado->id }}">
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Departamento</label>
                <select name="departamento_id" onchange="document.getElementById('form-filtros').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos</option>
                    @foreach ($departamentos as $dep)
                        <option value="{{ $dep->id }}" @selected(request('departamento_id') == $dep->id)>{{ $dep->nome }}</option>
                    @endforeach
                </select>
            </div>
            @if ($podeVerTodas)
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Responsável</label>
                <select name="responsavel_id" onchange="document.getElementById('form-filtros').submit()"
                        class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $usr)
                        <option value="{{ $usr->id }}" @selected(request('responsavel_id') == $usr->id)>{{ $usr->nome }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </form>

        {{-- Kanban Board --}}
        <div class="flex gap-4 overflow-x-auto pb-4 flex-1" id="kanban-board">
            @foreach ($etapas as $etapa)
                <div class="flex-shrink-0 w-64 flex flex-col bg-gray-100 dark:bg-slate-700 rounded-xl">
                    {{-- Column header --}}
                    <div class="flex items-center gap-2 px-3 py-2.5 rounded-t-xl"
                         style="background-color: {{ $etapa->cor ?? '#6b7280' }}1a; border-bottom: 2px solid {{ $etapa->cor ?? '#6b7280' }}">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $etapa->cor ?? '#6b7280' }}"></span>
                        <span class="font-semibold text-sm text-gray-800 dark:text-slate-200">{{ $etapa->nome }}</span>
                        <span class="ml-auto text-xs font-medium text-gray-500 dark:text-gray-400 bg-white dark:bg-slate-600 rounded-full px-2 py-0.5 kanban-count" data-etapa="{{ $etapa->id }}">
                            {{ ($tarefas[$etapa->id] ?? collect())->count() }}
                        </span>
                    </div>

                    {{-- Drop zone --}}
                    <div class="kanban-column flex-1 min-h-[200px] p-2 space-y-2 overflow-y-auto"
                         data-etapa-id="{{ $etapa->id }}"
                         data-etapa-nome="{{ $etapa->nome }}"
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
        const cardToMove = draggedCard;

        if (!tarefaId || novaEtapaId === etapaOrigem) {
            return;
        }

        const nomeEtapa = (col.dataset.etapaNome ?? '').trim().toLowerCase();
        const isImpedimento = nomeEtapa === 'impedimento';

        // ── Impedimento: pedir motivo antes de mover ─────────────────────────
        if (isImpedimento) {
            const swalResult = await Swal.fire({
                title: '<span style="font-size:1rem;font-weight:600"><i class="fa-solid fa-circle-exclamation mr-2 text-red-500"></i>Por que está impedida?</span>',
                html: '<p class="text-sm text-gray-500 mb-1">Descreva o motivo do impedimento para registrar no histórico da tarefa.</p>',
                input: 'textarea',
                inputPlaceholder: 'Ex: Aguardando documentação do cliente...',
                inputAttributes: { rows: 4, style: 'font-size:0.875rem;' },
                showCancelButton: true,
                confirmButtonText: 'Confirmar impedimento',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Informe o motivo do impedimento.';
                    }
                },
            });

            if (!swalResult.isConfirmed) { return; }

            if (cardToMove) { col.appendChild(cardToMove); }
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
                    body: JSON.stringify({ etapa_id: novaEtapaId, observacao: swalResult.value }),
                });

                if (!response.ok) { throw new Error(); }

                if (cardToMove) {
                    cardToMove.dataset.etapaId = novaEtapaId;
                    cardToMove.classList.remove('bg-green-50', 'border-green-300');
                    cardToMove.classList.add('bg-white', 'border-gray-200');
                }

                showToast('Tarefa marcada como impedida!', 'red');
            } catch {
                const originalCol = document.querySelector(`.kanban-column[data-etapa-id="${etapaOrigem}"]`);
                if (originalCol && cardToMove) { originalCol.appendChild(cardToMove); }
                updateCount(novaEtapaId, -1);
                updateCount(etapaOrigem, 1);
                showToast('Erro ao atualizar etapa. Tente novamente.', 'red');
            }

            return;
        }

        // ── Fluxo normal (otimista) ───────────────────────────────────────────
        if (cardToMove) { col.appendChild(cardToMove); }
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

            if (!response.ok) { throw new Error('Erro ao atualizar etapa.'); }

            const result = await response.json();

            if (cardToMove) {
                cardToMove.dataset.etapaId = novaEtapaId;

                if (result.finalizado) {
                    cardToMove.classList.remove('bg-white', 'border-gray-200', 'border-amber-400', 'border-l-4');
                    cardToMove.classList.add('bg-green-50', 'border-green-300');
                } else {
                    cardToMove.classList.remove('bg-green-50', 'border-green-300');
                    cardToMove.classList.add('bg-white', 'border-gray-200');
                }
            }

            showToast('Etapa atualizada com sucesso!', 'green');

            // Solicitar upload se finalizado e tarefa requer arquivo
            if (result.requer_envio_arquivo) {
                await mostrarUploadArquivo(tarefaId);
            }
        } catch {
            const originalCol = document.querySelector(`.kanban-column[data-etapa-id="${etapaOrigem}"]`);
            if (originalCol && cardToMove) { originalCol.appendChild(cardToMove); }
            updateCount(novaEtapaId, -1);
            updateCount(etapaOrigem, 1);
            showToast('Erro ao atualizar etapa. Tente novamente.', 'red');
        }
    }

    function gerarPeriodoPadrao() {
        const now = new Date();
        const mes = String(now.getMonth() + 1).padStart(2, '0');
        const nomesMeses = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
        const nomeMes = nomesMeses[now.getMonth()];
        const ano = now.getFullYear();
        return `${mes} - ${nomeMes} ${ano}`;
    }

    async function mostrarUploadArquivo(tarefaId) {
        const periodoPadrao = gerarPeriodoPadrao();
        await Swal.fire({
            title: '<span style="font-size:1rem;font-weight:600"><i class="fa-solid fa-file-arrow-up mr-2 text-blue-500"></i>Enviar arquivo ao portal do cliente</span>',
            html: `
                <p class="text-sm text-gray-500 mb-4">Esta tarefa requer o envio de um arquivo para o portal do cliente.</p>

                <div class="mb-3 text-left">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Pasta / Categoria <span class="text-red-500">*</span></label>
                    <select id="swal-pasta-categoria" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
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
                    <input type="text" id="swal-pasta-periodo" value="${periodoPadrao}"
                        placeholder="Ex: 05 - Maio 2026"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <p class="text-xs text-gray-400 mt-1">A subpasta de período será criada automaticamente se não existir.</p>
                </div>

                <div id="upload-area"
                     class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition"
                     onclick="document.getElementById('swal-file-input').click()"
                     ondragover="event.preventDefault(); this.classList.add('border-blue-400','bg-blue-50')"
                     ondragleave="this.classList.remove('border-blue-400','bg-blue-50')"
                     ondrop="onArquivoDrop(event)">
                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-gray-400 mb-2 block"></i>
                    <p class="text-sm text-gray-600 font-medium">Clique para selecionar ou arraste o arquivo aqui</p>
                    <p id="file-selected-name" class="text-xs text-blue-600 font-semibold mt-2 hidden"></p>
                </div>
                <input type="file" id="swal-file-input" class="hidden" onchange="onArquivoSelecionado(this)">
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-paper-plane mr-1"></i> Enviar arquivo',
            cancelButtonText: 'Enviar depois',
            confirmButtonColor: '#0084AA',
            preConfirm: async () => {
                const fileInput = document.getElementById('swal-file-input');
                const categoria = document.getElementById('swal-pasta-categoria').value.trim();
                const periodo = document.getElementById('swal-pasta-periodo').value.trim();

                if (!categoria) {
                    Swal.showValidationMessage('Selecione a pasta / categoria.');
                    return false;
                }
                if (!periodo) {
                    Swal.showValidationMessage('Informe o período.');
                    return false;
                }
                if (!fileInput || !fileInput.files.length) {
                    Swal.showValidationMessage('Selecione um arquivo para enviar.');
                    return false;
                }

                const formData = new FormData();
                formData.append('arquivo', fileInput.files[0]);
                formData.append('pasta_categoria', categoria);
                formData.append('pasta_periodo', periodo);

                Swal.showLoading();

                try {
                    const res = await fetch(`/tarefas/${tarefaId}/upload`, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                        body: formData,
                    });
                    const data = await res.json();
                    if (!res.ok) {
                        Swal.showValidationMessage(data.error ?? 'Erro ao enviar o arquivo.');
                        return false;
                    }
                    return data;
                } catch {
                    Swal.showValidationMessage('Erro de conexão ao enviar o arquivo.');
                    return false;
                }
            },
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                showToast(`Arquivo "${result.value.nome}" enviado ao portal!`, 'green');
            }
        });
    }

    function onArquivoSelecionado(input) {
        const nameEl = document.getElementById('file-selected-name');
        if (input.files.length && nameEl) {
            nameEl.textContent = '📎 ' + input.files[0].name;
            nameEl.classList.remove('hidden');
            document.getElementById('upload-area').classList.add('border-blue-400', 'bg-blue-50');
        }
    }

    function onArquivoDrop(event) {
        event.preventDefault();
        event.stopPropagation();
        const area = document.getElementById('upload-area');
        if (area) { area.classList.remove('border-blue-400', 'bg-blue-50'); }
        const files = event.dataTransfer.files;
        if (!files.length) { return; }
        const fakeInput = document.getElementById('swal-file-input');
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        fakeInput.files = dt.files;
        onArquivoSelecionado(fakeInput);
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

    // ── Detail Modal (Swal) ───────────────────────────────────────────────────
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

        if (e.target.closest('button, a, form')) { return; }

        const tarefaId = card.dataset.tarefaId;
        if (!tarefaId) { return; }

        activeTarefaId = tarefaId;
        await openDetailModal(tarefaId);
    });

    async function openDetailModal(tarefaId) {
        Swal.fire({
            title: '<span style="font-size:1rem;font-weight:600">Carregando...</span>',
            html: '<div class="flex justify-center py-6"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i></div>',
            showConfirmButton: false,
            width: 640,
            padding: '1.5rem',
            customClass: { popup: 'text-left' },
        });

        try {
            const res = await fetch(`/tarefas/${tarefaId}/detalhe`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!res.ok) { throw new Error(); }
            const t = await res.json();
            renderDetailModal(t);
        } catch {
            Swal.fire({ title: 'Erro', text: 'Não foi possível carregar os dados da tarefa.', icon: 'error' });
        }
    }

    function tfield(label, value, extra = '') {
        return `<div>
            <p class="text-xs text-gray-400 mb-0.5">${label}</p>
            <p class="text-sm text-gray-800 font-medium ${extra}">${value || '—'}</p>
        </div>`;
    }

    function renderDetailModal(t) {
        const frequenciaLabels = {
            nenhuma: 'Não se repete', semanal: 'Semanal', mensal: 'Mensal',
            trimestral: 'Trimestral', semestral: 'Semestral', anual: 'Anual',
        };

        const etapaHtml = `<span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full" style="background:${t.etapa.cor}22;color:${t.etapa.cor}">
            <span style="width:7px;height:7px;border-radius:50%;background:${t.etapa.cor};display:inline-block;flex-shrink:0"></span>
            ${t.etapa.nome}
        </span>`;

        const prioClass = prioridadeColors[t.prioridade] ?? 'text-gray-700';
        const prioHtml = `<span class="text-sm font-semibold ${prioClass}">${t.prioridade}</span>`;

        const recHtml = (t.recorrente && t.frequencia && t.frequencia !== 'nenhuma')
            ? `<span class="text-sm font-medium text-blue-600"><i class="fa-solid fa-rotate mr-1"></i>${frequenciaLabels[t.frequencia] ?? t.frequencia}</span>`
            : `<span class="text-sm text-gray-400">Não se repete</span>`;

        const vencClass = t.atrasada ? 'text-red-600' : '';
        const vencIcon = t.atrasada ? '<i class="fa-solid fa-triangle-exclamation mr-1 text-red-500"></i>' : '<i class="fa-regular fa-calendar mr-1 text-gray-400"></i>';

        // Histórico
        let historicoHtml = '';
        if (t.historico.length === 0) {
            historicoHtml = '<p class="text-xs text-gray-400 italic ml-4">Nenhuma movimentação registrada.</p>';
        } else {
            historicoHtml = '<ol reversed class="relative border-l border-gray-200 ml-2 space-y-3">';
            t.historico.forEach(h => {
                let item = `<li class="ml-4 relative">`;

                if (h.etapa_nova) {
                    item += `<p class="text-xs text-gray-700">
                        ${h.etapa_anterior ? `<span class="font-semibold">${h.etapa_anterior}</span> <i class="fa-solid fa-arrow-right text-gray-400" style="font-size:0.6rem"></i> ` : ''}
                        <span class="font-semibold" style="color:${h.etapa_nova_cor}">${h.etapa_nova}</span>
                    </p>`;
                }
                if (h.observacao) {
                    item += `<p class="text-xs text-red-600 mt-0.5 italic">
                        <i class="fa-solid fa-circle-exclamation mr-1" style="font-size:0.6rem"></i>${h.observacao}
                    </p>`;
                }
                if (h.responsavel_novo) {
                    item += `<p class="text-xs text-gray-700 mt-0.5">
                        <i class="fa-solid fa-user-pen text-gray-400 mr-1" style="font-size:0.6rem"></i>
                        <span class="font-semibold">${h.responsavel_anterior ?? 'Nenhum'}</span>
                        <i class="fa-solid fa-arrow-right text-gray-400" style="font-size:0.6rem"></i>
                        <span class="font-semibold">${h.responsavel_novo}</span>
                    </p>`;
                }
                item += `<p class="text-[11px] text-gray-400 mt-0.5">${h.data}${h.alterado_por ? ' · ' + h.alterado_por : ''}</p></li>`;
                historicoHtml += item;
            });
            historicoHtml += '</ol>';
        }

        const descricaoHtml = t.descricao
            ? `<div class="col-span-2"><p class="text-xs text-gray-400 mb-0.5">Descrição</p><p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">${t.descricao}</p></div>`
            : '';

        const html = `
            <div class="space-y-4 text-left">
                {{-- Etapa + Prioridade --}}
                <div class="flex items-center justify-between flex-wrap gap-2">
                    ${etapaHtml}
                    ${prioHtml}
                </div>

                {{-- Fields grid --}}
                <div class="grid grid-cols-2 gap-x-6 gap-y-3 bg-gray-50 rounded-xl p-4">
                    ${tfield('Cliente', t.cliente)}
                    ${tfield('Departamento', t.departamento)}
                    ${tfield('Responsável', t.responsavel)}
                    ${tfield('Supervisor', t.supervisor)}
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Vencimento</p>
                        <p class="text-sm font-medium ${vencClass}">${vencIcon}${t.data_vencimento ?? '—'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-0.5">Recorrência</p>
                        ${recHtml}
                    </div>
                    ${descricaoHtml}
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
                    ${(() => {
                        const uh = t.historico[0] ?? null;
                        return (uh && uh.etapa_anterior_id)
                            ? `<button onclick="voltarEtapaTarefa(${t.id}, ${uh.etapa_anterior_id}, '${(uh.etapa_anterior ?? '').replace(/'/g, "\\'")}')" title="Voltar para: ${uh.etapa_anterior ?? ''}" class="text-sm px-3 py-2 bg-amber-100 text-amber-700 rounded-lg border-0 hover:bg-amber-200 cursor-pointer"><i class="fa-solid fa-rotate-left"></i></button>`
                            : '';
                    })()}
                    <button onclick="window.openModal('/tarefas/${t.id}/form'); Swal.close();" class="flex-1 text-sm px-4 py-2 bg-brand text-white rounded-lg border-0 hover:bg-brand/80 cursor-pointer">
                        <i class="fa-solid fa-pen-to-square mr-1"></i> Editar
                    </button>
                </div>
            </div>`;

        Swal.fire({
            title: `<span style="font-size:1.05rem;font-weight:700">${t.titulo}</span>`,
            html: html,
            showConfirmButton: false,
            showCloseButton: true,
            width: 640,
            padding: '1.5rem',
            customClass: { popup: 'text-left' },
        });
    }

    async function voltarEtapaTarefa(tarefaId, etapaAnteriorId, etapaAnteriorNome) {
        Swal.close();
        await new Promise(r => setTimeout(r, 200));

        const result = await Swal.fire({
            title: 'Voltar etapa?',
            text: `A tarefa será movida de volta para "${etapaAnteriorNome}".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, voltar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d97706',
        });

        if (!result.isConfirmed) { return; }

        try {
            const res = await fetch(updateEtapaUrl(tarefaId), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ etapa_id: etapaAnteriorId }),
            });

            if (!res.ok) { throw new Error(); }

            const result2 = await res.json();

            // Move card visually
            const card = document.querySelector(`.kanban-card[data-tarefa-id="${tarefaId}"]`);
            const novaCol = document.querySelector(`.kanban-column[data-etapa-id="${etapaAnteriorId}"]`);
            if (card && novaCol) {
                const oldEtapaId = parseInt(card.dataset.etapaId);
                novaCol.appendChild(card);
                card.dataset.etapaId = etapaAnteriorId;
                updateCount(oldEtapaId, -1);
                updateCount(etapaAnteriorId, 1);

                if (result2.finalizado) {
                    card.classList.remove('bg-white', 'border-gray-200', 'border-amber-400', 'border-l-4');
                    card.classList.add('bg-green-50', 'border-green-300');
                } else {
                    card.classList.remove('bg-green-50', 'border-green-300');
                    card.classList.add('bg-white', 'border-gray-200');
                }
            }

            showToast(`Voltou para "${etapaAnteriorNome}"`, 'amber');
        } catch {
            showToast('Erro ao voltar etapa. Tente novamente.', 'red');
        }
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
