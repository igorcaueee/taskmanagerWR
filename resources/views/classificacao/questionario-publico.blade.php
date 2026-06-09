<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $questionario->titulo }}</title>
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f0f4f8; }
        .card-pergunta {
            animation: fadeSlide .35s ease;
        }
        @keyframes fadeSlide {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .opcao-btn {
            transition: all .15s ease;
            cursor: pointer;
        }
        .opcao-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,.12);
        }
        .opcao-btn.selected {
            border-color: #2563eb;
            background: #eff6ff;
        }
    </style>
</head>
<body class="min-h-full flex flex-col items-center justify-center px-4 py-10">

    <div class="w-full max-w-xl">

        {{-- Logo / cabeçalho --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-blue-600 text-white text-2xl mb-3">
                <i class="fa-solid fa-clipboard-question"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-800">{{ $questionario->titulo }}</h1>
            @if($questionario->descricao)
            <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">{{ $questionario->descricao }}</p>
            @endif
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
        <div id="tela-identificacao" class="bg-white rounded-2xl shadow-lg p-8 card-pergunta">
            <h2 class="text-lg font-semibold text-gray-800 mb-1">Antes de começar</h2>
            <p class="text-sm text-gray-500 mb-5">Preencha os dados abaixo para iniciar o diagnóstico.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Seu nome <span class="text-red-500">*</span></label>
                    <input id="id-nome" type="text" placeholder="Nome completo"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <input id="id-email" type="email" placeholder="seu@email.com"
                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
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
                    class="mt-6 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl text-sm border-0 cursor-pointer transition-colors">
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

        {{-- Barra de progresso --}}
        <div class="mb-4">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span>Progresso</span>
                <span>{{ $progresso }}/{{ $totalPerguntas }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="barra-progresso" class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $pct }}%"></div>
            </div>
        </div>

        {{-- Card da pergunta atual --}}
        @if($proximaPergunta)
        <div id="card-pergunta" class="bg-white rounded-2xl shadow-lg p-8 card-pergunta">
            <div class="flex items-center gap-2 mb-4">
                <span id="tag-categoria" class="text-xs px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full font-medium">
                    {{ $catLabels[$proximaPergunta->categoria] ?? $proximaPergunta->categoria }}
                </span>
                <span id="tag-ordem" class="text-xs text-gray-400">Pergunta {{ $proximaPergunta->ordem }} de {{ $totalPerguntas }}</span>
            </div>

            <p id="texto-pergunta" class="text-lg font-semibold text-gray-800 mb-6 leading-snug">
                {{ $proximaPergunta->texto }}
            </p>

            <div id="opcoes-container" class="space-y-3">
                @foreach($proximaPergunta->opcoes as $opcao)
                <button class="opcao-btn w-full text-left px-5 py-4 border-2 border-gray-200 rounded-xl text-sm text-gray-700 font-medium bg-white"
                        data-pergunta="{{ $proximaPergunta->id }}"
                        data-opcao="{{ $opcao->id }}"
                        onclick="responder({{ $proximaPergunta->id }}, {{ $opcao->id }}, this)">
                    <span class="inline-flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full border-2 border-gray-300 inline-flex items-center justify-center text-xs font-bold shrink-0 check-icon">
                            {{ ['A','B','C'][$loop->index] ?? ($loop->index + 1) }}
                        </span>
                        {{ $opcao->texto }}
                    </span>
                </button>
                @endforeach
            </div>

            <div id="loading" class="hidden text-center py-4 text-gray-400 text-sm">
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

        <p class="text-center text-xs text-gray-400 mt-6">Diagnóstico empresarial — IDE®</p>
    </div>

<script>
const SLUG_PUBLIC = @json($questionario->slug);
const CSRF_TOKEN  = @json(csrf_token());

async function iniciar() {
    const nome = document.getElementById('id-nome')?.value?.trim();
    if (!nome) { alert('Informe seu nome para continuar.'); return; }

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
        alert('Erro ao iniciar. Tente novamente.');
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
                <button class="opcao-btn w-full text-left px-5 py-4 border-2 border-gray-200 rounded-xl text-sm text-gray-700 font-medium bg-white"
                        onclick="responder(${p.id}, ${o.id}, this)">
                    <span class="inline-flex items-center gap-3">
                        <span class="w-6 h-6 rounded-full border-2 border-gray-300 inline-flex items-center justify-center text-xs font-bold shrink-0">
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
        alert('Erro ao salvar. Tente novamente.');
    }
}
@endif
</script>
</body>
</html>
