<?php

namespace App\Chatbot\Tools;

use App\Models\Cliente;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetClientesTool implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): string
    {
        return 'Busca os clientes cadastrados no sistema com nome, regime tributário, cidade e status.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): string
    {
        $clientes = Cliente::query()
            ->select('nome', 'regime_tributario', 'cidade', 'estado', 'status', 'tipo')
            ->where('status', '!=', 'encerrado')
            ->orderBy('nome')
            ->limit(50)
            ->get();

        if ($clientes->isEmpty()) {
            return 'Nenhum cliente ativo encontrado no sistema.';
        }

        $linhas = ["Total de clientes ativos: {$clientes->count()}"];
        foreach ($clientes as $cliente) {
            $regime = $cliente->regime_tributario ?? 'não informado';
            $local = collect([$cliente->cidade, $cliente->estado])->filter()->implode('/');
            $local = $local ?: 'localização não informada';
            $linhas[] = "- {$cliente->nome} | Regime: {$regime} | {$local} | Tipo: {$cliente->tipo}";
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
