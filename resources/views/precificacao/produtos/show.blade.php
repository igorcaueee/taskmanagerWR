@extends('layouts.internal')

@section('title', $produto->nome . ' — WR Assessoria')

@section('content')
    <div class="max-w-5xl mx-auto py-6 px-4">
        <a href="{{ route('precificacao.produtos', ['cliente_id' => $produto->cliente_id]) }}" class="text-sm text-brand hover:underline">&larr; Voltar para produtos de {{ $produto->cliente->nome }}</a>

        <div class="flex items-center justify-between flex-wrap gap-3 mt-2 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $produto->nome }}</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">NCM {{ $produto->ncm }} @if($produto->cest) &middot; CEST {{ $produto->cest }} @endif @if($produto->unidade) &middot; Unidade {{ $produto->unidade }} @endif</p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded focus:outline-none hover:bg-gray-50 dark:hover:bg-slate-600"
                        data-modal-url="{{ route('precificacao.produtos.form.edit', $produto->id) }}">
                    <i class="fa-solid fa-pencil"></i> Editar produto
                </button>
                <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-brand text-white rounded border-0 focus:outline-none hover:bg-brand/80"
                        data-modal-url="{{ route('precificacao.cenarios.form.create', $produto->id) }}">
                    <i class="fa-solid fa-plus"></i> Novo cenário
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 px-4 py-3 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
        @endif

        @if($produto->cenarios->isEmpty())
            <div class="bg-white dark:bg-slate-800 rounded shadow p-10 text-center text-gray-400">
                <p class="text-5xl mb-3">🧮</p>
                <p class="font-medium">Nenhum cenário de compra/venda cadastrado ainda.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($produto->cenarios as $cenario)
                    @php $r = $resultados[$cenario->id]; @endphp
                    <div class="bg-white dark:bg-slate-800 rounded shadow overflow-hidden">
                        <div class="flex items-center justify-between px-5 py-4 bg-gray-50 dark:bg-slate-900 border-b border-gray-100 dark:border-slate-700">
                            <div>
                                <span class="font-semibold text-gray-800 dark:text-slate-100">{{ $cenario->nome ?: 'Cenário #'.$cenario->id }}</span>
                                <span class="text-xs text-gray-400 dark:text-slate-500 ml-2">{{ $cenario->uf_compra }} &rarr; {{ $cenario->uf_venda }}</span>
                                @if(!$r->aliquotaEncontrada)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Sem alíquota cadastrada para este NCM/CEST — usando 0%</span>
                                @endif
                            </div>
                            <div class="flex gap-3">
                                <button type="button" class="text-gray-500 hover:text-brand bg-transparent border-0 p-0 focus:outline-none"
                                        data-modal-url="{{ route('precificacao.cenarios.form.edit', [$produto->id, $cenario->id]) }}">
                                    <i class="fa-solid fa-pencil"></i>
                                </button>
                                <form method="POST" action="{{ route('precificacao.cenarios.delete', [$produto->id, $cenario->id]) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="bg-transparent border-0 p-0 text-red-500 hover:text-red-600 btn-delete-cenario">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-5">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Custo de compra</p>
                                <dl class="space-y-1 text-sm">
                                    <div class="flex justify-between"><dt class="text-gray-500">Valor de compra</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->valorCompraTotal, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(+) ICMS-ST</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->icmsSt, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(+) IPI</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->ipi, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(+) Frete</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->freteCompra, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(-) Crédito PIS</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->creditoPis, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(-) Crédito COFINS</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->creditoCofins, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between pt-2 border-t border-gray-100 dark:border-slate-700 font-semibold"><dt class="text-gray-700 dark:text-slate-200">Custo total</dt><dd class="text-gray-900 dark:text-slate-100">R$ {{ number_format($r->custoTotal, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between font-semibold"><dt class="text-gray-700 dark:text-slate-200">Custo unitário</dt><dd class="text-gray-900 dark:text-slate-100">R$ {{ number_format($r->custoUnitario, 2, ',', '.') }}</dd></div>
                                </dl>
                            </div>

                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Preço de venda</p>
                                <dl class="space-y-1 text-sm">
                                    <div class="flex justify-between font-semibold"><dt class="text-gray-700 dark:text-slate-200">Preço de venda (markup {{ number_format($cenario->markup_pct, 2, ',', '.') }}%)</dt><dd class="text-gray-900 dark:text-slate-100">R$ {{ number_format($r->precoVenda, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(-) ICMS s/venda</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->icmsVenda, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(-) PIS s/venda</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->pisVenda, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(-) COFINS s/venda</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->cofinsVenda, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(-) Comissão</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->comissao, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(-) Frete s/venda</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->freteVenda, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between"><dt class="text-gray-500">(-) Custo unitário</dt><dd class="text-gray-800 dark:text-slate-200">R$ {{ number_format($r->custoUnitario, 2, ',', '.') }}</dd></div>
                                    <div class="flex justify-between pt-2 border-t border-gray-100 dark:border-slate-700 font-semibold">
                                        <dt class="text-gray-700 dark:text-slate-200">Margem de contribuição</dt>
                                        <dd class="{{ $r->margemContribuicaoValor >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            R$ {{ number_format($r->margemContribuicaoValor, 2, ',', '.') }} ({{ number_format($r->margemContribuicaoPercentual, 2, ',', '.') }}%)
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @push('scripts')
    <script type="module">
    document.querySelectorAll('.btn-delete-cenario').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const form = btn.closest('form');

            Swal.fire({
                title: 'Excluir cenário?',
                text: 'Tem certeza que deseja excluir este cenário? Esta ação não pode ser desfeita.',
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
