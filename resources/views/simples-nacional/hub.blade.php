@extends('layouts.internal')

@section('title', 'Utilitários Fiscal — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-file-invoice-dollar"></i> Utilitários Fiscal</h1>
                <p class="text-gray-700 dark:text-gray-300">Escolha o que você quer fazer.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <a href="{{ route('simples-nacional.configuracao.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-key"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Configuração da API</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Certificado do escritório e chaves SERPRO</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.importar-dominio.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-import"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Importar do Domínio</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Preencher receita a partir do relatório .txt</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.declaracoes.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Consultar declarações</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Histórico do PGDASD, DAS, extrato e RBT12</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.parcelamentos.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Parcelamentos (PARCSN)</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Histórico, detalhes e parcelas pendentes</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.defis.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-lines"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">DEFIS</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Consultar declarações anuais já transmitidas</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.defis-transmitir.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-paper-plane"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Transmitir DEFIS</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Lançar dados anuais e transmitir a DEFIS</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.transmitir.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-paper-plane"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Lançar receita e transmitir</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Apuração do mês e transmissão do PGDASD</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.caixa-postal.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-envelope"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Caixa Postal</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Mensagens da Receita Federal por cliente</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.sitfis.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-shield"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">SITFIS</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Relatório de situação fiscal (RFB/PGFN)</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.procuracoes.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-signature"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Procurações</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Checar procuração eletrônica ativa por cliente</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.mit.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-invoice"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">MIT</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Consultar apurações do Módulo de Inclusão de Tributos</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.processamentos') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-list-check"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Processamentos em lote</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Histórico de transmissão automática por período</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
@endsection
