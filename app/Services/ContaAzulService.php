<?php

namespace App\Services;

use App\Models\CategoriaFinanceira;
use App\Models\CentroCusto;
use App\Models\Cliente;
use App\Models\ContaFinanceira;
use App\Models\LancamentoFinanceiro;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ContaAzulService
{
    private string $baseUrl;
    private string $tokenUrl;
    private string $authUrl;
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->baseUrl      = rtrim(config('contaazul.base_url'), '/');
        $this->tokenUrl     = config('contaazul.token_url');
        $this->authUrl      = config('contaazul.auth_url');
        $this->clientId     = config('contaazul.client_id');
        $this->clientSecret = config('contaazul.client_secret');
        $this->redirectUri  = config('contaazul.redirect_uri');
    }

    // ─── OAuth2 ──────────────────────────────────────────────────────────────

    public function getAuthorizationUrl(Cliente $cliente): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => implode(' ', config('contaazul.scopes')),
            'state'         => $cliente->id,
        ]);

        return $this->authUrl . '?' . $params;
    }

    public function handleCallback(string $code, Cliente $cliente): void
    {
        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->redirectUri,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (! $response->successful()) {
            Log::error('ContaAzul: falha ao trocar code por token', [
                'cliente_id' => $cliente->id,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);
            throw new RuntimeException('Falha ao obter token da Conta Azul: ' . $response->body());
        }

        $data = $response->json();

        $this->saveTokens($cliente, $data);
    }

    public function refreshTokenIfNeeded(Cliente $cliente): void
    {
        if (! $cliente->contaAzulTokenExpirado()) {
            return;
        }

        $this->doRefreshToken($cliente);
    }

    private function doRefreshToken(Cliente $cliente): void
    {
        if (empty($cliente->conta_azul_refresh_token)) {
            $cliente->update(['conta_azul_conectada' => false]);
            throw new RuntimeException("Cliente #{$cliente->id}: sem refresh_token — reconecte a Conta Azul.");
        }

        $response = Http::asForm()->post($this->tokenUrl, [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $cliente->conta_azul_refresh_token,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (! $response->successful()) {
            $cliente->update(['conta_azul_conectada' => false]);
            Log::warning('ContaAzul: refresh_token inválido', ['cliente_id' => $cliente->id]);
            throw new RuntimeException("Refresh token expirado para cliente #{$cliente->id}. Reconecte.");
        }

        $this->saveTokens($cliente, $response->json());
    }

    private function saveTokens(Cliente $cliente, array $data): void
    {
        $expiresIn = $data['expires_in'] ?? 3600;

        $cliente->update([
            'conta_azul_conectada'       => true,
            'conta_azul_access_token'    => $data['access_token'],
            'conta_azul_refresh_token'   => $data['refresh_token'] ?? $cliente->conta_azul_refresh_token,
            'conta_azul_token_expira_em' => now()->addSeconds($expiresIn - 60),
        ]);

        $cliente->refresh();
    }

    // ─── Requisição autenticada ───────────────────────────────────────────────

    private function get(Cliente $cliente, string $path, array $query = []): array
    {
        $this->refreshTokenIfNeeded($cliente);

        $response = Http::withToken($cliente->conta_azul_access_token)
            ->get($this->baseUrl . $path, $query);

        if ($response->status() === 401) {
            // token pode ter expirado no servidor — força refresh e tenta uma vez
            $this->doRefreshToken($cliente);

            $response = Http::withToken($cliente->conta_azul_access_token)
                ->get($this->baseUrl . $path, $query);
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                "ContaAzul GET {$path} retornou {$response->status()}: {$response->body()}"
            );
        }

        return $response->json() ?? [];
    }

    // ─── Sincronizações ───────────────────────────────────────────────────────

    public function syncContasFinanceiras(Cliente $cliente): void
    {
        $contas = $this->get($cliente, '/v1/conta-financeira');

        foreach ($contas as $conta) {
            $id = (string) ($conta['id'] ?? '');
            if (! $id) {
                continue;
            }

            $saldo = 0;
            try {
                $saldoData = $this->get($cliente, "/v1/conta-financeira/{$id}/saldo-atual");
                $saldo = $saldoData['saldo'] ?? $saldoData['saldo_atual'] ?? 0;
            } catch (\Throwable $e) {
                Log::warning("ContaAzul: não obteve saldo da conta {$id}", ['erro' => $e->getMessage()]);
            }

            ContaFinanceira::updateOrCreate(
                ['cliente_id' => $cliente->id, 'conta_azul_id' => $id],
                [
                    'nome'         => $conta['descricao'] ?? $conta['nome'] ?? 'Sem nome',
                    'tipo'         => $conta['tipo'] ?? null,
                    'saldo_atual'  => $saldo,
                    'ativa'        => (bool) ($conta['ativo'] ?? true),
                    'atualizado_em' => now(),
                ]
            );
        }
    }

    public function syncCategoriasECentrosCusto(Cliente $cliente): void
    {
        // Categorias DRE
        try {
            $categorias = $this->get($cliente, '/v1/financeiro/categorias-dre');
            $this->persistirCategorias($cliente, $categorias, null);
        } catch (\Throwable $e) {
            Log::warning("ContaAzul: erro ao sincronizar categorias", ['cliente_id' => $cliente->id, 'erro' => $e->getMessage()]);
        }

        // Centros de custo
        try {
            $centros = $this->get($cliente, '/v1/centro-de-custo');
            foreach ($centros as $centro) {
                $id = (string) ($centro['id'] ?? '');
                if (! $id) {
                    continue;
                }

                CentroCusto::updateOrCreate(
                    ['cliente_id' => $cliente->id, 'conta_azul_id' => $id],
                    [
                        'codigo' => $centro['codigo'] ?? null,
                        'nome'   => $centro['nome'] ?? 'Sem nome',
                        'ativo'  => (bool) ($centro['ativo'] ?? true),
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning("ContaAzul: erro ao sincronizar centros de custo", ['cliente_id' => $cliente->id, 'erro' => $e->getMessage()]);
        }
    }

    private function persistirCategorias(Cliente $cliente, array $categorias, ?int $paiId): void
    {
        foreach ($categorias as $cat) {
            $id = (string) ($cat['id'] ?? '');
            if (! $id) {
                continue;
            }

            $tipo = strtolower($cat['tipo'] ?? 'despesa');
            $tipo = str_contains($tipo, 'receita') ? 'receita' : 'despesa';

            $model = CategoriaFinanceira::updateOrCreate(
                ['cliente_id' => $cliente->id, 'conta_azul_id' => $id],
                [
                    'nome'             => $cat['nome'] ?? 'Sem nome',
                    'tipo'             => $tipo,
                    'categoria_pai_id' => $paiId,
                ]
            );

            if (! empty($cat['subcategorias'])) {
                $this->persistirCategorias($cliente, $cat['subcategorias'], $model->id);
            }
        }
    }

    public function syncLancamentos(Cliente $cliente): void
    {
        $desde = $cliente->conta_azul_ultima_sincronizacao
            ? $cliente->conta_azul_ultima_sincronizacao->format('Y-m-d\TH:i:s')
            : '2020-01-01T00:00:00';

        $this->syncTipoLancamento($cliente, 'contas-a-receber', 'credito', $desde);
        $this->syncTipoLancamento($cliente, 'contas-a-pagar', 'debito', $desde);

        $cliente->update(['conta_azul_ultima_sincronizacao' => now()]);
    }

    private function syncTipoLancamento(Cliente $cliente, string $endpoint, string $tipo, string $desde): void
    {
        $pagina = 0;
        $tamanho = 100;

        do {
            $params = [
                'data_alteracao_de' => $desde,
                'pagina'            => $pagina,
                'tamanho'           => $tamanho,
            ];

            try {
                $data = $this->get($cliente, "/v1/financeiro/eventos-financeiros/{$endpoint}/buscar", $params);
            } catch (\Throwable $e) {
                Log::warning("ContaAzul: erro ao buscar {$endpoint}", [
                    'cliente_id' => $cliente->id,
                    'erro'       => $e->getMessage(),
                ]);
                break;
            }

            $itens = $data['itens'] ?? $data['content'] ?? $data ?? [];

            if (empty($itens) || ! is_array($itens)) {
                break;
            }

            foreach ($itens as $item) {
                $id = (string) ($item['id'] ?? '');
                if (! $id) {
                    continue;
                }

                $contaFinanceira = null;
                if (! empty($item['conta_financeira_id'])) {
                    $contaFinanceira = ContaFinanceira::where('cliente_id', $cliente->id)
                        ->where('conta_azul_id', (string) $item['conta_financeira_id'])
                        ->value('id');
                }

                $categoria = null;
                if (! empty($item['categoria_id'])) {
                    $categoria = CategoriaFinanceira::where('cliente_id', $cliente->id)
                        ->where('conta_azul_id', (string) $item['categoria_id'])
                        ->value('id');
                }

                $centroCusto = null;
                if (! empty($item['centro_custo_id'])) {
                    $centroCusto = CentroCusto::where('cliente_id', $cliente->id)
                        ->where('conta_azul_id', (string) $item['centro_custo_id'])
                        ->value('id');
                }

                LancamentoFinanceiro::updateOrCreate(
                    ['cliente_id' => $cliente->id, 'conta_azul_id' => $id],
                    [
                        'conta_financeira_id' => $contaFinanceira,
                        'categoria_id'        => $categoria,
                        'centro_custo_id'     => $centroCusto,
                        'tipo'                => $tipo,
                        'descricao'           => $item['descricao'] ?? $item['historico'] ?? null,
                        'valor'               => abs((float) ($item['valor'] ?? 0)),
                        'data_vencimento'     => $item['data_vencimento'] ?? $item['dataVencimento'] ?? null,
                        'data_competencia'    => $item['data_competencia'] ?? $item['dataCompetencia'] ?? null,
                        'data_pagamento'      => $item['data_pagamento'] ?? $item['dataPagamento'] ?? null,
                        'status'              => $this->mapStatus($item['situacao'] ?? $item['status'] ?? ''),
                        'conciliado'          => (bool) ($item['conciliado'] ?? false),
                        'forma_pagamento'     => $item['forma_pagamento'] ?? $item['formaPagamento'] ?? null,
                        'origem'              => 'conta_azul',
                    ]
                );
            }

            $totalPaginas = $data['total_paginas'] ?? $data['totalPages'] ?? 1;
            $pagina++;
        } while ($pagina < $totalPaginas);
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'liquidado', 'pago', 'paid'              => 'pago',
            'cancelado', 'cancelled', 'canceled'     => 'cancelado',
            'atrasado', 'overdue', 'vencido'         => 'atrasado',
            default                                  => 'pendente',
        };
    }
}
