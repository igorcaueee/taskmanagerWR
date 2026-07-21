<?php

namespace Tests\Unit\Services\SimplesNacional;

use App\Models\IntegraContadorConfiguracao;
use App\Services\SimplesNacional\IntegraContadorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntegraContadorAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function criarCertificadoP12Fake(string $senha): string
    {
        $tmpDir = sys_get_temp_dir();
        $keyPath = tempnam($tmpDir, 'key_');
        $certPath = tempnam($tmpDir, 'cert_');
        $p12Path = tempnam($tmpDir, 'p12_');

        exec(sprintf(
            'openssl req -x509 -newkey rsa:2048 -keyout %s -out %s -days 1 -nodes -subj "/CN=teste" 2>&1',
            escapeshellarg($keyPath),
            escapeshellarg($certPath)
        ));

        exec(sprintf(
            'openssl pkcs12 -export -out %s -inkey %s -in %s -passout pass:%s 2>&1',
            escapeshellarg($p12Path),
            escapeshellarg($keyPath),
            escapeshellarg($certPath),
            escapeshellarg($senha)
        ));

        Storage::disk('local')->put('certificados/integra-contador-teste.p12', file_get_contents($p12Path));

        @unlink($keyPath);
        @unlink($certPath);
        @unlink($p12Path);

        return 'certificados/integra-contador-teste.p12';
    }

    private function configurar(): void
    {
        $arquivo = $this->criarCertificadoP12Fake('senha123');

        IntegraContadorConfiguracao::create([
            'cnpj_contratante' => '11222333000181',
            'arquivo_certificado' => $arquivo,
            'senha_certificado' => 'senha123',
            'consumer_key' => 'chave-teste',
            'consumer_secret' => 'segredo-teste',
            'ambiente' => 'trial',
        ]);
    }

    public function test_autentica_e_retorna_tokens(): void
    {
        $this->configurar();

        Http::fake([
            'autenticacao.sapi.serpro.gov.br/*' => Http::response([
                'access_token' => 'token-abc',
                'jwt_token' => 'jwt-xyz',
                'expires_in' => 2008,
                'token_type' => 'Bearer',
                'scope' => 'default',
            ], 200),
        ]);

        $tokens = (new IntegraContadorAuthService())->obterTokens();

        $this->assertSame('token-abc', $tokens['access_token']);
        $this->assertSame('jwt-xyz', $tokens['jwt_token']);
    }

    public function test_usa_cache_em_chamadas_subsequentes(): void
    {
        $this->configurar();

        Http::fake([
            'autenticacao.sapi.serpro.gov.br/*' => Http::response([
                'access_token' => 'token-abc',
                'jwt_token' => 'jwt-xyz',
            ], 200),
        ]);

        $service = new IntegraContadorAuthService();
        $service->obterTokens();
        $service->obterTokens();

        Http::assertSentCount(1);
    }

    public function test_lanca_excecao_quando_configuracao_ausente(): void
    {
        $this->expectException(\RuntimeException::class);

        (new IntegraContadorAuthService())->obterTokens();
    }

    public function test_lanca_excecao_quando_autenticacao_falha(): void
    {
        $this->configurar();

        Http::fake([
            'autenticacao.sapi.serpro.gov.br/*' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $this->expectException(\RuntimeException::class);

        (new IntegraContadorAuthService())->obterTokens();
    }
}
