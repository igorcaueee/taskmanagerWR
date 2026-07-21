<?php

namespace Tests\Unit\Services\SimplesNacional;

use App\Models\Cliente;
use App\Models\SimplesDasProcessamento;
use App\Models\SimplesReceitaAtividade;
use App\Models\SimplesReceitaMensal;
use App\Services\SimplesNacional\IntegraContadorAuthService;
use App\Services\SimplesNacional\IntegraContadorClient;
use App\Services\SimplesNacional\PgdasdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PgdasdServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Evita exercitar o handshake mTLS real do IntegraContadorAuthService — os
        // testes aqui cobrem a lógica do PGDASD (transmitir/consultar/emitir), não
        // a autenticação (já coberta em IntegraContadorAuthServiceTest).
        $this->mock(IntegraContadorAuthService::class, function ($mock) {
            $mock->shouldReceive('obterTokens')->andReturn([
                'access_token' => 'token-teste',
                'jwt_token' => 'jwt-teste',
            ]);
            $mock->shouldReceive('invalidarTokens');
        });
    }

    private function cliente(): Cliente
    {
        return Cliente::create([
            'nome' => 'Empresa Teste LTDA',
            'cpfcnpj' => '11222333000181',
            'regime_tributario' => 'Simples Nacional',
            'status' => 'ativo',
        ]);
    }

    public function test_transmite_declaracao_com_sucesso(): void
    {
        Http::fake([
            'gateway.apiserpro.serpro.gov.br/*' => Http::response([
                'numeroRecibo' => '12345.67890',
            ], 200),
        ]);

        $cliente = $this->cliente();

        $registro = app(PgdasdService::class)->transmitirDeclaracao($cliente, '202606', [
            'cnaePrincipal' => '6201-5/01',
            'anexo' => 'III',
            'rbt12' => 100000.0,
        ]);

        $this->assertSame('sucesso', $registro->status);
        $this->assertSame('12345.67890', $registro->numero_recibo);
        $this->assertDatabaseHas('simples_das_processamentos', [
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'status' => 'sucesso',
        ]);
    }

    public function test_nao_retransmite_declaracao_ja_transmitida(): void
    {
        $cliente = $this->cliente();

        SimplesDasProcessamento::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'status' => 'sucesso',
            'numero_recibo' => 'ja-transmitido-antes',
        ]);

        Http::fake();

        app(PgdasdService::class)->transmitirDeclaracao($cliente, '202606', []);

        Http::assertNothingSent();
    }

    public function test_registra_erro_quando_api_retorna_falha(): void
    {
        Http::fake([
            'gateway.apiserpro.serpro.gov.br/*' => Http::response([
                'mensagens' => [['texto' => 'CNPJ sem procuração eletrônica ativa']],
            ], 403),
        ]);

        $cliente = $this->cliente();

        try {
            app(PgdasdService::class)->transmitirDeclaracao($cliente, '202606', []);
            $this->fail('Esperava RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('procuração', $e->getMessage());
        }

        $this->assertDatabaseHas('simples_das_processamentos', [
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'status' => 'erro',
        ]);
    }

    public function test_reautentica_quando_token_expira(): void
    {
        Http::fake([
            'gateway.apiserpro.serpro.gov.br/*' => Http::sequence()
                ->push(['error' => 'token expirado'], 401)
                ->push(['numeroRecibo' => 'recibo-2'], 200),
        ]);

        $cliente = $this->cliente();

        $registro = app(PgdasdService::class)->transmitirDeclaracao($cliente, '202607', []);

        $this->assertSame('sucesso', $registro->status);
        Http::assertSentCount(2);
    }

    public function test_transmitir_declaracao_do_cliente_monta_payload_e_envia(): void
    {
        $cliente = $this->cliente();

        SimplesReceitaMensal::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'receita_bruta_competencia' => 15000.0,
            'regime_apuracao' => 'competencia',
        ]);

        SimplesReceitaAtividade::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'id_atividade' => 42,
            'valor' => 15000.0,
        ]);

        Http::fake([
            'gateway.apiserpro.serpro.gov.br/*' => Http::response(['numeroRecibo' => 'recibo-real'], 200),
        ]);

        $registro = app(PgdasdService::class)->transmitirDeclaracaoDoCliente($cliente, '202606');

        $this->assertSame('sucesso', $registro->status);
        $this->assertSame('recibo-real', $registro->numero_recibo);

        Http::assertSent(function ($request) {
            $body = $request->data();
            $dados = json_decode($body['pedidoDados']['dados'], true);

            return $dados['periodoApuracao'] === 202606
                && $dados['declaracao']['estabelecimentos'][0]['atividades'][0]['idAtividade'] === 42
                && $dados['declaracao']['estabelecimentos'][0]['atividades'][0]['valorAtividade'] === 15000.0;
        });
    }

    public function test_transmitir_declaracao_do_cliente_falha_sem_receita_lancada(): void
    {
        $cliente = $this->cliente();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('não tem receita bruta lançada');

        app(PgdasdService::class)->transmitirDeclaracaoDoCliente($cliente, '202606');
    }

    public function test_transmitir_declaracao_do_cliente_falha_sem_atividades_lancadas(): void
    {
        $cliente = $this->cliente();

        SimplesReceitaMensal::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'receita_bruta_competencia' => 15000.0,
            'regime_apuracao' => 'competencia',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('não tem nenhuma atividade');

        app(PgdasdService::class)->transmitirDeclaracaoDoCliente($cliente, '202606');
    }

    public function test_transmitir_declaracao_do_cliente_falha_quando_soma_atividades_diverge(): void
    {
        $cliente = $this->cliente();

        SimplesReceitaMensal::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'receita_bruta_competencia' => 15000.0,
            'regime_apuracao' => 'competencia',
        ]);

        SimplesReceitaAtividade::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'id_atividade' => 42,
            'valor' => 9999.0, // não bate com os 15000 lançados
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('não bate com a receita bruta do período');

        app(PgdasdService::class)->transmitirDeclaracaoDoCliente($cliente, '202606');
    }

    public function test_transmitir_declaracao_do_cliente_falha_regime_caixa_sem_valor_caixa(): void
    {
        $cliente = $this->cliente();

        SimplesReceitaMensal::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'receita_bruta_competencia' => 15000.0,
            'receita_bruta_caixa' => null,
            'regime_apuracao' => 'caixa',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('regime de apuração é "caixa"');

        app(PgdasdService::class)->transmitirDeclaracaoDoCliente($cliente, '202606');
    }

    public function test_transmitir_declaracao_do_cliente_falha_receita_zerada_sem_confirmacao(): void
    {
        $cliente = $this->cliente();

        SimplesReceitaMensal::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'receita_bruta_competencia' => 0,
            'regime_apuracao' => 'competencia',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('receita bruta do período está zerada');

        app(PgdasdService::class)->transmitirDeclaracaoDoCliente($cliente, '202606');
    }

    public function test_transmitir_declaracao_do_cliente_permite_receita_zerada_com_confirmacao(): void
    {
        $cliente = $this->cliente();

        SimplesReceitaMensal::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'receita_bruta_competencia' => 0,
            'regime_apuracao' => 'competencia',
        ]);

        SimplesReceitaAtividade::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'id_atividade' => 9,
            'valor' => 0,
        ]);

        Http::fake([
            'gateway.apiserpro.serpro.gov.br/*' => Http::response(['numeroRecibo' => 'recibo-sem-movimento'], 200),
        ]);

        $registro = app(PgdasdService::class)->transmitirDeclaracaoDoCliente($cliente, '202606', confirmarReceitaZerada: true);

        $this->assertSame('sucesso', $registro->status);
    }

    public function test_transmitir_declaracao_do_cliente_bloqueia_se_ja_existe_declaracao(): void
    {
        $cliente = $this->cliente();

        SimplesReceitaMensal::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'receita_bruta_competencia' => 15000.0,
            'regime_apuracao' => 'competencia',
        ]);

        SimplesReceitaAtividade::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'id_atividade' => 42,
            'valor' => 15000.0,
        ]);

        Http::fake([
            'gateway.apiserpro.serpro.gov.br/*' => Http::response([
                'dados' => json_encode([
                    'anoCalendario' => '2026',
                    'periodos' => [
                        [
                            'periodoApuracao' => 202606,
                            'operacoes' => [
                                ['tipoOperacao' => 'Original', 'indiceDeclaracao' => ['numeroDeclaracao' => 'ja-existe-001']],
                            ],
                        ],
                    ],
                ]),
            ], 200),
        ]);

        try {
            app(PgdasdService::class)->transmitirDeclaracaoDoCliente($cliente, '202606');
            $this->fail('Esperava RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('já tem uma declaração ORIGINAL transmitida', $e->getMessage());
        }

        Http::assertSentCount(1); // consultou o histórico e parou, não chegou a transmitir
    }
}
