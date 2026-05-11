<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteConhecimento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ClienteConhecimentoController extends Controller
{
    public function modal(int $clienteId): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($clienteId);
        $conhecimentos = ClienteConhecimento::where('cliente_id', $clienteId)
            ->orderByDesc('created_at')
            ->get();

        return view('clientes.partials.conhecimentos', compact('cliente', 'conhecimentos'));
    }

    public function formCreate(int $clienteId): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($clienteId);

        return view('clientes.partials.formConhecimento', ['conhecimento' => null, 'cliente' => $cliente]);
    }

    public function store(Request $request, int $clienteId): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $cliente = Cliente::findOrFail($clienteId);

        $validator = Validator::make($request->all(), [
            'titulo' => ['required', 'string', 'max:255'],
            'conteudo' => ['required', 'string'],
        ], [
            'titulo.required' => 'O título é obrigatório.',
            'conteudo.required' => 'O conteúdo é obrigatório.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        ClienteConhecimento::create([
            'cliente_id' => $cliente->id,
            'titulo' => $request->string('titulo')->toString(),
            'conteudo' => $request->string('conteudo')->toString(),
        ]);

        return Redirect::route('clientes')
            ->with('success', 'Conhecimento adicionado com sucesso.');
    }

    public function formEdit(int $id): View
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $conhecimento = ClienteConhecimento::with('cliente')->findOrFail($id);

        return view('clientes.partials.formConhecimento', ['conhecimento' => $conhecimento, 'cliente' => $conhecimento->cliente]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $conhecimento = ClienteConhecimento::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'titulo' => ['required', 'string', 'max:255'],
            'conteudo' => ['required', 'string'],
        ], [
            'titulo.required' => 'O título é obrigatório.',
            'conteudo.required' => 'O conteúdo é obrigatório.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        $conhecimento->update([
            'titulo' => $request->string('titulo')->toString(),
            'conteudo' => $request->string('conteudo')->toString(),
        ]);

        return Redirect::route('clientes')
            ->with('success', 'Conhecimento atualizado com sucesso.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canEditarClientes(), 403);

        $conhecimento = ClienteConhecimento::findOrFail($id);
        $clienteId = $conhecimento->cliente_id;
        $conhecimento->delete();

        return Redirect::route('clientes')
            ->with('success', 'Conhecimento removido com sucesso.');
    }
}
