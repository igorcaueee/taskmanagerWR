@extends('layouts.internal')

@section('title', 'Chat — WR Assessoria')

@section('content')
<div class="flex h-[calc(100vh-64px-3rem)] rounded-xl overflow-hidden border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800">

    {{-- Painel esquerdo: lista de conversas --}}
    <div class="w-full sm:w-80 flex-shrink-0 border-r border-gray-200 dark:border-slate-700 flex flex-col" id="chat-lista-painel">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
            <h1 class="text-base font-semibold text-gray-800 dark:text-slate-100">Chat</h1>
            <button id="chat-nova-conversa-btn" title="Nova conversa"
                class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#0084AA] hover:bg-[#006e8e] text-white border-0 cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
            </button>
        </div>

        <ul id="chat-lista-conversas" class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-slate-700">
            <li class="px-4 py-6 text-center text-xs text-gray-400 dark:text-slate-500" id="chat-lista-vazia">Nenhuma conversa ainda.</li>
        </ul>
    </div>

    {{-- Painel direito: mensagens --}}
    <div class="flex-1 flex-col min-w-0" id="chat-painel-mensagens" style="display: none;">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-slate-700 flex items-center gap-3">
            <div id="chat-conversa-avatar" class="w-9 h-9 rounded-full bg-gray-200 dark:bg-slate-600 flex-shrink-0 flex items-center justify-center overflow-hidden">
                <i class="fa-solid fa-user text-gray-400 text-sm"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p id="chat-conversa-nome" class="text-sm font-semibold text-gray-800 dark:text-slate-100 truncate"></p>
                <p id="chat-conversa-status" class="text-xs text-gray-400 dark:text-slate-500 truncate">&nbsp;</p>
            </div>
        </div>

        <div id="chat-mensagens" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50 dark:bg-slate-900"></div>

        <form id="chat-form-envio" class="px-3 py-3 bg-white dark:bg-slate-800 border-t border-gray-100 dark:border-slate-700 flex gap-2 items-end">
            <input type="file" id="chat-input-anexos" name="anexos[]" multiple class="hidden">
            <button type="button" id="chat-btn-anexo" title="Anexar arquivo"
                class="flex-shrink-0 w-9 h-9 rounded-xl text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 border-0 bg-transparent cursor-pointer">
                <i class="fa-solid fa-paperclip"></i>
            </button>
            <div class="flex-1">
                <div id="chat-anexos-preview" class="hidden flex flex-wrap gap-1 mb-1 text-xs text-gray-500 dark:text-slate-400"></div>
                <textarea id="chat-input-texto" rows="1" placeholder="Digite uma mensagem..."
                    class="w-full resize-none rounded-xl border border-gray-200 dark:border-slate-600 px-3 py-2 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0084AA] focus:border-transparent max-h-24 overflow-y-auto"
                    style="min-height: 38px;"></textarea>
            </div>
            <button type="submit" id="chat-btn-enviar"
                class="flex-shrink-0 w-9 h-9 rounded-xl bg-[#0084AA] hover:bg-[#006e8e] text-white flex items-center justify-center border-0 cursor-pointer disabled:opacity-50">
                <i class="fa-solid fa-paper-plane text-xs"></i>
            </button>
        </form>
    </div>

    <div class="flex-1 flex items-center justify-center text-gray-400 dark:text-slate-500 text-sm" id="chat-painel-vazio">
        Selecione uma conversa para começar.
    </div>
</div>

{{-- Modal: nova conversa / novo grupo --}}
<div id="chat-modal-nova" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md mx-4 max-h-[85vh] flex flex-col">
        <div class="px-5 py-4 border-b border-gray-100 dark:border-slate-700 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-slate-100">Nova conversa</h2>
            <button type="button" id="chat-modal-fechar" class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700 border-0 bg-transparent cursor-pointer">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <div class="p-5 space-y-3 overflow-y-auto">
            <input type="text" id="chat-modal-grupo-nome" placeholder="Nome do grupo (opcional para conversa individual)"
                class="w-full rounded-lg border border-gray-200 dark:border-slate-600 px-3 py-2 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0084AA]">

            <input type="text" id="chat-modal-busca" placeholder="Buscar colaborador..."
                class="w-full rounded-lg border border-gray-200 dark:border-slate-600 px-3 py-2 text-sm bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-[#0084AA]">

            <ul id="chat-modal-usuarios" class="max-h-64 overflow-y-auto divide-y divide-gray-100 dark:divide-slate-700 border border-gray-100 dark:border-slate-700 rounded-lg"></ul>
        </div>

        <div class="px-5 py-4 border-t border-gray-100 dark:border-slate-700 flex justify-end gap-2">
            <button type="button" id="chat-modal-cancelar" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-700 border-0 bg-transparent cursor-pointer">Cancelar</button>
            <button type="button" id="chat-modal-criar" class="px-3 py-1.5 rounded-lg text-sm text-white bg-[#0084AA] hover:bg-[#006e8e] border-0 cursor-pointer">Iniciar</button>
        </div>
    </div>
</div>

{{-- Modal: visualizar imagem --}}
<div id="chat-modal-imagem" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
    <button type="button" id="chat-modal-imagem-fechar" title="Fechar"
        class="absolute top-4 right-4 w-10 h-10 flex items-center justify-center rounded-full bg-white/10 hover:bg-white/20 text-white border-0 cursor-pointer">
        <i class="fa-solid fa-xmark"></i>
    </button>
    <img id="chat-modal-imagem-img" src="" class="max-w-full max-h-[85vh] rounded-lg object-contain">
    <a id="chat-modal-imagem-download" href="" download
        title="Baixar imagem"
        class="absolute bottom-6 right-6 w-11 h-11 flex items-center justify-center rounded-full bg-[#0084AA] hover:bg-[#006e8e] text-white no-underline">
        <i class="fa-solid fa-download"></i>
    </a>
</div>

@push('scripts')
<script>
(function () {
    const userId = {{ auth()->id() }};

    const listaEl = document.getElementById('chat-lista-conversas');
    const listaVaziaEl = document.getElementById('chat-lista-vazia');
    const painelMensagens = document.getElementById('chat-painel-mensagens');
    const painelVazio = document.getElementById('chat-painel-vazio');
    const mensagensEl = document.getElementById('chat-mensagens');
    const conversaNomeEl = document.getElementById('chat-conversa-nome');
    const conversaStatusEl = document.getElementById('chat-conversa-status');
    const conversaAvatarEl = document.getElementById('chat-conversa-avatar');
    const formEnvio = document.getElementById('chat-form-envio');
    const inputTexto = document.getElementById('chat-input-texto');
    const inputAnexos = document.getElementById('chat-input-anexos');
    const btnAnexo = document.getElementById('chat-btn-anexo');
    const anexosPreview = document.getElementById('chat-anexos-preview');
    const btnEnviar = document.getElementById('chat-btn-enviar');

    const modalEl = document.getElementById('chat-modal-nova');
    const modalNomeInput = document.getElementById('chat-modal-grupo-nome');
    const modalBuscaInput = document.getElementById('chat-modal-busca');
    const modalUsuariosEl = document.getElementById('chat-modal-usuarios');

    window.chatConversaAbertaId = null;

    let conversaAtualId = null;
    let ultimaDataRenderizada = null;
    let canalConversaAtual = null;
    let canalUsuario = null;
    let usuariosSelecionados = new Set();
    let conversasCache = new Map();
    let digitandoTimeout = null;
    let ultimoDigitandoEnviado = false;
    let mensagensRenderizadasIds = new Set();
    let pollingMensagensInterval = null;
    let pollingConversasInterval = null;

    function whenEchoReady(callback) {
        // window.Echo pode nunca existir (Reverb não configurado no ambiente) —
        // nesse caso o chat funciona sem tempo real, sem lançar erro.
        if (window.Echo) { callback(window.Echo); return; }
        window.addEventListener('laravel-echo:ready', () => {
            if (window.Echo) { callback(window.Echo); }
        }, { once: true });
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]').content;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(text ?? ''));
        return div.innerHTML;
    }

    function escapeAttr(text) {
        return escapeHtml(text).replace(/"/g, '&quot;');
    }

    function formatRelTime(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60) return 'agora';
        if (diff < 3600) return Math.floor(diff / 60) + 'min';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h';
        return Math.floor(diff / 86400) + 'd';
    }

    function avatarHtml(fotoUrl, nome) {
        if (fotoUrl) {
            return '<img src="' + fotoUrl + '" class="w-full h-full object-cover">';
        }
        const inicial = (nome || '?').trim().charAt(0).toUpperCase();
        return '<span class="text-xs font-semibold text-gray-500 dark:text-slate-300">' + inicial + '</span>';
    }

    function carregarConversas() {
        fetch('{{ route("chat.conversas") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => renderConversas(data.conversas));
    }

    function renderConversas(conversas) {
        listaEl.innerHTML = '';

        if (!conversas.length) {
            listaEl.appendChild(listaVaziaEl);
            return;
        }

        conversasCache = new Map(conversas.map(c => [c.id, c]));

        conversas.forEach(function (c) {
            const li = document.createElement('li');
            li.className = 'flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors' +
                (c.id === conversaAtualId ? ' bg-blue-50 dark:bg-blue-900/20' : '');
            li.dataset.conversaId = c.id;
            li.innerHTML =
                '<div class="w-10 h-10 rounded-full bg-gray-200 dark:bg-slate-600 flex-shrink-0 flex items-center justify-center overflow-hidden">' + avatarHtml(c.foto_url, c.nome) + '</div>' +
                '<div class="flex-1 min-w-0">' +
                    '<div class="flex items-center justify-between gap-2">' +
                        '<p class="text-sm font-medium text-gray-800 dark:text-slate-100 truncate">' + escapeHtml(c.nome) + '</p>' +
                        (c.ultima_mensagem_em ? '<span class="text-[10px] text-gray-400 dark:text-slate-500 flex-shrink-0">' + formatRelTime(c.ultima_mensagem_em) + '</span>' : '') +
                    '</div>' +
                    '<div class="flex items-center justify-between gap-2">' +
                        '<p class="text-xs text-gray-400 dark:text-slate-500 truncate">' + escapeHtml(c.ultima_mensagem || 'Nenhuma mensagem ainda') + '</p>' +
                        (c.nao_lidas > 0 ? '<span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-[#0084AA] text-white flex-shrink-0">' + c.nao_lidas + '</span>' : '') +
                    '</div>' +
                '</div>';
            li.addEventListener('click', function () { abrirConversa(c.id); });
            listaEl.appendChild(li);
        });
    }

    function abrirConversa(conversaId) {
        conversaAtualId = conversaId;
        window.chatConversaAbertaId = conversaId;
        painelVazio.style.display = 'none';
        painelMensagens.style.display = 'flex';
        painelMensagens.classList.add('flex');

        whenEchoReady(function (Echo) {
            if (canalConversaAtual) {
                Echo.leave('conversa.' + canalConversaAtual);
            }
            canalConversaAtual = conversaId;
            Echo.private('conversa.' + conversaId)
                .listen('.mensagem.enviada', function (e) {
                    // Mensagens do próprio usuário já são renderizadas localmente
                    // na resposta do envio; ignorar aqui evita duplicar a bolha.
                    if (e.mensagem.conversa_id === conversaAtualId && e.mensagem.usuario.id !== userId) {
                        renderMensagem(e.mensagem, true);
                        marcarComoLida();
                    }
                    carregarConversas();
                })
                .listen('.usuario.digitando', function (e) {
                    if (e.usuario_id === userId) return;
                    conversaStatusEl.textContent = e.digitando ? (e.nome + ' está digitando...') : '';
                });
        });

        mensagensEl.innerHTML = '';
        ultimaDataRenderizada = null;
        mensagensRenderizadasIds = new Set();
        fetch('{{ url("chat/conversas") }}/' + conversaId + '/mensagens', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(function (data) {
                data.mensagens.forEach(m => renderMensagem(m, false));
                mensagensEl.scrollTop = mensagensEl.scrollHeight;
                marcarComoLida();
            });

        // Polling como reforço/alternativa ao WebSocket: garante que as
        // mensagens cheguem mesmo se o Reverb não estiver disponível no
        // ambiente (ex: bloqueado por proxy/tunnel).
        if (pollingMensagensInterval) { clearInterval(pollingMensagensInterval); }
        pollingMensagensInterval = setInterval(function () { pollarMensagens(conversaId); }, 2000);

        const dadosConversa = conversasCache.get(conversaId);
        if (dadosConversa) {
            conversaNomeEl.textContent = dadosConversa.nome;
            conversaAvatarEl.innerHTML = avatarHtml(dadosConversa.foto_url, dadosConversa.nome);
        }
        conversaStatusEl.textContent = '';

        carregarConversas();
    }

    function pollarMensagens(conversaId) {
        fetch('{{ url("chat/conversas") }}/' + conversaId + '/mensagens', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(function (data) {
                let novas = false;
                data.mensagens.forEach(function (m) {
                    if (!mensagensRenderizadasIds.has(m.id)) {
                        novas = true;
                        renderMensagem(m, false);
                    }
                });
                if (novas) {
                    mensagensEl.scrollTop = mensagensEl.scrollHeight;
                    marcarComoLida();
                    carregarConversas();
                }
            });
    }

    function formatHoraMensagem(dateStr) {
        return new Date(dateStr).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    }

    function formatDivisorData(dateStr) {
        const data = new Date(dateStr);
        const hoje = new Date();
        const ontem = new Date();
        ontem.setDate(hoje.getDate() - 1);

        const mesmoDia = (a, b) => a.toDateString() === b.toDateString();

        if (mesmoDia(data, hoje)) { return 'Hoje'; }
        if (mesmoDia(data, ontem)) { return 'Ontem'; }

        return data.toLocaleDateString('pt-BR', {
            day: '2-digit',
            month: 'long',
            year: data.getFullYear() !== hoje.getFullYear() ? 'numeric' : undefined,
        });
    }

    function inserirDivisorDataSeNecessario(dateStr) {
        const chaveData = new Date(dateStr).toDateString();
        if (chaveData === ultimaDataRenderizada) { return; }
        ultimaDataRenderizada = chaveData;

        const divisor = document.createElement('div');
        divisor.className = 'flex justify-center my-2';
        divisor.innerHTML = '<span class="text-[11px] px-3 py-1 rounded-full bg-gray-200 dark:bg-slate-700 text-gray-500 dark:text-slate-300">' + formatDivisorData(dateStr) + '</span>';
        mensagensEl.appendChild(divisor);
    }

    function iconeParaArquivo(nomeArquivo) {
        const ext = (nomeArquivo.split('.').pop() || '').toLowerCase();
        const mapa = {
            pdf: 'fa-file-pdf',
            doc: 'fa-file-word', docx: 'fa-file-word',
            xls: 'fa-file-excel', xlsx: 'fa-file-excel', csv: 'fa-file-excel',
            ppt: 'fa-file-powerpoint', pptx: 'fa-file-powerpoint',
            zip: 'fa-file-zipper', rar: 'fa-file-zipper', '7z': 'fa-file-zipper',
            mp3: 'fa-file-audio', wav: 'fa-file-audio',
            mp4: 'fa-file-video', mov: 'fa-file-video', avi: 'fa-file-video',
            txt: 'fa-file-lines',
        };
        return mapa[ext] || 'fa-file';
    }

    function renderAnexo(a, isMine) {
        if (a.tipo === 'imagem') {
            const btnClasses = 'absolute bottom-2 right-2 w-8 h-8 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition-colors no-underline';
            return (
                '<div class="relative mt-1 inline-block max-w-[220px]">' +
                    '<img src="' + a.url + '" class="w-full rounded-lg border border-black/10 cursor-pointer chat-imagem-anexo" data-url="' + a.url + '" data-nome="' + escapeAttr(a.nome_original) + '">' +
                    '<a href="' + a.url + '" download="' + escapeAttr(a.nome_original) + '" title="Baixar imagem" class="' + btnClasses + '" onclick="event.stopPropagation()">' +
                        '<i class="fa-solid fa-download text-xs"></i>' +
                    '</a>' +
                '</div>'
            );
        }

        const cardClasses = isMine
            ? 'flex items-center gap-2 mt-1 px-3 py-2 rounded-lg bg-white/15 hover:bg-white/25 transition-colors no-underline max-w-[240px] text-white visited:text-white'
            : 'flex items-center gap-2 mt-1 px-3 py-2 rounded-lg bg-black/5 dark:bg-white/10 hover:bg-black/10 dark:hover:bg-white/20 transition-colors no-underline max-w-[240px] text-gray-700 dark:text-slate-200 visited:text-gray-700 dark:visited:text-slate-200';
        const iconWrapClasses = isMine
            ? 'w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center flex-shrink-0'
            : 'w-9 h-9 rounded-lg bg-black/5 dark:bg-white/10 flex items-center justify-center flex-shrink-0';

        return (
            '<a href="' + a.url + '" target="_blank" download="' + escapeAttr(a.nome_original) + '" class="' + cardClasses + '">' +
                '<div class="' + iconWrapClasses + '">' +
                    '<i class="fa-solid ' + iconeParaArquivo(a.nome_original) + '"></i>' +
                '</div>' +
                '<span class="text-xs truncate flex-1 min-w-0">' + escapeHtml(a.nome_original) + '</span>' +
                '<i class="fa-solid fa-download text-xs opacity-70 flex-shrink-0"></i>' +
            '</a>'
        );
    }

    function renderMensagem(m, animar) {
        // Evita duplicar a mesma mensagem quando ela chega tanto pelo polling
        // quanto pelo WebSocket (ou é renderizada localmente ao enviar).
        if (mensagensRenderizadasIds.has(m.id)) { return; }
        mensagensRenderizadasIds.add(m.id);

        inserirDivisorDataSeNecessario(m.created_at);

        const isMine = m.usuario.id === userId;
        const div = document.createElement('div');
        div.className = isMine ? 'flex gap-2 justify-end' : 'flex gap-2';

        let anexosHtml = '';
        (m.anexos || []).forEach(function (a) {
            anexosHtml += renderAnexo(a, isMine);
        });

        const bubbleClasses = isMine
            ? 'bg-[#0084AA] rounded-2xl rounded-tr-sm px-3 py-2 text-sm text-white max-w-[75%] whitespace-pre-wrap'
            : 'bg-white dark:bg-slate-700 rounded-2xl rounded-tl-sm px-3 py-2 shadow-sm border border-gray-100 dark:border-slate-600 text-sm text-gray-700 dark:text-slate-200 max-w-[75%] whitespace-pre-wrap';

        const horaClasses = isMine
            ? 'text-[10px] text-white/70 float-right ml-2 mt-1 leading-none select-none'
            : 'text-[10px] text-gray-400 dark:text-slate-500 float-right ml-2 mt-1 leading-none select-none';

        div.innerHTML =
            (isMine ? '' : '<div class="w-7 h-7 rounded-full bg-gray-200 dark:bg-slate-600 flex-shrink-0 flex items-center justify-center overflow-hidden mt-0.5">' + avatarHtml(m.usuario.foto_url, m.usuario.nome) + '</div>') +
            '<div class="' + bubbleClasses + '">' +
                (m.texto ? escapeHtml(m.texto) : '') +
                anexosHtml +
                '<span class="' + horaClasses + '">' + formatHoraMensagem(m.created_at) + '</span>' +
                '<div class="clear-both"></div>' +
            '</div>';

        mensagensEl.appendChild(div);
        if (animar) { mensagensEl.scrollTop = mensagensEl.scrollHeight; }
    }

    function marcarComoLida() {
        if (!conversaAtualId) return;
        fetch('{{ url("chat/conversas") }}/' + conversaAtualId + '/lida', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
        }).then(() => carregarConversas());
    }

    // ── Envio de mensagem ──
    btnAnexo.addEventListener('click', () => inputAnexos.click());

    inputAnexos.addEventListener('change', function () {
        anexosPreview.classList.toggle('hidden', inputAnexos.files.length === 0);
        anexosPreview.innerHTML = Array.from(inputAnexos.files).map(f =>
            '<span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-slate-700">' + escapeHtml(f.name) + '</span>'
        ).join('');
    });

    formEnvio.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!conversaAtualId) return;

        const texto = inputTexto.value.trim();
        if (!texto && inputAnexos.files.length === 0) return;

        const formData = new FormData();
        if (texto) formData.append('texto', texto);
        Array.from(inputAnexos.files).forEach(f => formData.append('anexos[]', f));

        btnEnviar.disabled = true;

        fetch('{{ url("chat/conversas") }}/' + conversaAtualId + '/mensagens', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            body: formData,
        })
            .then(r => {
                if (!r.ok) throw new Error('Falha ao enviar mensagem.');
                return r.json();
            })
            .then(function (data) {
                renderMensagem(data.mensagem, true);
                inputTexto.value = '';
                inputAnexos.value = '';
                anexosPreview.classList.add('hidden');
                anexosPreview.innerHTML = '';
                enviarDigitando(false);
                carregarConversas();
            })
            .catch(function () {
                Swal.fire({ icon: 'error', title: 'Erro', text: 'Não foi possível enviar a mensagem.', confirmButtonColor: '#0084AA' });
            })
            .finally(function () { btnEnviar.disabled = false; });
    });

    inputTexto.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            formEnvio.requestSubmit();
        }
    });

    inputTexto.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 96) + 'px';

        enviarDigitando(true);
        clearTimeout(digitandoTimeout);
        digitandoTimeout = setTimeout(() => enviarDigitando(false), 2000);
    });

    function enviarDigitando(digitando) {
        if (!conversaAtualId || digitando === ultimoDigitandoEnviado) return;
        ultimoDigitandoEnviado = digitando;
        fetch('{{ url("chat/conversas") }}/' + conversaAtualId + '/digitando', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ digitando: digitando }),
        });
    }

    // ── Modal nova conversa / grupo ──
    document.getElementById('chat-nova-conversa-btn').addEventListener('click', abrirModal);
    document.getElementById('chat-modal-fechar').addEventListener('click', fecharModal);
    document.getElementById('chat-modal-cancelar').addEventListener('click', fecharModal);

    function abrirModal() {
        modalEl.classList.remove('hidden');
        usuariosSelecionados = new Set();
        modalNomeInput.value = '';
        modalBuscaInput.value = '';
        carregarUsuariosModal('');
    }

    function fecharModal() {
        modalEl.classList.add('hidden');
    }

    // ── Modal de visualização de imagem ──
    const modalImagemEl = document.getElementById('chat-modal-imagem');
    const modalImagemImg = document.getElementById('chat-modal-imagem-img');
    const modalImagemDownload = document.getElementById('chat-modal-imagem-download');

    function abrirModalImagem(url, nome) {
        modalImagemImg.src = url;
        modalImagemDownload.href = url;
        modalImagemDownload.setAttribute('download', nome || '');
        modalImagemEl.classList.remove('hidden');
    }

    function fecharModalImagem() {
        modalImagemEl.classList.add('hidden');
        modalImagemImg.src = '';
    }

    document.getElementById('chat-modal-imagem-fechar').addEventListener('click', fecharModalImagem);
    modalImagemEl.addEventListener('click', function (e) {
        if (e.target === modalImagemEl) { fecharModalImagem(); }
    });
    mensagensEl.addEventListener('click', function (e) {
        const img = e.target.closest('.chat-imagem-anexo');
        if (img) { abrirModalImagem(img.dataset.url, img.dataset.nome); }
    });

    let buscaTimeout = null;
    modalBuscaInput.addEventListener('input', function () {
        clearTimeout(buscaTimeout);
        buscaTimeout = setTimeout(() => carregarUsuariosModal(this.value.trim()), 250);
    });

    function carregarUsuariosModal(busca) {
        fetch('{{ route("chat.usuarios") }}?q=' + encodeURIComponent(busca), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(function (usuarios) {
                modalUsuariosEl.innerHTML = '';
                usuarios.forEach(function (u) {
                    const li = document.createElement('li');
                    li.className = 'flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-700/50';
                    li.innerHTML =
                        '<input type="checkbox" class="chat-modal-checkbox" data-id="' + u.id + '" ' + (usuariosSelecionados.has(u.id) ? 'checked' : '') + '>' +
                        '<div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-slate-600 flex-shrink-0 flex items-center justify-center overflow-hidden">' + avatarHtml(u.foto_url, u.nome) + '</div>' +
                        '<span class="text-sm text-gray-700 dark:text-slate-200">' + escapeHtml(u.nome) + '</span>';

                    li.addEventListener('click', function (e) {
                        const checkbox = li.querySelector('.chat-modal-checkbox');
                        if (e.target !== checkbox) { checkbox.checked = !checkbox.checked; }
                        if (checkbox.checked) { usuariosSelecionados.add(u.id); } else { usuariosSelecionados.delete(u.id); }
                    });

                    modalUsuariosEl.appendChild(li);
                });
            });
    }

    document.getElementById('chat-modal-criar').addEventListener('click', function () {
        if (usuariosSelecionados.size === 0) {
            Swal.fire({ icon: 'warning', title: 'Selecione ao menos um colaborador.', confirmButtonColor: '#0084AA' });
            return;
        }

        const nomeGrupo = modalNomeInput.value.trim();

        if (usuariosSelecionados.size === 1 && !nomeGrupo) {
            const usuarioId = Array.from(usuariosSelecionados)[0];
            fetch('{{ url("chat/conversas/individual") }}/' + usuarioId, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken() },
            })
                .then(r => r.json())
                .then(function (data) {
                    fecharModal();
                    carregarConversas();
                    abrirConversa(data.conversa_id);
                });
            return;
        }

        if (!nomeGrupo) {
            Swal.fire({ icon: 'warning', title: 'Informe um nome para o grupo.', confirmButtonColor: '#0084AA' });
            return;
        }

        const formData = new FormData();
        formData.append('nome', nomeGrupo);
        usuariosSelecionados.forEach(id => formData.append('usuarios[]', id));

        fetch('{{ route("chat.grupo.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken() },
            body: formData,
        })
            .then(r => r.json())
            .then(function (data) {
                fecharModal();
                carregarConversas();
                abrirConversa(data.conversa_id);
            });
    });

    carregarConversas();

    // Abre a conversa direto se veio de um link/toast (ex: ?conversa=5)
    const conversaViaUrl = Number(new URLSearchParams(window.location.search).get('conversa'));
    if (conversaViaUrl) {
        abrirConversa(conversaViaUrl);
    }

    // ── Badge de não lidas / novas conversas em tempo real ──
    whenEchoReady(function (Echo) {
        Echo.private('usuario.' + userId).listen('.mensagem.enviada', function (e) {
            carregarConversas();
        });
    });

    // Polling da lista de conversas (reforço/alternativa ao WebSocket),
    // mesmo padrão de intervalo usado no sino de notificações do header.
    pollingConversasInterval = setInterval(carregarConversas, 15000);
}());
</script>
@endpush
@endsection
