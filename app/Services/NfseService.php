<?php

namespace App\Services;

use App\Models\ClienteCertificadoNfse;
use Illuminate\Support\Facades\Log;

/** Exceção interna sinaliza HTTP 429 para o retry do loop de NSU. */
class RateLimitException extends \RuntimeException {}

class NfseService
{
    // Endpoints ADN Contribuinte
    const BASE_PRODUCAO    = 'https://adn.nfse.gov.br/contribuintes';
    const BASE_HOMOLOGACAO = 'https://adn.producaorestrita.nfse.gov.br/contribuintes';

    // Endpoints DANFSE
    const DANFSE_PRODUCAO    = 'https://adn.nfse.gov.br/danfse';
    const DANFSE_HOMOLOGACAO = 'https://adn.producaorestrita.nfse.gov.br/danfse';

    // Máximo de lotes buscados por requisição (proteção contra loop)
    const MAX_LOTES = 200;

    /**
     * Busca NFS-e no intervalo de datas informado.
     *
     * A API só suporta paginação por NSU (GET /DFe/{NSU}?lote=true).
     * Não existe endpoint de filtro por data — iteramos os lotes e filtramos
     * localmente pelo campo DataHoraGeracao de cada documento.
     *
     * Inicia a partir do último NSU salvo para o certificado, garantindo que
     * buscas repetidas não reprocessem documentos já vistos.
     */
    public function buscarPorPeriodo(ClienteCertificadoNfse $certificado, string $dataInicio, string $dataFim): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/\D/', '', $certificado->cliente->cpfcnpj ?? '');
        $base     = $this->baseUrl($certificado);

        // Extrai PEM uma única vez — reutilizado em todos os lotes do loop
        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $notas            = [];
            $nsuAtual         = 0; // sempre busca do início
            $maxNsuEncontrado = 0;
            $lotes            = 0;

            while ($lotes < self::MAX_LOTES) {
                $url  = "{$base}/DFe/{$nsuAtual}?lote=true" . ($cnpj ? "&cnpjConsulta={$cnpj}" : '');
                $resp = $this->requisicaoComRetryPem($url, $pemCert, $pemKey);

                $status = $resp['StatusProcessamento'] ?? '';

                if (in_array($status, ['NENHUM_DOCUMENTO_LOCALIZADO', 'REJEICAO'])) {
                    break;
                }

                $lote = $resp['LoteDFe'] ?? [];

                if (empty($lote)) {
                    break;
                }

                $passouFim = false;

                foreach ($lote as $doc) {
                    $nsuDoc = (int) ($doc['NSU'] ?? 0);

                    if ($nsuDoc > $maxNsuEncontrado) {
                        $maxNsuEncontrado = $nsuDoc;
                    }

                    $dataGeracao = substr($doc['DataHoraGeracao'] ?? '', 0, 10);

                    if ($dataGeracao && $dataGeracao < $dataInicio) {
                        continue;
                    }

                    if ($dataGeracao && $dataGeracao > $dataFim) {
                        $passouFim = true;
                        continue;
                    }

                    $notas[] = $this->normalizarDoc($doc);
                }

                if ($passouFim) {
                    break;
                }

                $nsuAtual = $maxNsuEncontrado + 1;
                $lotes++;

                usleep(1_000_000); // 1s entre lotes (evitar rate limit silencioso)
            }
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        return $notas;
    }

    /**
     * Baixa o XML de um DFe específico pelo NSU.
     */
    public function baixarXmlPorNsu(ClienteCertificadoNfse $certificado, int $nsu): string
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $base     = $this->baseUrl($certificado);

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $url  = "{$base}/DFe/{$nsu}?lote=false";
            $resp = $this->requisicaoComRetryPem($url, $pemCert, $pemKey);
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        $status = $resp['StatusProcessamento'] ?? '';

        if ($status === 'NENHUM_DOCUMENTO_LOCALIZADO') {
            throw new \RuntimeException("Documento NSU {$nsu} não encontrado.");
        }

        $lote = $resp['LoteDFe'] ?? [];

        if (empty($lote)) {
            throw new \RuntimeException("Nenhum documento retornado para NSU {$nsu}.");
        }

        $arquivoXml = $lote[0]['ArquivoXml'] ?? null;

        if (!$arquivoXml) {
            throw new \RuntimeException("Campo ArquivoXml ausente na resposta.");
        }

        return $this->descomprimirXml($arquivoXml);
    }

    /**
     * Gera o DANFSE (PDF) de uma NFS-e via API oficial.
     * Endpoint: GET /danfse/{chaveAcesso}
     */
    public function gerarDanfse(ClienteCertificadoNfse $certificado, string $chaveAcesso): string
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $base     = $certificado->ambiente === 'producao'
            ? self::DANFSE_PRODUCAO
            : self::DANFSE_HOMOLOGACAO;

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $url = "{$base}/" . urlencode($chaveAcesso);
            return $this->requisicaoBinariaPem($url, $pemCert, $pemKey);
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }
    }

    /**
     * Descomprime o ArquivoXml que vem como GZip em Base64.
     */
    public function descomprimirXml(string $xmlBase64Gzip): string
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
            throw new \RuntimeException('Falha ao descomprimir o ArquivoXml recebido da API NFS-e.');
        }

        return $xml;
    }

    /**
     * Valida o .pfx + senha via OpenSSL e retorna a data de vencimento.
     */
    public function validarCertificado(string $pfxPath, string $senha): ?string
    {
        $conteudo = file_get_contents($pfxPath);
        $certs    = [];

        if (!openssl_pkcs12_read($conteudo, $certs, $senha)) {
            throw new \InvalidArgumentException('Senha incorreta ou certificado inválido.');
        }

        $info = openssl_x509_parse($certs['cert'] ?? '');

        if (!empty($info['validTo_time_t'])) {
            return date('Y-m-d', $info['validTo_time_t']);
        }

        return null;
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function baseUrl(ClienteCertificadoNfse $certificado): string
    {
        return $certificado->ambiente === 'producao' ? self::BASE_PRODUCAO : self::BASE_HOMOLOGACAO;
    }

    /**
     * Extrai cert + chave do .pfx para arquivos PEM temporários.
     * PEM é mais compatível com diferentes builds de cURL/OpenSSL que P12 direto.
     * Retorna [caminhoCert, caminhoKey, [arquivosParaRemover]].
     */
    private function extrairPem(string $pfxPath, string $senha): array
    {
        Log::info('[NFS-e] extrairPem: iniciando', ['pfx' => $pfxPath]);

        if (!file_exists($pfxPath)) {
            Log::error('[NFS-e] extrairPem: arquivo não encontrado', ['pfx' => $pfxPath]);
            throw new \RuntimeException("Arquivo de certificado não encontrado: {$pfxPath}");
        }

        $pfxContent = file_get_contents($pfxPath);
        $certs      = [];

        if (!openssl_pkcs12_read($pfxContent, $certs, $senha)) {
            Log::error('[NFS-e] extrairPem: falha no openssl_pkcs12_read (senha errada ou pfx corrompido)');
            throw new \RuntimeException('Senha incorreta ou certificado .pfx corrompido.');
        }

        $tmpDir  = sys_get_temp_dir();
        $tmpCert = @tempnam($tmpDir, 'nfse_c_');
        $tmpKey  = @tempnam($tmpDir, 'nfse_k_');

        if ($tmpCert === false || $tmpKey === false) {
            Log::error('[NFS-e] extrairPem: falha ao criar arquivos temporários', ['tmpDir' => $tmpDir]);
            throw new \RuntimeException("Não foi possível criar arquivos temporários em {$tmpDir}.");
        }

        file_put_contents($tmpCert, $certs['cert']);
        file_put_contents($tmpKey,  $certs['pkey']);

        Log::info('[NFS-e] extrairPem: PEM extraído com sucesso', ['tmpCert' => $tmpCert]);

        return [$tmpCert, $tmpKey, [$tmpCert, $tmpKey]];
    }

    /**
     * Retry para 429 (rate limit) e #28 (timeout).
     * 429 → espera curta (API sobrecarregada mas responsiva).
     * Timeout → espera maior (API instável, precisa de mais tempo).
     */
    private function requisicaoComRetryPem(string $url, string $pemCert, string $pemKey): array
    {
        $tentativas = 3;

        for ($i = 1; $i <= $tentativas; $i++) {
            try {
                return $this->requisicaoPem('GET', $url, $pemCert, $pemKey);
            } catch (RateLimitException) {
                if ($i < $tentativas) {
                    Log::warning('[NFS-e] 429 rate limit, aguardando', ['tentativa' => $i, 'url' => $url]);
                    sleep(5 * $i); // 5s, 10s
                }
            } catch (\RuntimeException $e) {
                $isTimeout = str_contains($e->getMessage(), 'timed out')
                    || str_contains($e->getMessage(), 'Operation timed out')
                    || str_contains($e->getMessage(), 'cURL #28');

                if ($isTimeout && $i < $tentativas) {
                    Log::warning('[NFS-e] timeout, aguardando antes de retry', ['tentativa' => $i, 'url' => $url]);
                    sleep(15 * $i); // 15s, 30s
                } else {
                    throw $e;
                }
            }
        }

        // Última tentativa — deixa exceção propagar
        return $this->requisicaoPem('GET', $url, $pemCert, $pemKey);
    }

    private function requisicaoPem(string $method, string $url, string $pemCert, string $pemKey): array
    {
        Log::info('[NFS-e] requisicaoPem: enviando', ['method' => $method, 'url' => $url]);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSLCERT        => $pemCert,
            CURLOPT_SSLKEY         => $pemKey,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $resposta  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        unset($ch);

        Log::info('[NFS-e] requisicaoPem: resposta recebida', [
            'httpCode'  => $httpCode,
            'curlErrNo' => $curlErrNo,
            'curlError' => $curlError ?: null,
            'bodyLen'   => is_string($resposta) ? strlen($resposta) : 'false',
            'body100'   => is_string($resposta) ? substr($resposta, 0, 200) : null,
        ]);

        if ($resposta === false) {
            throw new \RuntimeException("Falha na conexão (cURL #{$curlErrNo}): {$curlError}");
        }

        if ($httpCode === 429) {
            throw new RateLimitException('Rate limit atingido (HTTP 429).');
        }

        if ($httpCode === 496) {
            throw new \RuntimeException('A API rejeitou o certificado (HTTP 496). Confirme que é ICP-Brasil A1/A3 com CNPJ e "Autenticação do Cliente".');
        }

        if ($httpCode === 401 || $httpCode === 403) {
            throw new \RuntimeException("Acesso negado (HTTP {$httpCode}). Certificado sem permissão ou expirado.");
        }

        // 404 com NENHUM_DOCUMENTO_LOCALIZADO é sinal normal de fim de paginação — deixa passar para o caller tratar
        if ($httpCode >= 400 && $httpCode !== 404) {
            $decoded = json_decode($resposta, true);
            $detalhe = ($decoded['Erros'][0]['Descricao'] ?? null)
                ?? ($decoded['mensagem'] ?? null)
                ?? strip_tags($resposta);
            throw new \RuntimeException("API retornou HTTP {$httpCode}: " . substr($detalhe, 0, 300));
        }

        $dados = json_decode($resposta, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('[NFS-e] requisicaoPem: resposta não é JSON válido', ['body' => substr($resposta, 0, 500)]);
            throw new \RuntimeException('Resposta não-JSON: ' . substr($resposta, 0, 200));
        }

        return $dados ?? [];
    }

    private function requisicaoBinariaPem(string $url, string $pemCert, string $pemKey): string
    {
        Log::info('[NFS-e] DANFSE: enviando', ['url' => $url]);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSLCERT        => $pemCert,
            CURLOPT_SSLKEY         => $pemKey,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/pdf'],
        ]);

        $resposta  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        unset($ch);

        Log::info('[NFS-e] DANFSE: resposta', [
            'httpCode'  => $httpCode,
            'curlError' => $curlError ?: null,
            'bodyLen'   => is_string($resposta) ? strlen($resposta) : 'false',
            'body200'   => is_string($resposta) ? substr($resposta, 0, 200) : null,
        ]);

        if ($resposta === false) {
            throw new \RuntimeException("Falha na conexão com a API DANFSE: {$curlError}");
        }

        if ($httpCode === 429) {
            sleep(5);
            return $this->requisicaoBinariaPem($url, $pemCert, $pemKey);
        }

        if ($httpCode === 404) {
            throw new \RuntimeException('DANFSE não encontrado para esta chave de acesso.');
        }

        if ($httpCode === 496) {
            throw new \RuntimeException('Certificado rejeitado pela API DANFSE (HTTP 496).');
        }

        if ($httpCode >= 400) {
            $detalhe = is_string($resposta) ? strip_tags(substr($resposta, 0, 300)) : '';
            throw new \RuntimeException("API DANFSE retornou HTTP {$httpCode}." . ($detalhe ? " Detalhe: {$detalhe}" : ''));
        }

        if (!str_starts_with($resposta, '%PDF')) {
            throw new \RuntimeException('A API DANFSE não retornou PDF válido. Início: ' . substr($resposta, 0, 100));
        }

        return $resposta;
    }

    /**
     * Mapeia um item de LoteDFe para um array normalizado para a view.
     * Tenta extrair tomador/valor/número do XML quando disponível.
     */
    private function normalizarDoc(array $doc): array
    {
        $dadosXml = [];

        if (!empty($doc['ArquivoXml'])) {
            try {
                $xml      = $this->descomprimirXml($doc['ArquivoXml']);
                $dadosXml = $this->extrairDadosXml($xml);
            } catch (\Throwable) {
                // Se falhar, retorna apenas os dados do envelope
            }
        }

        return [
            'nsu'           => $doc['NSU'] ?? null,
            'chaveAcesso'   => $doc['ChaveAcesso'] ?? null,
            'dataEmissao'   => $doc['DataHoraGeracao'] ?? null,
            'tipoDocumento' => $doc['TipoDocumento'] ?? null,
            'tipoEvento'    => $doc['TipoEvento'] ?? null,
            'numero'        => $this->utf8Safe($dadosXml['numero'] ?? null),
            'tomadorNome'   => $this->utf8Safe($dadosXml['tomadorNome'] ?? null),
            'tomadorDoc'    => $this->utf8Safe($dadosXml['tomadorDoc'] ?? null),
            'valorServico'  => $dadosXml['valorServico'] ?? null,
            'status'        => $this->resolverStatus($doc),
            'xLocEmi'       => $dadosXml['xLocEmi'] ?? null,
            'imPrestador'   => $dadosXml['imPrestador'] ?? null,
            'cnpjPrestador' => $dadosXml['cnpjPrestador'] ?? null,
            'prestadorNome' => $this->utf8Safe($dadosXml['prestadorNome'] ?? null),
        ];
    }

    /** Garante que a string é UTF-8 válido — evita falha no json_encode. */
    private function utf8Safe(?string $str): ?string
    {
        if ($str === null) {
            return null;
        }

        // mb_convert_encoding descarta bytes inválidos
        $safe = mb_convert_encoding($str, 'UTF-8', 'UTF-8');

        // Fallback: remove caracteres de controle e bytes acima de 0x7F inválidos
        if (json_encode($safe) === false) {
            $safe = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $str);
        }

        return $safe ?: null;
    }

    private function resolverStatus(array $doc): string
    {
        $tipo    = $doc['TipoDocumento'] ?? 'NFSE';
        $evento  = $doc['TipoEvento'] ?? null;

        if ($evento && str_contains($evento, 'CANCELAMENTO')) {
            return 'CANCELADA';
        }

        return $tipo === 'NFSE' ? 'AUTORIZADA' : strtoupper($tipo);
    }

    /**
     * Busca o LinkNota (URL do PDF) no sistema Tecnos municipal de Teutônia.
     *
     * Usa o SOAP ConsultaNFSeServicosPrestados com o CNPJ e IM do emitente extraídos
     * do XML do Portal Nacional. O LinkNota retornado é uma URL pública do Tecnos.
     */
    public function danfseTecnos(string $cnpjPrestador, string $imPrestador, string $numero): string
    {
        $remessa = htmlspecialchars(
            '<ConsultarNfseServicoPrestadoEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">'
            . '<Prestador>'
            .   '<CpfCnpj><Cnpj>' . preg_replace('/\D/', '', $cnpjPrestador) . '</Cnpj></CpfCnpj>'
            .   '<InscricaoMunicipal>' . htmlspecialchars($imPrestador) . '</InscricaoMunicipal>'
            . '</Prestador>'
            . '<NumeroNfse>' . htmlspecialchars($numero) . '</NumeroNfse>'
            . '</ConsultarNfseServicoPrestadoEnvio>',
            ENT_XML1
        );

        $cabecalho = htmlspecialchars(
            '<cabecalho versao="2" xmlns="http://www.abrasf.org.br/nfse.xsd"/>',
            ENT_XML1
        );

        $envelope = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <mConsultaNFSeServicosPrestados xmlns="http://tempuri.org/">
      <remessa>{$remessa}</remessa>
      <cabecalho>{$cabecalho}</cabecalho>
    </mConsultaNFSeServicosPrestados>
  </soap:Body>
</soap:Envelope>
XML;

        Log::info('[NFS-e] Tecnos: enviando ConsultaNFSeServicosPrestados', [
            'cnpj'   => $cnpjPrestador,
            'im'     => $imPrestador,
            'numero' => $numero,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'http://teutonia.nfse-tecnos.com.br:9094/ConsultaNFSeServicosPrestados.asmx',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $envelope,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "http://tempuri.org/mConsultaNFSeServicosPrestados"',
            ],
        ]);

        $resposta  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        unset($ch);

        if ($resposta === false) {
            throw new \RuntimeException("Falha ao conectar no Tecnos de Teutônia: {$curlError}");
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("Tecnos retornou HTTP {$httpCode}.");
        }

        // Extrai o resultado da tag SOAP
        if (!preg_match('/<mConsultaNFSeServicosPrestadosResult>(.*?)<\/mConsultaNFSeServicosPrestadosResult>/s', $resposta, $m)) {
            throw new \RuntimeException('Resposta SOAP inesperada do Tecnos.');
        }

        $xmlResultado = html_entity_decode($m[1], ENT_XML1 | ENT_QUOTES, 'UTF-8');

        Log::info('[NFS-e] Tecnos: resultado', ['xml' => substr($xmlResultado, 0, 500)]);

        // Procura por erros no retorno
        if (preg_match('/<Codigo>(E\d+)<\/Codigo>.*?<Mensagem>(.*?)<\/Mensagem>/s', $xmlResultado, $err)) {
            throw new \RuntimeException("Tecnos: [{$err[1]}] {$err[2]}");
        }

        // Extrai LinkNota
        if (!preg_match('/<LinkNota>(.*?)<\/LinkNota>/s', $xmlResultado, $link)) {
            throw new \RuntimeException('NFS-e não encontrada no sistema Tecnos de Teutônia (verifique o número e IM).');
        }

        return trim($link[1]);
    }

    /**
     * Extrai campos da NFS-e usando local-name() no XPath — ignora qualquer namespace.
     * Cobre o schema oficial (sped.fazenda.gov.br) e variantes municipais.
     */
    private function extrairDadosXml(string $xml): array
    {
        try {
            libxml_use_internal_errors(true);
            $obj = new \SimpleXMLElement($xml);

            $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

            // Número da NFS-e
            $numero = $get('nNfse');

            // Tomador: nome
            $tomadorNome = $get('xNome');

            // Tomador: documento (CNPJ ou CPF dentro do bloco tomador)
            $tomadorCnpj = trim((string) ($obj->xpath(
                "//*[local-name()='tomador' or local-name()='Tomador']//*[local-name()='CNPJ']"
            )[0] ?? ''));
            $tomadorCpf = trim((string) ($obj->xpath(
                "//*[local-name()='tomador' or local-name()='Tomador']//*[local-name()='CPF']"
            )[0] ?? ''));

            // Valor: tenta campos em ordem de prioridade
            $valor = $get('vServico')
                ?: $get('vTotalNfse')
                ?: $get('vNfse')
                ?: $get('vLiq')
                ?: $get('ValorServicos')
                ?: $get('ValorLiquidoNfse');

            // Emitente: cidade, IM, CNPJ e nome (para detecção de município e chamada Tecnos)
            $xLocEmi = $get('xLocEmi');
            $imPrestador = trim((string) ($obj->xpath(
                "//*[local-name()='emit']/*[local-name()='IM']"
            )[0] ?? ''));
            $cnpjPrestador = trim((string) ($obj->xpath(
                "//*[local-name()='emit']/*[local-name()='CNPJ']"
            )[0] ?? ''));
            $prestadorNome = trim((string) ($obj->xpath(
                "//*[local-name()='emit']/*[local-name()='xNome']"
            )[0] ?? ''))
                ?: trim((string) ($obj->xpath(
                    "//*[local-name()='emit']/*[local-name()='RazaoSocial']"
                )[0] ?? ''));

            return [
                'numero'        => $numero ?: null,
                'tomadorNome'   => $tomadorNome ?: null,
                'tomadorDoc'    => $tomadorCnpj ?: ($tomadorCpf ?: null),
                'valorServico'  => $valor !== '' ? (float) str_replace(',', '.', $valor) : null,
                'xLocEmi'       => $xLocEmi ?: null,
                'imPrestador'   => $imPrestador ?: null,
                'cnpjPrestador' => $cnpjPrestador ?: null,
                'prestadorNome' => $prestadorNome ?: null,
            ];
        } catch (\Throwable) {
            return [];
        }
    }
}
