<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function showClientes(Request $request): View
    {
        $query = Cliente::orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)
                    ->orWhere('cpfcnpj', 'like', $busca)
                    ->orWhere('cidade', 'like', $busca)
                    ->orWhere('estado', 'like', $busca)
                    ->orWhere('regime_tributario', 'like', $busca);
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('regime_tributario')) {
            $query->where('regime_tributario', $request->input('regime_tributario'));
        }

        $clientes = $query->get();

        return view('clientes.home', compact('clientes'));
    }

    public function formClienteCreate(): View
    {
        return view('clientes.partials.formCliente', ['cliente' => null]);
    }

    public function formClienteEdit(int $id): View
    {
        $cliente = Cliente::findOrFail($id);

        return view('clientes.partials.formCliente', compact('cliente'));
    }

    public function saveCliente(Request $request): RedirectResponse
    {
        $data = $request->only(['nome', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'status', 'cliente_desde', 'dataabertura']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cpfcnpj' => ['nullable', 'string', 'max:255'],
            'regime_tributario' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'status' => ['nullable', 'string', 'max:255'],
            'cliente_desde' => ['nullable', 'date'],
            'dataabertura' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        Cliente::create($data);

        return Redirect::route('clientes')->with('success', 'Cliente criado com sucesso.');
    }

    public function updateCliente(Request $request, int $id): RedirectResponse
    {
        $cliente = Cliente::findOrFail($id);

        $data = $request->only(['nome', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'status', 'cliente_desde', 'dataabertura']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cpfcnpj' => ['nullable', 'string', 'max:255'],
            'regime_tributario' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'status' => ['nullable', 'string', 'max:255'],
            'cliente_desde' => ['nullable', 'date'],
            'dataabertura' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $cliente->update($data);

        return Redirect::route('clientes')->with('success', 'Cliente atualizado com sucesso.');
    }

    public function deleteCliente(int $id): RedirectResponse
    {
        Cliente::findOrFail($id)->delete();

        return Redirect::route('clientes')->with('success', 'Cliente excluído com sucesso.');
    }
}
