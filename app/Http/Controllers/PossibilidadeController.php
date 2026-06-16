<?php

namespace App\Http\Controllers;

use App\Models\Possibilidade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PossibilidadeController extends Controller
{
    public function showPossibilidades(Request $request): View
    {
        $query = Possibilidade::orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where('nome', 'like', $busca);
        }

        if ($request->filled('status')) {
            $query->where('ativo', $request->input('status') === 'ativo');
        }

        $possibilidades = $query->get();

        return view('possibilidades.home', compact('possibilidades'));
    }

    public function formCreate(): View
    {
        abort_if(! auth()->user()?->canGerenciarPossibilidades(), 403);

        return view('possibilidades.partials.formPossibilidade', ['possibilidade' => null]);
    }

    public function formEdit(int $id): View
    {
        abort_if(! auth()->user()?->canGerenciarPossibilidades(), 403);

        $possibilidade = Possibilidade::findOrFail($id);

        return view('possibilidades.partials.formPossibilidade', compact('possibilidade'));
    }

    public function storeInline(Request $request): JsonResponse
    {
        abort_if(! auth()->user()?->canGerenciarPossibilidades(), 403);

        $validator = Validator::make($request->only(['nome', 'descricao']), [
            'nome' => ['required', 'string', 'max:255', 'unique:possibilidades,nome'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ], [
            'nome.required' => 'O nome da possibilidade é obrigatório.',
            'nome.unique' => 'Já existe uma possibilidade com este nome.',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $possibilidade = Possibilidade::create([
            'nome' => $request->string('nome'),
            'descricao' => $request->string('descricao') ?: null,
            'ativo' => true,
        ]);

        return response()->json(['id' => $possibilidade->id, 'nome' => $possibilidade->nome]);
    }

    public function save(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPossibilidades(), 403);
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

        Possibilidade::create($data);

        return Redirect::route('possibilidades')->with('success', 'Possibilidade criada com sucesso.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPossibilidades(), 403);

        $possibilidade = Possibilidade::findOrFail($id);

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

        $possibilidade->update($data);

        return Redirect::route('possibilidades')->with('success', 'Possibilidade atualizada com sucesso.');
    }

    public function delete(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPossibilidades(), 403);

        Possibilidade::findOrFail($id)->delete();

        return Redirect::route('possibilidades')->with('success', 'Possibilidade excluída com sucesso.');
    }
}
