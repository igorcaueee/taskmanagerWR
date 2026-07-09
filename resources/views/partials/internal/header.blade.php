<header id="main-header-internal" class="bg-white dark:bg-[#1e293b] border-b border-gray-200 dark:border-[#334155] shadow-sm transition-colors duration-200 fixed top-0 left-0 right-0 z-30">
    <div class="w-full px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">

        <!-- Esquerda: hamburguer + logo -->
        <div class="flex items-center gap-3">
            <!-- Botão hamburguer (mobile E desktop para recolher) -->
            <button id="nav-toggle" aria-label="Abrir menu" aria-expanded="false"
                class="flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-150 border-0 bg-transparent cursor-pointer">
                <i class="fa-solid fa-bars text-base"></i>
            </button>

            <img src="/images/torresemfundo.png" alt="WR" class="w-10 h-10 object-contain">
            <span class="hidden sm:block text-sm font-semibold text-gray-700 dark:text-slate-200">WR Assessoria</span>
        </div>

        <!-- Centro: busca de clientes -->
        <div class="flex-1 flex justify-center px-4 max-w-xl mx-auto relative" id="header-search-wrapper">
            <div class="w-full relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" id="header-search-input"
                       placeholder="Buscar cliente..."
                       autocomplete="off"
                       class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-700 text-gray-700 dark:text-slate-200 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-brand">
                <!-- Dropdown de resultados -->
                <div id="header-search-results"
                     class="hidden absolute top-full left-0 right-0 mt-1 bg-white dark:bg-slate-800 rounded-lg shadow-xl z-50 overflow-hidden">
                    <ul id="header-search-list" class="max-h-72 overflow-y-auto"></ul>
                    <div id="header-search-empty" class="hidden px-4 py-3 text-xs text-gray-400 dark:text-slate-500 italic text-center">Nenhum cliente encontrado.</div>
                    <div id="header-search-loading" class="hidden px-4 py-3 text-xs text-gray-400 dark:text-slate-500 text-center">
                        <i class="fa-solid fa-spinner fa-spin mr-1"></i> Buscando...
                    </div>
                </div>
            </div>
        </div>

        <!-- Direita: nome, toggle dark, logout -->
        <div class="flex items-center gap-2">

<script>
(function () {
    const input = document.getElementById('header-search-input');
    const dropdown = document.getElementById('header-search-results');
    const list = document.getElementById('header-search-list');
    const empty = document.getElementById('header-search-empty');
    const loading = document.getElementById('header-search-loading');
    let timer = null;

    function showDropdown() { dropdown.classList.remove('hidden'); }
    function hideDropdown() { dropdown.classList.add('hidden'); }

    function setLoading(on) {
        loading.classList.toggle('hidden', !on);
        empty.classList.add('hidden');
        if (on) { list.innerHTML = ''; }
    }

    input.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(timer);

        if (q.length < 2) { hideDropdown(); return; }

        setLoading(true);
        showDropdown();

        timer = setTimeout(function () {
            fetch('{{ route('clientes.busca') }}?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(function (data) {
                setLoading(false);
                list.innerHTML = '';

                if (!data.length) {
                    empty.classList.remove('hidden');
                    return;
                }

                data.forEach(function (c) {
                    const li = document.createElement('li');
                    li.className = 'flex items-center gap-2 px-2 py-2.5 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer';
                    li.innerHTML =
                        '<span class="text-gray-400 text-xs flex-shrink-0">' +
                            (c.tipo == 1 ? '<i class="fa-solid fa-building-user"></i>' : '<i class="fa-solid fa-user"></i>') +
                        '</span>' +
                        '<div class="min-w-0 flex-1">' +
                            '<p class="text-xs font-medium text-gray-800 dark:text-slate-100 truncate">' + c.nome + '</p>' +
                            (c.cpfcnpj ? '<p class="text-xs text-gray-400 dark:text-slate-500">' + c.cpfcnpj + '</p>' : '') +
                        '</div>' +
                        (c.status === 'ativo'
                            ? '<span class="text-xs px-1.5 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300 flex-shrink-0">Ativo</span>'
                            : '<span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 flex-shrink-0">Inativo</span>');
                    li.addEventListener('click', function () {
                        window.location.href = c.url;
                    });
                    list.appendChild(li);
                });
            })
            .catch(function () { setLoading(false); });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('header-search-wrapper').contains(e.target)) {
            hideDropdown();
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { hideDropdown(); input.blur(); }
    });
}());
</script>

            <span class="hidden sm:block text-sm text-gray-600 dark:text-slate-400">{{ auth()->user()?->nome }}</span>

            {{-- Chat --}}
            <a href="{{ route('chat.index') }}" title="Chat"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-150 relative no-underline">
                <i class="fa-solid fa-comments text-sm"></i>
                <span id="chat-badge" class="hidden absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold items-center justify-center leading-none"></span>
            </a>

            {{-- Notificações --}}
            <div class="relative" id="notif-wrapper">
                <button id="notif-btn" title="Notificações"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-150 border-0 bg-transparent cursor-pointer relative">
                    <i class="fa-solid fa-bell text-sm"></i>
                    <span id="notif-badge" class="hidden absolute -top-0.5 -right-0.5 w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold items-center justify-center leading-none"></span>
                </button>

                <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-gray-200 dark:border-slate-700 z-50 overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                        <span class="text-sm font-semibold text-gray-700 dark:text-slate-200">Notificações</span>
                        <button id="notif-mark-all" type="button" title="Marcar todas como lidas"
                            class="shrink-0 w-7 h-7 flex items-center justify-center rounded-lg text-brand hover:bg-gray-100 dark:hover:bg-slate-700 border-0 bg-transparent p-0 cursor-pointer focus:outline-none">
                            <i class="fa-solid fa-check-double text-xs"></i>
                        </button>
                    </div>
                    <ul id="notif-list" class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-slate-700">
                        <li class="px-4 py-6 text-center text-xs text-gray-400 dark:text-slate-500" id="notif-empty">Nenhuma notificação.</li>
                    </ul>
                </div>
            </div>

            {{-- Dark mode toggle --}}
            <button id="dark-mode-toggle" title="Alternar modo escuro"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-500 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors duration-150 border-0 bg-transparent cursor-pointer">
                <i id="dark-mode-icon" class="fa-solid fa-moon text-sm"></i>
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Sair"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors duration-150 border-0 bg-transparent cursor-pointer">
                    <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                </button>
            </form>
        </div>
    </div>
</header>

{{-- Container de toasts de notificação --}}
<div id="notif-toast-container" class="fixed bottom-6 right-6 z-9999 flex flex-col gap-2 items-end pointer-events-none">
</div>
<style>
    #notif-toast-container > * { pointer-events: auto; }
</style>

{{-- Espaçador para compensar o header fixo --}}
<div class="h-16"></div>

{{-- Overlay mobile --}}
<div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/40 z-20 md:hidden"></div>

<script>
(function () {
    const html = document.getElementById('html-root');
    const btn = document.getElementById('dark-mode-toggle');
    const icon = document.getElementById('dark-mode-icon');
    const overlay = document.getElementById('sidebar-overlay');
    const sidebar = document.getElementById('main-sidebar');

    /* ── Dark mode toggle ── */
    function syncIcon() {
        if (html.classList.contains('dark')) {
            icon.className = 'fa-solid fa-sun text-sm';
        } else {
            icon.className = 'fa-solid fa-moon text-sm';
        }
    }
    syncIcon();

    btn.addEventListener('click', function () {
        if (html.classList.contains('dark')) {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
        syncIcon();
    });

    /* ── Overlay mobile fecha sidebar ── */
    if (overlay && sidebar) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.add('hidden');
        });
    }
}());

(function () {
    const btn      = document.getElementById('notif-btn');
    const dropdown = document.getElementById('notif-dropdown');
    const badge    = document.getElementById('notif-badge');
    const list     = document.getElementById('notif-list');
    const empty    = document.getElementById('notif-empty');
    const markAll  = document.getElementById('notif-mark-all');

    function formatRelTime(dateStr) {
        const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
        if (diff < 60)   return 'agora mesmo';
        if (diff < 3600) return Math.floor(diff / 60) + ' min atrás';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h atrás';
        return Math.floor(diff / 86400) + 'd atrás';
    }

    function renderNotificacoes(data) {
        const { notificacoes, nao_lidas } = data;

        if (nao_lidas > 0) {
            badge.textContent = nao_lidas > 9 ? '9+' : nao_lidas;
            badge.classList.remove('hidden');
            badge.style.display = 'flex';
        } else {
            badge.classList.add('hidden');
            badge.style.display = '';
        }

        if (!notificacoes.length) {
            empty.classList.remove('hidden');
            list.innerHTML = '';
            list.appendChild(empty);
            return;
        }

        empty.classList.add('hidden');
        list.innerHTML = '';

        notificacoes.forEach(function (n) {
            const li = document.createElement('li');
            li.className = 'flex items-start gap-3 px-4 py-3 cursor-pointer transition-colors ' +
                (n.lida ? 'hover:bg-gray-50 dark:hover:bg-slate-700/50' : 'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30');

            const iconBg  = n.lida ? 'bg-gray-100 dark:bg-slate-700' : 'bg-[#0084AA]';
            const iconClr = n.lida ? 'text-gray-400 dark:text-slate-500' : 'text-white';

            li.innerHTML =
                '<div class="mt-0.5 w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center ' + iconBg + '">' +
                    '<i class="fa-solid fa-bell text-xs ' + iconClr + '"></i>' +
                '</div>' +
                '<div class="flex-1 min-w-0">' +
                    '<p class="text-xs text-gray-700 dark:text-slate-200 leading-snug">' + n.mensagem + '</p>' +
                    '<p class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">' + formatRelTime(n.created_at) + '</p>' +
                '</div>';

            li.addEventListener('click', function () {
                const irParaTarefa = function () {
                    if (n.tarefa_id) {
                        window.location.href = '{{ route("tarefas.list") }}?tarefa_id=' + n.tarefa_id;
                    }
                };

                if (!n.lida) {
                    fetch('{{ route("notificacoes.marcar-lida", ":id") }}'.replace(':id', n.id), {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    }).then(function () {
                        carregar();
                        irParaTarefa();
                    });
                } else {
                    irParaTarefa();
                }
            });

            list.appendChild(li);
        });
    }

    // IDs já vistos nesta sessão — evita toast no primeiro carregamento
    let idsConhecidos = null;

    function showToast(mensagem) {
        const container = document.getElementById('notif-toast-container');
        const toast = document.createElement('div');
        toast.className = [
            'flex items-start gap-3 px-4 py-3 rounded-xl shadow-2xl border',
            'bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-600',
            'text-sm text-gray-800 dark:text-slate-100',
            'transition-all duration-300 opacity-0 translate-y-2',
        ].join(' ');
        toast.style.maxWidth = '320px';
        toast.innerHTML =
            '<div class="w-8 h-8 rounded-full bg-[#0084AA] flex-shrink-0 flex items-center justify-center">' +
                '<i class="fa-solid fa-bell text-white text-xs"></i>' +
            '</div>' +
            '<div class="flex-1 min-w-0">' +
                '<p class="text-xs font-semibold text-gray-700 dark:text-slate-200 mb-0.5">Nova tarefa atribuída</p>' +
                '<p class="text-xs text-gray-600 dark:text-slate-300 leading-snug">' + mensagem + '</p>' +
            '</div>' +
            '<button type="button" title="Fechar" class="shrink-0 w-6 h-6 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-slate-300 dark:hover:bg-slate-700 border-0 bg-transparent p-0 cursor-pointer focus:outline-none" onclick="this.closest(\'.notif-toast\').remove()"><i class="fa-solid fa-xmark text-xs"></i></button>';
        toast.classList.add('notif-toast');

        container.appendChild(toast);

        // Animação entrada
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                toast.classList.remove('opacity-0', 'translate-y-2');
                toast.classList.add('opacity-100', 'translate-y-0');
            });
        });

        // Auto-dismiss em 5s
        setTimeout(function () {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(function () { toast.remove(); }, 300);
        }, 5000);
    }

    function carregar() {
        fetch('{{ route("notificacoes.index") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            const novosIds = data.notificacoes.map(function (n) { return n.id; });

            if (idsConhecidos !== null) {
                // Detecta notificações que ainda não conhecíamos
                data.notificacoes.forEach(function (n) {
                    if (!idsConhecidos.includes(n.id) && !n.lida) {
                        showToast(n.mensagem);
                    }
                });
            }

            idsConhecidos = novosIds;
            renderNotificacoes(data);
        })
        .catch(function () {});
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
        if (!dropdown.classList.contains('hidden')) { carregar(); }
    });

    markAll.addEventListener('click', function (e) {
        e.stopPropagation();
        fetch('{{ route("notificacoes.marcar-todas-lidas") }}', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        }).then(function () { carregar(); });
    });

    document.addEventListener('click', function (e) {
        if (!document.getElementById('notif-wrapper').contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    carregar();
    setInterval(carregar, 15000);
}());

// Badge de mensagens não lidas do chat
(function () {
    const badge = document.getElementById('chat-badge');
    if (!badge) { return; }

    function renderBadge(total) {
        if (total > 0) {
            badge.textContent = total > 9 ? '9+' : total;
            badge.classList.remove('hidden');
            badge.style.display = 'flex';
        } else {
            badge.classList.add('hidden');
            badge.style.display = '';
        }
    }

    function carregar() {
        fetch('{{ route("chat.nao-lidas") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) { renderBadge(data.nao_lidas); })
            .catch(function () {});
    }

    carregar();

    function mostrarToastChat(mensagem) {
        const container = document.getElementById('notif-toast-container');
        if (!container) { return; }

        const preview = mensagem.texto
            ? mensagem.texto
            : (mensagem.anexos && mensagem.anexos.length ? (mensagem.anexos[0].tipo === 'imagem' ? '📷 Foto' : '📎 Anexo') : '');

        const toast = document.createElement('div');
        toast.className = [
            'flex items-start gap-3 px-4 py-3 rounded-xl shadow-2xl border cursor-pointer',
            'bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-600',
            'text-sm text-gray-800 dark:text-slate-100',
            'transition-all duration-300 opacity-0 translate-y-2',
        ].join(' ');
        toast.style.maxWidth = '320px';
        toast.innerHTML =
            '<div class="w-8 h-8 rounded-full bg-[#0084AA] flex-shrink-0 flex items-center justify-center overflow-hidden">' +
                (mensagem.usuario.foto_url
                    ? '<img src="' + mensagem.usuario.foto_url + '" class="w-full h-full object-cover">'
                    : '<i class="fa-solid fa-comments text-white text-xs"></i>') +
            '</div>' +
            '<div class="flex-1 min-w-0">' +
                '<p class="text-xs font-semibold text-gray-700 dark:text-slate-200 mb-0.5">' + mensagem.usuario.nome + '</p>' +
                '<p class="text-xs text-gray-600 dark:text-slate-300 leading-snug truncate">' + preview + '</p>' +
            '</div>' +
            '<button type="button" title="Fechar" class="shrink-0 w-6 h-6 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-slate-300 dark:hover:bg-slate-700 border-0 bg-transparent p-0 cursor-pointer focus:outline-none"><i class="fa-solid fa-xmark text-xs"></i></button>';

        toast.querySelector('button').addEventListener('click', function (e) {
            e.stopPropagation();
            toast.remove();
        });
        toast.addEventListener('click', function () {
            window.location.href = '{{ route("chat.index") }}?conversa=' + mensagem.conversa_id;
        });

        container.appendChild(toast);

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                toast.classList.remove('opacity-0', 'translate-y-2');
                toast.classList.add('opacity-100', 'translate-y-0');
            });
        });

        setTimeout(function () {
            toast.classList.add('opacity-0', 'translate-y-2');
            setTimeout(function () { toast.remove(); }, 300);
        }, 6000);
    }

    function assinarEcho() {
        if (!window.Echo) { return; }

        window.Echo.private('usuario.{{ auth()->id() }}').listen('.mensagem.enviada', function (e) {
            carregar();

            if (e.mensagem.usuario.id === {{ auth()->id() }}) { return; }
            if (window.chatConversaAbertaId === e.mensagem.conversa_id) { return; }

            mostrarToastChat(e.mensagem);
        });
    }

    if (window.Echo) {
        assinarEcho();
    } else {
        window.addEventListener('laravel-echo:ready', assinarEcho, { once: true });
    }

    // Polling como reforço/alternativa ao WebSocket (mesmo padrão das
    // notificações), garante que o badge atualize mesmo sem o Reverb.
    setInterval(carregar, 15000);
}());

// Aviso de nova versão do sistema disponível
(function () {
    const versaoAtual = document.querySelector('meta[name="app-version"]')?.content;
    if (!versaoAtual) { return; }

    let avisado = false;

    function verificarVersao() {
        if (avisado) { return; }

        fetch('{{ route('system.version') }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.version && data.version !== versaoAtual) {
                avisado = true;
                Swal.fire({
                    icon: 'info',
                    title: 'Sistema atualizado',
                    text: 'Uma nova versão está disponível. Atualize a página para continuar.',
                    confirmButtonText: 'Atualizar agora',
                    confirmButtonColor: '#0084AA',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                }).then(function () {
                    window.location.reload();
                });
            }
        })
        .catch(function () {});
    }

    setInterval(verificarVersao, 5 * 60 * 1000);
}());
</script>
