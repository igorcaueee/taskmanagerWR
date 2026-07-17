@extends('layouts.internal')

@section('title', 'Alíquotas de Precificação — WR Assessoria')

@section('content')
    <div class="max-w-7xl mx-auto py-6 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100"><i class="fa-solid fa-percent"></i> Alíquotas de Precificação</h1>
                <p class="text-gray-700 dark:text-gray-300">Tabela fiscal por NCM/CEST usada no cálculo de custo e preço de venda dos clientes.</p>
            </div>
            @if (auth()->user()?->canGerenciarPrecificacao())
            <div class="flex gap-2">
                <a href="{{ route('precificacao.aliquotas.import.template') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded hover:bg-gray-50 dark:hover:bg-slate-600 focus:outline-none">
                    <i class="fa-solid fa-download"></i>
                </a>
                <button type="button" class="inline-flex items-center px-4 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded focus:outline-none hover:bg-gray-50 dark:hover:bg-slate-600"
                        data-modal-url="{{ route('precificacao.aliquotas.form.import') }}">
                    <i class="fa-solid fa-file-import"></i>
                </button>
                <button type="button" class="inline-flex items-center px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('precificacao.aliquotas.form.create') }}">
                    <i class="fa-solid fa-plus"></i>
                </button>
            </div>
            @endif
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 px-4 py-3 bg-red-100 text-red-800 rounded">{{ session('error') }}</div>
        @endif

        <details class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded text-sm text-blue-800 dark:text-blue-300" open>
            <summary class="px-4 py-3 font-medium cursor-pointer select-none">
                <i class="fa-solid fa-circle-info mr-1"></i> Como funciona o cálculo automático (passo a passo)
            </summary>
            <div class="px-4 pb-4 space-y-3">
                <div>
                    <p class="font-medium mb-1">1. Cadastre as alíquotas aqui (uma vez por NCM/CEST)</p>
                    <p class="text-blue-700 dark:text-blue-300/80">Baixe o modelo (ícone de download), preencha com os dados fiscais que você já tem (ex: relatório do Econet Validador) e importe (ícone de import). Ou cadastre um NCM de cada vez pelo botão "+".</p>
                    <table class="mt-2 w-full text-xs border-collapse">
                        <tbody class="divide-y divide-blue-200 dark:divide-blue-800">
                            <tr><td class="py-1 pr-3 font-mono whitespace-nowrap">ncm</td><td class="py-1 text-blue-700 dark:text-blue-300/80">NCM do produto (ex: 87082999)</td></tr>
                            <tr><td class="py-1 pr-3 font-mono whitespace-nowrap">cest</td><td class="py-1 text-blue-700 dark:text-blue-300/80">CEST, se tiver (ex: 0199900)</td></tr>
                            <tr><td class="py-1 pr-3 font-mono whitespace-nowrap">uf_referencia</td><td class="py-1 text-blue-700 dark:text-blue-300/80">UF em que a alíquota foi consultada</td></tr>
                            <tr><td class="py-1 pr-3 font-mono whitespace-nowrap">aliquota_icms_interna</td><td class="py-1 text-blue-700 dark:text-blue-300/80">% ICMS dentro do estado</td></tr>
                            <tr><td class="py-1 pr-3 font-mono whitespace-nowrap">aplica_st / aliquota_icms_st</td><td class="py-1 text-blue-700 dark:text-blue-300/80">Se tem Substituição Tributária, e o % efetivo cobrado na compra</td></tr>
                            <tr><td class="py-1 pr-3 font-mono whitespace-nowrap">icms_venda_regra</td><td class="py-1 text-blue-700 dark:text-blue-300/80">"Tributado" ou "St_ja_paga" (0% de ICMS na venda)</td></tr>
                            <tr><td class="py-1 pr-3 font-mono whitespace-nowrap">regime_pis_cofins</td><td class="py-1 text-blue-700 dark:text-blue-300/80">"Tributado" ou "Monofasico" (0% de PIS/COFINS na venda)</td></tr>
                            <tr><td class="py-1 pr-3 font-mono whitespace-nowrap">aliquota_pis / aliquota_cofins</td><td class="py-1 text-blue-700 dark:text-blue-300/80">% usado como crédito na compra (e débito na venda, se não for monofásico)</td></tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <p class="font-medium mb-1">2. O cliente (ou você) cadastra os produtos</p>
                    <p class="text-blue-700 dark:text-blue-300/80">Em <span class="font-mono">Produtos de Precificação</span> ou no portal do cliente. O NCM + CEST do produto precisa bater exatamente com o que foi cadastrado no passo 1 para o sistema encontrar a alíquota certa.</p>
                </div>
                <div>
                    <p class="font-medium mb-1">3. Cria-se o cenário de compra/venda no produto</p>
                    <p class="text-blue-700 dark:text-blue-300/80">UF de compra, UF de venda, valor, quantidade, frete, IPI, markup, comissão. O sistema busca a alíquota automaticamente pelo NCM+CEST e calcula custo, preço de venda e margem na hora.</p>
                </div>
                <p class="text-xs text-blue-600 dark:text-blue-400 italic">Dica: cadastre as alíquotas antes dos produtos. Se um produto ficar sem alíquota correspondente, ele aparece com aviso amarelo e calcula com 0% de imposto até você cadastrar a alíquota certa — não precisa reeditar o produto depois.</p>
            </div>
        </details>

        <div class="bg-white dark:bg-slate-800 rounded shadow overflow-x-auto">
            <form method="GET" action="{{ route('precificacao.aliquotas') }}" id="form-filtros-aliquotas"
                  class="flex flex-wrap gap-3 px-4 py-3 border-b border-gray-100 dark:border-slate-700">
                <div>
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Pesquisar</label>
                    <input type="text" name="busca" value="{{ request('busca') }}"
                           placeholder="NCM, CEST ou descrição..."
                           onchange="document.getElementById('form-filtros-aliquotas').submit()"
                           class="border border-gray-300 dark:border-slate-600 rounded px-3 py-1.5 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-700 focus:outline-none focus:ring-1 focus:ring-brand w-64">
                </div>
            </form>

            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">NCM</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">CEST</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">UF</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ICMS interno</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ICMS-ST</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ICMS venda</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">PIS/COFINS</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                    @forelse($aliquotas as $aliquota)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $aliquota->ncm }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $aliquota->cest ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">{{ $aliquota->descricao ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $aliquota->uf_referencia ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ number_format($aliquota->aliquota_icms_interna, 2, ',', '.') }}%</td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                @if($aliquota->aplica_st)
                                    {{ number_format($aliquota->aliquota_icms_st, 2, ',', '.') }}%
                                @else
                                    Não se aplica
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $aliquota->icms_venda_regra === 'st_ja_paga' ? 'ST já paga' : 'Tributado' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ $aliquota->regime_pis_cofins === 'monofasico' ? 'Monofásico' : 'Tributado' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                @if (auth()->user()?->canGerenciarPrecificacao())
                                <button type="button"
                                        class="text-brand hover:text-brand/80 focus:outline-none focus:ring-0 border-0 bg-transparent p-0"
                                        data-modal-url="{{ route('precificacao.aliquotas.form.edit', $aliquota->id) }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>

                                <form method="POST" action="{{ route('precificacao.aliquotas.delete', $aliquota->id) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-red-600 hover:text-red-700 ml-3 focus:outline-none focus:ring-0 border-0 bg-transparent p-0 btn-delete-aliquota" data-nome="{{ $aliquota->ncm }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Nenhuma alíquota cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-6 py-4">
                {{ $aliquotas->links() }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script type="module">
    document.querySelectorAll('.btn-delete-aliquota').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const nome = btn.dataset.nome;
            const form = btn.closest('form');

            Swal.fire({
                title: 'Excluir alíquota?',
                text: `Tem certeza que deseja excluir o NCM "${nome}"? Esta ação não pode ser desfeita.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
    </script>
    @endpush
@endsection
