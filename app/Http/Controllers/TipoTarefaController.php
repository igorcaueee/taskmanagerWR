<?php

namespace App\Http\Controllers;

use App\Models\TipoTarefa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TipoTarefaController extends Controller
{
    public function index(Request $request): View
    {
        $query = TipoTarefa::orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where('nome', 'like', $busca);
        }

        $tipos = $query->withCount('tarefas')->get();

        return view('tipos-tarefa.home', compact('tipos'));
    }

    public function formCreate(): View
    {
        return view('tipos-tarefa.partials.formTipoTarefa', ['tipo' => null]);
    }

    public function formEdit(int $id): View
    {
        $tipo = TipoTarefa::findOrFail($id);

        return view('tipos-tarefa.partials.formTipoTarefa', compact('tipo'));
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->only(['nome', 'titulo_padrao', 'data_vencimento']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'titulo_padrao' => ['nullable', 'string', 'max:255'],
            'data_vencimento' => ['nullable', 'date'],
        ], [
            'nome.required' => 'O nome do tipo é obrigatório.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        TipoTarefa::create($data);

        return Redirect::route('tipos-tarefa.index')->with('success', 'Tipo de tarefa criado com sucesso.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $tipo = TipoTarefa::findOrFail($id);

        $data = $request->only(['nome', 'titulo_padrao', 'data_vencimento']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'titulo_padrao' => ['nullable', 'string', 'max:255'],
            'data_vencimento' => ['nullable', 'date'],
        ], [
            'nome.required' => 'O nome do tipo é obrigatório.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $tipo->update($data);

        return Redirect::route('tipos-tarefa.index')->with('success', 'Tipo de tarefa atualizado com sucesso.');
    }

    public function delete(int $id): RedirectResponse
    {
        $tipo = TipoTarefa::withCount('tarefas')->findOrFail($id);

        if ($tipo->tarefas_count > 0) {
            return Redirect::back()->with('error', "Não é possível excluir: existem {$tipo->tarefas_count} tarefa(s) vinculada(s) a este tipo.");
        }

        $tipo->delete();

        return Redirect::route('tipos-tarefa.index')->with('success', 'Tipo de tarefa excluído com sucesso.');
    }
}
