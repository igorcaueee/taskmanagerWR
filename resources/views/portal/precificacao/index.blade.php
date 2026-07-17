@extends('layouts.portal')

@section('title', 'Precificação de Produtos')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100">Precificação de Produtos</h1>
            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Custo de compra e preço de venda sugerido dos seus produtos.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('portal.precificacao.produtos.import.form') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-[#1e293b] border border-gray-300 dark:border-[#334155] text-gray-700 dark:text-slate-200 rounded-lg text-sm no-underline hover:bg-gray-50 dark:hover:bg-[#334155]">
                <i class="fa-solid fa-file-import"></i> Importar planilha
            </a>
            <a href="{{ route('portal.precificacao.produtos.form.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-[#0084AA] text-white rounded-lg text-sm no-underline hover:bg-[#006884]">
                <i class="fa-solid fa-plus"></i> Novo produto
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="px-4 py-3 bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300 rounded-lg text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="px-4 py-3 bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 rounded-lg text-sm">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('portal.precificacao.index') }}" class="flex">
        <input type="text" name="busca" value="{{ request('busca') }}" placeholder="Buscar por nome, NCM ou CEST..."
               class="w-full max-w-sm rounded-lg border border-gray-300 dark:border-[#334155] bg-white dark:bg-[#0f172a] text-gray-800 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0084AA]">
    </form>

    @if($resumo->isEmpty())
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-10 text-center text-gray-400 dark:text-slate-500 shadow-sm">
            <p class="text-5xl mb-3">📦</p>
            <p class="font-medium">Nenhum produto cadastrado ainda.</p>
            <p class="text-xs mt-1">Cadastre manualmente ou importe uma planilha para começar.</p>
        </div>
    @else
        <div class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl shadow-sm overflow-hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-[#334155]">
                <thead class="bg-gray-50 dark:bg-[#334155]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Produto</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">NCM / CEST</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Custo unitário</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Preço sugerido</th>
                        <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Margem</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#334155]">
                    @foreach($resumo as $item)
                        @php $produto = $item['produto']; $resultado = $item['resultado']; @endphp
                        <tr>
                            <td class="px-5 py-4 text-sm text-gray-800 dark:text-slate-100 whitespace-nowrap">
                                <a href="{{ route('portal.precificacao.show', $produto->id) }}" class="text-[#0084AA] no-underline hover:underline">{{ $produto->nome }}</a>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-slate-300 whitespace-nowrap">{{ $produto->ncm }} @if($produto->cest) / {{ $produto->cest }} @endif</td>
                            @if($resultado)
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-slate-100 whitespace-nowrap">R$ {{ number_format($resultado->custoUnitario, 2, ',', '.') }}</td>
                                <td class="px-5 py-4 text-sm text-gray-800 dark:text-slate-100 whitespace-nowrap">R$ {{ number_format($resultado->precoVenda, 2, ',', '.') }}</td>
                                <td class="px-5 py-4 text-sm whitespace-nowrap">
                                    <span class="{{ $resultado->margemContribuicaoValor >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ number_format($resultado->margemContribuicaoPercentual, 2, ',', '.') }}%
                                    </span>
                                </td>
                            @else
                                <td class="px-5 py-4 text-sm text-gray-400 dark:text-slate-500" colspan="3">Nenhum cenário de compra cadastrado ainda.</td>
                            @endif
                            <td class="px-5 py-4 text-sm text-right whitespace-nowrap">
                                <a href="{{ route('portal.precificacao.show', $produto->id) }}" class="text-gray-500 dark:text-slate-400 no-underline hover:text-[#0084AA]">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
