@extends('layouts.internal')

@section('title', 'Relatório de Produtos — WR Assessoria')

@section('content')
    <div class="py-6 px-6">

        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><i class="fa-solid fa-box-open"></i> Relatório de Produtos</h1>
                <p class="text-gray-700">Veja quais clientes contratam cada produto/serviço e identifique oportunidades de venda.</p>
            </div>
        </div>

        {{-- Filtro por produto --}}
        <form method="GET" action="{{ route('relatorios.produtos') }}" id="form-relatorio-produtos"
              class="bg-white rounded shadow px-4 py-3 mb-6 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Produto</label>
                <select name="produto_id" class="border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-brand">
                    <option value="">— Selecione um produto —</option>
                    @foreach($produtos as $produto)
                        <option value="{{ $produto->id }}" @selected(request('produto_id') == $produto->id)>
                            {{ $produto->nome }} ({{ $produto->clientes_count }} {{ $produto->clientes_count === 1 ? 'cliente' : 'clientes' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-1.5 bg-brand text-white rounded border-0 text-sm focus:outline-none hover:bg-brand/80">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
        </form>

        {{-- KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Total de Produtos</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $produtos->count() }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Produtos Ativos</p>
                <p class="text-2xl font-bold text-green-600 mt-1">{{ $produtos->where('ativo', true)->count() }}</p>
            </div>
            <div class="bg-white rounded shadow p-4">
                <p class="text-xs text-gray-500 uppercase tracking-wide">Clientes sem Produtos</p>
                <p class="text-2xl font-bold text-orange-600 mt-1">{{ $clientesSemProdutos->count() }}</p>
            </div>
        </div>

        {{-- Tabela de produtos com contagem --}}
        <div class="bg-white rounded shadow overflow-x-auto mb-6">
            <div class="px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-700">Produtos e quantidade de clientes</h2>
            </div>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Produto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clientes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($produtos as $produto)
                        <tr>
                            <td class="px-6 py-3 text-sm text-gray-700">{{ $produto->nome }}</td>
                            <td class="px-6 py-3 text-sm text-gray-500">{{ $produto->descricao ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm">
                                @if($produto->ativo)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-sm font-semibold text-gray-900">{{ $produto->clientes_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum produto cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Clientes do produto selecionado --}}
        @if($produtoFiltro)
            <div class="bg-white rounded shadow overflow-x-auto mb-6">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-filter mr-1 text-brand"></i>
                        Clientes que contratam: <span class="text-brand">{{ $produtoFiltro->nome }}</span>
                        ({{ $clientesDoProduto->count() }})
                    </h2>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF/CNPJ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cidade/UF</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($clientesDoProduto as $cliente)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $cliente->nome }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $cliente->cpfcnpj ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $cliente->cidade ?? '—' }}{{ $cliente->estado ? '/' . $cliente->estado : '' }}</td>
                                <td class="px-6 py-3 text-sm">
                                    @if($cliente->status === 'ativo')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">Nenhum cliente encontrado para este produto.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Clientes sem nenhum produto --}}
        @if($clientesSemProdutos->isNotEmpty())
            <div class="bg-white rounded shadow overflow-x-auto">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="text-sm font-semibold text-gray-700">
                        <i class="fa-solid fa-triangle-exclamation mr-1 text-orange-500"></i>
                        Clientes ativos sem nenhum produto associado ({{ $clientesSemProdutos->count() }})
                    </h2>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF/CNPJ</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cidade/UF</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($clientesSemProdutos as $cliente)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $cliente->nome }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $cliente->cpfcnpj ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $cliente->cidade ?? '—' }}{{ $cliente->estado ? '/' . $cliente->estado : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
