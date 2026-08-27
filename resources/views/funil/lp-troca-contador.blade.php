<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Troque de contador sem dor de cabeça. A WR Assessoria cuida de toda a transição — contabilidade, fiscal e folha — com mais de 17 anos de experiência e 500 clientes atendidos.">
    <meta name="robots" content="noindex, nofollow">
    <title>Troque de contador sem dor de cabeça — WR Assessoria</title>
    @include('partials.head')

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        html { scroll-behavior: smooth; }
        body { background:#000; color:#f3f4f6; }

        /* ── Reveal animation ── */
        .reveal { opacity:0; transform:translateY(40px); transition:opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1); }
        .reveal.visible { opacity:1; transform:translateY(0); }
        .reveal-left  { opacity:0; transform:translateX(-50px); transition:opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1); }
        .reveal-right { opacity:0; transform:translateX(50px);  transition:opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1); }
        .reveal-left.visible, .reveal-right.visible { opacity:1; transform:translateX(0); }
        .reveal-delay-1 { transition-delay:.1s; }
        .reveal-delay-2 { transition-delay:.2s; }
        .reveal-delay-3 { transition-delay:.3s; }
        .reveal-delay-4 { transition-delay:.4s; }

        .blue-line { height:2px; background:linear-gradient(90deg, transparent, #0084aa, transparent); border:none; }

        /* ── Hero ── */
        #lp-hero { position:relative; overflow:hidden; background:#000; }
        #lp-hero .glow { position:absolute; inset:0; pointer-events:none; background:radial-gradient(ellipse at 75% 30%, rgba(0,132,170,.20) 0%, transparent 62%); }
        #lp-hero .dots { position:absolute; inset:0; pointer-events:none; background-image:radial-gradient(rgba(0,132,170,.16) 1px, transparent 1px); background-size:36px 36px; }

        /* ── Cards ── */
        .lp-card {
            position:relative; background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.08);
            border-radius:16px; padding:1.75rem; transition:border-color .4s, transform .4s, background .4s;
        }
        .lp-card:hover { border-color:rgba(0,132,170,.4); transform:translateY(-4px); background:rgba(0,132,170,.05); }
        .lp-card .icon-wrap {
            width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center;
            background:rgba(0,132,170,.12); border:1px solid rgba(0,132,170,.25); margin-bottom:1.1rem;
        }

        .step-num {
            width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center;
            background:#0084aa; color:#fff; font-weight:700; flex-shrink:0;
        }

        .stat-card {
            padding:2rem 1.5rem; border:1px solid rgba(255,255,255,.07); border-radius:16px;
            background:rgba(255,255,255,.02); transition:border-color .3s, background .3s;
        }
        .stat-card:hover { border-color:rgba(0,132,170,.3); background:rgba(0,132,170,.04); }
        .counter { font-variant-numeric:tabular-nums; }

        /* ── FAQ ── */
        .faq-item { border:1px solid rgba(255,255,255,.08); border-radius:14px; background:rgba(255,255,255,.02); overflow:hidden; }
        .faq-q { width:100%; display:flex; align-items:center; justify-content:space-between; gap:1rem;
            padding:1.15rem 1.35rem; background:transparent; border:none; color:#fff; font-weight:600; font-size:.95rem;
            text-align:left; cursor:pointer; appearance:none; }
        .faq-a { max-height:0; overflow:hidden; transition:max-height .35s ease; color:rgba(255,255,255,.55); font-size:.9rem; line-height:1.6; }
        .faq-a > div { padding:0 1.35rem 1.25rem; }
        .faq-item.open .faq-a { max-height:320px; }
        .faq-item.open .faq-q i { transform:rotate(180deg); }
        .faq-q i { transition:transform .3s; color:#0084aa; }

        /* ── Glass form ── */
        .glass-form { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1); backdrop-filter:blur(12px); border-radius:20px; padding:2.25rem; }
        .glass-form input, .glass-form textarea {
            background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.12); color:#fff; border-radius:10px;
            padding:.85rem 1.1rem; width:100%; font-size:.9rem; transition:border-color .3s, box-shadow .3s; outline:none;
        }
        .glass-form input::placeholder, .glass-form textarea::placeholder { color:rgba(255,255,255,.3); }
        .glass-form input:focus, .glass-form textarea:focus { border-color:rgba(0,132,170,.6); box-shadow:0 0 0 3px rgba(0,132,170,.15); }
        .glass-form label {
            color:rgba(255,255,255,.55); font-size:.72rem; font-weight:600; letter-spacing:.08em;
            text-transform:uppercase; display:block; margin-bottom:.4rem;
        }
        .glass-form .field-error { color:#f87171; font-size:.75rem; margin-top:.3rem; display:block; }

        .lp-cta {
            display:inline-flex; align-items:center; gap:.75rem; background:#0084aa; color:#fff; font-weight:700;
            padding:1rem 2rem; border-radius:12px; text-decoration:none; transition:all .3s;
        }
        .lp-cta:hover { box-shadow:0 0 40px rgba(0,132,170,.5); transform:scale(1.02); color:#fff; }
    </style>
</head>
<body class="antialiased">

{{-- ═══════════ HEADER ═══════════ --}}
<header class="fixed top-0 inset-x-0 z-50" style="background:rgba(0,0,0,.85); backdrop-filter:blur(14px); border-bottom:1px solid rgba(255,255,255,.07);">
    <div class="max-w-6xl mx-auto px-5 h-[64px] flex items-center justify-between">
        <a href="/" class="flex items-center gap-3" style="text-decoration:none;">
            <img src="/images/torresemfundo.png" alt="WR Assessoria" class="w-8 h-8 object-contain">
            <div class="leading-tight">
                <p class="text-white text-[14px] leading-none font-semibold" style="margin:0;"><b>WR</b> Assessoria</p>
                <p class="text-[9px] font-semibold tracking-[.14em] uppercase leading-none mt-1" style="color:#0084aa; margin:0;">Contabilidade &amp; Gestão Empresarial</p>
            </div>
        </a>
        <a href="#form" class="hidden sm:inline-flex items-center gap-2 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all duration-300 hover:scale-[1.02] no-underline" style="background:#0084aa;">
            <i class="fas fa-right-left text-xs"></i> Quero migrar
        </a>
    </div>
</header>

{{-- ═══════════ HERO ═══════════ --}}
<section id="lp-hero" class="pt-32 pb-20 px-5">
    <div class="glow"></div>
    <div class="dots"></div>
    <div class="relative z-10 max-w-3xl mx-auto text-center">
        <span class="text-xs font-semibold tracking-[.2em] uppercase reveal" style="color:#0084aa;">Troca de contabilidade</span>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mt-4 mb-6 leading-[1.08] reveal reveal-delay-1">
            Troque de contador<br><span style="color:#0084aa;">sem dor de cabeça</span>
        </h1>
        <p class="text-lg text-white/55 max-w-xl mx-auto leading-relaxed mb-9 reveal reveal-delay-2">
            Cansado de atraso, imposto pago a mais e contador que não atende? A WR cuida de toda a transição da sua contabilidade — você não levanta um dedo.
        </p>
        <div class="reveal reveal-delay-3">
            <a href="#form" class="lp-cta">Quero uma análise gratuita <i class="fas fa-arrow-right text-sm"></i></a>
        </div>

        <div class="grid grid-cols-3 gap-4 max-w-lg mx-auto mt-14 reveal reveal-delay-4">
            <div><p class="text-3xl font-bold text-white"><span class="counter" data-target="500">0</span>+</p><p class="text-white/40 text-xs mt-1">Clientes atendidos</p></div>
            <div><p class="text-3xl font-bold text-white"><span class="counter" data-target="17">0</span>+</p><p class="text-white/40 text-xs mt-1">Anos de experiência</p></div>
            <div><p class="text-3xl font-bold text-white"><span class="counter" data-target="73">0</span></p><p class="text-white/40 text-xs mt-1">Cidades atendidas</p></div>
        </div>
    </div>
</section>

{{-- ═══════════ DORES ═══════════ --}}
<section class="py-24 px-5" style="background:#080808; border-top:1px solid rgba(255,255,255,.05);">
    <div class="max-w-6xl mx-auto">
        <div class="mb-14 reveal">
            <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Você se identifica?</span>
            <h2 class="text-3xl md:text-4xl font-bold text-white mt-3 mb-4 leading-tight">Sinais de que está na hora de trocar</h2>
            <div class="blue-line w-16"></div>
        </div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @php
            $dores = [
                ['fa-clock', 'Balancetes e relatórios sempre atrasados', 'Você pede uma informação e demora dias — ou semanas — pra receber.'],
                ['fa-coins', 'Desconfiança de que paga imposto a mais', 'Ninguém nunca revisou seu regime tributário ou buscou créditos a recuperar.'],
                ['fa-comment-slash', 'Contador que não retorna', 'Ligações e mensagens sem resposta quando você mais precisa.'],
                ['fa-triangle-exclamation', 'Sustos com multas e obrigações', 'Você só descobre o problema quando a multa já chegou.'],
                ['fa-file-circle-question', 'Nada é explicado', 'Recebe as guias pra pagar, mas não entende o que está acontecendo na empresa.'],
                ['fa-user-clock', 'Atendimento sem prioridade', 'Sente que é só mais um número na carteira do escritório.'],
            ];
            @endphp
            @foreach($dores as $i => $d)
                <div class="lp-card reveal reveal-delay-{{ min($i + 1, 4) }}">
                    <div class="icon-wrap"><i class="fas {{ $d[0] }} text-brand"></i></div>
                    <h3 class="text-white font-semibold mb-2">{{ $d[1] }}</h3>
                    <p class="text-white/45 text-sm leading-relaxed">{{ $d[2] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ COMO FUNCIONA ═══════════ --}}
<section class="py-24 px-5" style="background:#050505; border-top:1px solid rgba(255,255,255,.05);">
    <div class="max-w-4xl mx-auto">
        <div class="mb-14 reveal">
            <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Simples e sem esforço</span>
            <h2 class="text-3xl md:text-4xl font-bold text-white mt-3 mb-4 leading-tight">Como funciona a migração</h2>
            <div class="blue-line w-16"></div>
        </div>
        <div class="space-y-5">
            @php
            $passos = [
                ['Análise gratuita', 'Você preenche o formulário e conversamos sobre a sua empresa, o regime atual e o que está incomodando. Sem compromisso.'],
                ['Nós assumimos a transição', 'A WR solicita toda a documentação ao seu contador atual, revisa pendências e organiza a mudança. Você só assina os documentos.'],
                ['Acompanhamento de verdade', 'Contador responsável, prazos cumpridos e revisão do seu regime tributário para você parar de pagar imposto a mais.'],
            ];
            @endphp
            @foreach($passos as $i => $p)
                <div class="lp-card flex items-start gap-5 reveal reveal-delay-{{ $i + 1 }}">
                    <div class="step-num">{{ $i + 1 }}</div>
                    <div>
                        <h3 class="text-white font-semibold mb-1">{{ $p[0] }}</h3>
                        <p class="text-white/45 text-sm leading-relaxed">{{ $p[1] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-white/35 text-sm mt-8 text-center reveal">A troca de contabilidade é um direito seu e pode ser feita a qualquer momento do ano.</p>
    </div>
</section>

{{-- ═══════════ POR QUE A WR ═══════════ --}}
<section class="py-24 px-5" style="background:#080808; border-top:1px solid rgba(255,255,255,.05);">
    <div class="max-w-6xl mx-auto">
        <div class="grid md:grid-cols-2 gap-14 items-center">
            <div class="reveal reveal-left">
                <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Por que a WR</span>
                <h2 class="text-3xl md:text-4xl font-bold text-white mt-3 mb-4 leading-tight">Uma assessoria, não só um escritório</h2>
                <div class="blue-line w-16 mb-7"></div>
                <p class="text-white/50 mb-8 leading-relaxed">
                    Fundada em 2009, a WR Assessoria atende mais de 500 clientes dos setores industrial, comercial e de serviços em 73 cidades de 7 estados. Equipe multidisciplinar com formação em Ciências Contábeis, Direito, Análise de Sistemas e Recursos Humanos.
                </p>
                <ul class="space-y-3">
                    @foreach(['Contador responsável pela sua conta', 'Revisão tributária e recuperação de créditos', 'Portal do cliente e cofre fiscal digital', 'Atendimento próximo, sem robô no meio'] as $idx => $item)
                        <li class="flex items-center gap-3 reveal reveal-delay-{{ $idx + 1 }}">
                            <span class="shrink-0 w-6 h-6 rounded-lg flex items-center justify-center" style="background:rgba(0,132,170,.2); border:1px solid rgba(0,132,170,.4);">
                                <i class="fas fa-check text-brand text-[10px]"></i>
                            </span>
                            <span class="text-white/70 font-medium text-sm">{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="grid grid-cols-2 gap-4 reveal reveal-right">
                @php
                $stats = [
                    ['fa-trophy', 500, '+', 'Clientes atendidos'],
                    ['fa-clock', 17, '+', 'Anos de experiência'],
                    ['fa-map-marker-alt', 73, '', 'Cidades atendidas'],
                    ['fa-users', 25, '', 'Colaboradores'],
                ];
                @endphp
                @foreach($stats as $s)
                    <div class="stat-card">
                        <i class="fas {{ $s[0] }} text-brand mb-3 text-lg"></i>
                        <p class="text-3xl font-bold text-white"><span class="counter" data-target="{{ $s[1] }}">0</span>{{ $s[2] }}</p>
                        <p class="text-white/40 text-sm mt-1">{{ $s[3] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ═══════════ FAQ ═══════════ --}}
<section class="py-24 px-5" style="background:#050505; border-top:1px solid rgba(255,255,255,.05);">
    <div class="max-w-3xl mx-auto">
        <div class="mb-12 reveal">
            <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Dúvidas comuns</span>
            <h2 class="text-3xl md:text-4xl font-bold text-white mt-3 mb-4 leading-tight">Antes de trocar, você deve estar pensando...</h2>
            <div class="blue-line w-16"></div>
        </div>
        <div class="space-y-3">
            @php
            $faqs = [
                ['Dá trabalho pra mim trocar de contador?', 'Não. A WR cuida de todo o processo: solicita a documentação ao contador atual, revisa o que está pendente e organiza a migração. Da sua parte, só a assinatura de alguns documentos.'],
                ['Posso trocar em qualquer época do ano?', 'Sim. Não existe obrigação de esperar o fim do ano ou o fechamento do exercício. A transição pode começar assim que você decidir.'],
                ['Vou ter problema com o meu contador atual?', 'O contador é obrigado a repassar a documentação contábil e fiscal da sua empresa. Conduzimos essa comunicação de forma profissional pra que a transição seja tranquila.'],
                ['Minha empresa é pequena, vale a pena?', 'Sim. Atendemos de MEI e Simples Nacional a Lucro Real. Muitas vezes é justamente na empresa menor que encontramos imposto pago a mais e obrigações sendo ignoradas.'],
                ['Quanto custa a mensalidade?', 'Depende do porte, do regime tributário e do volume de notas e funcionários. Na análise gratuita levantamos esses dados e enviamos uma proposta sem compromisso.'],
            ];
            @endphp
            @foreach($faqs as $f)
                <div class="faq-item reveal">
                    <button type="button" class="faq-q">{{ $f[0] }} <i class="fas fa-chevron-down text-xs"></i></button>
                    <div class="faq-a"><div>{{ $f[1] }}</div></div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════ FORM ═══════════ --}}
<section id="form" class="py-24 px-5" style="background:#080808; border-top:1px solid rgba(255,255,255,.05);">
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-10 reveal">
            <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Análise gratuita</span>
            <h2 class="text-3xl md:text-4xl font-bold text-white mt-3 mb-4 leading-tight">Peça sua análise sem compromisso</h2>
            <p class="text-white/50 leading-relaxed">Preencha os dados abaixo. Um especialista da WR entra em contato para entender sua empresa e mostrar como fica a troca.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 px-5 py-4 rounded-2xl flex items-start gap-3" style="background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.3);">
                <i class="fas fa-circle-check mt-0.5 flex-shrink-0" style="color:#10b981;"></i>
                <span class="text-white/80 text-sm">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="mb-6 px-5 py-4 rounded-2xl flex items-start gap-3" style="background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.3);">
                <i class="fas fa-circle-exclamation mt-0.5 flex-shrink-0" style="color:#ef4444;"></i>
                <div class="text-white/80 text-sm">
                    @if(session('error'))
                        {{ session('error') }}
                    @else
                        <ul class="space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('funil.captura.store') }}" class="glass-form reveal">
            @csrf
            <input type="hidden" name="origem" value="lp-troca-contador">

            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label>Nome completo *</label>
                    <input name="nome" type="text" value="{{ old('nome') }}" placeholder="Seu nome completo" required>
                    @error('nome')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label>E-mail</label>
                    <input name="email" type="email" value="{{ old('email') }}" placeholder="seu@email.com">
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="grid sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label>Telefone / WhatsApp</label>
                    <input name="telefone" type="text" value="{{ old('telefone') }}" placeholder="(00) 00000-0000">
                    @error('telefone')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label>Empresa</label>
                    <input name="empresa" type="text" value="{{ old('empresa') }}" placeholder="Nome da sua empresa">
                    @error('empresa')<span class="field-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="mb-6">
                <label>O que mais te incomoda hoje?</label>
                <textarea name="mensagem" rows="4" placeholder="Ex: regime tributário, atraso nos relatórios, atendimento, folha de pagamento..." style="resize:none;">{{ old('mensagem') }}</textarea>
                @error('mensagem')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <div class="mb-6">
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}" data-theme="dark"></div>
                @error('g-recaptcha-response')<span class="field-error">{{ $message }}</span>@enderror
            </div>

            <button type="submit"
                    class="w-full flex items-center justify-center gap-3 text-white py-4 rounded-xl font-bold transition-all duration-300 hover:shadow-[0_0_40px_rgba(0,132,170,.5)] hover:scale-[1.01]"
                    style="background:#0084aa; border:none;">
                <i class="fas fa-paper-plane"></i>
                Quero minha análise gratuita
            </button>
            <p class="text-white/30 text-xs text-center mt-4">Seus dados são usados apenas para o contato comercial da WR Assessoria.</p>
        </form>
    </div>
</section>

{{-- ═══════════ FOOTER ═══════════ --}}
<footer class="py-12 px-5" style="background:#000; border-top:1px solid rgba(255,255,255,.07);">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
        <div>
            <p class="text-white text-sm font-semibold">WR Assessoria — Contabilidade &amp; Gestão Empresarial</p>
            <p class="text-white/40 text-xs mt-1">R. Carlos Arnt, 2215 — Sala 201, Canabarro, Teutônia — RS, 95890-000</p>
        </div>
        <a href="tel:+555137628117" class="text-white/60 text-sm hover:text-brand transition-colors no-underline">
            <i class="fas fa-phone text-xs mr-2"></i>(51) 3762-8117
        </a>
    </div>
</footer>

{{-- WhatsApp float --}}
<a href="https://wa.me/555137628117?text=Ol%C3%A1%2C%20quero%20trocar%20de%20contador%20e%20conhecer%20a%20WR%20Assessoria" target="_blank" rel="noopener noreferrer"
   title="Fale pelo WhatsApp"
   style="position:fixed; bottom:1.75rem; right:1.75rem; z-index:9999; width:56px; height:56px; border-radius:50%; background:#25D366; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 24px rgba(37,211,102,.5); transition:transform .25s; text-decoration:none;"
   onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
    <i class="fab fa-whatsapp" style="color:#fff; font-size:1.6rem;"></i>
</a>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
(function () {
    const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.12 });
    revealEls.forEach(el => obs.observe(el));

    const counters = document.querySelectorAll('.counter');
    const cObs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (!e.isIntersecting) { return; }
            const el = e.target;
            const target = parseInt(el.dataset.target, 10);
            const step = Math.max(1, Math.floor(target / 90));
            let current = 0;
            const tick = () => {
                current = Math.min(current + step, target);
                el.textContent = current;
                if (current < target) { requestAnimationFrame(tick); }
            };
            requestAnimationFrame(tick);
            cObs.unobserve(el);
        });
    }, { threshold: 0.5 });
    counters.forEach(c => cObs.observe(c));

    document.querySelectorAll('.faq-q').forEach(btn => {
        btn.addEventListener('click', () => btn.closest('.faq-item').classList.toggle('open'));
    });

    @if(session('success') || $errors->any())
        document.getElementById('form').scrollIntoView();
    @endif
}());
</script>
</body>
</html>
