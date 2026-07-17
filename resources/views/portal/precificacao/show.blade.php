@extends('layouts.portal')

@section('title', $produto->nome)

@section('content')
<div class="space-y-6">

    <div>
        <a href="{{ route('portal.precificacao.index') }}" class="text-sm text-[#0084AA] no-underline hover:underline">&larr; Voltar para Precificação</a>
        <div class="flex items-center justify-between flex-wrap gap-3 mt-2">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">{{ $produto->nome }}</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">NCM {{ $produto->ncm }} @if($produto->cest) &middot; CEST {{ $produto->cest }} @endif @if($produto->unidade) &middot; Unidade {{ $produto->unidade }} @endif</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('portal.precificacao.produtos.form.edit', $produto->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-[#1e293b] border border-gray-300 dark:border-[#334155] text-gray-700 dark:text-slate-200 rounded-lg text-sm no-underline hover:bg-gray-50 dark:hover:bg-[#334155]">
                    <i class="fa-solid fa-pencil"></i> Editar produto
                </a>
                <a href="{{ route('portal.precificacao.cenarios.form.create', $produto->id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-[#0084AA] text-white rounded-lg text-sm no-underline hover:bg-[#006884]">
                    <i class="fa-solid fa-plus"></i> Novo cenário
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    @if($produto->cenarios->isEmpty())
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-10 text-center text-gray-400 dark:text-slate-500 shadow-sm">
            <p class="text-5xl mb-3">🧮</p>
            <p class="font-medium">Nenhum cenário de compra/venda cadastrado ainda.</p>
            <p class="text-xs mt-1">Crie um cenário informando UF de compra, UF de venda, custo e markup.</p>
        </div>
    @else
        @foreach($produto->cenarios as $cenario)
            @php $r = $resultados[$cenario->id]; @endphp
            <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 bg-gray-50 dark:bg-[#334155] border-b border-gray-200 dark:border-[#475569]">
                    <div>
                        <span class="font-semibold text-gray-800 dark:text-slate-100">{{ $cenario->nome ?: 'Cenário #'.$cenario->id }}</span>
                        <span class="text-xs text-gray-400 dark:text-slate-500 ml-2">{{ $cenario->uf_compra }} &rarr; {{ $cenario->uf_venda }}</span>
                        @if(!$r->aliquotaEncontrada)
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Sem alíquota cadastrada para este NCM/CEST — usando 0%</span>
                        @endif
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('portal.precificacao.cenarios.form.edit', [$produto->id, $cenario->id]) }}" class="text-gray-500 dark:text-slate-400 no-underline hover:text-[#0084AA]">
                            <i class="fa-solid fa-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('portal.precificacao.cenarios.delete', [$produto->id, $cenario->id]) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" onclick="return confirm('Excluir este cenário?')" class="bg-transparent border-0 p-0 text-red-500 hover:text-red-600">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-5">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Custo de compra</p>
                        <dl class="space-y-1 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">Valor de compra</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->valorCompraTotal, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(+) ICMS-ST</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->icmsSt, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(+) IPI</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->ipi, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(+) Frete</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->freteCompra, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(-) Crédito PIS</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->creditoPis, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(-) Crédito COFINS</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->creditoCofins, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between pt-2 border-t border-gray-100 dark:border-[#334155] font-semibold"><dt class="text-gray-700 dark:text-slate-200">Custo total</dt><dd class="text-gray-900 dark:text-slate-100">R$ {{ number_format($r->custoTotal, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between font-semibold"><dt class="text-gray-700 dark:text-slate-200">Custo unitário</dt><dd class="text-gray-900 dark:text-slate-100">R$ {{ number_format($r->custoUnitario, 2, ',', '.') }}</dd></div>
                        </dl>
                    </div>

                    <div>
                        <p class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Preço de venda</p>
                        <dl class="space-y-1 text-sm">
                            <div class="flex justify-between font-semibold"><dt class="text-gray-700 dark:text-slate-200">Preço de venda (markup {{ number_format($cenario->markup_pct, 2, ',', '.') }}%)</dt><dd class="text-gray-900 dark:text-slate-100">R$ {{ number_format($r->precoVenda, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(-) ICMS s/venda</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->icmsVenda, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(-) PIS s/venda</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->pisVenda, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(-) COFINS s/venda</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->cofinsVenda, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(-) Comissão</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->comissao, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(-) Frete s/venda</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->freteVenda, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between"><dt class="text-gray-500 dark:text-slate-400">(-) Custo unitário</dt><dd class="text-gray-800 dark:text-slate-100">R$ {{ number_format($r->custoUnitario, 2, ',', '.') }}</dd></div>
                            <div class="flex justify-between pt-2 border-t border-gray-100 dark:border-[#334155] font-semibold">
                                <dt class="text-gray-700 dark:text-slate-200">Margem de contribuição</dt>
                                <dd class="{{ $r->margemContribuicaoValor >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    R$ {{ number_format($r->margemContribuicaoValor, 2, ',', '.') }} ({{ number_format($r->margemContribuicaoPercentual, 2, ',', '.') }}%)
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
