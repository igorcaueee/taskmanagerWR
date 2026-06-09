@extends('layouts.internal')

@section('title', 'Questionários — Classificação')

@section('content')
<div class="w-full mx-auto py-6 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                <i class="fa-solid fa-clipboard-question"></i> Questionários
            </h1>
            <p class="text-gray-700 dark:text-gray-300">Gerencie e envie questionários de diagnóstico para leads e clientes.</p>
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

    <div class="grid gap-4">
        @forelse($questionarios as $q)
        <div class="bg-white dark:bg-slate-800 rounded shadow p-5 flex items-center justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-1">
                    <span class="text-lg font-semibold text-gray-900 dark:text-slate-100">{{ $q->titulo }}</span>
                    @if($q->ativo)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">Ativo</span>
                    @else
                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">Inativo</span>
                    @endif
                </div>
                @if($q->descricao)
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $q->descricao }}</p>
                @endif
                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span><i class="fa-solid fa-chart-bar mr-1"></i>{{ $q->respostas_count }} {{ Str::plural('resposta', $q->respostas_count) }}</span>
                    <span><i class="fa-solid fa-link mr-1"></i>Link público: <code class="bg-gray-100 dark:bg-slate-700 px-1 rounded">/q/{{ $q->slug }}</code></span>
                </div>
            </div>
            <div class="flex gap-2 shrink-0">
                <button type="button"
                        onclick="copiarLink('{{ route('questionario.publico', $q->slug) }}')"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600">
                    <i class="fa-solid fa-copy"></i> Copiar link
                </button>
                <button type="button"
                        onclick="abrirModalEnvio({{ $q->id }})"
                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white border-0 rounded text-sm hover:bg-blue-700 cursor-pointer">
                    <i class="fa-solid fa-paper-plane"></i> Enviar
                </button>
                <a href="{{ route('questionarios.show', $q->id) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-50 dark:hover:bg-slate-600 no-underline">
                    <i class="fa-solid fa-list"></i> Ver respostas
                </a>
            </div>
        </div>
        @empty
        <div class="bg-white dark:bg-slate-800 rounded shadow p-10 text-center text-gray-400 dark:text-gray-500">
            <i class="fa-solid fa-clipboard-question text-4xl mb-3"></i>
            <p>Nenhum questionário cadastrado.</p>
        </div>
        @endforelse
    </div>
</div>

{{-- Modal Enviar Link --}}
<div id="modal-envio" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50" style="display:none">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md p-6 relative mx-4">
        <button type="button" onclick="fecharModalEnvio()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 bg-transparent border-0 text-xl leading-none cursor-pointer">&times;</button>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100 mb-4">
            <i class="fa-solid fa-paper-plane mr-2"></i>Gerar link de questionário
        </h2>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tipo</label>
            <select id="envio-tipo"
                    onchange="buscarVinculos()"
                    class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="cliente">Cliente</option>
                <option value="lead">Lead</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Selecionar</label>
            <input type="text" id="envio-busca" placeholder="Digite para pesquisar..."
                   oninput="filtrarVinculos(this.value)"
                   class="w-full border border-gray-300 dark:border-slate-600 rounded-t px-3 py-2 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                   autocomplete="off">
            <select id="envio-vinculo" size="5"
                    class="w-full border border-gray-300 dark:border-slate-600 border-t-0 rounded-b px-1 py-1 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">Aguarde...</option>
            </select>
        </div>

        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Responsável <span class="text-red-500">*</span></label>
            <input type="text" id="envio-responsavel" placeholder="Nome do responsável que responderá"
                   class="w-full border border-gray-300 dark:border-slate-600 rounded px-3 py-2 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-1 focus:ring-blue-500"
                   autocomplete="off">
        </div>

        <div id="envio-resultado" style="display:none" class="mb-4 p-3 bg-gray-50 dark:bg-slate-700 rounded border border-gray-200 dark:border-slate-600">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Link gerado — copie e envie para o cliente:</p>
            <div class="flex items-center gap-2">
                <input id="envio-link" type="text" readonly
                       class="flex-1 text-xs bg-transparent border-0 text-blue-600 dark:text-blue-400 focus:outline-none"
                       style="min-width:0">
                <button type="button" onclick="copiarLinkGerado()" class="text-xs px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 border-0 cursor-pointer shrink-0">Copiar</button>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" onclick="fecharModalEnvio()" class="px-4 py-2 bg-gray-100 dark:bg-slate-700 text-gray-700 dark:text-slate-200 rounded text-sm hover:bg-gray-200 dark:hover:bg-slate-600 border-0 cursor-pointer">
                Fechar
            </button>
            <button type="button" onclick="gerarLink()" class="px-4 py-2 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 border-0 cursor-pointer">
                <i class="fa-solid fa-link mr-1"></i>Gerar link
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
var _questionarioAtivo = null;
var _vinculosTodos = [];

function abrirModalEnvio(id) {
    _questionarioAtivo = id;
    document.getElementById('modal-envio').style.display = 'flex';
    document.getElementById('envio-resultado').style.display = 'none';
    document.getElementById('envio-busca').value = '';
    document.getElementById('envio-responsavel').value = '';
    buscarVinculos();
}

function fecharModalEnvio() {
    document.getElementById('modal-envio').style.display = 'none';
}

function filtrarVinculos(termo) {
    var sel = document.getElementById('envio-vinculo');
    var lower = termo.toLowerCase();
    var filtrados = _vinculosTodos.filter(function(v) {
        return v.label.toLowerCase().includes(lower);
    });
    renderOpcoes(sel, filtrados);
}

function renderOpcoes(sel, lista) {
    if (!lista.length) {
        sel.innerHTML = '<option value="">Nenhum encontrado</option>';
        return;
    }
    sel.innerHTML = lista.map(function(v) {
        return '<option value="' + v.id + '">' + v.label + '</option>';
    }).join('');
}

async function buscarVinculos() {
    var tipo = document.getElementById('envio-tipo').value;
    var sel  = document.getElementById('envio-vinculo');
    var busca = document.getElementById('envio-busca');
    _vinculosTodos = [];
    busca.value = '';
    busca.placeholder = 'Carregando...';
    busca.disabled = true;
    sel.innerHTML = '<option value="">Carregando...</option>';

    try {
        var res = await fetch('/classificacao/vinculos?tipo=' + tipo, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) {
            var txt = await res.text();
            console.error('vinculos error', res.status, txt);
            throw new Error('HTTP ' + res.status);
        }
        _vinculosTodos = await res.json();
        busca.placeholder = 'Digite para pesquisar...';
        busca.disabled = false;
        busca.focus();
        renderOpcoes(sel, _vinculosTodos);
    } catch(e) {
        console.error('buscarVinculos error:', e);
        busca.placeholder = 'Erro ao carregar';
        sel.innerHTML = '<option value="">Erro ao carregar</option>';
    }
}

async function gerarLink() {
    var tipo         = document.getElementById('envio-tipo').value;
    var vinculo      = document.getElementById('envio-vinculo').value;
    var responsavel  = document.getElementById('envio-responsavel').value.trim();
    if (!vinculo) { Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Selecione um ' + (tipo === 'lead' ? 'lead' : 'cliente') + '.', confirmButtonColor: '#2563eb' }); return; }
    if (!responsavel) { Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe o nome do responsável.', confirmButtonColor: '#2563eb' }).then(() => document.getElementById('envio-responsavel').focus()); return; }

    try {
        var res = await fetch('/classificacao/questionarios/' + _questionarioAtivo + '/enviar-link', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]') ? document.querySelector('meta[name=csrf-token]').content : '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ tipo: tipo, vinculo_id: vinculo, responsavel: responsavel }),
        });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        var data = await res.json();
        document.getElementById('envio-link').value = data.link;
        document.getElementById('envio-resultado').style.display = 'block';
    } catch(e) {
        console.error('gerarLink error:', e);
        Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro ao gerar link. Tente novamente.', confirmButtonColor: '#dc2626' });
    }
}

function copiarLink(url) {
    navigator.clipboard.writeText(url).then(function() {
        Swal.fire({ icon: 'success', title: 'Link copiado!', text: url, confirmButtonColor: '#2563eb', timer: 2000, showConfirmButton: false });
    });
}

function copiarLinkGerado() {
    var val = document.getElementById('envio-link').value;
    navigator.clipboard.writeText(val).then(function() {
        Swal.fire({ icon: 'success', title: 'Link copiado!', confirmButtonColor: '#2563eb', timer: 1500, showConfirmButton: false });
    });
}
</script>
@endpush
@endsection
