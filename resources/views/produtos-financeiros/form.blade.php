@extends('layouts.internal')

@section('title', ($produto ? 'Editar' : 'Novo') . ' Produto Financeiro — WR Assessoria')

@section('content')
<div class="w-full max-w-2xl mx-auto py-6 px-4">

    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
            <i class="fa-solid fa-box-open text-blue-600"></i>
            {{ $produto ? 'Editar Produto' : 'Novo Produto' }}
        </h1>
        <a href="{{ route('financeiro.produtos.index') }}"
           class="text-sm text-blue-600 dark:text-blue-400 hover:underline no-underline">← Voltar</a>
    </div>

    <form method="POST"
          action="{{ $produto ? route('financeiro.produtos.update', $produto) : route('financeiro.produtos.store') }}"
          class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 p-6 space-y-4">
        @csrf
        @if ($produto) @method('PUT') @endif

        @if ($errors->any())
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded p-3">
                <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                    @foreach ($errors->all() as $err)
                        <li>• {{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Empresa *</label>
            <select name="cliente_id" required
                    class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
                <option value="">Selecione...</option>
                @foreach ($empresas as $emp)
                    <option value="{{ $emp->id }}" {{ old('cliente_id', $produto?->cliente_id) == $emp->id ? 'selected' : '' }}>
                        {{ $emp->nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome *</label>
                <input type="text" name="nome" value="{{ old('nome', $produto?->nome) }}" required
                       class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Código</label>
                <input type="text" name="codigo" value="{{ old('codigo', $produto?->codigo) }}"
                       class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Categoria</label>
                <input type="text" name="categoria" value="{{ old('categoria', $produto?->categoria) }}"
                       class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Preço de Custo</label>
                <input type="number" name="preco_custo" step="0.01" min="0"
                       value="{{ old('preco_custo', $produto?->preco_custo) }}"
                       class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Preço de Venda</label>
                <input type="number" name="preco_venda" step="0.01" min="0"
                       value="{{ old('preco_venda', $produto?->preco_venda) }}"
                       class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estoque Atual</label>
                <input type="number" name="estoque_atual" step="0.001"
                       value="{{ old('estoque_atual', $produto?->estoque_atual) }}"
                       class="w-full text-sm border border-gray-300 dark:border-slate-600 rounded px-3 py-2 bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-100">
            </div>
            <div class="flex items-center gap-2 pt-6">
                <input type="hidden" name="ativo" value="0">
                <input type="checkbox" name="ativo" id="ativo" value="1"
                       {{ old('ativo', $produto?->ativo ?? true) ? 'checked' : '' }}
                       class="rounded">
                <label for="ativo" class="text-sm text-gray-700 dark:text-gray-300">Ativo</label>
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('financeiro.produtos.index') }}"
               class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-slate-600 rounded hover:bg-gray-50 dark:hover:bg-slate-700 no-underline">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 border-0">
                {{ $produto ? 'Salvar alterações' : 'Criar produto' }}
            </button>
        </div>
    </form>
</div>
@endsection
