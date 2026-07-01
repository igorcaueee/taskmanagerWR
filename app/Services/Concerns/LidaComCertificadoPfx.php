<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Extração de certificado .pfx (P12) para PEM, com fallback para OpenSSL legacy
 * (RC2/3DES, incompatível com OpenSSL 3.0 sem -legacy). Compartilhado entre
 * serviços que autenticam via mTLS com o certificado A1 do cliente.
 */
trait LidaComCertificadoPfx
{
    /**
     * Extrai cert + chave do .pfx para arquivos PEM temporários.
     * PEM é mais compatível com diferentes builds de cURL/OpenSSL que P12 direto.
     * Retorna [caminhoCert, caminhoKey, [arquivosParaRemover]].
     */
    protected function extrairPem(string $pfxPath, string $senha): array
    {
        Log::info('[Certificado] extrairPem: iniciando', ['pfx' => $pfxPath]);

        if (!file_exists($pfxPath)) {
            Log::error('[Certificado] extrairPem: arquivo não encontrado', ['pfx' => $pfxPath]);
            throw new \RuntimeException("Arquivo de certificado não encontrado: {$pfxPath}");
        }

        $pfxContent = file_get_contents($pfxPath);
        $certs      = [];

        while (openssl_error_string() !== false);

        if (!openssl_pkcs12_read($pfxContent, $certs, $senha)) {
            $erroOpenssl = '';
            while ($err = openssl_error_string()) {
                $erroOpenssl .= $err;
            }

            if (str_contains($erroOpenssl, 'unsupported') || str_contains($erroOpenssl, 'legacy')) {
                Log::info('[Certificado] extrairPem: algoritmo legado detectado, usando CLI com -legacy');
                return $this->extrairPemLegacy($pfxPath, $senha);
            }

            Log::error('[Certificado] extrairPem: falha no openssl_pkcs12_read', ['erro' => $erroOpenssl]);
            throw new \RuntimeException('Senha incorreta ou certificado .pfx corrompido.');
        }

        $tmpDir  = sys_get_temp_dir();
        $tmpCert = @tempnam($tmpDir, 'cert_c_');
        $tmpKey  = @tempnam($tmpDir, 'cert_k_');

        if ($tmpCert === false || $tmpKey === false) {
            Log::error('[Certificado] extrairPem: falha ao criar arquivos temporários', ['tmpDir' => $tmpDir]);
            throw new \RuntimeException("Não foi possível criar arquivos temporários em {$tmpDir}.");
        }

        file_put_contents($tmpCert, $certs['cert']);
        file_put_contents($tmpKey,  $certs['pkey']);

        Log::info('[Certificado] extrairPem: PEM extraído com sucesso', ['tmpCert' => $tmpCert]);

        return [$tmpCert, $tmpKey, [$tmpCert, $tmpKey]];
    }

    protected function extrairPemLegacy(string $pfxPath, string $senha): array
    {
        $tmpDir  = sys_get_temp_dir();
        $tmpCert = tempnam($tmpDir, 'cert_c_');
        $tmpKey  = tempnam($tmpDir, 'cert_k_');

        $cmdCert = sprintf(
            'openssl pkcs12 -legacy -in %s -passin pass:%s -clcerts -nokeys -out %s 2>&1',
            escapeshellarg($pfxPath),
            escapeshellarg($senha),
            escapeshellarg($tmpCert)
        );
        exec($cmdCert, $outCert, $exitCert);

        $cmdKey = sprintf(
            'openssl pkcs12 -legacy -in %s -passin pass:%s -nocerts -nodes -out %s 2>&1',
            escapeshellarg($pfxPath),
            escapeshellarg($senha),
            escapeshellarg($tmpKey)
        );
        exec($cmdKey, $outKey, $exitKey);

        if ($exitCert !== 0 || $exitKey !== 0) {
            @unlink($tmpCert);
            @unlink($tmpKey);
            Log::error('[Certificado] extrairPemLegacy: falha na extração', [
                'cert_saida' => implode("\n", $outCert),
                'key_saida'  => implode("\n", $outKey),
            ]);
            throw new \RuntimeException('Falha ao extrair certificado legacy.');
        }

        Log::info('[Certificado] extrairPemLegacy: PEM extraído com sucesso (modo legacy)');

        return [$tmpCert, $tmpKey, [$tmpCert, $tmpKey]];
    }

    /**
     * Descomprime um XML que vem como GZip em Base64 (docZip/ArquivoXml).
     */
    protected function descomprimirXml(string $xmlBase64Gzip): string
    {
        $binario = base64_decode($xmlBase64Gzip, true);

        if ($binario === false) {
            return $xmlBase64Gzip; // Pode já ser XML puro
        }

        $xml = @gzdecode($binario);

        if ($xml === false) {
            $xml = @gzinflate(substr($binario, 10, -8));
        }

        if ($xml === false) {
            $xml = @gzuncompress($binario);
        }

        if ($xml === false) {
            throw new \RuntimeException('Falha ao descomprimir o XML recebido da API.');
        }

        return $xml;
    }
}
