<?php

namespace App\Services;

use App\Models\ClienteCertificadoNfse;
use App\Services\Concerns\LidaComCertificadoPfx;
use Illuminate\Support\Facades\Log;

/** Exceção interna sinaliza HTTP 429 para o retry do loop de NSU. */
class RateLimitException extends \RuntimeException {}

class NfseService
{
    use LidaComCertificadoPfx;

    // Endpoints ADN Contribuinte
    const BASE_PRODUCAO    = 'https://adn.nfse.gov.br/contribuintes';
    const BASE_HOMOLOGACAO = 'https://adn.producaorestrita.nfse.gov.br/contribuintes';

    // Endpoints DANFSE
    const DANFSE_PRODUCAO    = 'https://adn.nfse.gov.br/danfse';
    const DANFSE_HOMOLOGACAO = 'https://adn.producaorestrita.nfse.gov.br/danfse';

    // Máximo de lotes buscados por chamada (chunk). O Cloudflare na frente da
    // aplicação derruba a conexão com HTTP 524 se o backend não responder em
    // ~100s, então cada chamada processa só uma fatia — o front-end chama
    // repetidas vezes (nsuInicio = proximoNsu do chunk anterior) até concluido=true.
    const MAX_LOTES_POR_CHUNK = 12;

    // Intervalo mínimo entre requisições ao ADN — dispara os lotes em rajada
    // estoura o rate limit deles (HTTP 429) e cada 429 custa 10-20s de espera
    // reativa. Espaçando as chamadas evitamos boa parte desses 429.
    const INTERVALO_MIN_REQ_US = 300_000; // 0.3s

    private static ?float $ultimaRequisicaoEm = null;

    /**
     * Busca uma fatia (chunk) de NFS-e no intervalo de datas informado, a partir
     * do NSU indicado.
     *
     * A API só suporta paginação por NSU (GET /DFe/{NSU}?lote=true). Não existe
     * endpoint de filtro por data — iteramos os lotes e filtramos localmente
     * pelo campo DataHoraGeracao de cada documento.
     *
     * Processa no máximo MAX_LOTES_POR_CHUNK lotes por chamada — o Cloudflare
     * na frente da aplicação derruba a conexão (HTTP 524) se o backend demorar
     * demais pra responder, então quem orquestra a busca completa (o front-end)
     * chama este método repetidamente, usando 'proximoNsu' da resposta anterior
     * como 'nsuInicio' da próxima, até 'concluido' vir true.
     *
     * @return array{notas: array, proximoNsu: int, concluido: bool, canceledChaves: array<string>}
     */
    public function buscarPorPeriodoChunk(ClienteCertificadoNfse $certificado, string $dataInicio, string $dataFim, int $nsuInicio = 0): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $certificado->cliente->cpfcnpj ?? '');
        $base     = $this->baseUrl($certificado);

        // Extrai PEM uma única vez — reutilizado em todos os lotes do chunk
        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $notas            = [];
            $canceledChaves   = []; // chaves de eventos de cancelamento encontradas neste chunk
            $nsuAtual         = $nsuInicio;
            $maxNsuEncontrado = $nsuInicio > 0 ? $nsuInicio - 1 : 0;
            $lotes            = 0;
            $concluido        = false;

            while ($lotes < self::MAX_LOTES_POR_CHUNK) {
                $url  = "{$base}/DFe/{$nsuAtual}?lote=true" . ($cnpj ? "&cnpjConsulta={$cnpj}" : '');
                $resp = $this->requisicaoComRetryPem($url, $pemCert, $pemKey);

                $status = $resp['StatusProcessamento'] ?? '';

                if (in_array($status, ['NENHUM_DOCUMENTO_LOCALIZADO', 'REJEICAO'])) {
                    $concluido = true;
                    break;
                }

                $lote = $resp['LoteDFe'] ?? [];

                if (empty($lote)) {
                    $concluido = true;
                    break;
                }

                $passouFim = false;

                foreach ($lote as $doc) {
                    $nsuDoc = (int) ($doc['NSU'] ?? 0);

                    if ($nsuDoc > $maxNsuEncontrado) {
                        $maxNsuEncontrado = $nsuDoc;
                    }

                    $tipo = $doc['TipoDocumento'] ?? 'NFSE';

                    // Eventos de cancelamento: registra a chave e ignora como linha separada
                    if ($tipo === 'EVENTO' && str_contains($doc['TipoEvento'] ?? '', 'CANCELAMENTO')) {
                        if (!empty($doc['ChaveAcesso'])) {
                            $canceledChaves[$doc['ChaveAcesso']] = true;
                        }
                        continue;
                    }

                    if (!in_array($tipo, ['NFSE', 'NENHUM'])) {
                        continue;
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
                    $concluido = true;
                    break;
                }

                $nsuAtual = $maxNsuEncontrado + 1;
                $lotes++;
            }

            // Marca como CANCELADA as notas cuja chave apareceu num evento de
            // cancelamento dentro deste mesmo chunk. Cancelamentos referentes a
            // notas de chunks anteriores são resolvidos pelo chamador (front-end),
            // que acumula 'canceledChaves' de todos os chunks e reaplica no final.
            if (!empty($canceledChaves)) {
                foreach ($notas as &$nota) {
                    if (!empty($nota['chaveAcesso']) && isset($canceledChaves[$nota['chaveAcesso']])) {
                        $nota['status'] = 'CANCELADA';
                    }
                }
                unset($nota);
            }

            // A varredura acima seleciona os lotes pela data de PROCESSAMENTO no ADN
            // (único filtro que a API permite via NSU). A data de EMISSÃO real de uma
            // nota pode cair fora do período pedido (ex.: nota emitida em maio mas só
            // disponibilizada/processada em junho). Filtra o resultado final pela data
            // de emissão real para respeitar o período solicitado pelo usuário.
            $notas = array_values(array_filter($notas, function ($nota) use ($dataInicio, $dataFim) {
                $dataEmissao = substr($nota['dataEmissao'] ?? '', 0, 10);
                return !$dataEmissao || ($dataEmissao >= $dataInicio && $dataEmissao <= $dataFim);
            }));
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        return [
            'notas'          => $notas,
            'proximoNsu'     => $maxNsuEncontrado + 1,
            'concluido'      => $concluido,
            'canceledChaves' => array_keys($canceledChaves),
        ];
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
     * Envia a DPS (já assinada digitalmente) para emissão da NFS-e (POST /nfse).
     *
     * NOTA: o manual não documenta um exemplo de request — o formato do body
     * abaixo (envelope JSON com o XML em GZip+Base64, espelhando o formato já
     * usado no retorno da consulta) precisa ser confirmado contra o Swagger de
     * homologação antes de considerar este método pronto para produção.
     */
    public function enviarDps(ClienteCertificadoNfse $certificado, string $xmlDpsAssinado): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $base     = $this->baseUrl($certificado);

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $body = json_encode(['dpsXmlGZipB64' => base64_encode(gzencode($xmlDpsAssinado))]);
            return $this->requisicaoPem('POST', "{$base}/nfse", $pemCert, $pemKey, $body);
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }
    }

    /**
     * Envia um Pedido de Registro de Evento (cancelamento, substituição etc.)
     * para uma NFS-e já emitida (POST /nfse/{chaveAcesso}/eventos).
     *
     * NOTA: mesma ressalva do enviarDps() quanto ao formato exato do payload.
     */
    public function enviarEvento(ClienteCertificadoNfse $certificado, string $chaveAcesso, string $xmlEventoAssinado): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $base     = $this->baseUrl($certificado);

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $body = json_encode(['pedidoRegistroEventoXmlGZipB64' => base64_encode(gzencode($xmlEventoAssinado))]);
            return $this->requisicaoPem('POST', "{$base}/nfse/" . urlencode($chaveAcesso) . '/eventos', $pemCert, $pemKey, $body);
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }
    }

    /**
     * Valida o .pfx + senha via OpenSSL e retorna a data de vencimento.
     */
    public function validarCertificado(string $pfxPath, string $senha): ?string
    {
        $conteudo = file_get_contents($pfxPath);
        $certs    = [];

        while (openssl_error_string() !== false);

        if (openssl_pkcs12_read($conteudo, $certs, $senha)) {
            $info = openssl_x509_parse($certs['cert'] ?? '');
            return !empty($info['validTo_time_t']) ? date('Y-m-d', $info['validTo_time_t']) : null;
        }

        $erroOpenssl = '';
        while ($err = openssl_error_string()) {
            $erroOpenssl .= $err;
        }

        // Certificado com criptografia legada (RC2/3DES) — incompatível com OpenSSL 3.0 sem -legacy
        if (str_contains($erroOpenssl, 'unsupported') || str_contains($erroOpenssl, 'legacy')) {
            Log::info('[Certificado] Algoritmo legado detectado, tentando via CLI com -legacy');
            return $this->validarCertificadoLegacy($pfxPath, $senha);
        }

        throw new \InvalidArgumentException('Senha incorreta ou certificado inválido.');
    }

    private function validarCertificadoLegacy(string $pfxPath, string $senha): ?string
    {
        $tmpPem = tempnam(sys_get_temp_dir(), 'nfse_v_');

        try {
            $cmd    = sprintf(
                'openssl pkcs12 -legacy -in %s -passin pass:%s -clcerts -nokeys -out %s 2>&1',
                escapeshellarg($pfxPath),
                escapeshellarg($senha),
                escapeshellarg($tmpPem)
            );
            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0) {
                Log::error('[Certificado] CLI legacy falhou', ['saida' => implode("\n", $output)]);
                throw new \InvalidArgumentException('Senha incorreta ou certificado inválido.');
            }

            $cert = openssl_x509_read(file_get_contents($tmpPem));
            if (!$cert) {
                throw new \InvalidArgumentException('Não foi possível ler o certificado após extração legacy.');
            }

            $info = openssl_x509_parse($cert);
            return !empty($info['validTo_time_t']) ? date('Y-m-d', $info['validTo_time_t']) : null;
        } finally {
            if (file_exists($tmpPem)) {
                @unlink($tmpPem);
            }
        }
    }

    // ─── Helpers privados ────────────────────────────────────────────────────

    private function baseUrl(ClienteCertificadoNfse $certificado): string
    {
        return $certificado->ambiente === 'producao' ? self::BASE_PRODUCAO : self::BASE_HOMOLOGACAO;
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
                    sleep(10 * $i); // 10s, 20s
                }
            } catch (\RuntimeException $e) {
                $msg = $e->getMessage();
                $isTimeout = str_contains($msg, 'timed out')
                    || str_contains($msg, 'Operation timed out')
                    || str_contains($msg, 'cURL #28');

                // Falha de handshake/leitura TLS do ADN (bad record mac, reset) — em
                // geral some numa nova tentativa com conexão limpa.
                $isTlsGlitch = str_contains($msg, 'cURL #56')
                    || str_contains($msg, 'cURL #35')
                    || str_contains($msg, 'decryption failed or bad record mac');

                if (($isTimeout || $isTlsGlitch) && $i < $tentativas) {
                    Log::warning('[NFS-e] falha de rede/TLS, aguardando antes de retry', ['tentativa' => $i, 'url' => $url, 'erro' => $msg]);
                    sleep(($isTlsGlitch ? 3 : 15) * $i);
                } else {
                    throw $e;
                }
            }
        }

        // Última tentativa — deixa exceção propagar
        return $this->requisicaoPem('GET', $url, $pemCert, $pemKey);
    }

    /** Garante um espaçamento mínimo entre chamadas ao ADN para não estourar o rate limit deles. */
    private function aguardarIntervaloMinimo(): void
    {
        if (self::$ultimaRequisicaoEm !== null) {
            $decorridoUs = (microtime(true) - self::$ultimaRequisicaoEm) * 1_000_000;
            $faltaUs     = self::INTERVALO_MIN_REQ_US - $decorridoUs;

            if ($faltaUs > 0) {
                usleep((int) $faltaUs);
            }
        }

        self::$ultimaRequisicaoEm = microtime(true);
    }

    private function requisicaoPem(string $method, string $url, string $pemCert, string $pemKey, ?string $body = null): array
    {
        $this->aguardarIntervaloMinimo();

        Log::debug('[NFS-e] requisicaoPem: enviando', ['method' => $method, 'url' => $url, 'temBody' => $body !== null]);

        $ch = curl_init();

        $opcoes = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSLCERT        => $pemCert,
            CURLOPT_SSLKEY         => $pemKey,
            CURLOPT_SSL_VERIFYPEER => true,
            // O ADN (Cloudflare + OpenSSL 3) fecha a conexão com "decryption failed
            // or bad record mac" (cURL #56) quando negocia TLS 1.3 ou reaproveita
            // uma conexão keep-alive já corrompida. Fixar TLS 1.2, HTTP/1.1 e
            // forçar conexão nova elimina esse erro intermitente.
            CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_FORBID_REUSE   => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
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

        Log::debug('[NFS-e] requisicaoPem: resposta recebida', [
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
        Log::debug('[NFS-e] DANFSE: enviando', ['url' => $url]);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSLCERT        => $pemCert,
            CURLOPT_SSLKEY         => $pemKey,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSLVERSION     => CURL_SSLVERSION_TLSv1_2,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_FRESH_CONNECT  => true,
            CURLOPT_FORBID_REUSE   => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/pdf'],
        ]);

        $resposta  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        unset($ch);

        Log::debug('[NFS-e] DANFSE: resposta', [
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
        $dadosXml   = [];
        $xmlContent = null;

        if (!empty($doc['ArquivoXml'])) {
            try {
                $xml        = $this->descomprimirXml($doc['ArquivoXml']);
                $xmlContent = $xml;
                $dadosXml   = $this->extrairDadosXml($xml);
            } catch (\Throwable) {
                // Se falhar, retorna apenas os dados do envelope
            }
        }

        return [
            'nsu'           => $doc['NSU'] ?? null,
            'chaveAcesso'   => $doc['ChaveAcesso'] ?? null,
            'dataEmissao'   => $dadosXml['dataEmissao'] ?? ($doc['DataHoraGeracao'] ?? null),
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
            'xmlContent'    => $xmlContent,
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
            .   '<CpfCnpj><Cnpj>' . preg_replace('/[.\-\/\s]/', '', $cnpjPrestador) . '</Cnpj></CpfCnpj>'
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

        Log::debug('[NFS-e] Tecnos: enviando ConsultaNFSeServicosPrestados', [
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

        Log::debug('[NFS-e] Tecnos: resultado', ['xml' => substr($xmlResultado, 0, 500)]);

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
            $numero = $get('nNFSe') ?: $get('Numero');

            // Data/hora de emissão da NFS-e propriamente dita (dhProc, em infNFSe).
            // Diferente de dhEmi (emissão da DPS, o documento enviado pelo prestador)
            // e de dCompet (competência) — é o campo que o DANFSe rotula como
            // "Data e Hora da emissão da NFS-e" e que deve valer para o filtro/exibição.
            $dhEmi = $get('dhProc') ?: ($get('dhEmi') ?: $get('DataEmissao'));

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
            $valor = $get('vServ')
                ?: $get('ValorServicos')
                ?: $get('vTotalNfse')
                ?: $get('vNfse')
                ?: $get('vLiq')
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
                'dataEmissao'   => $dhEmi ?: null,
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
