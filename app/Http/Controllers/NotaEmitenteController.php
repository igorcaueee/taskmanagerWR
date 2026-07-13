<?php

namespace App\Http\Controllers;

use App\Models\NotaEmitente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class NotaEmitenteController extends Controller
{
    public function index(Request $request): View
    {
        $query = NotaEmitente::with('cliente:id,nome,cpfcnpj')->withCount('notasEmitidas');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)
                    ->orWhere('cpfcnpj', 'like', $busca)
                    ->orWhereHas('cliente', fn ($q) => $q->where('nome', 'like', $busca)->orWhere('cpfcnpj', 'like', $busca));
            });
        }

        $emitentes = $query->get()->sortBy('nome_exibicao')->values();

        return view('notas-emitidas.emitentes.index', compact('emitentes'));
    }

    public function formCreate(): View
    {
        return view('notas-emitidas.emitentes.partials.form', ['emitente' => null]);
    }

    public function formEdit(int $id): View
    {
        $emitente = NotaEmitente::with('cliente:id,nome,cpfcnpj')->findOrFail($id);

        return view('notas-emitidas.emitentes.partials.form', compact('emitente'));
    }

    public function save(Request $request): RedirectResponse
    {
        if ($request->input('modo') === 'cliente') {
            return $this->salvarClientesSelecionados($request);
        }

        $validator = $this->validador($request);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        NotaEmitente::create($this->dadosParaSalvar($validator->validated()));

        return Redirect::route('notas-emitidas.emitentes.index')->with('success', 'Cadastro criado com sucesso.');
    }

    private function salvarClientesSelecionados(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cliente_ids' => ['required', 'array', 'min:1'],
            'cliente_ids.*' => ['integer', 'distinct', 'exists:clientes,id'],
        ], [
            'cliente_ids.required' => 'Selecione ao menos um cliente.',
        ]);

        $jaCadastrados = NotaEmitente::whereIn('cliente_id', $data['cliente_ids'])->pluck('cliente_id')->all();
        $novos = array_diff($data['cliente_ids'], $jaCadastrados);

        foreach ($novos as $clienteId) {
            NotaEmitente::create(['cliente_id' => $clienteId]);
        }

        $mensagem = count($novos) === 1 ? '1 cliente adicionado.' : count($novos).' clientes adicionados.';
        if (! empty($jaCadastrados)) {
            $mensagem .= ' '.count($jaCadastrados).' já estava(m) cadastrado(s) e foi(ram) ignorado(s).';
        }

        return Redirect::route('notas-emitidas.emitentes.index')->with('success', $mensagem);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $emitente = NotaEmitente::findOrFail($id);

        $validator = $this->validador($request, $emitente->id);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $emitente->update($this->dadosParaSalvar($validator->validated()));

        return Redirect::route('notas-emitidas.emitentes.index')->with('success', 'Cadastro atualizado com sucesso.');
    }

    public function toggleAtivo(int $id): RedirectResponse
    {
        $emitente = NotaEmitente::findOrFail($id);
        $emitente->update(['ativo' => ! $emitente->ativo]);

        return Redirect::route('notas-emitidas.emitentes.index')
            ->with('success', $emitente->ativo ? 'Cadastro reativado.' : 'Cadastro desativado.');
    }

    public function delete(int $id): RedirectResponse
    {
        $emitente = NotaEmitente::withCount('notasEmitidas')->findOrFail($id);

        if ($emitente->notas_emitidas_count > 0) {
            return Redirect::back()->with('error', 'Não é possível excluir: existem notas emitidas registradas para este cadastro. Desative-o em vez de excluir.');
        }

        $emitente->delete();

        return Redirect::route('notas-emitidas.emitentes.index')->with('success', 'Cadastro excluído com sucesso.');
    }

    private function validador(Request $request, ?int $ignorarId = null): \Illuminate\Contracts\Validation\Validator
    {
        $data = $request->only(['cliente_id', 'nome', 'cpfcnpj']);

        return Validator::make($data, [
            'cliente_id' => ['nullable', 'exists:clientes,id', 'unique:nota_emitentes,cliente_id,'.($ignorarId ?? 'NULL').',id'],
            'nome' => ['required_without:cliente_id', 'nullable', 'string', 'max:255'],
            'cpfcnpj' => ['nullable', 'string', 'max:20'],
        ], [
            'cliente_id.unique' => 'Este cliente já está cadastrado na lista.',
            'nome.required_without' => 'Informe o nome ou selecione um cliente já cadastrado.',
        ]);
    }

    private function dadosParaSalvar(array $data): array
    {
        // Quando vinculado a um cliente, nome/cpfcnpj vêm sempre do cadastro de clientes.
        if (! empty($data['cliente_id'])) {
            $data['nome'] = null;
            $data['cpfcnpj'] = null;
        }

        return $data;
    }
}
