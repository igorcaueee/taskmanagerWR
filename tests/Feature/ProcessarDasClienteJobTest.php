<?php

namespace Tests\Feature;

use App\Jobs\ProcessarDasClienteJob;
use App\Models\Cliente;
use App\Models\SimplesReceitaAtividade;
use App\Models\SimplesReceitaMensal;
use App\Services\SimplesNacional\IntegraContadorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessarDasClienteJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_processa_cliente_elegivel_com_sucesso(): void
    {
        $cliente = $this->cliente();

        SimplesReceitaMensal::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'receita_bruta_competencia' => 10000.0,
            'regime_apuracao' => 'competencia',
        ]);

        SimplesReceitaAtividade::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'id_atividade' => 1,
            'valor' => 10000.0,
        ]);

        Http::fake([
            'gateway.apiserpro.serpro.gov.br/*' => Http::response(['numeroRecibo' => 'recibo-1'], 200),
        ]);

        (new ProcessarDasClienteJob($cliente, '202606'))->handle(app(\App\Services\SimplesNacional\PgdasdService::class));

        $this->assertDatabaseHas('simples_das_processamentos', [
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'status' => 'sucesso',
        ]);
    }

    public function test_falha_registra_erro_quando_cliente_sem_receita_lancada(): void
    {
        $cliente = $this->cliente();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('não tem receita bruta lançada');

        (new ProcessarDasClienteJob($cliente, '202606'))->handle(app(\App\Services\SimplesNacional\PgdasdService::class));
    }

    public function test_falha_registra_erro_quando_cliente_sem_atividades_lancadas(): void
    {
        $cliente = $this->cliente();

        SimplesReceitaMensal::create([
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'receita_bruta_competencia' => 10000.0,
            'regime_apuracao' => 'competencia',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('não tem nenhuma atividade');

        (new ProcessarDasClienteJob($cliente, '202606'))->handle(app(\App\Services\SimplesNacional\PgdasdService::class));
    }

    public function test_failed_registra_erro_no_processamento(): void
    {
        $cliente = $this->cliente();

        $job = new ProcessarDasClienteJob($cliente, '202606');
        $job->failed(new \RuntimeException('Falha simulada'));

        $this->assertDatabaseHas('simples_das_processamentos', [
            'cliente_id' => $cliente->id,
            'periodo_apuracao' => '202606',
            'status' => 'erro',
            'mensagem_erro' => 'Falha simulada',
        ]);
    }
}
