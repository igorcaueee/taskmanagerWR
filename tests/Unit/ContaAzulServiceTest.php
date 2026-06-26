<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\ContaFinanceira;
use App\Models\LancamentoFinanceiro;
use App\Services\ContaAzulService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContaAzulServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): ContaAzulService
    {
        config([
            'contaazul.client_id'     => 'test-client-id',
            'contaazul.client_secret' => 'test-secret',
            'contaazul.redirect_uri'  => 'http://localhost/conta-azul/callback',
            'contaazul.base_url'      => 'https://api-v2.contaazul.com',
            'contaazul.auth_url'      => 'https://auth.contaazul.com/auth/realms/prod/protocol/openid-connect/auth',
            'contaazul.token_url'     => 'https://auth.contaazul.com/auth/realms/prod/protocol/openid-connect/token',
            'contaazul.scopes'        => ['openid'],
        ]);

        return new ContaAzulService();
    }

    private function clienteConectado(): Cliente
    {
        return Cliente::factory()->conectadoContaAzul()->create();
    }

    // ─── getAuthorizationUrl ──────────────────────────────────────────────────

    public function test_gera_url_de_autorizacao(): void
    {
        $cliente = Cliente::factory()->create();
        $url = $this->service()->getAuthorizationUrl($cliente);

        $this->assertStringContainsString('openid-connect/auth', $url);
        $this->assertStringContainsString('client_id=test-client-id', $url);
        $this->assertStringContainsString("state={$cliente->id}", $url);
    }

    // ─── handleCallback ───────────────────────────────────────────────────────

    public function test_handle_callback_salva_tokens(): void
    {
        Http::fake([
            '*/openid-connect/token' => Http::response([
                'access_token'  => 'novo-access-token',
                'refresh_token' => 'novo-refresh-token',
                'expires_in'    => 3600,
            ], 200),
        ]);

        $cliente = Cliente::factory()->create();

        $this->service()->handleCallback('auth-code-123', $cliente);

        $cliente->refresh();
        $this->assertTrue($cliente->conta_azul_conectada);
        $this->assertEquals('novo-access-token', $cliente->conta_azul_access_token);
        $this->assertEquals('novo-refresh-token', $cliente->conta_azul_refresh_token);
        $this->assertFalse($cliente->contaAzulTokenExpirado());
    }

    public function test_handle_callback_com_erro_lanca_excecao(): void
    {
        Http::fake([
            '*/openid-connect/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $cliente = Cliente::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->service()->handleCallback('codigo-invalido', $cliente);
    }

    // ─── refreshTokenIfNeeded ─────────────────────────────────────────────────

    public function test_nao_faz_refresh_com_token_valido(): void
    {
        Http::fake();

        $cliente = $this->clienteConectado(); // token válido por 1h

        $this->service()->refreshTokenIfNeeded($cliente);

        Http::assertNothingSent();
    }

    public function test_refresh_token_com_token_expirado(): void
    {
        Http::fake([
            '*/openid-connect/token' => Http::response([
                'access_token'  => 'refreshed-token',
                'refresh_token' => 'new-refresh',
                'expires_in'    => 3600,
            ], 200),
        ]);

        $cliente = Cliente::factory()->tokenExpirado()->create();

        $this->service()->refreshTokenIfNeeded($cliente);

        $cliente->refresh();
        $this->assertEquals('refreshed-token', $cliente->conta_azul_access_token);
    }

    public function test_refresh_invalido_desconecta_cliente(): void
    {
        Http::fake([
            '*/openid-connect/token' => Http::response(['error' => 'invalid_grant'], 400),
        ]);

        $cliente = Cliente::factory()->tokenExpirado()->create();

        $this->expectException(\RuntimeException::class);

        try {
            $this->service()->refreshTokenIfNeeded($cliente);
        } finally {
            $cliente->refresh();
            $this->assertFalse($cliente->conta_azul_conectada);
        }
    }

    // ─── syncContasFinanceiras ────────────────────────────────────────────────

    public function test_sync_contas_financeiras(): void
    {
        Http::fake([
            '*/v1/conta-financeira' => Http::response([
                ['id' => 'ca-1', 'descricao' => 'Conta Corrente', 'tipo' => 'corrente', 'ativo' => true],
                ['id' => 'ca-2', 'descricao' => 'Poupança', 'tipo' => 'poupanca', 'ativo' => true],
            ], 200),
            '*/v1/conta-financeira/ca-1/saldo-atual' => Http::response(['saldo' => 1500.00], 200),
            '*/v1/conta-financeira/ca-2/saldo-atual' => Http::response(['saldo' => 500.50], 200),
        ]);

        $cliente = $this->clienteConectado();

        $this->service()->syncContasFinanceiras($cliente);

        $this->assertDatabaseHas('contas_financeiras', [
            'cliente_id'    => $cliente->id,
            'conta_azul_id' => 'ca-1',
            'nome'          => 'Conta Corrente',
        ]);
        $this->assertDatabaseHas('contas_financeiras', [
            'cliente_id'    => $cliente->id,
            'conta_azul_id' => 'ca-2',
        ]);
        $this->assertEquals(2, ContaFinanceira::where('cliente_id', $cliente->id)->count());
    }

    public function test_sync_contas_idempotente(): void
    {
        Http::fake([
            '*/v1/conta-financeira' => Http::response([
                ['id' => 'ca-1', 'descricao' => 'Conta Corrente', 'tipo' => 'corrente', 'ativo' => true],
            ], 200),
            '*/v1/conta-financeira/ca-1/saldo-atual' => Http::response(['saldo' => 2000.00], 200),
        ]);

        $cliente = $this->clienteConectado();

        $this->service()->syncContasFinanceiras($cliente);
        $this->service()->syncContasFinanceiras($cliente); // segunda vez

        $this->assertEquals(1, ContaFinanceira::where('cliente_id', $cliente->id)->count());
    }

    // ─── syncLancamentos ──────────────────────────────────────────────────────

    public function test_sync_lancamentos_credito_e_debito(): void
    {
        Http::fake([
            '*/contas-a-receber/buscar*' => Http::response([
                'itens' => [
                    [
                        'id'              => 'lan-1',
                        'descricao'       => 'Venda de produto',
                        'valor'           => 500.00,
                        'data_vencimento' => '2026-01-10',
                        'situacao'        => 'liquidado',
                        'conciliado'      => true,
                    ],
                ],
                'total_paginas' => 1,
            ], 200),
            '*/contas-a-pagar/buscar*' => Http::response([
                'itens' => [
                    [
                        'id'              => 'lan-2',
                        'descricao'       => 'Fornecedor X',
                        'valor'           => 200.00,
                        'data_vencimento' => '2026-01-15',
                        'situacao'        => 'pendente',
                        'conciliado'      => false,
                    ],
                ],
                'total_paginas' => 1,
            ], 200),
        ]);

        $cliente = $this->clienteConectado();

        $this->service()->syncLancamentos($cliente);

        $this->assertDatabaseHas('lancamentos_financeiros', [
            'conta_azul_id' => 'lan-1',
            'tipo'          => 'credito',
            'status'        => 'pago',
            'valor'         => 500.00,
        ]);
        $this->assertDatabaseHas('lancamentos_financeiros', [
            'conta_azul_id' => 'lan-2',
            'tipo'          => 'debito',
            'status'        => 'pendente',
            'valor'         => 200.00,
        ]);
        $this->assertEquals(2, LancamentoFinanceiro::where('cliente_id', $cliente->id)->count());
    }

    // ─── Retry em 401 ─────────────────────────────────────────────────────────

    public function test_retry_automatico_em_401(): void
    {
        Http::fake([
            '*/v1/conta-financeira' => Http::sequence()
                ->push([], 401) // primeiro retorna 401
                ->push([        // segundo retorna OK após refresh
                    ['id' => 'ca-1', 'descricao' => 'Conta Corrente', 'tipo' => 'corrente', 'ativo' => true],
                ], 200),
            '*/openid-connect/token' => Http::response([
                'access_token'  => 'novo-token-pos-retry',
                'refresh_token' => 'novo-refresh',
                'expires_in'    => 3600,
            ], 200),
            '*/v1/conta-financeira/ca-1/saldo-atual' => Http::response(['saldo' => 100], 200),
        ]);

        $cliente = $this->clienteConectado();

        $this->service()->syncContasFinanceiras($cliente);

        $this->assertDatabaseHas('contas_financeiras', [
            'cliente_id'    => $cliente->id,
            'conta_azul_id' => 'ca-1',
        ]);
    }
}
