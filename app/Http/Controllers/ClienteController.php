<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Produto;
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
        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();

        return view('clientes.partials.formCliente', ['cliente' => null, 'produtos' => $produtos]);
    }

    public function formClienteEdit(int $id): View
    {
        $cliente = Cliente::with('produtos')->findOrFail($id);
        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();

        return view('clientes.partials.formCliente', compact('cliente', 'produtos'));
    }

    public function saveCliente(Request $request): RedirectResponse
    {
        $data = $request->only(['nome', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'status', 'fator_r', 'cliente_desde', 'dataabertura', 'faturamento', 'servico', 'honorario', 'possibilidade']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cpfcnpj' => ['nullable', 'string', 'max:255'],
            'regime_tributario' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'status' => ['nullable', 'string', 'max:255'],
            'fator_r' => ['nullable'],
            'cliente_desde' => ['nullable', 'date'],
            'dataabertura' => ['nullable', 'date'],
            'faturamento' => ['nullable', 'numeric', 'min:0'],
            'servico' => ['nullable', 'string', 'max:255'],
            'honorario' => ['nullable', 'numeric', 'min:0'],
            'possibilidade' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $data['fator_r'] = isset($data['fator_r']);

        Cliente::create($data);

        $cliente = Cliente::query()->latest()->first();
        $cliente->produtos()->sync($request->input('produtos', []));

        return Redirect::route('clientes')->with('success', 'Cliente criado com sucesso.');
    }

    public function updateCliente(Request $request, int $id): RedirectResponse
    {
        $cliente = Cliente::findOrFail($id);

        $data = $request->only(['nome', 'descricao', 'cpfcnpj', 'regime_tributario', 'cidade', 'estado', 'status', 'fator_r', 'cliente_desde', 'dataabertura', 'faturamento', 'servico', 'honorario', 'possibilidade']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
            'cpfcnpj' => ['nullable', 'string', 'max:255'],
            'regime_tributario' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'status' => ['nullable', 'string', 'max:255'],
            'fator_r' => ['nullable'],
            'cliente_desde' => ['nullable', 'date'],
            'dataabertura' => ['nullable', 'date'],
            'faturamento' => ['nullable', 'numeric', 'min:0'],
            'servico' => ['nullable', 'string', 'max:255'],
            'honorario' => ['nullable', 'numeric', 'min:0'],
            'possibilidade' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $data['fator_r'] = isset($data['fator_r']);

        $cliente->update($data);

        $cliente->produtos()->sync($request->input('produtos', []));

        return Redirect::route('clientes')->with('success', 'Cliente atualizado com sucesso.');
    }

    public function deleteCliente(int $id): RedirectResponse
    {
        Cliente::findOrFail($id)->delete();

        return Redirect::route('clientes')->with('success', 'Cliente excluído com sucesso.');
    }
}
