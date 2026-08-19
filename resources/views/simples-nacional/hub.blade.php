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

            <a href="{{ route('nfse.index') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-invoice"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">NFS-e Portal Nacional</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Consulta de notas de serviço no Portal Nacional</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('nfe.index') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-truck-ramp-box"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">NF-e / NFC-e / CT-e</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Distribuição DFe — consulta de NF-e/NFC-e/CT-e</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.das.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">DAS mensal (PGDASD)</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Lançar receita, transmitir, consultar declarações, importar do Domínio e histórico</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.mit.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-invoice"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">MIT</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Consultar apurações e encerrar (sem ou com movimento)</p>
                    </div>
                </div>
            </a>

            @php $podeAcessarReinf = auth()->user()?->canAcessarReinf() ?? false; @endphp
            @if($podeAcessarReinf)
                <a href="{{ route('reinf.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group relative">
                    <span class="absolute top-3 right-3 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-full px-2 py-0.5">Em desenvolvimento</span>
                    <div class="flex items-center gap-4">
                        <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-shield"></i></div>
                        <div>
                            <h2 class="font-semibold text-gray-800 dark:text-slate-100">EFD-Reinf — Fechamento</h2>
                            <p class="text-sm text-gray-500 dark:text-slate-400">Enviar/consultar R-2099 e R-4099 (certificado do cliente)</p>
                        </div>
                    </div>
                </a>
            @else
                <div class="opacity-50 grayscale cursor-not-allowed bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm relative" title="Restrito a Diretor e TI — feature em desenvolvimento">
                    <span class="absolute top-3 right-3 text-[10px] font-semibold uppercase tracking-wide text-gray-600 dark:text-slate-400 bg-gray-100 dark:bg-slate-700 rounded-full px-2 py-0.5">Em desenvolvimento</span>
                    <div class="flex items-center gap-4">
                        <div class="bg-gray-200 dark:bg-slate-700 text-gray-500 dark:text-slate-400 rounded-lg p-3 text-2xl"><i class="fa-solid fa-file-shield"></i></div>
                        <div>
                            <h2 class="font-semibold text-gray-600 dark:text-slate-300">EFD-Reinf — Fechamento</h2>
                            <p class="text-sm text-gray-500 dark:text-slate-400">Restrito a Diretor e TI</p>
                        </div>
                    </div>
                </div>
            @endif

            <a href="{{ route('simples-nacional.defis.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-lines"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">DEFIS</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Transmitir e consultar declarações anuais</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.parcelamentos.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Parcelamentos</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">PARCSN, PARCSN-ESP, PERTSN e RELPSN — histórico, detalhes e parcelas pendentes</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.parcelamentos-mei.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Parcelamentos MEI</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">PARCMEI, PARCMEI-ESP, PERTMEI e RELPMEI</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('simples-nacional.dctfweb.tela') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-invoice"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">DCTFWeb</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Guia, recibo, relatório completo e XML da declaração</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('cnd.index') }}" class="no-underline bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl p-6 shadow-sm hover:border-brand hover:shadow-md transition group">
                <div class="flex items-center gap-4">
                    <div class="bg-brand/10 text-brand rounded-lg p-3 text-2xl group-hover:bg-brand/20 transition"><i class="fa-solid fa-file-shield"></i></div>
                    <div>
                        <h2 class="font-semibold text-gray-800 dark:text-slate-100">Consulta CND</h2>
                        <p class="text-sm text-gray-500 dark:text-slate-400">Certidão negativa de débito (PGFN/RFB) — PJ, PF e imóvel rural</p>
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
        </div>
    </div>
@endsection
