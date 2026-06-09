<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Lead;
use App\Models\Questionario;
use App\Models\QuestionarioResposta;
use App\Models\QuestionarioRespostaItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class QuestionarioController extends Controller
{
    // ─── Admin ───────────────────────────────────────────────────────────────

    public function index(): View
    {
        $questionarios = Questionario::withCount(['respostas' => fn($q) => $q->where('finalizado', true)])->get();

        return view('classificacao.questionarios.index', compact('questionarios'));
    }

    public function show(int $id, Request $request): View
    {
        $questionario = Questionario::findOrFail($id);

        $query = QuestionarioResposta::with(['lead', 'cliente'])
            ->where('questionario_id', $id)
            ->where('finalizado', true)
            ->orderBy('created_at', 'desc');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('respondente_nome', 'like', $busca)
                  ->orWhere('respondente_empresa', 'like', $busca)
                  ->orWhere('respondente_email', 'like', $busca);
            });
        }

        if ($request->filled('classificacao')) {
            $query->where('classificacao', $request->string('classificacao'));
        }

        $respostas = $query->paginate(20)->withQueryString();

        return view('classificacao.questionarios.show', compact('questionario', 'respostas'));
    }

    public function detalheResposta(int $respostaId): View
    {
        $resposta = QuestionarioResposta::with([
            'questionario',
            'lead',
            'cliente',
            'itens.pergunta',
            'itens.opcao',
        ])->findOrFail($respostaId);

        $porCategoria = $resposta->itens->groupBy('pergunta.categoria');

        return view('classificacao.questionarios.detalhe', compact('resposta', 'porCategoria'));
    }

    public function deleteResposta(int $id): RedirectResponse
    {
        $resposta = QuestionarioResposta::findOrFail($id);
        $questionarioId = $resposta->questionario_id;
        $resposta->delete();

        return redirect()->route('questionarios.show', $questionarioId)
            ->with('success', 'Resposta excluída com sucesso.');
    }

    public function enviarLink(int $questionarioId, Request $request): JsonResponse
    {
        $request->validate([
            'tipo'        => 'required|in:lead,cliente',
            'vinculo_id'  => 'required|integer',
            'responsavel' => 'required|string|max:255',
        ]);

        $questionario = Questionario::findOrFail($questionarioId);

        $leadId    = null;
        $clienteId = null;
        $empresa   = null;

        if ($request->tipo === 'lead') {
            $lead      = Lead::findOrFail($request->vinculo_id);
            $leadId    = $lead->id;
            $empresa   = $lead->empresa ?? $lead->nome;
        } else {
            $cliente   = Cliente::findOrFail($request->vinculo_id);
            $clienteId = $cliente->id;
            $empresa   = $cliente->nome;
        }

        $token = (string) Str::uuid();

        QuestionarioResposta::create([
            'questionario_id'     => $questionario->id,
            'lead_id'             => $leadId,
            'cliente_id'          => $clienteId,
            'respondente_nome'    => $request->responsavel,
            'respondente_empresa' => $empresa,
            'respondente_email'   => null,
            'token'               => $token,
            'finalizado'          => false,
        ]);

        $link = route('questionario.publico', [
            'slug'  => $questionario->slug,
            'token' => $token,
        ]);

        return response()->json(['link' => $link]);
    }

    public function vinculos(Request $request): JsonResponse
    {
        $tipo = $request->string('tipo')->toString();

        if ($tipo === 'lead') {
            $items = Lead::orderBy('nome')
                ->limit(200)
                ->get(['id', 'nome', 'empresa'])
                ->map(fn($l) => ['id' => $l->id, 'label' => $l->nome.($l->empresa ? ' — '.$l->empresa : '')]);
        } else {
            $items = Cliente::where('status', 'ativo')
                ->orderBy('nome')
                ->get(['id', 'nome'])
                ->map(fn($c) => ['id' => $c->id, 'label' => $c->nome]);
        }

        return response()->json($items);
    }

    // ─── Público ─────────────────────────────────────────────────────────────

    public function publico(string $slug, Request $request): View
    {
        $questionario = Questionario::where('slug', $slug)->where('ativo', true)->firstOrFail();
        $token        = $request->query('token');
        $resposta     = null;

        if ($token) {
            $resposta = QuestionarioResposta::where('token', $token)
                ->where('questionario_id', $questionario->id)
                ->first();
        }

        $perguntas        = $questionario->perguntas()->with('opcoes')->get();
        $totalPerguntas   = $perguntas->count();
        $respondidas      = $resposta ? $resposta->itens()->pluck('pergunta_id')->toArray() : [];
        $proximaPergunta  = $perguntas->first(fn($p) => ! in_array($p->id, $respondidas));

        $finalizado = $resposta?->finalizado ?? false;

        return view('classificacao.questionario-publico', compact(
            'questionario', 'resposta', 'perguntas', 'totalPerguntas',
            'respondidas', 'proximaPergunta', 'finalizado', 'token'
        ));
    }

    public function iniciar(string $slug, Request $request): JsonResponse
    {
        $request->validate([
            'nome'          => 'required|string|max:255',
            'email'         => 'nullable|email|max:255',
            'empresa'       => 'nullable|string|max:255',
            'segmento'      => 'nullable|string|max:255',
            'faturamento'   => 'nullable|numeric|min:0',
            'colaboradores' => 'nullable|integer|min:0',
        ]);

        $questionario = Questionario::where('slug', $slug)->where('ativo', true)->firstOrFail();

        $token = (string) Str::uuid();

        QuestionarioResposta::create([
            'questionario_id'     => $questionario->id,
            'respondente_nome'    => $request->nome,
            'respondente_email'   => $request->email,
            'respondente_empresa' => $request->empresa,
            'respondente_segmento'=> $request->segmento,
            'faturamento_mensal'  => $request->faturamento,
            'num_colaboradores'   => $request->colaboradores,
            'token'               => $token,
            'finalizado'          => false,
        ]);

        return response()->json(['token' => $token]);
    }

    public function responder(string $slug, Request $request): JsonResponse
    {
        $request->validate([
            'token'      => 'required|string',
            'pergunta_id'=> 'required|integer',
            'opcao_id'   => 'required|integer',
        ]);

        $questionario = Questionario::where('slug', $slug)->where('ativo', true)->firstOrFail();

        $resposta = QuestionarioResposta::where('token', $request->token)
            ->where('questionario_id', $questionario->id)
            ->where('finalizado', false)
            ->firstOrFail();

        $pergunta = $questionario->perguntas()->with('opcoes')->findOrFail($request->pergunta_id);
        $opcao    = $pergunta->opcoes()->findOrFail($request->opcao_id);

        QuestionarioRespostaItem::updateOrCreate(
            ['resposta_id' => $resposta->id, 'pergunta_id' => $pergunta->id],
            ['opcao_id' => $opcao->id, 'pontos' => $opcao->pontos]
        );

        $totalPerguntas = $questionario->perguntas()->count();
        $respondidas    = $resposta->itens()->pluck('pergunta_id')->toArray();

        $proximaPergunta = $questionario->perguntas()
            ->with('opcoes')
            ->whereNotIn('id', $respondidas)
            ->orderBy('ordem')
            ->first();

        if (! $proximaPergunta) {
            $pontuacao      = $resposta->calcularPontuacao();
            $classificacao  = QuestionarioResposta::classificarIDE($pontuacao);

            $resposta->update([
                'pontuacao_total' => $pontuacao,
                'classificacao'   => $classificacao,
                'finalizado'      => true,
            ]);

            return response()->json([
                'finalizado'    => true,
                'pontuacao'     => $pontuacao,
                'classificacao' => $classificacao,
                'respondidas'   => count($respondidas),
                'total'         => $totalPerguntas,
            ]);
        }

        return response()->json([
            'finalizado'  => false,
            'respondidas' => count($respondidas),
            'total'       => $totalPerguntas,
            'proxima'     => [
                'id'       => $proximaPergunta->id,
                'texto'    => $proximaPergunta->texto,
                'categoria'=> $proximaPergunta->categoria,
                'ordem'    => $proximaPergunta->ordem,
                'opcoes'   => $proximaPergunta->opcoes->map(fn($o) => [
                    'id'     => $o->id,
                    'texto'  => $o->texto,
                    'pontos' => $o->pontos,
                ]),
            ],
        ]);
    }
}
