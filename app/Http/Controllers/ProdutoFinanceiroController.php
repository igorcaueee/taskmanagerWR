<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ProdutoFinanceiro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProdutoFinanceiroController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $empresas  = Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']);
        $clienteId = $request->integer('cliente_id') ?: null;

        $produtos = ProdutoFinanceiro::with('cliente')
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId))
            ->orderBy('nome')
            ->paginate(50)
            ->withQueryString();

        return view('produtos-financeiros.index', compact('produtos', 'empresas', 'clienteId'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $empresas = Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']);

        return view('produtos-financeiros.form', ['produto' => null, 'empresas' => $empresas]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $data = $request->validate([
            'cliente_id'    => ['required', 'exists:clientes,id'],
            'nome'          => ['required', 'string', 'max:255'],
            'codigo'        => ['nullable', 'string', 'max:100'],
            'categoria'     => ['nullable', 'string', 'max:255'],
            'preco_custo'   => ['nullable', 'numeric', 'min:0'],
            'preco_venda'   => ['nullable', 'numeric', 'min:0'],
            'estoque_atual' => ['nullable', 'numeric'],
            'ativo'         => ['boolean'],
        ]);

        ProdutoFinanceiro::create($data);

        return redirect()->route('financeiro.produtos.index')
            ->with('success', 'Produto criado com sucesso.');
    }

    public function edit(ProdutoFinanceiro $produtoFinanceiro): View
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $empresas = Cliente::where('status', 'ativo')->orderBy('nome')->get(['id', 'nome']);

        return view('produtos-financeiros.form', ['produto' => $produtoFinanceiro, 'empresas' => $empresas]);
    }

    public function update(Request $request, ProdutoFinanceiro $produtoFinanceiro): RedirectResponse
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $data = $request->validate([
            'cliente_id'    => ['required', 'exists:clientes,id'],
            'nome'          => ['required', 'string', 'max:255'],
            'codigo'        => ['nullable', 'string', 'max:100'],
            'categoria'     => ['nullable', 'string', 'max:255'],
            'preco_custo'   => ['nullable', 'numeric', 'min:0'],
            'preco_venda'   => ['nullable', 'numeric', 'min:0'],
            'estoque_atual' => ['nullable', 'numeric'],
            'ativo'         => ['boolean'],
        ]);

        $produtoFinanceiro->update($data);

        return redirect()->route('financeiro.produtos.index')
            ->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(ProdutoFinanceiro $produtoFinanceiro): RedirectResponse
    {
        abort_unless(auth()->user()?->canGerenciarFinanceiro(), 403);

        $produtoFinanceiro->delete();

        return redirect()->route('financeiro.produtos.index')
            ->with('success', 'Produto excluído.');
    }
}
