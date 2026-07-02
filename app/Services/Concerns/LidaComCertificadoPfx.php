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

        // Inclui a cadeia intermediária (extracerts) junto do certificado folha —
        // sem ela, ACs cujo intermediário não é conhecido de antemão pelo servidor
        // causam falha de validação da cadeia (HTTP 400 "Erro Cadeia de Certificação")
        // no handshake mTLS. Muitos .pfx de e-CNPJ são exportados sem os
        // intermediários embutidos, então complementamos com o cofre local.
        $conteudoCert = $certs['cert'];
        foreach ($certs['extracerts'] ?? [] as $extraCert) {
            $conteudoCert .= $extraCert;
        }
        $conteudoCert = $this->completarCadeiaComCofreLocal($conteudoCert);

        file_put_contents($tmpCert, $conteudoCert);
        file_put_contents($tmpKey,  $certs['pkey']);

        Log::info('[Certificado] extrairPem: PEM extraído com sucesso', ['tmpCert' => $tmpCert]);

        return [$tmpCert, $tmpKey, [$tmpCert, $tmpKey]];
    }

    protected function extrairPemLegacy(string $pfxPath, string $senha): array
    {
        $tmpDir  = sys_get_temp_dir();
        $tmpCert = tempnam($tmpDir, 'cert_c_');
        $tmpKey  = tempnam($tmpDir, 'cert_k_');

        // -chain inclui o certificado folha + a cadeia intermediária (equivalente
        // ao extracerts do openssl_pkcs12_read) — necessário para o servidor validar
        // a cadeia de confiança no handshake mTLS.
        $cmdCert = sprintf(
            'openssl pkcs12 -legacy -in %s -passin pass:%s -chain -nokeys -out %s 2>&1',
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

        $conteudoCert = $this->completarCadeiaComCofreLocal(file_get_contents($tmpCert));
        file_put_contents($tmpCert, $conteudoCert);

        Log::info('[Certificado] extrairPemLegacy: PEM extraído com sucesso (modo legacy)');

        return [$tmpCert, $tmpKey, [$tmpCert, $tmpKey]];
    }

    /**
     * Complementa a cadeia de certificação com os intermediários do ICP-Brasil
     * conhecidos localmente (resources/certificados-icp-brasil), para os casos em
     * que o .pfx do cliente foi exportado sem a cadeia embutida. Sobe a hierarquia
     * a partir do emissor do certificado folha até não encontrar mais nenhum
     * intermediário conhecido no cofre local (a AC Raiz não precisa ser enviada,
     * pois já é confiada pelo servidor).
     */
    private function completarCadeiaComCofreLocal(string $certPem): string
    {
        $blocos = $this->extrairBlocosCertificado($certPem);

        if (empty($blocos)) {
            return $certPem;
        }

        $folha = openssl_x509_parse($blocos[0]);

        if (!$folha) {
            return $certPem;
        }

        $presentes = [];
        foreach ($blocos as $bloco) {
            $info = openssl_x509_parse($bloco);
            if ($info && !empty($info['subject']['CN'])) {
                $presentes[$info['subject']['CN']] = true;
            }
        }

        $cofre     = $this->carregarCofreIntermediariosIcpBrasil();
        $issuerCN  = $folha['issuer']['CN'] ?? null;
        $visitados = [];
        $resultado = $certPem;

        while ($issuerCN && isset($cofre[$issuerCN]) && !isset($visitados[$issuerCN])) {
            $visitados[$issuerCN] = true;

            if (!isset($presentes[$issuerCN])) {
                $resultado .= $cofre[$issuerCN]['pem'];
                Log::info('[Certificado] completarCadeiaComCofreLocal: intermediário adicionado', ['cn' => $issuerCN]);
            }

            $issuerCN = $cofre[$issuerCN]['issuerCN'];
        }

        return $resultado;
    }

    /** Divide um blob PEM em blocos individuais de certificado. */
    private function extrairBlocosCertificado(string $pem): array
    {
        preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $pem, $matches);

        return $matches[0] ?? [];
    }

    /** Carrega os certificados intermediários do ICP-Brasil disponíveis localmente, indexados por CN. */
    private function carregarCofreIntermediariosIcpBrasil(): array
    {
        static $cofre = null;

        if ($cofre !== null) {
            return $cofre;
        }

        $cofre = [];

        foreach (glob(resource_path('certificados-icp-brasil') . '/*.crt') as $arquivo) {
            $pem  = file_get_contents($arquivo);
            $info = openssl_x509_parse($pem);

            if (!$info || empty($info['subject']['CN'])) {
                continue;
            }

            $cofre[$info['subject']['CN']] = [
                'pem'      => $pem,
                'issuerCN' => $info['issuer']['CN'] ?? null,
            ];
        }

        return $cofre;
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
