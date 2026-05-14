@extends('layouts.internal')

@section('title', 'Nova Campanha de E-mail — WR Assessoria')

@section('content')
<div class="w-full mx-auto py-6 px-4">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('email-campanhas.index') }}" class="text-gray-400 hover:text-brand">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-envelope-open-text"></i> Nova Campanha</h1>
            <p class="text-gray-700 dark:text-gray-300">Crie um e-mail com ajuda de IA e selecione os destinatários.</p>
        </div>
    </div>

    @if($errors->any())
    @push('scripts')
    <script type="module">
    Swal.fire({ icon: 'error', title: 'Verifique os campos', html: '{!! implode('<br>', $errors->all()) !!}', confirmButtonColor: '#dc2626' });
    </script>
    @endpush
    @endif

    <form method="POST" action="{{ route('email-campanhas.store') }}" id="form-campanha">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Coluna principal: conteúdo do e-mail --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Título e Assunto --}}
                <div class="bg-white dark:bg-slate-800 rounded shadow p-5 space-y-4">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wide">Informações da Campanha</h2>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Título interno <span class="text-red-500">*</span></label>
                        <input type="text" name="titulo" value="{{ old('titulo') }}"
                               placeholder="Ex: Informativo Abril 2026"
                               class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assunto do e-mail <span class="text-red-500">*</span></label>
                        <input type="text" name="assunto" value="{{ old('assunto') }}"
                               placeholder="Ex: Novidades tributárias de abril"
                               class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    </div>
                </div>

                {{-- Assistente de IA --}}
                <div class="bg-white dark:bg-slate-800 rounded shadow p-5">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-wand-magic-sparkles text-purple-500"></i> Gerar Conteúdo com IA
                    </h2>
                    <div class="flex gap-2">
                        <input type="text" id="ai-instrucao"
                               placeholder="Ex: Crie um e-mail informando sobre a nova tabela do Simples Nacional para 2026..."
                               class="flex-1 border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-purple-400">
                        <button type="button" id="btn-gerar-ia"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded border-0 hover:bg-purple-700 text-sm whitespace-nowrap">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                            <span id="btn-gerar-ia-texto">Gerar</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Descreva o que deseja comunicar. A IA irá gerar o HTML do e-mail automaticamente.</p>
                </div>

                {{-- Editor de conteúdo --}}
                <div class="bg-white dark:bg-slate-800 rounded shadow p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wide">Conteúdo do E-mail <span class="text-red-500">*</span></h2>
                    </div>
                    <textarea name="conteudo_html" id="conteudo-html" rows="18"
                              placeholder="Cole ou escreva o HTML do e-mail aqui..."
                              class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-xs font-mono text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand resize-y">{{ old('conteudo_html') }}</textarea>
                </div>


            </div>

            {{-- Coluna lateral: seleção de clientes --}}
            <div class="space-y-5">
                <div class="bg-white dark:bg-slate-800 rounded shadow p-5">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-users"></i> Destinatários
                    </h2>

                    {{-- Campo de busca com dropdown --}}
                    <div class="relative mb-3" id="busca-wrapper">
                        <input type="text" id="busca-cliente" autocomplete="off"
                               placeholder="Buscar e adicionar cliente..."
                               class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-xs text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand pr-8">
                        <span id="busca-loading" class="absolute right-2 top-2 text-gray-400 hidden">
                            <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                        </span>
                        {{-- Dropdown de resultados --}}
                        <div id="busca-dropdown"
                             class="hidden absolute z-20 left-0 right-0 top-full mt-1 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded shadow-lg max-h-56 overflow-y-auto">
                        </div>
                    </div>

                    {{-- Contador e limpar --}}
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span id="contador-selecionados">0</span> cliente(s) selecionado(s)
                        </p>
                        <button type="button" id="btn-limpar-todos"
                                class="text-xs text-red-400 hover:text-red-600 bg-transparent border-0 p-0 hidden">
                            Limpar todos
                        </button>
                    </div>

                    {{-- Tags dos selecionados --}}
                    <div id="tags-selecionados" class="flex flex-wrap gap-1.5 min-h-[32px]">
                        <p id="placeholder-vazio" class="text-xs text-gray-400 dark:text-gray-500 italic">Nenhum cliente selecionado ainda.</p>
                    </div>

                    {{-- Inputs hidden para o form --}}
                    <div id="inputs-hidden"></div>
                </div>

                {{-- Agendamento --}}
                <div class="bg-white dark:bg-slate-800 rounded shadow p-5">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-clock text-blue-500"></i> Agendamento (opcional)
                    </h2>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Enviar em</label>
                    <input type="datetime-local" name="enviar_em" value="{{ old('enviar_em') }}"
                           min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                           class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm text-gray-800 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Deixe em branco para salvar como rascunho.</p>
                </div>

                {{-- Botão salvar --}}
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-brand text-white rounded border-0 hover:bg-brand/80 text-sm font-medium">
                    <i class="fa-solid fa-floppy-disk"></i> Salvar Campanha
                </button>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script type="module">
const aiInstrucao = document.getElementById('ai-instrucao');
const btnGerarIa = document.getElementById('btn-gerar-ia');
const btnGerarIaTexto = document.getElementById('btn-gerar-ia-texto');
const conteudoHtml = document.getElementById('conteudo-html');

// ── Destinatários (autocomplete + tags) ──────────────────────────────────────
const buscaInput      = document.getElementById('busca-cliente');
const buscaDropdown   = document.getElementById('busca-dropdown');
const buscaLoading    = document.getElementById('busca-loading');
const tagsContainer   = document.getElementById('tags-selecionados');
const inputsHidden    = document.getElementById('inputs-hidden');
const contador        = document.getElementById('contador-selecionados');
const placeholderVazio = document.getElementById('placeholder-vazio');
const btnLimparTodos  = document.getElementById('btn-limpar-todos');

// Mapa de clientes disponíveis: { id -> nome }
const todosClientes = @json($clientes->pluck('nome', 'id'));

// Estado: Set de IDs selecionados
const selecionados = new Set(
    @json(is_array(old('clientes_ids')) ? array_map('intval', old('clientes_ids')) : [])
);

function renderTags() {
    tagsContainer.innerHTML = '';
    contador.textContent = selecionados.size;
    placeholderVazio.classList.toggle('hidden', selecionados.size > 0);
    btnLimparTodos.classList.toggle('hidden', selecionados.size === 0);
    inputsHidden.innerHTML = '';

    selecionados.forEach(id => {
        const nome = todosClientes[id] ?? `#${id}`;

        // Tag visual
        const tag = document.createElement('span');
        tag.className = 'inline-flex items-center gap-1 px-2 py-1 bg-brand/10 text-brand rounded text-xs font-medium border border-brand/20';
        tag.innerHTML = `<span class="max-w-[140px] truncate" title="${nome}">${nome}</span>
            <button type="button" data-id="${id}" class="btn-remover-tag ml-0.5 text-brand/60 hover:text-red-500 bg-transparent border-0 p-0 leading-none">&times;</button>`;
        tagsContainer.appendChild(tag);

        // Input hidden
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = 'clientes_ids[]';
        input.value = id;
        inputsHidden.appendChild(input);
    });
}

function adicionarCliente(id, nome) {
    id = parseInt(id);
    if (selecionados.has(id)) { return; }
    selecionados.add(id);
    if (!todosClientes[id]) { todosClientes[id] = nome; }
    renderTags();
}

function removerCliente(id) {
    selecionados.delete(parseInt(id));
    renderTags();
}

// Delegação de clique nas tags
tagsContainer.addEventListener('click', e => {
    const btn = e.target.closest('.btn-remover-tag');
    if (btn) { removerCliente(btn.dataset.id); }
});

btnLimparTodos.addEventListener('click', () => {
    selecionados.clear();
    renderTags();
});

// Busca com debounce
let debounceTimer;
buscaInput.addEventListener('input', () => {
    clearTimeout(debounceTimer);
    const q = buscaInput.value.trim().toLowerCase();

    if (q.length < 1) {
        buscaDropdown.classList.add('hidden');
        buscaDropdown.innerHTML = '';
        return;
    }

    debounceTimer = setTimeout(() => {
        const resultados = Object.entries(todosClientes)
            .filter(([, nome]) => nome.toLowerCase().includes(q))
            .slice(0, 15);

        buscaDropdown.innerHTML = '';

        if (resultados.length === 0) {
            buscaDropdown.innerHTML = '<p class="px-3 py-2 text-xs text-gray-400 dark:text-gray-500">Nenhum cliente encontrado.</p>';
        } else {
            resultados.forEach(([id, nome]) => {
                const jaSelecionado = selecionados.has(parseInt(id));
                const item = document.createElement('button');
                item.type = 'button';
                item.dataset.id = id;
                item.dataset.nome = nome;
                item.className = `w-full text-left px-3 py-2 text-xs flex items-center justify-between gap-2 border-0 bg-transparent
                    ${jaSelecionado
                        ? 'text-brand font-medium bg-brand/5 cursor-default'
                        : 'text-gray-700 dark:text-gray-200 hover:bg-brand hover:text-white cursor-pointer'}`;
                item.innerHTML = `<span class="truncate">${nome}</span>${jaSelecionado ? '<i class="fa-solid fa-check text-brand shrink-0"></i>' : ''}`;
                item.addEventListener('click', () => {
                    if (!jaSelecionado) {
                        adicionarCliente(id, nome);
                        buscaInput.value = '';
                        buscaDropdown.classList.add('hidden');
                        buscaDropdown.innerHTML = '';
                        buscaInput.focus();
                    }
                });
                buscaDropdown.appendChild(item);
            });
        }

        buscaDropdown.classList.remove('hidden');
    }, 200);
});

// Fechar dropdown ao clicar fora
document.addEventListener('click', e => {
    if (!document.getElementById('busca-wrapper').contains(e.target)) {
        buscaDropdown.classList.add('hidden');
    }
});

// Renderiza estado inicial (p/ old() após erro de validação)
renderTags();

// ── Gerar conteúdo com IA ─────────────────────────────────────────────────────
btnGerarIa.addEventListener('click', async () => {
    const instrucao = aiInstrucao.value.trim();
    if (!instrucao) {
        Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Descreva o que deseja comunicar antes de gerar.', confirmButtonColor: '#2563eb' });
        return;
    }

    btnGerarIa.disabled = true;
    btnGerarIaTexto.textContent = 'Gerando...';

    try {
        const response = await axios.post('{{ route('email-campanhas.gerar-ia') }}', { instrucao });
        conteudoHtml.value = response.data.html;
        Swal.fire({ icon: 'success', title: 'Conteúdo gerado!', text: 'O HTML foi inserido no editor. Você pode editar antes de salvar.', confirmButtonColor: '#2563eb', timer: 3000, showConfirmButton: false });
    } catch (err) {
        const msg = err.response?.data?.error ?? 'Erro ao gerar conteúdo. Tente novamente.';
        Swal.fire({ icon: 'error', title: 'Erro', text: msg, confirmButtonColor: '#dc2626' });
    } finally {
        btnGerarIa.disabled = false;
        btnGerarIaTexto.textContent = 'Gerar';
    }
});


</script>
@endpush
@endsection
