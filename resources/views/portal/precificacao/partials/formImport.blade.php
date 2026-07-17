@extends('layouts.portal')

@section('title', 'Importar Produtos')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <div>
        <a href="{{ route('portal.precificacao.index') }}" class="text-sm text-[#0084AA] no-underline hover:underline">&larr; Voltar para Precificação</a>
        <h1 class="text-2xl font-bold text-gray-800 dark:text-slate-100 mt-2">Importar Produtos</h1>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 text-sm text-blue-800 dark:text-blue-300">
        <p class="font-medium mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Instruções</p>
        <ul class="list-disc list-inside space-y-0.5">
            <li>Faça o download do modelo abaixo e preencha com seus produtos.</li>
            <li>As colunas <strong>nome</strong> e <strong>ncm</strong> são obrigatórias.</li>
            <li>Se você preencher <strong>valor_compra</strong> e <strong>quantidade</strong>, um cenário de compra/venda já é criado automaticamente.</li>
            <li>Se <strong>uf_venda</strong> ficar em branco, será usada a UF do seu cadastro.</li>
        </ul>
    </div>

    <div>
        <a href="{{ route('portal.precificacao.produtos.import.template') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-[#1e293b] border border-gray-300 dark:border-[#334155] text-gray-700 dark:text-slate-200 rounded-lg text-sm no-underline hover:bg-gray-50 dark:hover:bg-[#334155]">
            <i class="fa-solid fa-download text-green-600"></i> Baixar modelo (.xlsx)
        </a>
    </div>

    <form method="POST" action="{{ route('portal.precificacao.produtos.import') }}" enctype="multipart/form-data"
          class="bg-white dark:bg-[#1e293b] border border-gray-200 dark:border-[#334155] rounded-xl p-6 shadow-sm space-y-5">
        @csrf

        <div>
            <label for="arquivo" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Arquivo Excel (.xlsx)</label>
            <input type="file" name="arquivo" id="arquivo" accept=".xlsx,.xls" required
                   class="w-full text-sm text-gray-600 dark:text-slate-300 border border-gray-300 dark:border-[#334155] rounded-lg px-3 py-2 bg-white dark:bg-[#0f172a] file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-[#0084AA] file:text-white hover:file:bg-[#006884]">
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('portal.precificacao.index') }}" class="px-4 py-2 text-sm text-gray-700 dark:text-slate-200 bg-white dark:bg-[#1e293b] border border-gray-300 dark:border-[#334155] rounded-lg no-underline hover:bg-gray-50 dark:hover:bg-[#334155]">
                Cancelar
            </a>
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-[#0084AA] text-white text-sm rounded-lg border-0 hover:bg-[#006884]">
                <i class="fa-solid fa-upload"></i> Importar
            </button>
        </div>
    </form>
</div>
@endsection
