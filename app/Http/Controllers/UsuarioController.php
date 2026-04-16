<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function showColaboradores(Request $request)
    {
        $query = Usuario::with('departamento')->orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)
                    ->orWhere('email', 'like', $busca);
            });
        }

        if ($request->filled('cargo')) {
            $query->where('cargo', $request->input('cargo'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        $colaboradores = $query->get();

        return view('colaboradores.home', compact('colaboradores'));
    }

    public function formColabCreate(): View
    {
        $departamentos = Departamento::orderBy('nome')->get();

        return view('colaboradores.partials.formUsuario', ['colab' => null, 'departamentos' => $departamentos]);
    }

    public function formColabEdit(int $id): View
    {
        $colab = Usuario::findOrFail($id);
        $departamentos = Departamento::orderBy('nome')->get();

        return view('colaboradores.partials.formUsuario', compact('colab', 'departamentos'));
    }

    /**
     * Save a new colaborador (users table).
     */
    public function saveColab(Request $request)
    {
        $data = $request->only(['nome', 'email', 'senha', 'cargo', 'telefone', 'sexo', 'data_nascimento', 'data_registro', 'status', 'departamento_id']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'senha' => ['required', 'string', 'min:6'],
            'cargo' => ['required', 'in:diretor,supervisor,analista,assistente,auxiliar'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'sexo' => ['nullable', 'in:masculino,feminino,outro'],
            'data_nascimento' => ['nullable', 'date'],
            'data_registro' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
            'departamento_id' => ['nullable', 'exists:departamentos,id'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        Usuario::create([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'senha' => Hash::make($data['senha']),
            'cargo' => $data['cargo'],
            'telefone' => $data['telefone'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'data_registro' => $data['data_registro'] ?? null,
            'status' => isset($data['status']) ? (bool) $data['status'] : true,
            'departamento_id' => $data['departamento_id'] ?? null,
        ]);

        return Redirect::back()->with('success', 'Colaborador criado com sucesso.');
    }

    /**
     * Update an existing colaborador using Eloquent.
     */
    public function updateColab(Request $request, int $id): RedirectResponse
    {
        $usuario = Usuario::findOrFail($id);

        $data = $request->only(['nome', 'email', 'senha', 'cargo', 'telefone', 'sexo', 'data_nascimento', 'data_registro', 'status', 'departamento_id']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email,'.$id],
            'senha' => ['nullable', 'string', 'min:6'],
            'cargo' => ['required', 'in:diretor,supervisor,analista,assistente,auxiliar'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'sexo' => ['nullable', 'in:masculino,feminino,outro'],
            'data_nascimento' => ['nullable', 'date'],
            'data_registro' => ['nullable', 'date'],
            'status' => ['nullable', 'boolean'],
            'departamento_id' => ['nullable', 'exists:departamentos,id'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $update = [
            'nome' => $data['nome'],
            'email' => $data['email'],
            'cargo' => $data['cargo'],
            'telefone' => $data['telefone'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'data_registro' => $data['data_registro'] ?? null,
            'status' => isset($data['status']) ? (bool) $data['status'] : false,
            'departamento_id' => $data['departamento_id'] ?? null,
        ];

        if (! empty($data['senha'])) {
            $update['senha'] = Hash::make($data['senha']);
        }

        $usuario->update($update);

        return Redirect::back()->with('success', 'Colaborador atualizado com sucesso.');
    }

    /**
     * Delete a colaborador by id using Eloquent.
     */
    public function deleteColab(int $id): RedirectResponse
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();

        return Redirect::back()->with('success', 'Colaborador excluído com sucesso.');
    }
}
