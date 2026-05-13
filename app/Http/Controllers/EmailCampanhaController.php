<?php

namespace App\Http\Controllers;

use App\Jobs\EnviarEmailCampanhaJob;
use App\Models\Cliente;
use App\Models\EmailCampanha;
use App\Models\Segmentacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Exceptions\RateLimitedException;

class EmailCampanhaController extends Controller
{
    public function index(): View
    {
        $campanhas = EmailCampanha::with('criador')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('email-campanhas.index', compact('campanhas'));
    }

    public function create(Request $request): View
    {
        $segmentacoes = Segmentacao::orderBy('nome')->get();

        $clientes = Cliente::query()
            ->when($request->filled('busca'), fn ($q) => $q->where('nome', 'like', '%'.$request->busca.'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('segmentacao_id'), fn ($q) => $q->where('segmentacao_id', $request->segmentacao_id))
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome', 'status', 'segmentacao_id']);

        return view('email-campanhas.create', compact('clientes', 'segmentacoes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'assunto' => ['required', 'string', 'max:255'],
            'conteudo_html' => ['required', 'string'],
            'clientes_ids' => ['required', 'array', 'min:1'],
            'clientes_ids.*' => ['integer', 'exists:clientes,id'],
        ], [
            'titulo.required' => 'O título da campanha é obrigatório.',
            'assunto.required' => 'O assunto do e-mail é obrigatório.',
            'conteudo_html.required' => 'O conteúdo do e-mail é obrigatório.',
            'clientes_ids.required' => 'Selecione pelo menos um cliente.',
            'clientes_ids.min' => 'Selecione pelo menos um cliente.',
        ]);

        $campanha = EmailCampanha::create([
            'titulo' => $request->titulo,
            'assunto' => $request->assunto,
            'conteudo_html' => $request->conteudo_html,
            'status' => 'rascunho',
            'destinatarios' => $request->clientes_ids,
            'total_destinatarios' => count($request->clientes_ids),
            'criado_por' => Auth::id(),
        ]);

        return redirect()->route('email-campanhas.show', $campanha->id)
            ->with('success', 'Campanha criada com sucesso!');
    }

    public function show(EmailCampanha $emailCampanha): View
    {
        $emailCampanha->load('criador');

        return view('email-campanhas.show', ['campanha' => $emailCampanha]);
    }

    public function edit(EmailCampanha $emailCampanha): View
    {
        $clientes = Cliente::query()
            ->where('status', 'ativo')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('email-campanhas.edit', ['campanha' => $emailCampanha, 'clientes' => $clientes]);
    }

    public function update(Request $request, EmailCampanha $emailCampanha): RedirectResponse
    {
        if ($emailCampanha->status === 'enviando') {
            return back()->with('error', 'Não é possível editar uma campanha em andamento.');
        }

        $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'assunto' => ['required', 'string', 'max:255'],
            'conteudo_html' => ['required', 'string'],
            'clientes_ids' => ['required', 'array', 'min:1'],
            'clientes_ids.*' => ['integer', 'exists:clientes,id'],
        ], [
            'titulo.required' => 'O título da campanha é obrigatório.',
            'assunto.required' => 'O assunto do e-mail é obrigatório.',
            'conteudo_html.required' => 'O conteúdo do e-mail é obrigatório.',
            'clientes_ids.required' => 'Selecione pelo menos um cliente.',
            'clientes_ids.min' => 'Selecione pelo menos um cliente.',
        ]);

        $emailCampanha->update([
            'titulo' => $request->titulo,
            'assunto' => $request->assunto,
            'conteudo_html' => $request->conteudo_html,
            'destinatarios' => $request->clientes_ids,
            'total_destinatarios' => count($request->clientes_ids),
            'status' => 'rascunho',
        ]);

        return redirect()->route('email-campanhas.show', $emailCampanha->id)
            ->with('success', 'Campanha atualizada com sucesso!');
    }

    public function enviar(EmailCampanha $emailCampanha): RedirectResponse
    {
        Log::info('[EmailCampanha] Iniciando envio', ['campanha_id' => $emailCampanha->id, 'titulo' => $emailCampanha->titulo]);

        if ($emailCampanha->status === 'enviando') {
            Log::warning('[EmailCampanha] Campanha já está sendo enviada', ['campanha_id' => $emailCampanha->id]);

            return back()->with('error', 'Esta campanha já está sendo enviada.');
        }

        $clienteIds = $emailCampanha->destinatarios ?? [];

        if (empty($clienteIds)) {
            Log::error('[EmailCampanha] Nenhum destinatário encontrado', ['campanha_id' => $emailCampanha->id]);

            return back()->with('error', 'Nenhum destinatário encontrado para esta campanha.');
        }

        Log::info('[EmailCampanha] Buscando clientes', ['campanha_id' => $emailCampanha->id, 'total_ids' => count($clienteIds), 'ids' => $clienteIds]);

        $clientes = Cliente::whereIn('id', $clienteIds)
            ->with(['contatoClientes', 'socios'])
            ->get();

        Log::info('[EmailCampanha] Clientes encontrados', ['campanha_id' => $emailCampanha->id, 'total_clientes' => $clientes->count()]);

        $emailCampanha->update(['status' => 'enviando']);

        $totalJobs = 0;

        foreach ($clientes as $cliente) {
            $destinatarios = $this->resolverEmailsDoCliente($cliente);

            if (empty($destinatarios)) {
                Log::warning('[EmailCampanha] Cliente sem e-mail resolvido', ['campanha_id' => $emailCampanha->id, 'cliente_id' => $cliente->id, 'cliente_nome' => $cliente->nome]);
            }

            foreach ($destinatarios as $destinatario) {
                Log::info('[EmailCampanha] Despachando job', ['campanha_id' => $emailCampanha->id, 'email' => $destinatario['email'], 'nome' => $destinatario['nome']]);
                EnviarEmailCampanhaJob::dispatch($emailCampanha, $destinatario['email'], $destinatario['nome']);
                $totalJobs++;
            }
        }

        Log::info('[EmailCampanha] Jobs despachados', ['campanha_id' => $emailCampanha->id, 'total_jobs' => $totalJobs]);

        $emailCampanha->update([
            'status' => 'enviada',
            'enviada_em' => now(),
        ]);

        return back()->with('success', 'Campanha enviada com sucesso para a fila de e-mails!');
    }

    public function destroy(EmailCampanha $emailCampanha): RedirectResponse
    {
        if ($emailCampanha->status === 'enviando') {
            return back()->with('error', 'Não é possível excluir uma campanha em andamento.');
        }

        $emailCampanha->delete();

        return redirect()->route('email-campanhas.index')->with('success', 'Campanha excluída com sucesso.');
    }

    public function gerarConteudoAi(Request $request): JsonResponse
    {
        $request->validate([
            'instrucao' => ['required', 'string', 'max:1000'],
        ]);

        $prompt = <<<PROMPT
Crie o conteúdo HTML de um e-mail profissional de marketing para clientes de um escritório de contabilidade/assessoria empresarial.

Instruções do usuário:
{$request->instrucao}

Regras obrigatórias:
- Use tags HTML: <h2>, <h3>, <p>, <ul>, <li>, <strong>, <em>
- NÃO inclua <!DOCTYPE>, <html>, <head>, <body>, <style> ou qualquer tag de estrutura — apenas o conteúdo interno
- Linguagem clara, profissional e acessível para empresários
- Estrutura sugerida: saudação, desenvolvimento (2-3 seções), encerramento com chamada para ação
- Retorne APENAS o HTML do conteúdo, sem explicações ou markdown
PROMPT;

        try {
            $response = AnonymousAgent::make(
                instructions: 'Você é um especialista em marketing e redação de e-mails profissionais para escritórios de contabilidade e assessoria empresarial no Brasil.',
                messages: [],
                tools: [],
            )->prompt(
                prompt: $prompt,
                provider: 'groq',
                model: 'llama-3.3-70b-versatile',
            );

            return response()->json(['html' => $response->text]);
        } catch (RateLimitedException) {
            return response()->json(['error' => 'Limite de requisições atingido. Aguarde um momento e tente novamente.'], 429);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Erro ao gerar conteúdo com IA. Tente novamente.'], 500);
        }
    }

    /** @return array<int, array{nome: string, email: string}> */
    private function resolverEmailsDoCliente(Cliente $cliente): array
    {
        $emails = [];

        foreach ($cliente->contatoClientes as $contato) {
            if (filled($contato->gmail)) {
                $emails[] = ['nome' => $contato->nome ?: $cliente->nome, 'email' => $contato->gmail];
            }
        }

        if (empty($emails)) {
            foreach ($cliente->socios as $socio) {
                if (filled($socio->gmail)) {
                    $emails[] = ['nome' => $socio->nome, 'email' => $socio->gmail];
                    break;
                }
            }
        }

        return $emails;
    }
}
