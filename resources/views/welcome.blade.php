@extends('layouts.app')

@section('title', 'WR Assessoria - Contabilidade Profissional')

@section('content')
    <!-- Hero Section -->
    <section class="relative bg-black text-white overflow-hidden">
        <!-- Decorative accent bar -->
        <div class="absolute top-0 left-0 w-full h-1 bg-brand"></div>
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, #fff 0, #fff 1px, transparent 0, transparent 50%); background-size: 24px 24px;"></div>
        </div>
        <!-- Glow accent -->
        <div class="absolute -top-32 -right-32 w-[600px] h-[600px] rounded-full opacity-10" style="background: radial-gradient(circle, #0084aa 0%, transparent 70%);"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-36 flex flex-col md:flex-row items-center gap-16">
            <div class="flex-1 text-center md:text-left">
                <span class="inline-block text-brand text-sm font-semibold tracking-widest uppercase mb-4 border border-brand/40 px-4 py-1 rounded-full">
                    Contabilidade &amp; Gestão
                </span>
                <h2 class="text-5xl md:text-6xl font-bold leading-tight mb-6">
                    Expertise em<br>
                    <span class="text-brand">Contabilidade</span><br>
                    Empresarial
                </h2>
                <p class="text-lg text-white/70 mb-10 max-w-xl leading-relaxed">
                    Soluções contábeis completas para empresas que desejam crescer com segurança, conformidade e inteligência estratégica.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center md:justify-start">
                    <a href="#contact" class="bg-brand text-white px-10 py-4 rounded-md font-semibold hover:opacity-90 transition shadow-lg">
                        Solicite Orçamento
                    </a>
                    <a href="#services" class="border border-white/30 text-white px-10 py-4 rounded-md font-semibold hover:border-brand hover:text-brand transition">
                        Nossos Serviços
                    </a>
                </div>
            </div>
            <!-- Right decorative card -->
            <div class="flex-shrink-0 hidden lg:grid grid-cols-2 gap-4 w-80">
                <div class="bg-white/5 border border-white/10 rounded-xl p-5 col-span-2">
                    <p class="text-brand font-bold text-3xl">500+</p>
                    <p class="text-white/60 text-sm mt-1">Clientes atendidos com excelência</p>
                </div>
                <div class="bg-white/5 border border-white/10 rounded-xl p-5">
                    <p class="text-brand font-bold text-3xl">15+</p>
                    <p class="text-white/60 text-sm mt-1">Anos de experiência</p>
                </div>
                <div class="bg-white/5 border border-white/10 rounded-xl p-5">
                    <p class="text-brand font-bold text-3xl">98%</p>
                    <p class="text-white/60 text-sm mt-1">Satisfação dos clientes</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-24 px-4 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="mb-16">
                <span class="text-brand text-sm font-semibold tracking-widest uppercase">O que oferecemos</span>
                <h2 class="text-4xl md:text-5xl font-bold text-black mt-2 mb-4">Nossos Serviços</h2>
                <div class="w-12 h-1 bg-brand rounded"></div>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Service Card 1 -->
                <div class="group border border-gray-100 p-8 rounded-xl hover:border-brand hover:shadow-xl transition duration-300 bg-white">
                    <div class="w-12 h-12 bg-brand/10 rounded-lg mb-6 flex items-center justify-center">
                        <i class="fas fa-chart-bar text-brand text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-3 group-hover:text-brand transition">Contabilidade Geral</h3>
                    <p class="text-gray-500 leading-relaxed text-sm">Gestão completa de registros contábeis, lançamentos, conciliações e conformidade fiscal.</p>
                </div>

                <!-- Service Card 2 -->
                <div class="group border border-gray-100 p-8 rounded-xl hover:border-brand hover:shadow-xl transition duration-300 bg-white">
                    <div class="w-12 h-12 bg-brand/10 rounded-lg mb-6 flex items-center justify-center">
                        <i class="fas fa-briefcase text-brand text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-3 group-hover:text-brand transition">Consultoria Tributária</h3>
                    <p class="text-gray-500 leading-relaxed text-sm">Estratégias otimizadas para redução de carga tributária com total segurança legal.</p>
                </div>

                <!-- Service Card 3 -->
                <div class="group border border-gray-100 p-8 rounded-xl hover:border-brand hover:shadow-xl transition duration-300 bg-white">
                    <div class="w-12 h-12 bg-brand/10 rounded-lg mb-6 flex items-center justify-center">
                        <i class="fas fa-chart-line text-brand text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-3 group-hover:text-brand transition">Análise de Resultados</h3>
                    <p class="text-gray-500 leading-relaxed text-sm">Relatórios estratégicos e análises para tomada de decisão embasada em dados.</p>
                </div>

                <!-- Service Card 4 -->
                <div class="group border border-gray-100 p-8 rounded-xl hover:border-brand hover:shadow-xl transition duration-300 bg-white">
                    <div class="w-12 h-12 bg-brand/10 rounded-lg mb-6 flex items-center justify-center">
                        <i class="fas fa-balance-scale text-brand text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-3 group-hover:text-brand transition">Conformidade Legal</h3>
                    <p class="text-gray-500 leading-relaxed text-sm">Certificação completa de conformidade com regulamentações e legislações vigentes.</p>
                </div>

                <!-- Service Card 5 -->
                <div class="group border border-gray-100 p-8 rounded-xl hover:border-brand hover:shadow-xl transition duration-300 bg-white">
                    <div class="w-12 h-12 bg-brand/10 rounded-lg mb-6 flex items-center justify-center">
                        <i class="fas fa-users text-brand text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-3 group-hover:text-brand transition">Gestão de Pessoal</h3>
                    <p class="text-gray-500 leading-relaxed text-sm">Folha de pagamento, gestão trabalhista e questões de recursos humanos.</p>
                </div>

                <!-- Service Card 6 -->
                <div class="group border border-gray-100 p-8 rounded-xl hover:border-brand hover:shadow-xl transition duration-300 bg-white">
                    <div class="w-12 h-12 bg-brand/10 rounded-lg mb-6 flex items-center justify-center">
                        <i class="fas fa-search text-brand text-xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-black mb-3 group-hover:text-brand transition">Auditoria</h3>
                    <p class="text-gray-500 leading-relaxed text-sm">Revisão independente de processos, controles internos e conformidade regulatória.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-24 px-4 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-2 gap-16 items-center">
                <!-- Left: text -->
                <div>
                    <span class="text-brand text-sm font-semibold tracking-widest uppercase">Quem somos</span>
                    <h2 class="text-4xl md:text-5xl font-bold text-black mt-2 mb-6">Sobre a WR Assessoria</h2>
                    <div class="w-12 h-1 bg-brand rounded mb-8"></div>
                    <p class="text-gray-600 mb-4 text-base leading-relaxed">
                        A WR Assessoria é uma empresa especializada em soluções contábeis e consultoria empresarial, dedicada a auxiliar negócios de diversos tamanhos e setores a alcançarem seus objetivos financeiros.
                    </p>
                    <p class="text-gray-600 mb-10 text-base leading-relaxed">
                        Com uma equipe de profissionais altamente capacitados, garantimos conformidade fiscal, otimização tributária e gestão eficiente para o crescimento sustentável do seu negócio.
                    </p>
                    <ul class="space-y-3">
                        @foreach(['Expertise e Profissionalismo', 'Atendimento Personalizado', 'Tecnologia de Ponta', 'Conformidade Garantida'] as $item)
                            <li class="flex items-center gap-3">
                                <span class="flex-shrink-0 w-5 h-5 flex items-center justify-center bg-brand text-white rounded-full">
                                    <i class="fas fa-check text-xs"></i>
                                </span>
                                <span class="text-gray-700 font-medium">{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <!-- Right: stats grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-black text-white rounded-xl p-8 flex flex-col justify-between">
                        <i class="fas fa-trophy text-brand text-2xl mb-6"></i>
                        <div>
                            <p class="text-4xl font-bold">500+</p>
                            <p class="text-white/60 text-sm mt-1">Clientes atendidos</p>
                        </div>
                    </div>
                    <div class="bg-brand text-white rounded-xl p-8 flex flex-col justify-between">
                        <i class="fas fa-clock text-white text-2xl mb-6"></i>
                        <div>
                            <p class="text-4xl font-bold">15+</p>
                            <p class="text-white/80 text-sm mt-1">Anos de experiência</p>
                        </div>
                    </div>
                    <div class="bg-brand text-white rounded-xl p-8 flex flex-col justify-between">
                        <i class="fas fa-star text-white text-2xl mb-6"></i>
                        <div>
                            <p class="text-4xl font-bold">98%</p>
                            <p class="text-white/80 text-sm mt-1">Satisfação dos clientes</p>
                        </div>
                    </div>
                    <div class="bg-black text-white rounded-xl p-8 flex flex-col justify-between">
                        <i class="fas fa-headset text-brand text-2xl mb-6"></i>
                        <div>
                            <p class="text-4xl font-bold">24h</p>
                            <p class="text-white/60 text-sm mt-1">Suporte disponível</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Banner -->
    <section class="bg-brand py-20 px-4">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">Pronto para simplificar sua contabilidade?</h2>
            <p class="text-white/80 text-lg mb-8 max-w-xl mx-auto">Nossa equipe está pronta para oferecer a solução ideal para o seu negócio.</p>
            <a href="#contact" class="inline-block bg-white text-brand px-10 py-4 rounded-md font-bold hover:bg-gray-100 transition shadow-lg">
                Falar com um Especialista
            </a>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-24 px-4 bg-white">
        <div class="max-w-6xl mx-auto">
            <div class="grid md:grid-cols-2 gap-16 items-start">
                <!-- Left info -->
                <div>
                    <span class="text-brand text-sm font-semibold tracking-widest uppercase">Fale conosco</span>
                    <h2 class="text-4xl md:text-5xl font-bold text-black mt-2 mb-4">Entre em Contato</h2>
                    <div class="w-12 h-1 bg-brand rounded mb-8"></div>
                    <p class="text-gray-600 mb-10 leading-relaxed">
                        Pronto para transformar a gestão contábil da sua empresa? Fale com nossos especialistas e descubra como podemos ajudar.
                    </p>
                    <div class="space-y-5">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-phone text-brand"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Telefone</p>
                                <a href="tel:+555137628117" class="text-gray-800 font-medium hover:text-brand transition">(51) 3762-8117</a>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-map-marker-alt text-brand"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Endereço</p>
                                <p class="text-gray-800 font-medium">R. Carlos Arnt, 2215 — Sala 201</p>
                                <p class="text-gray-500 text-sm">Canabarro, Teutônia — RS, 95890-000</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-clock text-brand"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Horário de Funcionamento</p>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between gap-8">
                                        <span class="text-gray-500">Segunda a Sexta</span>
                                        <span class="text-gray-800 font-medium">08:00 – 17:30</span>
                                    </div>
                                    <div class="flex justify-between gap-8">
                                        <span class="text-gray-500">Sábado e Domingo</span>
                                        <span class="text-red-400 font-medium">Fechado</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 bg-brand/10 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fas fa-star text-brand"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wide mb-0.5">Avaliação Google</p>
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-800 font-bold text-lg">5,0</span>
                                    <div class="flex gap-0.5">
                                        @for($i = 0; $i < 5; $i++)
                                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Right form -->
                <form id="contact-form" onsubmit="enviarWhatsApp(event)" class="space-y-5 bg-gray-50 p-8 rounded-2xl border border-gray-100">
                    <div class="grid md:grid-cols-2 gap-5">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Nome</label>
                            <input id="campo-nome" type="text" required placeholder="Seu nome completo" class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 transition text-sm">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">E-mail</label>
                            <input id="campo-email" type="email" placeholder="seu@email.com" class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 transition text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Empresa</label>
                        <input id="campo-empresa" type="text" placeholder="Nome da sua empresa" class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 transition text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide block mb-1">Mensagem</label>
                        <textarea id="campo-mensagem" required placeholder="Como podemos ajudar?" rows="5" class="w-full px-4 py-3 border border-gray-200 rounded-lg bg-white focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 transition resize-none text-sm"></textarea>
                    </div>
                    <button type="submit" class="w-full text-white py-4 rounded-lg font-semibold hover:opacity-90 transition shadow-md flex items-center justify-center gap-2" style="background:#0084aa;">
                        <i class="fab fa-whatsapp text-lg"></i>
                        Enviar pelo WhatsApp
                    </button>
                </form>

                <script>
                    function enviarWhatsApp(e) {
                        e.preventDefault();

                        const nome     = document.getElementById('campo-nome').value.trim();
                        const email    = document.getElementById('campo-email').value.trim();
                        const empresa  = document.getElementById('campo-empresa').value.trim();
                        const mensagem = document.getElementById('campo-mensagem').value.trim();

                        let texto = `Olá! Gostaria de entrar em contato.\n\n`;
                        texto += `*Nome:* ${nome}\n`;
                        if (email)   texto += `*E-mail:* ${email}\n`;
                        if (empresa) texto += `*Empresa:* ${empresa}\n`;
                        texto += `\n*Mensagem:*\n${mensagem}`;

                        const numero = '5551376281 17'.replace(/\s/g, '');
                        const url = `https://wa.me/${numero}?text=${encodeURIComponent(texto)}`;

                        window.open(url, '_blank');
                    }
                </script>
            </div>
        </div>
    </section>
@endsection
