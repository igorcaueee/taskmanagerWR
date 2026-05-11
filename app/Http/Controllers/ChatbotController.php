<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ClienteConhecimento;
use App\Models\Tarefa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;

class ChatbotController extends Controller
{
    private const SESSION_KEY = 'chatbot_messages';

    private const SYSTEM_INSTRUCTIONS = <<<'INSTRUCTIONS'
Você é um assistente especialista em contabilidade e assessoria empresarial, integrado ao sistema interno da WR Assessoria.
Seu objetivo é ajudar os funcionários com dúvidas sobre:

- Contabilidade geral (lançamentos, balanço, DRE, fluxo de caixa)
- Regimes tributários brasileiros: Simples Nacional, Lucro Presumido, Lucro Real, MEI
- Obrigações fiscais e trabalhistas (FGTS, INSS, IRPF, IRPJ, CSLL, PIS, COFINS, ISS, ICMS, IPI)
- Folha de pagamento, férias, 13º salário, rescisões
- Abertura e encerramento de empresas, CNPJ, Junta Comercial
- Nota fiscal eletrônica (NF-e, NFS-e), SPED, ECF, ECD
- Planejamento tributário e financeiro
- Dados do sistema: tarefas do usuário e clientes cadastrados

Regras de comportamento:
- Responda sempre em português do Brasil, de forma clara e objetiva.
- Use emojis moderadamente para tornar a conversa mais amigável e interativa (ex: ✅, 📊, 💡, ⚠️, 📋, 🔢).
- Formate respostas com **negrito**, listas e títulos quando for útil para a leitura.
- Quando o funcionário perguntar sobre suas tarefas ou clientes, use as ferramentas disponíveis para buscar dados reais do sistema.
- Forneça informações práticas e precisas. Se não souber algo, diga claramente.
- Não responda perguntas não relacionadas ao trabalho contábil/assessoria ou ao sistema.
- Seja cordial e profissional.
INSTRUCTIONS;

    /**
     * Process an incoming chat message and return the AI response.
     */
    public function message(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        /** @var array<UserMessage|AssistantMessage> $history */
        $history = session(self::SESSION_KEY, []);

        $userText = $request->string('message')->toString();

        $contextualInstructions = self::SYSTEM_INSTRUCTIONS."\n\n".$this->buildSystemContext();

        try {
            $response = AnonymousAgent::make(
                instructions: $contextualInstructions,
                messages: $history,
                tools: [],
            )->prompt(
                prompt: $userText,
                provider: 'groq',
                model: 'llama-3.3-70b-versatile',
            );

            $assistantText = $response->text;

            $history[] = new UserMessage($userText);
            $history[] = new AssistantMessage($assistantText);
            session([self::SESSION_KEY => $history]);

            return response()->json(['message' => $assistantText]);
        } catch (RateLimitedException) {
            return response()->json([
                'message' => 'O serviço de IA está com muitas requisições no momento. Aguarde alguns segundos e tente novamente.',
            ], 429);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Desculpe, ocorreu um erro ao processar sua mensagem. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Build contextual data from the system to inject into the prompt.
     */
    private function buildSystemContext(): string
    {
        $userId = Auth::id();
        $lines = ['=== DADOS DO SISTEMA (atualizados agora) ==='];

        // Tarefas do usuário logado
        $tarefas = Tarefa::query()
            ->with(['etapa', 'cliente'])
            ->where('responsavel_id', $userId)
            ->whereNull('data_conclusao')
            ->orderBy('prioridade', 'desc')
            ->orderBy('data_vencimento')
            ->get();

        if ($tarefas->isEmpty()) {
            $lines[] = 'Minhas tarefas: nenhuma tarefa pendente.';
        } else {
            $prioridades = [1 => 'Baixa', 2 => 'Normal', 3 => 'Alta', 4 => 'Urgente', 5 => 'Crítica'];
            $lines[] = "Minhas tarefas pendentes ({$tarefas->count()}):";
            foreach ($tarefas->groupBy(fn ($t) => $t->etapa?->nome ?? 'Sem etapa') as $etapa => $grupo) {
                $lines[] = "  Etapa: {$etapa}";
                foreach ($grupo as $t) {
                    $prioridade = $prioridades[$t->prioridade] ?? 'Normal';
                    $vencimento = $t->data_vencimento?->format('d/m/Y') ?? 'sem vencimento';
                    $cliente = $t->cliente?->nome ?? 'sem cliente';
                    $lines[] = "    - [{$prioridade}] {$t->titulo} | {$cliente} | vence {$vencimento}";
                }
            }
        }

        // Clientes ativos
        $clientes = Cliente::query()
            ->select('nome', 'regime_tributario', 'cidade', 'estado', 'status', 'tipo')
            ->where('status', '!=', 'encerrado')
            ->orderBy('nome')
            ->limit(50)
            ->get();

        if ($clientes->isEmpty()) {
            $lines[] = 'Clientes: nenhum cliente ativo.';
        } else {
            $lines[] = "Clientes ativos ({$clientes->count()}):";
            foreach ($clientes as $c) {
                $regime = $c->regime_tributario ?? 'não informado';
                $local = collect([$c->cidade, $c->estado])->filter()->implode('/');
                $lines[] = "  - {$c->nome} | {$regime} | {$local} | {$c->tipo}";
            }
        }

        $lines[] = '=== FIM DOS DADOS DO SISTEMA ===';

        // Conhecimento específico por cliente (todos os clientes com entradas cadastradas)
        $conhecimentos = ClienteConhecimento::query()
            ->with('cliente:id,nome')
            ->orderBy('cliente_id')
            ->orderByDesc('created_at')
            ->get();

        if ($conhecimentos->isNotEmpty()) {
            $lines[] = '';
            $lines[] = '=== CONHECIMENTO ESPECÍFICO DE CLIENTES ===';
            foreach ($conhecimentos->groupBy('cliente_id') as $clienteIdKey => $entradas) {
                $nomeCliente = $entradas->first()->cliente?->nome ?? "Cliente #{$clienteIdKey}";
                $lines[] = "[{$nomeCliente}]";
                foreach ($entradas as $entrada) {
                    $conteudoTruncado = mb_substr($entrada->conteudo, 0, 500);
                    if (mb_strlen($entrada->conteudo) > 500) {
                        $conteudoTruncado .= '...';
                    }
                    $lines[] = "  Tópico: {$entrada->titulo}";
                    $lines[] = "  {$conteudoTruncado}";
                }
            }
            $lines[] = '=== FIM DO CONHECIMENTO ===';
        }

        return implode("\n", $lines);
    }

    /**
     * Clear the chat session history.
     */
    public function clear(): JsonResponse
    {
        session()->forget(self::SESSION_KEY);

        return response()->json(['status' => 'ok']);
    }
}
