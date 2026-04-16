<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    public function showProdutos(Request $request): View
    {
        $query = Produto::orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where('nome', 'like', $busca);
        }

        if ($request->filled('status')) {
            $query->where('ativo', $request->input('status') === 'ativo');
        }

        $produtos = $query->get();

        return view('produtos.home', compact('produtos'));
    }

    public function formCreate(): View
    {
        return view('produtos.partials.formProduto', ['produto' => null]);
    }

    public function formEdit(int $id): View
    {
        $produto = Produto::findOrFail($id);

        return view('produtos.partials.formProduto', compact('produto'));
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->only(['nome', 'descricao', 'ativo']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $data['ativo'] = isset($data['ativo']);

        Produto::create($data);

        return Redirect::route('produtos')->with('success', 'Produto criado com sucesso.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $produto = Produto::findOrFail($id);

        $data = $request->only(['nome', 'descricao', 'ativo']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $data['ativo'] = isset($data['ativo']);

        $produto->update($data);

        return Redirect::route('produtos')->with('success', 'Produto atualizado com sucesso.');
    }

    public function delete(int $id): RedirectResponse
    {
        Produto::findOrFail($id)->delete();

        return Redirect::route('produtos')->with('success', 'Produto excluído com sucesso.');
    }
}
