<?php

namespace App\Http\Controllers;

use App\Models\Produto;
use Illuminate\Http\JsonResponse;
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
        abort_if(! auth()->user()?->canGerenciarProdutos(), 403);

        return view('produtos.partials.formProduto', ['produto' => null]);
    }

    public function formEdit(int $id): View
    {
        abort_if(! auth()->user()?->canGerenciarProdutos(), 403);

        $produto = Produto::findOrFail($id);

        return view('produtos.partials.formProduto', compact('produto'));
    }

    public function storeInline(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->canGerenciarProdutos(), 403);

        $validator = Validator::make($request->only(['nome', 'descricao']), [
            'nome' => ['required', 'string', 'max:255', 'unique:produtos,nome'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ], [
            'nome.required' => 'O nome do produto é obrigatório.',
            'nome.unique' => 'Já existe um produto com este nome.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $produto = Produto::create([
            'nome' => $request->string('nome'),
            'descricao' => $request->string('descricao') ?: null,
            'ativo' => true,
        ]);

        return response()->json(['id' => $produto->id, 'nome' => $produto->nome]);
    }

    public function save(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarProdutos(), 403);
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
        abort_if(! auth()->user()?->canGerenciarProdutos(), 403);

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
        abort_if(! auth()->user()?->canGerenciarProdutos(), 403);

        Produto::findOrFail($id)->delete();

        return Redirect::route('produtos')->with('success', 'Produto excluído com sucesso.');
    }
}
