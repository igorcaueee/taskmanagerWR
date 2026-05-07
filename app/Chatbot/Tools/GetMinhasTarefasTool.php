<?php

namespace App\Chatbot\Tools;

use App\Models\Tarefa;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetMinhasTarefasTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): string
    {
        return 'Busca as tarefas do usuário logado agrupadas por etapa, mostrando título, prioridade, vencimento e cliente.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): string
    {
        $tarefas = Tarefa::query()
            ->with(['etapa', 'cliente'])
            ->where('responsavel_id', Auth::id())
            ->whereNull('data_conclusao')
            ->orderBy('prioridade', 'desc')
            ->orderBy('data_vencimento')
            ->get();

        if ($tarefas->isEmpty()) {
            return 'O usuário não possui tarefas pendentes no momento.';
        }

        $prioridades = [1 => 'Baixa', 2 => 'Normal', 3 => 'Alta', 4 => 'Urgente', 5 => 'Crítica'];

        $agrupadas = $tarefas->groupBy(fn ($t) => $t->etapa?->nome ?? 'Sem etapa');

        $linhas = [];
        foreach ($agrupadas as $etapa => $grupo) {
            $linhas[] = "Etapa: {$etapa} ({$grupo->count()} tarefa(s))";
            foreach ($grupo as $t) {
                $prioridade = $prioridades[$t->prioridade] ?? 'Normal';
                $vencimento = $t->data_vencimento?->format('d/m/Y') ?? 'sem vencimento';
                $cliente = $t->cliente?->nome ?? 'sem cliente';
                $linhas[] = "  - [{$prioridade}] {$t->titulo} | Cliente: {$cliente} | Vence: {$vencimento}";
            }
        }

        return implode("\n", $linhas);
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
