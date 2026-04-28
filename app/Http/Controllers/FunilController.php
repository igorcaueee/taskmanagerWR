<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\EtapaFunil;
use App\Models\HistoricoFunil;
use App\Models\Lead;
use App\Models\Produto;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class FunilController extends Controller
{
    public function showFunil(Request $request): View
    {
        $etapas = EtapaFunil::orderBy('ordem')->get();

        $query = Lead::with(['etapaFunil', 'responsavel'])->orderBy('created_at', 'desc');

        if ($request->filled('responsavel_id')) {
            $query->where('responsavel_id', $request->integer('responsavel_id'));
        }

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)
                    ->orWhere('empresa', 'like', $busca);
            });
        }

        $leads = $query->get()->groupBy('etapa_funil_id');

        $usuario = Auth::user();
        $podeVerTodos = in_array($usuario->cargo, ['diretor', 'supervisor']);
        $usuarios = $podeVerTodos ? Usuario::orderBy('nome')->get() : collect();

        return view('funil.kanban', compact('etapas', 'leads', 'usuarios', 'podeVerTodos'));
    }

    public function formCreate(): View
    {
        $etapas = EtapaFunil::orderBy('ordem')->get();
        $usuarios = Usuario::orderBy('nome')->get();
        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();
        $etapaDefault = $etapas->first()?->id;

        return view('funil.partials.formLead', [
            'lead' => null,
            'etapas' => $etapas,
            'usuarios' => $usuarios,
            'produtos' => $produtos,
            'etapaDefault' => $etapaDefault,
        ]);
    }

    public function formEdit(int $id): View
    {
        $lead = Lead::with([
            'historico.etapaAnterior',
            'historico.etapaNova',
            'historico.alteradoPor',
            'produtos',
        ])->findOrFail($id);

        $etapas = EtapaFunil::orderBy('ordem')->get();
        $usuarios = Usuario::orderBy('nome')->get();
        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();

        return view('funil.partials.formLead', compact('lead', 'etapas', 'usuarios', 'produtos'));
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->only([
            'nome', 'email', 'telefone', 'empresa',
            'tipo', 'cpfcnpj',
            'faturamento', 'honorario', 'possibilidade',
            'etapa_funil_id', 'responsavel_id', 'observacoes',
        ]);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'empresa' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'integer', 'in:0,1'],
            'cpfcnpj' => ['nullable', 'string', 'max:18'],
            'faturamento' => ['nullable', 'numeric', 'min:0'],
            'honorario' => ['nullable', 'numeric', 'min:0'],
            'possibilidade' => ['nullable', 'string'],
            'etapa_funil_id' => ['required', 'exists:etapas_funil,id'],
            'responsavel_id' => ['nullable', 'exists:usuarios,id'],
            'observacoes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $lead = Lead::create(array_merge($data, ['origem' => 'manual']));
        $lead->produtos()->sync($request->input('produtos', []));

        return Redirect::back()->with('success', 'Lead criado com sucesso.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $lead = Lead::findOrFail($id);

        $data = $request->only([
            'nome', 'email', 'telefone', 'empresa',
            'tipo', 'cpfcnpj',
            'faturamento', 'honorario', 'possibilidade',
            'etapa_funil_id', 'responsavel_id', 'observacoes',
        ]);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'empresa' => ['nullable', 'string', 'max:255'],
            'tipo' => ['nullable', 'integer', 'in:0,1'],
            'cpfcnpj' => ['nullable', 'string', 'max:18'],
            'faturamento' => ['nullable', 'numeric', 'min:0'],
            'honorario' => ['nullable', 'numeric', 'min:0'],
            'possibilidade' => ['nullable', 'string'],
            'etapa_funil_id' => ['required', 'exists:etapas_funil,id'],
            'responsavel_id' => ['nullable', 'exists:usuarios,id'],
            'observacoes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $etapaAnteriorId = $lead->etapa_funil_id;

        $lead->update($data);
        $lead->produtos()->sync($request->input('produtos', []));

        if ((int) $etapaAnteriorId !== (int) $data['etapa_funil_id']) {
            $descricao = $request->input('descricao_historico');

            HistoricoFunil::create([
                'lead_id' => $lead->id,
                'etapa_anterior_id' => $etapaAnteriorId,
                'etapa_nova_id' => $data['etapa_funil_id'],
                'descricao' => $descricao ?: null,
                'alterado_por' => Auth::id(),
            ]);
        }

        return Redirect::back()->with('success', 'Lead atualizado com sucesso.');
    }

    public function updateEtapa(Request $request, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);

        $validator = Validator::make($request->only('etapa_funil_id', 'descricao'), [
            'etapa_funil_id' => ['required', 'exists:etapas_funil,id'],
            'descricao' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $etapaAnteriorId = $lead->etapa_funil_id;
        $novaEtapa = EtapaFunil::findOrFail($request->integer('etapa_funil_id'));

        $lead->update(['etapa_funil_id' => $novaEtapa->id]);

        HistoricoFunil::create([
            'lead_id' => $lead->id,
            'etapa_anterior_id' => $etapaAnteriorId,
            'etapa_nova_id' => $novaEtapa->id,
            'descricao' => $request->input('descricao') ?: null,
            'alterado_por' => Auth::id(),
        ]);

        $isCliente = strtolower(trim($novaEtapa->nome)) === 'cliente';

        return response()->json([
            'success' => true,
            'sugerir_conversao' => $isCliente,
        ]);
    }

    public function detalhe(int $id): JsonResponse
    {
        $lead = Lead::with([
            'etapaFunil',
            'responsavel',
            'historico.etapaAnterior',
            'historico.etapaNova',
            'historico.alteradoPor',
        ])->findOrFail($id);

        return response()->json([
            'id' => $lead->id,
            'nome' => $lead->nome,
            'email' => $lead->email,
            'telefone' => $lead->telefone,
            'empresa' => $lead->empresa,
            'faturamento' => $lead->faturamento,
            'servico' => $lead->servico,
            'honorario' => $lead->honorario,
            'possibilidade' => $lead->possibilidade,
            'observacoes' => $lead->observacoes,
            'origem' => $lead->origem,
            'responsavel' => $lead->responsavel?->nome,
            'convertido' => ! is_null($lead->convertido_cliente_id),
            'etapa' => [
                'id' => $lead->etapaFunil?->id,
                'nome' => $lead->etapaFunil?->nome,
                'cor' => $lead->etapaFunil?->cor ?? '#6b7280',
            ],
            'criado_em' => $lead->created_at?->format('d/m/Y H:i'),
            'historico' => $lead->historico->sortByDesc('created_at')->map(fn ($h) => [
                'etapa_anterior_id' => $h->etapaAnterior?->id,
                'etapa_anterior' => $h->etapaAnterior?->nome,
                'etapa_nova' => $h->etapaNova?->nome,
                'etapa_nova_cor' => $h->etapaNova?->cor ?? '#6b7280',
                'descricao' => $h->descricao,
                'alterado_por' => $h->alteradoPor?->nome,
                'data' => $h->created_at->format('d/m/Y H:i'),
            ])->values(),
        ]);
    }

    public function formConversao(int $id): View
    {
        $lead = Lead::with('produtos')->findOrFail($id);
        $produtos = Produto::where('ativo', true)->orderBy('nome')->get();

        $prefill = [
            'nome' => $lead->empresa ?: $lead->nome,
            'tipo' => $lead->tipo !== null ? (string) $lead->tipo : '1',
            'cpfcnpj' => $lead->cpfcnpj ?? '',
            'dataabertura' => now()->toDateString(),
            'cliente_desde' => now()->toDateString(),
            'descricao' => $lead->observacoes ?? '',
            'faturamento' => $lead->faturamento ?? '',
            'honorario' => $lead->honorario ?? '',
            'possibilidade' => $lead->possibilidade ?? '',
            'status' => 'ativo',
            'produtos' => $lead->produtos->pluck('id')->toArray(),
        ];

        return view('clientes.partials.formCliente', [
            'cliente' => null,
            'produtos' => $produtos,
            'prefill' => $prefill,
            'overrideAction' => route('leads.converter', $id),
            'formTitle' => 'Converter em cliente',
        ]);
    }

    public function converterParaCliente(Request $request, int $id): JsonResponse|RedirectResponse
    {
        $lead = Lead::findOrFail($id);

        if (! is_null($lead->convertido_cliente_id)) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Lead já foi convertido em cliente.'], 422);
            }

            return Redirect::route('funil')->with('error', 'Lead já foi convertido em cliente.');
        }

        $data = $request->only([
            'nome', 'cpfcnpj', 'tipo', 'regime_tributario', 'cidade', 'estado',
            'dataabertura', 'cliente_desde', 'descricao', 'faturamento', 'servico',
            'honorario', 'possibilidade', 'status', 'fator_r',
        ]);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'cpfcnpj' => ['required', 'string', 'max:18'],
            'tipo' => ['nullable', 'in:0,1'],
            'regime_tributario' => ['required_if:tipo,1', 'nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'dataabertura' => ['nullable', 'date'],
            'cliente_desde' => ['nullable', 'date'],
            'descricao' => ['nullable', 'string'],
            'faturamento' => ['required', 'numeric', 'min:0'],
            'servico' => ['nullable', 'string', 'max:255'],
            'honorario' => ['required', 'numeric', 'min:0'],
            'possibilidade' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'fator_r' => ['nullable'],
        ], [
            'cpfcnpj.required' => 'O CPF/CNPJ é obrigatório.',
            'regime_tributario.required_if' => 'O regime tributário é obrigatório para Pessoa Jurídica.',
            'faturamento.required' => 'O faturamento é obrigatório.',
            'honorario.required' => 'O honorário é obrigatório.',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            return Redirect::route('funil')->with('validation_error', $validator->errors()->first());
        }

        try {
            $cliente = Cliente::create([
                'nome' => $data['nome'] ?? ($lead->empresa ?: $lead->nome),
                'cpfcnpj' => $data['cpfcnpj'] ?? $lead->cpfcnpj ?? '',
                'tipo' => isset($data['tipo']) ? (string) $data['tipo'] : ($lead->tipo !== null ? (string) $lead->tipo : '1'),
                'regime_tributario' => $data['regime_tributario'] ?? null,
                'cidade' => $data['cidade'] ?? null,
                'estado' => $data['estado'] ?? null,
                'dataabertura' => $data['dataabertura'] ?? now()->toDateString(),
                'cliente_desde' => $data['cliente_desde'] ?? now()->toDateString(),
                'descricao' => $data['descricao'] ?? $lead->observacoes,
                'faturamento' => $data['faturamento'] ?? $lead->faturamento,
                'honorario' => $data['honorario'] ?? $lead->honorario,
                'possibilidade' => $data['possibilidade'] ?? $lead->possibilidade,
                'status' => $data['status'] ?? 'ativo',
                'fator_r' => ! empty($data['fator_r']),
            ]);
        } catch (UniqueConstraintViolationException) {
            $cpfcnpj = $data['cpfcnpj'] ?? $lead->cpfcnpj ?? '';

            if ($request->wantsJson()) {
                return response()->json(['error' => "Já existe um cliente cadastrado com o CPF/CNPJ \"{$cpfcnpj}\"."], 422);
            }

            return Redirect::route('funil')->with('cpfcnpj_duplicado', $cpfcnpj);
        }

        $produtosIds = $request->input('produtos', []);
        if (empty($produtosIds)) {
            $produtosIds = $lead->produtos()->pluck('produtos.id')->toArray();
        }
        if (! empty($produtosIds)) {
            $cliente->produtos()->sync($produtosIds);
        }

        $lead->update(['convertido_cliente_id' => $cliente->id]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'cliente_id' => $cliente->id,
                'cliente_nome' => $cliente->nome,
            ]);
        }

        return Redirect::route('funil')->with('success', "\"{$cliente->nome}\" convertido em cliente com sucesso!");
    }

    public function delete(int $id): RedirectResponse
    {
        Lead::findOrFail($id)->delete();

        return Redirect::route('funil')->with('success', 'Lead excluído com sucesso.');
    }
}
