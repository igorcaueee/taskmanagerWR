@extends('layouts.app')

@section('title', 'WR Assessoria - Contabilidade Profissional')

@section('content')
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-blue-600 via-blue-700 to-blue-800 text-white py-32 px-4 overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-blue-400 rounded-full mix-blend-multiply blur-3xl"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-blue-300 rounded-full mix-blend-multiply blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto text-center">
            <h2 class="text-5xl md:text-6xl font-bold mb-6 leading-tight">
                Expertise em Contabilidade e Gestão Empresarial
            </h2>
            <p class="text-xl md:text-2xl text-blue-100 mb-10 max-w-3xl mx-auto leading-relaxed">
                Soluções contábeis completas para empresas que desejam crescer com segurança, conformidade e inteligência estratégica.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button class="bg-white text-blue-600 px-10 py-4 rounded-lg font-bold hover:bg-gray-50 transition shadow-lg hover:shadow-xl transform hover:scale-105">
                    Solicite Orçamento
                </button>
                <button class="border-2 border-white text-white px-10 py-4 rounded-lg font-bold hover:bg-white hover:text-blue-600 transition">
                    Saiba Mais
                </button>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-24 px-4 bg-gray-50">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Nossos Serviços</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Oferecemos soluções completas e integradas para a gestão contábil e tributária de sua empresa
                </p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Service Card 1 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-blue-200">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl mb-6 flex items-center justify-center text-4xl">
                        📊
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Contabilidade Geral</h3>
                    <p class="text-gray-600 leading-relaxed">Gestão completa de registros contábeis, lançamentos, conciliações e conformidade fiscal</p>
                </div>

                <!-- Service Card 2 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-blue-200">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl mb-6 flex items-center justify-center text-4xl">
                        💼
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Consultoria Tributária</h3>
                    <p class="text-gray-600 leading-relaxed">Estratégias otimizadas para redução de carga tributária com total segurança legal</p>
                </div>

                <!-- Service Card 3 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-blue-200">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl mb-6 flex items-center justify-center text-4xl">
                        📈
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Análise de Resultados</h3>
                    <p class="text-gray-600 leading-relaxed">Relatórios estratégicos e análises para tomada de decisão embasada em dados</p>
                </div>

                <!-- Service Card 4 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-blue-200">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl mb-6 flex items-center justify-center text-4xl">
                        ⚖️
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Conformidade Legal</h3>
                    <p class="text-gray-600 leading-relaxed">Certificação completa de conformidade com regulamentações e legislações vigentes</p>
                </div>

                <!-- Service Card 5 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-blue-200">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl mb-6 flex items-center justify-center text-4xl">
                        👥
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Gestão de Pessoal</h3>
                    <p class="text-gray-600 leading-relaxed">Folha de pagamento, gestão trabalhista e questões de recursos humanos</p>
                </div>

                <!-- Service Card 6 -->
                <div class="bg-white p-8 rounded-xl shadow-md hover:shadow-2xl transition duration-300 border border-gray-100 hover:border-blue-200">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl mb-6 flex items-center justify-center text-4xl">
                        🔍
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Auditoria</h3>
                    <p class="text-gray-600 leading-relaxed">Revisão independente de processos, controles internos e conformidade regulatória</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-24 px-4 bg-white">
        <div class="max-w-7xl mx-auto">
            <div class="grid md:grid-cols-2 gap-16 items-center">
                <div>
                    <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">Sobre a WR Assessoria</h2>
                    <p class="text-gray-700 mb-4 text-lg leading-relaxed">
                        A WR Assessoria é uma empresa especializada em soluções contábeis e consultoria empresarial, dedicada a auxiliar negócios de diversos tamanhos e setores a alcançarem seus objetivos financeiros.
                    </p>
                    <p class="text-gray-700 mb-8 text-lg leading-relaxed">
                        Com uma equipe de profissionais altamente capacitados, garantimos conformidade fiscal, otimização tributária e gestão eficiente para o crescimento sustentável e lucrativo do seu negócio.
                    </p>
                    <ul class="space-y-4">
                        <li class="flex items-center gap-3">
                            <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-600 text-white rounded-full text-sm font-bold">✓</span>
                            <span class="text-gray-700 font-medium">Expertise e Profissionalismo</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-600 text-white rounded-full text-sm font-bold">✓</span>
                            <span class="text-gray-700 font-medium">Atendimento Personalizado</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-600 text-white rounded-full text-sm font-bold">✓</span>
                            <span class="text-gray-700 font-medium">Tecnologia de Ponta</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex-shrink-0 w-6 h-6 flex items-center justify-center bg-blue-600 text-white rounded-full text-sm font-bold">✓</span>
                            <span class="text-gray-700 font-medium">Conformidade Garantida</span>
                        </li>
                    </ul>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-12 text-center h-96 flex flex-col justify-center items-center shadow-lg border border-blue-200">
                    <div class="text-8xl mb-6">💡</div>
                    <p class="text-gray-800 font-bold text-2xl">Transformando Dados em Decisões Estratégicas</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20 px-4">
        <div class="max-w-7xl mx-auto grid md:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="text-5xl font-bold mb-3">500+</div>
                <p class="text-blue-100 text-lg">Clientes Atendidos</p>
            </div>
            <div class="text-center">
                <div class="text-5xl font-bold mb-3">15+</div>
                <p class="text-blue-100 text-lg">Anos de Experiência</p>
            </div>
            <div class="text-center">
                <div class="text-5xl font-bold mb-3">98%</div>
                <p class="text-blue-100 text-lg">Satisfação de Clientes</p>
            </div>
            <div class="text-center">
                <div class="text-5xl font-bold mb-3">24h</div>
                <p class="text-blue-100 text-lg">Suporte Disponível</p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-24 px-4 bg-gray-50">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">Entre em Contato</h2>
                <p class="text-xl text-gray-600">
                    Pronto para transformar a gestão contábil da sua empresa? Fale com nossos especialistas.
                </p>
            </div>
            <form class="space-y-6 bg-white p-8 rounded-xl shadow-lg border border-gray-200">
                <div class="grid md:grid-cols-2 gap-6">
                    <input type="text" placeholder="Seu Nome" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition">
                    <input type="email" placeholder="Seu Email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition">
                </div>
                <input type="text" placeholder="Empresa" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition">
                <textarea placeholder="Sua Mensagem" rows="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition resize-none"></textarea>
                <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-4 rounded-lg font-bold hover:from-blue-700 hover:to-blue-800 transition shadow-lg hover:shadow-xl transform hover:scale-105">
                    Enviar Mensagem
                </button>
            </form>
        </div>
    </section>
@endsection
