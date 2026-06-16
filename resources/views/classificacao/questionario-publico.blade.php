<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Diagnóstico IDE — WR Assessoria</title>
    <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp">
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --wr-blue: #0084AA;
            --wr-blue-dark: #006688;
            --wr-blue-light: #e6f4f8;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        body {
            background: linear-gradient(160deg, #0a0a0a 0%, #1a2a30 60%, #0a0a0a 100%);
            background-attachment: fixed;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .card-pergunta {
            animation: fadeSlide .35s ease;
        }
        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Botões de opção */
        .opcao-btn {
            transition: all .18s ease;
            cursor: pointer;
            background: #fff;
            border: 2px solid #e5e7eb;
            color: #111827;
        }
        .opcao-btn:hover {
            border-color: var(--wr-blue);
            background: var(--wr-blue-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,132,170,.15);
        }
        .opcao-btn.selected {
            border-color: var(--wr-blue);
            background: var(--wr-blue-light);
        }

        /* Letra do círculo */
        .letra-opcao {
            border-color: #d1d5db;
            color: #6b7280;
        }
        .opcao-btn:hover .letra-opcao,
        .opcao-btn.selected .letra-opcao {
            background: var(--wr-blue);
            border-color: var(--wr-blue);
            color: #fff;
        }

        /* Badge de categoria */
        .tag-cat {
            background: rgba(0,132,170,.15);
            color: var(--wr-blue);
        }

        /* Barra de progresso */
        .progress-bar { background: var(--wr-blue); }

        /* Botão primário */
        .btn-primary {
            background: var(--wr-blue);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: background .15s ease;
        }
        .btn-primary:hover { background: var(--wr-blue-dark); }

        /* Input */
        .input-wr:focus {
            outline: none;
            ring: 2px solid var(--wr-blue);
            border-color: var(--wr-blue);
        }
    </style>
</head>
<body class="flex flex-col items-center justify-center px-4 py-6" style="min-height:100vh">

    <div class="w-full max-w-xl">

        {{-- Header WR --}}
        <div class="text-center mb-5">
            <div class="inline-flex items-center gap-3 bg-white/10 backdrop-blur-sm border border-white/20 rounded-2xl px-5 py-3 mb-5">
                <img src="{{ asset('images/torresemfundo.png') }}" alt="WR Assessoria" class="h-9 w-9 object-contain">
                <span class="text-base font-bold text-white tracking-tight">WR Assessoria</span>
            </div>
            <h1 class="text-xl font-bold text-white leading-snug">{{ $questionario->titulo }}</h1>
        </div>

        {{-- TELA 1: Finalizado --}}
        @if($finalizado && $resposta)
        @php
            $pontos = $resposta->pontuacao_total;
            $cls    = $resposta->classificacao;
            $cor    = match($cls) {
                'Muito Dinheiro Escondido'    => ['bg' => 'bg-red-600',    'light' => 'bg-red-50',    'text' => 'text-red-700',    'border' => 'border-red-200'],
                'Dinheiro Escondido Relevante' => ['bg' => 'bg-orange-500', 'light' => 'bg-orange-50', 'text' => 'text-orange-700', 'border' => 'border-orange-200'],
                'Boa Gestão'                  => ['bg' => 'bg-blue-600',   'light' => 'bg-blue-50',   'text' => 'text-blue-700',   'border' => 'border-blue-200'],
                'Alta Performance'             => ['bg' => 'bg-green-600',  'light' => 'bg-green-50',  'text' => 'text-green-700',  'border' => 'border-green-200'],
                default                       => ['bg' => 'bg-gray-500',   'light' => 'bg-gray-50',   'text' => 'text-gray-700',   'border' => 'border-gray-200'],
            };
        @endphp
        <div class="bg-white rounded-2xl shadow-lg p-8 text-center card-pergunta">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full {{ $cor['bg'] }} text-white text-3xl font-extrabold mb-4">
                {{ number_format($pontos, 0) }}
            </div>
            <div class="text-xs text-gray-400 mb-1">Seu Índice IDE</div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ $cls }}</h2>

            <div class="my-6 p-4 {{ $cor['light'] }} {{ $cor['border'] }} border rounded-xl text-sm {{ $cor['text'] }} text-left">
                @if($cls === 'Muito Dinheiro Escondido')
                    <p class="font-semibold mb-1">Diagnóstico: Nível Crítico (0–40)</p>
                    <p>Existem oportunidades expressivas de recuperação financeira, tributária e gerencial. É fundamental agir rapidamente para evitar maiores perdas.</p>
                @elseif($cls === 'Dinheiro Escondido Relevante')
                    <p class="font-semibold mb-1">Diagnóstico: Dinheiro Escondido Relevante (41–60)</p>
                    <p>Há áreas importantes a melhorar. Com ações estruturadas é possível recuperar resultados significativos para o negócio.</p>
                @elseif($cls === 'Boa Gestão')
                    <p class="font-semibold mb-1">Diagnóstico: Boa Gestão (61–80)</p>
                    <p>A empresa tem uma boa base de gestão. Ainda existem oportunidades de otimização para atingir alta performance.</p>
                @else
                    <p class="font-semibold mb-1">Diagnóstico: Alta Performance (81–100)</p>
                    <p>Parabéns! Sua empresa demonstra maturidade de gestão. O foco agora é manter e evoluir os processos conquistados.</p>
                @endif
            </div>

            <p class="text-sm text-gray-500">Obrigado por responder ao diagnóstico!<br>Em breve entraremos em contato.</p>
        </div>

        {{-- TELA 2: Formulário de identificação (sem token ou sem sessão iniciada) --}}
        @elseif(! $token || ! $resposta)
        <div id="tela-identificacao" class="bg-white rounded-2xl shadow-xl p-8 card-pergunta">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0" style="background:#0084AA">
                    <i class="fa-solid fa-user text-white text-xs"></i>
                </div>
                <h2 class="text-lg font-bold text-gray-900">Antes de começar</h2>
            </div>
            <p class="text-sm text-gray-500 mb-5 ml-9">Preencha os dados abaixo para iniciar o diagnóstico.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Seu nome <span class="text-red-500">*</span></label>
                    <input id="id-nome" type="text" placeholder="Nome completo"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                        <input id="id-email" type="email" placeholder="seu@email.com"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Telefone <span class="text-red-500">*</span></label>
                        <input id="id-telefone" type="text" placeholder="(00) 00000-0000"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
                        <input id="id-empresa" type="text" placeholder="Nome da empresa"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Segmento</label>
                        <input id="id-segmento" type="text" placeholder="Ex: Comércio"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Faturamento mensal</label>
                        <input id="id-faturamento" type="text" placeholder="R$ 0,00"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nº colaboradores</label>
                        <input id="id-colaboradores" type="number" min="0" placeholder="0"
                               class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <button id="btn-iniciar" onclick="iniciar()"
                    class="btn-primary mt-6 w-full font-semibold py-3 rounded-xl text-sm">
                Iniciar diagnóstico <i class="fa-solid fa-arrow-right ml-1"></i>
            </button>
        </div>

        {{-- TELA 3: Questionário progressivo (sessão em andamento) --}}
        @else
        @php
            $progresso = count($respondidas);
            $pct       = $totalPerguntas > 0 ? round(($progresso / $totalPerguntas) * 100) : 0;
            $catLabels = [
                'financeiro'    => 'Financeiro',
                'tributario'    => 'Tributário',
                'endividamento' => 'Endividamento',
                'gestao'        => 'Gestão',
                'lucratividade' => 'Lucratividade',
            ];
        @endphp

        {{-- Greeting personalizado (link enviado pelo admin) --}}
        @if($resposta && $resposta->respondente_nome)
        <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl px-4 py-3 mb-4 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0" style="background:#0084AA">
                <i class="fa-solid fa-building text-white text-xs"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-white leading-tight">{{ $resposta->respondente_nome }}</p>
                @if($resposta->respondente_empresa)
                <p class="text-xs text-white/80">{{ $resposta->respondente_empresa }}</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Barra de progresso --}}
        <div class="mb-4">
            <div class="flex justify-between text-xs text-white/80 mb-1">
                <span>Progresso</span>
                <span id="progresso-texto">{{ $progresso }}/{{ $totalPerguntas }}</span>
            </div>
            <div class="w-full rounded-full h-1.5" style="background:rgba(255,255,255,.15)">
                <div id="barra-progresso" class="progress-bar h-1.5 rounded-full transition-all duration-500"
                     style="width: {{ $pct }}%"></div>
            </div>
        </div>

        {{-- Card da pergunta atual --}}
        @if($proximaPergunta)
        <div id="card-pergunta" class="bg-white rounded-2xl shadow-xl p-8 card-pergunta">
            <div class="flex items-center gap-2 mb-4">
                <span id="tag-categoria" class="tag-cat text-xs px-2.5 py-0.5 rounded-full font-semibold">
                    {{ $catLabels[$proximaPergunta->categoria] ?? $proximaPergunta->categoria }}
                </span>
                <span id="tag-ordem" class="text-xs text-gray-400">Pergunta {{ $proximaPergunta->ordem }} de {{ $totalPerguntas }}</span>
            </div>

            <p id="texto-pergunta" class="text-lg font-bold text-gray-900 mb-6 leading-snug">
                {{ $proximaPergunta->texto }}
            </p>

            <div id="opcoes-container" class="space-y-3">
                @foreach($proximaPergunta->opcoes as $opcao)
                <button class="opcao-btn w-full text-left px-5 py-4 border-2 rounded-xl text-sm font-medium"
                        data-pergunta="{{ $proximaPergunta->id }}"
                        data-opcao="{{ $opcao->id }}"
                        onclick="responder({{ $proximaPergunta->id }}, {{ $opcao->id }}, this)">
                    <span class="inline-flex items-center gap-3">
                        <span class="letra-opcao w-6 h-6 rounded-full border-2 inline-flex items-center justify-center text-xs font-bold shrink-0 transition-all">
                            {{ ['A','B','C'][$loop->index] ?? ($loop->index + 1) }}
                        </span>
                        {{ $opcao->texto }}
                    </span>
                </button>
                @endforeach
            </div>

            <div id="loading" class="hidden text-center py-4 text-sm" style="color:#0084AA">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Salvando...
            </div>
        </div>
        @endif

        {{-- Dados ocultos para JS --}}
        <script>
            const SLUG  = @json($questionario->slug);
            const TOKEN = @json($token);
            const CSRF  = @json(csrf_token());
            const CAT_LABELS = @json($catLabels);
            const TOTAL = {{ $totalPerguntas }};
        </script>
        @endif

        <div class="flex items-center justify-center gap-2 mt-4">
            <img src="{{ asset('images/torresemfundo.png') }}" alt="WR" class="h-5 w-5 object-contain opacity-40">
            <p class="text-xs text-white/40">WR Assessoria — Diagnóstico IDE®</p>
        </div>
    </div>

<script>
const SLUG_PUBLIC = @json($questionario->slug);
const CSRF_TOKEN  = @json(csrf_token());

async function iniciar() {
    const nome = document.getElementById('id-nome')?.value?.trim();
    if (!nome) { Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe seu nome para continuar.', confirmButtonColor: '#0084AA' }); return; }

    const telefone = document.getElementById('id-telefone')?.value?.trim();
    if (!telefone) { Swal.fire({ icon: 'warning', title: 'Atenção', text: 'Informe seu telefone para continuar.', confirmButtonColor: '#0084AA' }); return; }

    const btn = document.getElementById('btn-iniciar');
    btn.disabled = true;
    btn.textContent = 'Aguarde...';

    const fatRaw = document.getElementById('id-faturamento')?.value ?? '';
    const fat    = fatRaw.replace(/[^\d,]/g,'').replace(',','.');

    try {
        const res = await fetch(`/q/${SLUG_PUBLIC}/iniciar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify({
                nome:          nome,
                email:         document.getElementById('id-email')?.value || null,
                telefone:      telefone,
                empresa:       document.getElementById('id-empresa')?.value || null,
                segmento:      document.getElementById('id-segmento')?.value || null,
                faturamento:   fat || null,
                colaboradores: document.getElementById('id-colaboradores')?.value || null,
            }),
        });
        const data = await res.json();
        if (data.token) {
            window.location.href = `/q/${SLUG_PUBLIC}?token=${data.token}`;
        }
    } catch {
        btn.disabled = false;
        btn.textContent = 'Iniciar diagnóstico →';
        Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro ao iniciar. Tente novamente.', confirmButtonColor: '#0084AA' });
    }
}

@if($token && $resposta && !$finalizado)
async function responder(perguntaId, opcaoId, el) {
    document.querySelectorAll('.opcao-btn').forEach(b => b.disabled = true);
    el.classList.add('selected');
    document.getElementById('loading').classList.remove('hidden');

    try {
        const res = await fetch(`/q/${SLUG}/responder`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ token: TOKEN, pergunta_id: perguntaId, opcao_id: opcaoId }),
        });
        const data = await res.json();

        if (data.finalizado) {
            window.location.href = `/q/${SLUG}?token=${TOKEN}`;
            return;
        }

        const pct = Math.round((data.respondidas / data.total) * 100);
        document.getElementById('barra-progresso').style.width = pct + '%';
        document.getElementById('progresso-texto').textContent = data.respondidas + '/' + data.total;

        const card = document.getElementById('card-pergunta');
        card.style.opacity = '0';
        card.style.transform = 'translateY(12px)';

        setTimeout(() => {
            const p = data.proxima;
            document.getElementById('texto-pergunta').textContent = p.texto;
            document.getElementById('tag-categoria').textContent = CAT_LABELS[p.categoria] ?? p.categoria;
            document.getElementById('tag-ordem').textContent = `Pergunta ${p.ordem} de ${TOTAL}`;

            const cont = document.getElementById('opcoes-container');
            const letras = ['A','B','C'];
            cont.innerHTML = p.opcoes.map((o, i) => `
                <button class="opcao-btn w-full text-left px-5 py-4 border-2 rounded-xl text-sm font-medium"
                        onclick="responder(${p.id}, ${o.id}, this)">
                    <span class="inline-flex items-center gap-3">
                        <span class="letra-opcao w-6 h-6 rounded-full border-2 inline-flex items-center justify-center text-xs font-bold shrink-0 transition-all">
                            ${letras[i] ?? (i+1)}
                        </span>
                        ${o.texto}
                    </span>
                </button>
            `).join('');

            document.getElementById('loading').classList.add('hidden');
            card.style.transition = 'opacity .3s ease, transform .3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 280);

    } catch {
        document.querySelectorAll('.opcao-btn').forEach(b => b.disabled = false);
        el.classList.remove('selected');
        document.getElementById('loading').classList.add('hidden');
        Swal.fire({ icon: 'error', title: 'Erro', text: 'Erro ao salvar. Tente novamente.', confirmButtonColor: '#0084AA' });
    }
}
@endif
</script>
</body>
</html>
