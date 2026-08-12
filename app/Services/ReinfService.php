<?php

namespace App\Services;

use App\Models\ClienteCertificadoNfse;
use Illuminate\Support\Facades\Log;

/**
 * Transporte HTTP (mTLS com certificado do cliente) para a API REST nativa do
 * EFD-Reinf (modelo assíncrono): envio de lote de eventos e consulta de
 * protocolo. Não usa SERPRO/Integra Contador — é a API direta da Receita
 * Federal (ver "EFD-Reinf Manual de Orientação do Desenvolvedor", seções 4 e 5).
 *
 * Mesmo padrão de conexão do NfseService::requisicaoPem — curl com
 * CURLOPT_SSLCERT/SSLKEY em vez de Http::withOptions, pois o certificado é
 * por-requisição e extraído sob demanda do .pfx do cliente.
 */
class ReinfService
{
    const BASE_PRODUCAO = 'https://reinf.receita.economia.gov.br';
    const BASE_RESTRITA = 'https://pre-reinf.receita.economia.gov.br';

    /**
     * POST /recepcao/lotes — envia o XML do lote assíncrono já assinado.
     *
     * @return array{httpCode: int, corpo: string}
     */
    public function enviarLote(ClienteCertificadoNfse $certificado, string $xmlLote, string $pemCert, string $pemKey): array
    {
        $url = $this->baseUrl($certificado) . '/recepcao/lotes';

        return $this->requisicao('POST', $url, $pemCert, $pemKey, $xmlLote);
    }

    /**
     * GET /consulta/lotes/{numeroProtocolo} — resultado do processamento do lote.
     *
     * @return array{httpCode: int, corpo: string}
     */
    public function consultarLote(ClienteCertificadoNfse $certificado, string $numeroProtocolo, string $pemCert, string $pemKey): array
    {
        $url = $this->baseUrl($certificado) . '/consulta/lotes/' . urlencode($numeroProtocolo);

        return $this->requisicao('GET', $url, $pemCert, $pemKey);
    }

    private function baseUrl(ClienteCertificadoNfse $certificado): string
    {
        return $certificado->ambiente === 'producao' ? self::BASE_PRODUCAO : self::BASE_RESTRITA;
    }

    private function requisicao(string $method, string $url, string $pemCert, string $pemKey, ?string $body = null): array
    {
        Log::debug('[EFD-Reinf] requisicao: enviando', ['method' => $method, 'url' => $url, 'temBody' => $body !== null]);

        $ch = curl_init();

        $opcoes = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSLCERT        => $pemCert,
            CURLOPT_SSLKEY         => $pemKey,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/xml; charset=UTF-8',
                'Accept: application/xml',
            ],
        ];

        if ($body !== null) {
            $opcoes[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $opcoes);

        $resposta  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        unset($ch);

        Log::debug('[EFD-Reinf] requisicao: resposta recebida', [
            'httpCode'  => $httpCode,
            'curlErrNo' => $curlErrNo,
            'curlError' => $curlError ?: null,
            'body300'   => is_string($resposta) ? substr($resposta, 0, 300) : null,
        ]);

        if ($resposta === false) {
            throw new \RuntimeException("Falha de conexão com a API EFD-Reinf ({$curlError}, cURL #{$curlErrNo}).");
        }

        return ['httpCode' => $httpCode, 'corpo' => $resposta];
    }
}
