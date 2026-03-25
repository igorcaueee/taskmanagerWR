<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class UsuarioController extends Controller
{
    public function showColaboradores(Request $request)
    {
        $colaboradores = Usuario::orderBy('nome')->get();
        return view('colaboradores.home', compact('colaboradores'));
    }

    /**
     * Save a new colaborador (users table).
     */
    public function saveColab(Request $request)
    {
        $data = $request->only(['nome', 'email', 'senha', 'cargo']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:usuarios,email'],
            'senha' => ['required', 'string', 'min:6'],
            'cargo' => ['required', 'in:diretor,supervisor,analista,assistente,auxiliar'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        Usuario::create([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'senha' => Hash::make($data['senha']),
            'cargo' => $data['cargo'],
        ]);

        return Redirect::back()->with('success', 'Colaborador criado com sucesso.');
    }
}
