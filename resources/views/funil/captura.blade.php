@extends('layouts.app')

@section('title', 'Seja Cliente — WR Assessoria')

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

    /* ── Blue line ── */
    .blue-line {
        height: 2px;
        background: linear-gradient(90deg, transparent, #0084aa, transparent);
        border: none;
    }

    /* ── Glass form ── */
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
    .glass-form label {
        color: rgba(255,255,255,.55);
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        display: block;
        margin-bottom: .4rem;
    }
    .glass-form .field-error {
        color: #f87171;
        font-size: .75rem;
        margin-top: .3rem;
        display: block;
    }

    /* ── Info cards ── */
    .info-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.25rem;
        border-radius: 16px;
        background: rgba(0,132,170,.06);
        border: 1px solid rgba(0,132,170,.15);
    }
    .info-card .icon-wrap {
        width: 44px; height: 44px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        background: rgba(0,132,170,.15);
        border: 1px solid rgba(0,132,170,.3);
    }
</style>
@endpush

@section('content')

{{-- ═══════════════════ HERO MINI ═══════════════════ --}}
<section class="relative pt-40 pb-20 px-4 overflow-hidden" style="background:#000;">
    {{-- Dot grid overlay --}}
    <div class="absolute inset-0 pointer-events-none" style="background-image:radial-gradient(rgba(0,132,170,.15) 1px, transparent 1px); background-size:36px 36px;"></div>
    {{-- Side glow --}}
    <div class="absolute inset-0 pointer-events-none" style="background:radial-gradient(ellipse at 70% 40%, rgba(0,132,170,.18) 0%, transparent 60%);"></div>

    <div class="relative z-10 max-w-3xl mx-auto text-center reveal">
        <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Dê o primeiro passo</span>
        <h1 class="text-4xl sm:text-5xl font-bold text-white mt-3 mb-4 leading-tight">
            Quero ser <span style="color:#0084aa;">cliente</span>
        </h1>
        <div class="blue-line w-16 mx-auto mb-5"></div>
        <p class="text-white/50 text-lg max-w-xl mx-auto leading-relaxed">
            Preencha o formulário abaixo e nossa equipe entrará em contato em breve para entender como podemos ajudar.
        </p>
    </div>
</section>

{{-- ═══════════════════ FORM + INFO ═══════════════════ --}}
<section class="py-20 px-4" style="background:#080808; border-top:1px solid rgba(255,255,255,.05);">
    <div class="max-w-6xl mx-auto">
        <div class="grid md:grid-cols-2 gap-16 items-start">

            {{-- ── Left: Info ── --}}
            <div class="reveal reveal-left">
                <span class="text-xs font-semibold tracking-[.2em] uppercase" style="color:#0084aa;">Por que a WR?</span>
                <h2 class="text-3xl md:text-4xl font-bold text-white mt-3 mb-4 leading-tight">
                    Expertise que<br>transforma seu negócio
                </h2>
                <div class="blue-line w-16 mb-8"></div>
                <p class="text-white/45 mb-10 leading-relaxed">
                    Desde 2009 transformando números em lucro. Soluções contábeis e assessoria empresarial para mais de 500 clientes em 56 cidades de 4 estados.
                </p>

                <div class="space-y-4">
                    <div class="info-card reveal reveal-delay-1">
                        <div class="icon-wrap">
                            <i class="fas fa-trophy text-brand"></i>
                        </div>
                        <div>
                            <p class="text-[11px] text-white/35 uppercase tracking-wider mb-0.5">Clientes Atendidos</p>
                            <p class="text-white font-semibold">Mais de 500 empresas</p>
                            <p class="text-white/40 text-sm mt-0.5">Nos setores industrial, comercial e de serviços</p>
                        </div>
                    </div>

                    <div class="info-card reveal reveal-delay-2">
                        <div class="icon-wrap">
                            <i class="fas fa-clock text-brand"></i>
                        </div>
                        <div>
                            <p class="text-[11px] text-white/35 uppercase tracking-wider mb-0.5">Anos de Experiência</p>
                            <p class="text-white font-semibold">Mais de 17 anos no mercado</p>
                            <p class="text-white/40 text-sm mt-0.5">Fundada em 2009, evoluiu para WR Assessoria em 2026</p>
                        </div>
                    </div>

                    <div class="info-card reveal reveal-delay-3">
                        <div class="icon-wrap">
                            <i class="fas fa-map-marker-alt text-brand"></i>
                        </div>
                        <div>
                            <p class="text-[11px] text-white/35 uppercase tracking-wider mb-0.5">Abrangência</p>
                            <p class="text-white font-semibold">56 cidades em 4 estados</p>
                            <p class="text-white/40 text-sm mt-0.5">R. Carlos Arnt, 2215 — Teutônia, RS</p>
                        </div>
                    </div>

                    <div class="info-card reveal reveal-delay-4">
                        <div class="icon-wrap">
                            <i class="fas fa-phone text-brand"></i>
                        </div>
                        <div>
                            <p class="text-[11px] text-white/35 uppercase tracking-wider mb-0.5">Telefone</p>
                            <a href="tel:+555137628117" class="text-white font-semibold hover:text-brand transition-colors no-underline">(51) 3762-8117</a>
                            <p class="text-white/40 text-sm mt-0.5">Segunda a Sexta, 08:00 – 17:30</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Right: Form ── --}}
            <div class="reveal reveal-right">

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

                <form method="POST" action="{{ route('funil.captura.store') }}" class="glass-form">
                    @csrf
                    <h3 class="text-white font-bold text-xl mb-6">Envie sua mensagem</h3>

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

                    <div class="mb-4">
                        <label>Telefone / WhatsApp</label>
                        <input name="telefone" type="text" value="{{ old('telefone') }}" placeholder="(00) 00000-0000">
                        @error('telefone')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-4">
                        <label>Empresa</label>
                        <input name="empresa" type="text" value="{{ old('empresa') }}" placeholder="Nome da sua empresa">
                        @error('empresa')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-6">
                        <label>Como podemos ajudar?</label>
                        <textarea name="possibilidade" rows="5" placeholder="Descreva brevemente o que você precisa..." style="resize:none;">{{ old('possibilidade') }}</textarea>
                        @error('possibilidade')<span class="field-error">{{ $message }}</span>@enderror
                    </div>

                    <button type="submit"
                            class="w-full flex items-center justify-center gap-3 text-white py-4 rounded-xl font-bold transition-all duration-300 hover:shadow-[0_0_40px_rgba(0,132,170,.5)] hover:scale-[1.01]"
                            style="background:#0084aa;">
                        <i class="fas fa-paper-plane"></i>
                        Enviar mensagem
                    </button>
                </form>
            </div>

        </div>
    </div>
</section>

{{-- ══ Reveal script ══ --}}
<script>
(function () {
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
}());
</script>

@endsection
