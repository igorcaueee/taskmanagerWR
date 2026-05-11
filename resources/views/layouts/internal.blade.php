<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" id="html-root">
<script>
    (function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.getElementById('html-root').classList.add('dark');
        }
    }());
</script>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="WR Assessoria - Área Interna">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Painel — WR Assessoria')</title>
        @include('partials.head')

        <style>
            /* Sidebar */
            .sidebar { width: 16rem; flex-shrink: 0; transition: transform .2s ease-in-out; }

            /* Mobile: sidebar desliza sobre o conteúdo */
            @media (max-width: 767px) {
                .sidebar {
                    transform: translateX(-100%);
                    position: fixed;
                    z-index: 40;
                    top: 0;
                    left: 0;
                    height: 100vh;
                    overflow-y: auto;
                }
                .sidebar.open { transform: translateX(0); }
            }

            @keyframes chatbotSlideUp {
                from { opacity: 0; transform: translateY(12px) scale(0.97); }
                to   { opacity: 1; transform: translateY(0) scale(1); }
            }
            .chatbot-open { animation: chatbotSlideUp 0.2s ease-out forwards; }

            .chatbot-prose p { margin: 0 0 0.4em; }
            .chatbot-prose p:last-child { margin-bottom: 0; }
            .chatbot-prose strong { font-weight: 600; }
            .chatbot-prose em { font-style: italic; }
            .chatbot-prose ul, .chatbot-prose ol { padding-left: 1.3em; margin: 0.3em 0; }
            .chatbot-prose li { margin: 0.15em 0; }
            .chatbot-prose code { background: #f3f4f6; padding: 0.1em 0.3em; border-radius: 3px; font-size: 0.85em; font-family: monospace; }
            .chatbot-prose pre { background: #f3f4f6; padding: 0.6em; border-radius: 6px; overflow-x: auto; font-size: 0.8em; margin: 0.3em 0; }
            .chatbot-prose pre code { background: none; padding: 0; }
            .chatbot-prose h1, .chatbot-prose h2, .chatbot-prose h3 { font-weight: 600; margin: 0.5em 0 0.2em; line-height: 1.3; }
            .chatbot-prose a { color: #0084AA; text-decoration: underline; }
            .chatbot-prose blockquote { border-left: 3px solid #0084AA; padding-left: 0.75em; margin: 0.3em 0; color: #6b7280; }
        </style>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-gray-50 dark:bg-[#0f172a] text-gray-900 dark:text-slate-100 min-h-screen transition-colors duration-200">
        @include('partials.internal.header')

        <div class="flex min-h-[calc(100vh-64px)]">
            <aside id="main-sidebar" class="sidebar bg-white dark:bg-[#1e293b] border-r border-gray-200 dark:border-[#334155]">
                @include('partials.internal.sidebar')
            </aside>

            <div class="flex-1 p-4 sm:p-6 min-w-0 overflow-x-hidden">
                @yield('content')
            </div>
        </div>

        {{-- Global modal --}}
        <div id="globalModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div id="modalContent" class="p-6"></div>
            </div>
        </div>

        @stack('scripts')

        {{-- Chatbot flutuante --}}
        <div id="chatbot-widget" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">

            {{-- Painel de chat --}}
            <div id="chatbot-panel" class="hidden flex-col bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-slate-700 overflow-hidden" style="width: 384px; height: 520px; min-width: 280px; min-height: 360px; max-width: 680px; max-height: 85vh; position: relative;">
                {{-- Resize handle --}}
                <div id="chatbot-resize" title="Redimensionar" style="position:absolute;top:0;left:0;width:18px;height:18px;cursor:nw-resize;z-index:10;" class="flex items-center justify-center">
                    <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity:0.35">
                        <circle cx="2" cy="2" r="1" fill="white"/>
                        <circle cx="5" cy="2" r="1" fill="white"/>
                        <circle cx="2" cy="5" r="1" fill="white"/>
                        <circle cx="5" cy="5" r="1" fill="white"/>
                        <circle cx="8" cy="2" r="1" fill="white"/>
                        <circle cx="2" cy="8" r="1" fill="white"/>
                    </svg>
                </div>

                {{-- Header --}}
                <div class="flex items-center justify-between bg-[#0084AA] px-4 py-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fa-solid fa-robot text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-white font-semibold text-sm leading-tight">Liri</p>
                            <p class="text-[#b3dde8] text-xs">Especialista em Contabilidade</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button id="chatbot-clear" title="Limpar conversa" class="w-7 h-7 flex items-center justify-center rounded-full bg-black/30 hover:bg-black/50 text-white transition-all text-sm">
                            <i class="fa-solid fa-broom"></i>
                        </button>
                    </div>
                </div>

                {{-- Messages --}}
                <div id="chatbot-messages" class="flex-1 overflow-y-auto p-4 space-y-3 bg-gray-50 dark:bg-slate-900" style="min-height: 0;">
                    <div class="flex gap-2">
                        <div class="w-7 h-7 rounded-full bg-[#e0f4f9] flex-shrink-0 flex items-center justify-center mt-0.5">
                            <i class="fa-solid fa-robot text-[#0084AA] text-xs"></i>
                        </div>
                        <div class="bg-white dark:bg-slate-700 rounded-2xl rounded-tl-sm px-3 py-2 shadow-sm border border-gray-100 dark:border-slate-600 text-sm text-gray-700 dark:text-slate-200 max-w-[85%]">
                            Olá! Sou a Liri, assistente de contabilidade da WR. Pode me perguntar sobre tributos, obrigações fiscais, suas tarefas no sistema e muito mais. Como posso ajudar?
                        </div>
                    </div>
                </div>

                {{-- Typing indicator (hidden) --}}
                <div id="chatbot-typing" class="hidden px-4 py-2 bg-gray-50 dark:bg-slate-900 border-t border-gray-100 dark:border-slate-700">
                    <div class="flex gap-2 items-center">
                        <div class="w-7 h-7 rounded-full bg-[#e0f4f9] flex-shrink-0 flex items-center justify-center">
                            <i class="fa-solid fa-robot text-[#0084AA] text-xs"></i>
                        </div>
                        <div class="flex gap-1 items-center bg-white dark:bg-slate-700 rounded-full px-3 py-2 shadow-sm border border-gray-100 dark:border-slate-600">
                            <span class="w-1.5 h-1.5 rounded-full animate-bounce" style="background-color:#0084AA;animation-delay:0ms"></span>
                            <span class="w-1.5 h-1.5 rounded-full animate-bounce" style="background-color:#0084AA;animation-delay:150ms"></span>
                            <span class="w-1.5 h-1.5 rounded-full animate-bounce" style="background-color:#0084AA;animation-delay:300ms"></span>
                        </div>
                    </div>
                </div>

                {{-- Input --}}
                <div class="px-3 py-3 bg-white dark:bg-slate-800 border-t border-gray-100 dark:border-slate-700 flex gap-2 items-end">
                    <textarea
                        id="chatbot-input"
                        rows="1"
                        placeholder="Digite sua pergunta..."
                        class="flex-1 resize-none rounded-xl border border-gray-200 dark:border-slate-600 px-3 py-2 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-[#0084AA] focus:border-transparent max-h-24 overflow-y-auto"
                        style="min-height: 38px;"
                    ></textarea>
                    <button
                        id="chatbot-send"
                        class="flex-shrink-0 w-9 h-9 rounded-xl bg-[#0084AA] hover:bg-[#006e8e] text-white flex items-center justify-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fa-solid fa-paper-plane text-xs"></i>
                    </button>
                </div>
            </div>

            {{-- Toggle button --}}
            <button
                id="chatbot-toggle"
                class="w-14 h-14 rounded-full bg-[#0084AA] hover:bg-[#006e8e] text-white shadow-lg flex items-center justify-center transition-all duration-200 hover:scale-105 active:scale-95"
                title="Liri — Assistente de Contabilidade"
            >
                <i id="chatbot-toggle-icon" class="fa-solid fa-robot text-xl"></i>
            </button>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/marked@14/marked.min.js"></script>
        <script>
        (function () {
            const panel = document.getElementById('chatbot-panel');
            const toggleBtn = document.getElementById('chatbot-toggle');
            const toggleIcon = document.getElementById('chatbot-toggle-icon');
            const clearBtn = document.getElementById('chatbot-clear');
            const input = document.getElementById('chatbot-input');
            const sendBtn = document.getElementById('chatbot-send');
            const messagesEl = document.getElementById('chatbot-messages');
            const typingEl = document.getElementById('chatbot-typing');

            // Restore saved panel size
            const savedW = localStorage.getItem('chatbot_width');
            const savedH = localStorage.getItem('chatbot_height');
            if (savedW) { panel.style.width = savedW; }
            if (savedH) { panel.style.height = savedH; }

            let isOpen = false;
            let isSending = false;

            function openPanel() {
                isOpen = true;
                panel.classList.remove('hidden', 'chatbot-open');
                panel.classList.add('flex');
                void panel.offsetWidth; // reflow to restart animation
                panel.classList.add('chatbot-open');
                toggleIcon.className = 'fa-solid fa-xmark text-xl';
                input.focus();
                scrollToBottom();
            }

            function closePanel() {
                isOpen = false;
                panel.classList.add('hidden');
                panel.classList.remove('flex', 'chatbot-open');
                toggleIcon.className = 'fa-solid fa-robot text-xl';
            }

            toggleBtn.addEventListener('click', function () {
                isOpen ? closePanel() : openPanel();
            });

            clearBtn.addEventListener('click', function () {
                axios.delete('{{ route('chatbot.clear') }}')
                    .then(function () {
                        messagesEl.innerHTML = `
                            <div class="flex gap-2">
                                <div class="w-7 h-7 rounded-full bg-[#e0f4f9] flex-shrink-0 flex items-center justify-center mt-0.5">
                                    <i class="fa-solid fa-robot text-[#0084AA] text-xs"></i>
                                </div>
                                <div class="bg-white dark:bg-slate-700 rounded-2xl rounded-tl-sm px-3 py-2 shadow-sm border border-gray-100 dark:border-slate-600 text-sm text-gray-700 dark:text-slate-200 max-w-[85%]">
                                    Conversa reiniciada. Como posso ajudar?
                                </div>
                            </div>`;
                    });
            });

            function scrollToBottom() {
                messagesEl.scrollTop = messagesEl.scrollHeight;
            }

            function appendMessage(text, role) {
                const isUser = role === 'user';
                const div = document.createElement('div');
                div.className = isUser ? 'flex gap-2 justify-end' : 'flex gap-2';

                if (isUser) {
                    div.innerHTML = `
                        <div class="bg-[#0084AA] rounded-2xl rounded-tr-sm px-3 py-2 text-sm text-white max-w-[85%] whitespace-pre-wrap">${escapeHtml(text)}</div>
                        <div class="w-7 h-7 rounded-full bg-gray-200 flex-shrink-0 flex items-center justify-center mt-0.5">
                            <i class="fa-solid fa-user text-gray-500 text-xs"></i>
                        </div>`;
                } else {
                    div.innerHTML = `
                        <div class="w-7 h-7 rounded-full bg-[#e0f4f9] flex-shrink-0 flex items-center justify-center mt-0.5">
                            <i class="fa-solid fa-robot text-[#0084AA] text-xs"></i>
                        </div>
                        <div class="bg-white dark:bg-slate-700 rounded-2xl rounded-tl-sm px-3 py-2 shadow-sm border border-gray-100 dark:border-slate-600 text-sm text-gray-700 dark:text-slate-200 max-w-[85%] chatbot-prose">${marked.parse(text)}</div>`;
                }

                messagesEl.appendChild(div);
                scrollToBottom();
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.appendChild(document.createTextNode(text));
                return div.innerHTML;
            }

            function sendMessage() {
                const text = input.value.trim();
                if (!text || isSending) { return; }

                isSending = true;
                sendBtn.disabled = true;
                input.value = '';
                input.style.height = 'auto';

                appendMessage(text, 'user');

                typingEl.classList.remove('hidden');
                scrollToBottom();

                axios.post('{{ route('chatbot.message') }}', { message: text })
                    .then(function (res) {
                        typingEl.classList.add('hidden');
                        appendMessage(res.data.message, 'assistant');
                    })
                    .catch(function (err) {
                        typingEl.classList.add('hidden');
                        const msg = err.response?.data?.message ?? 'Desculpe, ocorreu um erro. Tente novamente.';
                        appendMessage(msg, 'assistant');
                    })
                    .finally(function () {
                        isSending = false;
                        sendBtn.disabled = false;
                        input.focus();
                    });
            }

            sendBtn.addEventListener('click', sendMessage);

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Auto-resize textarea
            input.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 96) + 'px';
            });

            // Panel resize handle (drag top-left corner)
            const resizeHandle = document.getElementById('chatbot-resize');
            resizeHandle.addEventListener('mousedown', function (e) {
                e.preventDefault();
                const startX = e.clientX;
                const startY = e.clientY;
                const startW = panel.offsetWidth;
                const startH = panel.offsetHeight;
                const minW = parseInt(panel.style.minWidth);
                const minH = parseInt(panel.style.minHeight);
                const maxW = parseInt(panel.style.maxWidth);

                function onMove(e) {
                    const newW = Math.min(maxW, Math.max(minW, startW - (e.clientX - startX)));
                    const newH = Math.max(minH, startH - (e.clientY - startY));
                    panel.style.width = newW + 'px';
                    panel.style.height = newH + 'px';
                }
                function onUp() {
                    document.removeEventListener('mousemove', onMove);
                    document.removeEventListener('mouseup', onUp);
                    localStorage.setItem('chatbot_width', panel.style.width);
                    localStorage.setItem('chatbot_height', panel.style.height);
                }
                document.addEventListener('mousemove', onMove);
                document.addEventListener('mouseup', onUp);
            });
        }());
        </script>
    </body>
</html>
