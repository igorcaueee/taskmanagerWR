@extends('layouts.app')

@section('title', 'WR Assessoria - Contabilidade Profissional')

@push('styles')
<style>
    html { scroll-behavior: smooth; }

    /* ── Reveal animation ── */
    .reveal {
        opacity: 0;
        transform: translateY(40px);
        transition: opacity 0.8s cubic-bezier(.16,1,.3,1), transform 0.8s cubic-bezier(.16,1,.3,1);
    }
    .reveal.visible { opacity: 1; transform: translateY(0); }
    .reveal-left  { opacity:0; transform:translateX(-50px); transition: opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1); }
    .reveal-right { opacity:0; transform:translateX(50px);  transition: opacity .8s cubic-bezier(.16,1,.3,1), transform .8s cubic-bezier(.16,1,.3,1); }
    .reveal-left.visible, .reveal-right.visible { opacity:1; transform:translateX(0); }
    .reveal-delay-1 { transition-delay: .1s; }
    .reveal-delay-2 { transition-delay: .2s; }
    .reveal-delay-3 { transition-delay: .3s; }
    .reveal-delay-4 { transition-delay: .4s; }
    .reveal-delay-5 { transition-delay: .5s; }

    /* ── Hero ── */
    #hero {
        min-height: 100vh;
        position: relative;
        display: flex;
        align-items: center;
        overflow: hidden;
        background: #000;
    }
    #hero-bg {
        position: absolute; inset: 0;
        background-image: url('/images/fachada.webp');
        background-size: cover;
        background-position: center 30%;
        transform: scale(1.05);
        transition: transform 12s ease-out;
        filter: brightness(.55) saturate(0.7);
    }
    #hero-bg.loaded { transform: scale(1); }
    #hero-gradient {
        position: absolute; inset: 0;
        background: linear-gradient(
            to bottom,
            rgba(0,0,0,0.15) 0%,
            rgba(0,0,0,0.25) 40%,
            rgba(0,0,0,0.85) 75%,
            rgba(0,0,0,1)    100%
        );
    }
    /* Blue side glow */
    #hero-glow {
        position: absolute; top: 0; right: 0;
        width: 55%; height: 100%;
        background: radial-gradient(ellipse at 80% 30%, rgba(0,132,170,.22) 0%, transparent 65%);
        pointer-events: none;
    }
    /* Dot grid overlay */
    #hero-dots {
        position: absolute; inset: 0;
        background-image: radial-gradient(rgba(0,132,170,.18) 1px, transparent 1px);
        background-size: 36px 36px;
        pointer-events: none;
    }
    /* Scroll indicator */
    @keyframes scrollBounce {
        0%,100% { transform: translateY(0); }
        50%      { transform: translateY(8px); }
    }
    .scroll-indicator { animation: scrollBounce 2s ease-in-out infinite; }

    /* ── Glowing line accent ── */
    .blue-line {
        height: 2px;
        background: linear-gradient(90deg, transparent, #0084aa, transparent);
        border: none;
    }

    /* ── Service cards ── */
    .service-card {
        position: relative;
        background: rgba(255,255,255,.03);
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 16px;
        padding: 2rem;
        transition: border-color .4s, transform .4s, box-shadow .4s, background .4s;
        overflow: hidden;
    }
    .service-card::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(circle at 50% 0%, rgba(0,132,170,.12) 0%, transparent 70%);
        opacity: 0;
        transition: opacity .4s;
    }
    .service-card:hover {
        border-color: rgba(0,132,170,.5);
        transform: translateY(-6px);
        box-shadow: 0 20px 60px rgba(0,132,170,.15), 0 0 0 1px rgba(0,132,170,.2);
        background: rgba(0,132,170,.05);
    }
    .service-card:hover::before { opacity: 1; }
    .service-card .icon-wrap {
        width: 48px; height: 48px;
        background: rgba(0,132,170,.12);
        border: 1px solid rgba(0,132,170,.25);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        margin-bottom: 1.5rem;
        transition: background .3s, border-color .3s, box-shadow .3s;
    }
    .service-card:hover .icon-wrap {
        background: rgba(0,132,170,.25);
        border-color: rgba(0,132,170,.5);
        box-shadow: 0 0 20px rgba(0,132,170,.3);
    }

    /* ── Stats ── */
    .stat-card {
        position: relative;
        padding: 2.5rem 2rem;
        border: 1px solid rgba(255,255,255,.07);
        border-radius: 16px;
        background: rgba(255,255,255,.02);
        overflow: hidden;
        transition: border-color .3s, background .3s;
    }
    .stat-card::after {
        content: '';
        position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, transparent, #0084aa, transparent);
        transform: scaleX(0);
        transition: transform .5s;
    }
    .stat-card:hover { border-color: rgba(0,132,170,.3); background: rgba(0,132,170,.04); }
    .stat-card:hover::after { transform: scaleX(1); }

    /* ── Contact form ── */
    .glass-form {
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.1);
        backdrop-filter: blur(12px);
        border-radius: 20px;
        padding: 2.5rem;
    }
    .glass-form input,
    .glass-form textarea {
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.12);
        color: #fff;
        border-radius: 10px;
        padding: .85rem 1.1rem;
        width: 100%;
        font-size: .9rem;
        transition: border-color .3s, box-shadow .3s;
        outline: none;
    }
    .glass-form input::placeholder,
    .glass-form textarea::placeholder { color: rgba(255,255,255,.3); }
    .glass-form input:focus,
    .glass-form textarea:focus {
        border-color: rgba(0,132,170,.6);
        box-shadow: 0 0 0 3px rgba(0,132,170,.15);
    }
    .glass-form label { color: rgba(255,255,255,.55); font-size: .72rem; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; display: block; margin-bottom: .4rem; }

    /* ── CTA parallax band ── */
    .cta-band {
        position: relative;
        overflow: hidden;
        background: #000;
    }
    .cta-band::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(0,132,170,.35) 0%, transparent 60%);
    }
    .cta-band::after {
        content: '';
        position: absolute; inset: 0;
        background-image: radial-gradient(rgba(0,132,170,.1) 1px, transparent 1px);
        background-size: 28px 28px;
    }

    /* ── Pulse badge ── */
    @keyframes pulse-ring {
        0%   { box-shadow: 0 0 0 0 rgba(0,132,170,.5); }
        70%  { box-shadow: 0 0 0 10px rgba(0,132,170,0); }
        100% { box-shadow: 0 0 0 0 rgba(0,132,170,0); }
    }
    .pulse-badge { animation: pulse-ring 2.5s ease-out infinite; }

    /* ── Typing cursor ── */
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
    .cursor { display:inline-block; width:3px; height:.9em; background:#0084aa; margin-left:4px; vertical-align:middle; animation: blink 1s step-start infinite; border-radius:2px; }

    /* ── About section image ── */
    .about-img-wrap {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        border: 1px solid rgba(0,132,170,.25);
        box-shadow: 0 30px 80px rgba(0,132,170,.12), 0 0 0 1px rgba(0,132,170,.1);
    }
    .about-img-wrap::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(to bottom, transparent 50%, rgba(0,0,0,.7) 100%);
        z-index: 1;
    }
    .about-img-wrap img { width:100%; height:420px; object-fit:cover; object-position: center; display:block; }
    .about-img-badge {
        position: absolute; bottom: 1.5rem; left: 1.5rem; z-index: 2;
        background: rgba(0,0,0,.7);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(0,132,170,.3);
        border-radius: 12px;
        padding: .75rem 1.25rem;
        display: flex; align-items: center; gap:.75rem;
    }

    /* ── Number counter ── */
    .counter { font-variant-numeric: tabular-nums; }
</style>
@endpush

@section('content')

{{-- ═══════════════════════════════════════════════════════════
     HERO
═══════════════════════════════════════════════════════════ --}}
<section id="hero">
    <div id="hero-bg"></div>
    <div id="hero-gradient"></div>
    <div id="hero-glow"></div>
    <div id="hero-dots"></div>

    <div class="relative z-10 w-full max-w-7xl mx-auto px-6 lg:px-8 py-32">
        <div class="max-w-3xl">

            <!-- Headline -->
            <h1 class="text-5xl sm:text-6xl lg:text-7xl font-bold text-white leading-[1.05] mb-6 reveal reveal-delay-1">
                Expertise que<br>
                <span style="color:#0084aa;">transforma</span><br>
                seu negócio
                <span class="cursor"></span>
            </h1>

            <p class="text-lg text-white/55 max-w-xl leading-relaxed mb-10 reveal reveal-delay-2">
                Desde 2009 transformando números em lucro. Soluções contábeis e assessoria empresarial para mais de 500 clientes em 56 cidades de 4 estados do Brasil.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 reveal reveal-delay-3">
                <a href="#contact"
                   class="group inline-flex items-center gap-3 bg-brand text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 hover:shadow-[0_0_40px_rgba(0,132,170,.5)] hover:scale-[1.02] no-underline">
                    Quero ser cliente
                    <i class="fas fa-arrow-right text-sm transition-transform group-hover:translate-x-1"></i>
                </a>
                <a href="#services"
                   class="inline-flex items-center gap-3 border border-white/20 text-white/80 px-8 py-4 rounded-xl font-semibold transition-all duration-300 hover:border-brand/60 hover:text-white hover:bg-white/5 no-underline">
                    Nossos Serviços
                </a>
            </div>
        </div>

        <!-- Floating stat pills -->
        <div class="hidden lg:flex absolute right-8 bottom-20 flex-col gap-3 reveal reveal-delay-4">
            <div class="flex items-center gap-3 bg-black/50 backdrop-blur-sm border border-white/10 rounded-xl px-5 py-3">
                <span class="text-brand font-bold text-2xl">500<span class="text-lg">+</span></span>
                <span class="text-white/50 text-sm leading-tight">Clientes<br>atendidos</span>
            </div>
            <div class="flex items-center gap-3 bg-black/50 backdrop-blur-sm border border-white/10 rounded-xl px-5 py-3">
                <span class="text-brand font-bold text-2xl">17<span class="text-lg">+</span></span>
                <span class="text-white/50 text-sm leading-tight">Anos de<br>experiência</span>
            </div>
            <div class="flex items-center gap-3 bg-black/50 backdrop-blur-sm border border-brand/30 rounded-xl px-5 py-3">
                <span class="text-brand font-bold text-2xl">56</span>
                <span class="text-white/50 text-sm leading-tight">Cidades<br>atendidas</span>
            </div>
        </div>

        <!-- Scroll indicator -->
        <div class="scroll-indicator absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 text-white/30">
            <span class="text-[10px] tracking-[.2em] uppercase">Scroll</span>
            <i class="fas fa-chevron-down text-xs"></i>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     SERVICES
═══════════════════════════════════════════════════════════ --}}
<section id="services" class="py-28 px-4" style="background:#080808;">
    <div class="max-w-7xl mx-auto">

        <div class="mb-16 reveal">
            <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Soluções WR</span>
            <h2 class="text-4xl md:text-5xl font-bold text-white mt-3 mb-4 leading-tight">Nossas Soluções</h2>
            <div class="blue-line w-16"></div>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            @php
            $services = [
                ['icon' => 'fa-calculator',      'title' => 'WR Contabilidade',         'desc' => 'Gestão contábil eficiente para garantir conformidade fiscal e maximizar os resultados da sua empresa.'],
                ['icon' => 'fa-rotate-left',     'title' => 'WR Recupera',              'desc' => 'Programa de recuperação tributária para identificar créditos e maximizar o potencial financeiro do seu negócio.'],
                ['icon' => 'fa-compass',         'title' => 'WR Indicadores',           'desc' => 'Análise estratégica de indicadores financeiros para tomada de decisões mais assertivas e embasadas em dados.'],
                ['icon' => 'fa-shield-halved',   'title' => 'WR Holding',               'desc' => 'Planejamento patrimonial e societário para proteger e estruturar o patrimônio da sua família e empresa.'],
                ['icon' => 'fa-fingerprint',     'title' => 'WR Digital',               'desc' => 'Certificado digital, armazenamento fiscal e registro de marca com praticidade e segurança total.'],
                ['icon' => 'fa-trowel',          'title' => 'WR Sero',                  'desc' => 'Soluções completas para regularizar sua obra junto à Receita Federal com agilidade e segurança.'],
                ['icon' => 'fa-clipboard-check', 'title' => 'WR Assessment',            'desc' => 'Análise de perfil comportamental para contratações assertivas e elaboração do DNA organizacional da empresa.'],
            ];
            @endphp

            @foreach($services as $i => $s)
                <div class="service-card reveal reveal-delay-{{ min($i + 1, 5) }}">
                    <div class="icon-wrap">
                        <i class="fas {{ $s['icon'] }} text-brand"></i>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">{{ $s['title'] }}</h3>
                    <p class="text-white/45 text-sm leading-relaxed">{{ $s['desc'] }}</p>
                    <div class="mt-5 flex items-center gap-2 text-brand text-xs font-semibold opacity-0 group-hover:opacity-100 transition-opacity">
                        Saiba mais <i class="fas fa-arrow-right text-[10px]"></i>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     STATS
═══════════════════════════════════════════════════════════ --}}
<section id="stats" class="py-20 px-4" style="background:#050505; border-top: 1px solid rgba(255,255,255,.05);">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
            $stats = [
                ['value' => 500, 'suffix' => '+', 'label' => 'Clientes atendidos',     'icon' => 'fa-trophy'],
                ['value' => 17,  'suffix' => '+', 'label' => 'Anos de experiência',    'icon' => 'fa-clock'],
                ['value' => 56,  'suffix' => '',  'label' => 'Cidades atendidas',      'icon' => 'fa-map-marker-alt'],
                ['value' => 25,  'suffix' => '',  'label' => 'Colaboradores',          'icon' => 'fa-users'],
            ];
            @endphp
            @foreach($stats as $i => $stat)
                <div class="stat-card reveal reveal-delay-{{ $i + 1 }}">
                    <i class="fas {{ $stat['icon'] }} text-brand mb-4 text-lg"></i>
                    <p class="text-4xl font-bold text-white">
                        <span class="counter" data-target="{{ $stat['value'] }}">0</span>{{ $stat['suffix'] }}
                    </p>
                    <p class="text-white/40 text-sm mt-1">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     ABOUT
═══════════════════════════════════════════════════════════ --}}
<section id="about" class="py-28 px-4" style="background:#080808;">
    <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-2 gap-16 items-center">

            <!-- Image -->
            <div class="about-img-wrap reveal reveal-left">
                <img src="/images/globo.jpg" alt="Fachada WR Assessoria" onerror="this.parentElement.style.background='rgba(0,132,170,.05)'; this.style.display='none';">
                <div class="about-img-badge">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#0084aa;">
                        <i class="fas fa-building text-white text-xs"></i>
                    </div>
                    <div>
                        <p class="text-white text-xs font-semibold leading-none">Sede WR Assessoria</p>
                        <p class="text-white/40 text-[11px] mt-0.5">Teutônia — RS</p>
                    </div>
                </div>
            </div>

            <!-- Text -->
            <div class="reveal reveal-right">
                <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Quem somos</span>
                <h2 class="text-4xl md:text-5xl font-bold text-white mt-3 mb-4 leading-tight">Sobre a<br>WR Assessoria</h2>
                <div class="blue-line w-16 mb-8"></div>
                <p class="text-white/50 mb-4 leading-relaxed">
                    Fundada em 2009 como WR Contabilidade, em 2026 evoluímos para <strong class="text-white/80">WR Assessoria</strong> — acompanhando o crescimento dos nossos clientes e suas necessidades cada vez mais específicas.
                </p>
                <p class="text-white/50 mb-10 leading-relaxed">
                    Atendemos mais de 500 clientes dos setores industrial, comercial e de serviços, alcançando 56 cidades em 4 estados do Brasil. Nossa equipe é formada por 25 colaboradores com formação em Ciências Contábeis, Direito, Análise de Sistemas e Recursos Humanos.
                </p>
                <ul class="space-y-3">
                    @foreach(['Atendimento Personalizado e Exclusivo', '25 Especialistas Multidisciplinares', 'Presença em 4 Estados do Brasil', 'Soluções Integradas para o seu Negócio'] as $idx => $item)
                        <li class="flex items-center gap-3 reveal reveal-delay-{{ $idx + 1 }}">
                            <span class="flex-shrink-0 w-6 h-6 rounded-lg flex items-center justify-center" style="background:rgba(0,132,170,.2); border:1px solid rgba(0,132,170,.4);">
                                <i class="fas fa-check text-brand text-[10px]"></i>
                            </span>
                            <span class="text-white/70 font-medium text-sm">{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     CTA BAND
═══════════════════════════════════════════════════════════ --}}
<section class="cta-band py-24 px-4">
    <div class="relative z-10 max-w-4xl mx-auto text-center reveal">
        <span class="text-xs font-semibold tracking-[.2em] uppercase text-brand mb-4 inline-block">Próximo passo</span>
        <h2 class="text-4xl md:text-5xl font-bold text-white mb-5 leading-tight">
            Transforme números<br>em lucro.
        </h2>
        <p class="text-white/50 text-lg mb-10 max-w-xl mx-auto leading-relaxed">
            As Soluções WR reúnem tudo que sua empresa precisa para crescer com segurança, conformidade e inteligência estratégica.
        </p>
        <a href="#contact"
           class="group inline-flex items-center gap-3 bg-brand text-white px-10 py-4 rounded-xl font-bold transition-all duration-300 hover:shadow-[0_0_50px_rgba(0,132,170,.6)] hover:scale-[1.03] no-underline text-base">
            Falar com um Especialista
            <i class="fas fa-arrow-right transition-transform group-hover:translate-x-1"></i>
        </a>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     LOCATION
═══════════════════════════════════════════════════════════ --}}
<section id="location" class="py-28 px-4" style="background:#050505; border-top:1px solid rgba(255,255,255,.05);">
    <div class="max-w-7xl mx-auto">

        <div class="mb-12 reveal">
            <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Onde estamos</span>
            <h2 class="text-4xl md:text-5xl font-bold text-white mt-3 mb-4 leading-tight">Nossa Localização</h2>
            <div class="blue-line w-16"></div>
        </div>

        <div class="grid md:grid-cols-2 gap-8 items-stretch reveal">
            <!-- Map -->
            <div style="border-radius:20px; overflow:hidden; border:1px solid rgba(0,132,170,.25); box-shadow:0 20px 60px rgba(0,132,170,.1); min-height:420px;">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3477.3!2d-51.7955!3d-29.4448!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sR.+Carlos+Arnt%2C+2215+-+Canabarro%2C+Teut%C3%B4nia+-+RS%2C+95890-000!5e0!3m2!1spt-BR!2sbr!4v1713900000000!5m2!1spt-BR!2sbr&q=R.+Carlos+Arnt+2215+Teut%C3%B4nia+RS"
                    width="100%"
                    height="100%"
                    style="border:0; min-height:420px; display:block; filter:grayscale(30%) invert(5%) contrast(1.1);"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Localização WR Assessoria">
                </iframe>
            </div>

            <!-- Info cards -->
            <div class="flex flex-col gap-4 justify-center">
                <div class="flex items-start gap-4 p-5 rounded-2xl" style="background:rgba(0,132,170,.06); border:1px solid rgba(0,132,170,.15);">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(0,132,170,.15); border:1px solid rgba(0,132,170,.3);">
                        <i class="fas fa-map-marker-alt text-brand"></i>
                    </div>
                    <div>
                        <p class="text-[11px] text-white/35 uppercase tracking-wider mb-1">Endereço</p>
                        <p class="text-white font-semibold">R. Carlos Arnt, 2215 — Sala 201</p>
                        <p class="text-white/45 text-sm mt-0.5">Canabarro, Teutônia — RS, 95890-000</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl" style="background:rgba(0,132,170,.06); border:1px solid rgba(0,132,170,.15);">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(0,132,170,.15); border:1px solid rgba(0,132,170,.3);">
                        <i class="fas fa-clock text-brand"></i>
                    </div>
                    <div>
                        <p class="text-[11px] text-white/35 uppercase tracking-wider mb-2">Horário de Funcionamento</p>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between gap-10">
                                <span class="text-white/45">Segunda a Sexta</span>
                                <span class="text-white font-medium">08:00 – 17:30</span>
                            </div>
                            <div class="flex justify-between gap-10">
                                <span class="text-white/45">Sábado e Domingo</span>
                                <span class="text-red-400/80 font-medium">Fechado</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-5 rounded-2xl" style="background:rgba(0,132,170,.06); border:1px solid rgba(0,132,170,.15);">
                    <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(0,132,170,.15); border:1px solid rgba(0,132,170,.3);">
                        <i class="fas fa-phone text-brand"></i>
                    </div>
                    <div>
                        <p class="text-[11px] text-white/35 uppercase tracking-wider mb-1">Telefone</p>
                        <a href="tel:+555137628117" class="text-white font-semibold hover:text-brand transition-colors no-underline">(51) 3762-8117</a>
                    </div>
                </div>

                <a href="https://www.google.com/maps/dir/?api=1&destination=R.+Carlos+Arnt+2215+Teut%C3%B4nia+RS"
                   target="_blank" rel="noopener noreferrer"
                   class="group inline-flex items-center justify-center gap-3 mt-2 py-4 rounded-xl font-semibold text-white transition-all duration-300 hover:shadow-[0_0_30px_rgba(0,132,170,.4)] hover:scale-[1.02] no-underline"
                   style="background:#0084aa;">
                    <i class="fas fa-route text-sm"></i>
                    Como chegar
                    <i class="fas fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════
     CONTACT
═══════════════════════════════════════════════════════════ --}}
<section id="contact" class="py-28 px-4" style="background:#080808;">
    <div class="max-w-6xl mx-auto">
        <div class="grid md:grid-cols-2 gap-16 items-start">

            <!-- Info -->
            <div class="reveal reveal-left">
                <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Fale conosco</span>
                <h2 class="text-4xl md:text-5xl font-bold text-white mt-3 mb-4 leading-tight">Entre em<br>Contato</h2>
                <div class="blue-line w-16 mb-8"></div>
                <p class="text-white/45 mb-10 leading-relaxed">
                    Pronto para transformar a gestão contábil da sua empresa? Fale com nossos especialistas e descubra como podemos ajudar.
                </p>

                <div class="space-y-5">
                    <a href="tel:+555137628117" class="flex items-center gap-4 group no-underline">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 transition-all group-hover:shadow-[0_0_20px_rgba(0,132,170,.4)]" style="background:rgba(0,132,170,.12); border:1px solid rgba(0,132,170,.25);">
                            <i class="fas fa-phone text-brand text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] text-white/35 uppercase tracking-wider mb-0.5">Telefone</p>
                            <p class="text-white font-medium group-hover:text-brand transition-colors">(51) 3762-8117</p>
                        </div>
                    </a>

                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 mt-0.5" style="background:rgba(0,132,170,.12); border:1px solid rgba(0,132,170,.25);">
                            <i class="fas fa-map-marker-alt text-brand text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] text-white/35 uppercase tracking-wider mb-0.5">Endereço</p>
                            <p class="text-white font-medium">R. Carlos Arnt, 2215 — Sala 201</p>
                            <p class="text-white/40 text-sm">Canabarro, Teutônia — RS, 95890-000</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 mt-0.5" style="background:rgba(0,132,170,.12); border:1px solid rgba(0,132,170,.25);">
                            <i class="fas fa-clock text-brand text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] text-white/35 uppercase tracking-wider mb-2">Horário</p>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between gap-8">
                                    <span class="text-white/40">Segunda a Sexta</span>
                                    <span class="text-white font-medium"> 08:00 – 17:30</span>
                                </div>
                                <div class="flex justify-between gap-8">
                                    <span class="text-white/40">Sábado e Domingo</span>
                                    <span class="text-red-400/80 font-medium">Fechado</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(0,132,170,.12); border:1px solid rgba(0,132,170,.25);">
                            <i class="fas fa-star text-brand text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] text-white/35 uppercase tracking-wider mb-1">Avaliação Google</p>
                            <div class="flex items-center gap-2">
                                <span class="text-white font-bold text-lg">5,0</span>
                                <div class="flex gap-0.5">
                                    @for ($i = 0; $i < 5; $i++)
                                        <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="reveal reveal-right">
                <form id="contact-form" onsubmit="enviarWhatsApp(event)" class="glass-form">
                    <h3 class="text-white font-bold text-xl mb-6">Envie sua mensagem</h3>
                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label>Nome *</label>
                            <input id="campo-nome" type="text" required placeholder="Seu nome completo">
                        </div>
                        <div>
                            <label>E-mail</label>
                            <input id="campo-email" type="email" placeholder="seu@email.com">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label>Empresa</label>
                        <input id="campo-empresa" type="text" placeholder="Nome da sua empresa">
                    </div>
                    <div class="mb-6">
                        <label>Mensagem *</label>
                        <textarea id="campo-mensagem" required placeholder="Como podemos ajudar?" rows="5" style="resize:none;"></textarea>
                    </div>
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-3 text-white py-4 rounded-xl font-bold transition-all duration-300 hover:shadow-[0_0_40px_rgba(0,132,170,.5)] hover:scale-[1.01]"
                            style="background:#0084aa;">
                        <i class="fab fa-whatsapp text-xl"></i>
                        Enviar pelo WhatsApp
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

{{-- ══ Scripts ══ --}}
<script>
(function () {
    /* ── Reveal on scroll ── */
    const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.12 });
    revealEls.forEach(el => observer.observe(el));

    /* ── Hero bg loaded ── */
    const heroBg = document.getElementById('hero-bg');
    if (heroBg) {
        const img = new Image();
        img.onload = () => heroBg.classList.add('loaded');
        img.src = '/images/fachada.webp';
    }

    /* ── Counter animation ── */
    const counters = document.querySelectorAll('.counter');
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (!e.isIntersecting) { return; }
            const el = e.target;
            const target = parseInt(el.dataset.target, 10);
            const duration = 1800;
            const step = Math.max(1, Math.floor(target / (duration / 16)));
            let current = 0;
            const tick = () => {
                current = Math.min(current + step, target);
                el.textContent = current;
                if (current < target) { requestAnimationFrame(tick); }
            };
            requestAnimationFrame(tick);
            counterObserver.unobserve(el);
        });
    }, { threshold: 0.5 });
    counters.forEach(c => counterObserver.observe(c));

    /* ── WhatsApp ── */
    window.enviarWhatsApp = function (e) {
        e.preventDefault();
        const nome     = document.getElementById('campo-nome').value.trim();
        const email    = document.getElementById('campo-email').value.trim();
        const empresa  = document.getElementById('campo-empresa').value.trim();
        const mensagem = document.getElementById('campo-mensagem').value.trim();
        let texto = 'Olá! Gostaria de entrar em contato.\n\n';
        texto += '*Nome:* ' + nome + '\n';
        if (email)   { texto += '*E-mail:* ' + email + '\n'; }
        if (empresa) { texto += '*Empresa:* ' + empresa + '\n'; }
        texto += '\n*Mensagem:*\n' + mensagem;
        const numero = '55513762' + '8117';
        window.open('https://wa.me/' + numero + '?text=' + encodeURIComponent(texto), '_blank');
    };
}());
</script>
@endsection
