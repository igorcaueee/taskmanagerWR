<?php

namespace App\Services;

use App\Models\ClienteCertificadoNfse;
use App\Services\Concerns\LidaComCertificadoPfx;
use Illuminate\Support\Facades\Log;

/**
 * Busca NF-e e CT-e via webservice nacional NFeDistribuicaoDFe (Ambiente Nacional),
 * usando o mesmo certificado digital A1 já cadastrado para NFS-e (mTLS por CNPJ).
 *
 * Diferente da API de NFS-e (REST/JSON), este é um webservice SOAP 1.2. Documentos
 * ficam disponíveis por até ~3 meses após a emissão/recepção pelo Ambiente Nacional,
 * e NF-e sem manifestação do destinatário podem não aparecer na distribuição.
 *
 * NFC-e não é distribuída por este serviço (não tem destinatário identificado na
 * maioria dos casos) — fora do escopo desta integração.
 */
class NfeService
{
    use LidaComCertificadoPfx;

    const ENDPOINT_PRODUCAO    = 'https://www1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx';
    const ENDPOINT_HOMOLOGACAO = 'https://hom1.nfe.fazenda.gov.br/NFeDistribuicaoDFe/NFeDistribuicaoDFe.asmx';

    const SOAP_ACTION = 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe/nfeDistDFeInteresse';
    const VERSAO      = '1.35';

    // Máximo de lotes buscados por requisição (proteção contra loop)
    const MAX_LOTES = 200;

    // Código IBGE da UF — usado em cUFAutor. Índice: sigla da UF cadastrada no cliente.
    const UF_CODIGO = [
        'RO' => 11, 'AC' => 12, 'AM' => 13, 'RR' => 14, 'PA' => 15, 'AP' => 16, 'TO' => 17,
        'MA' => 21, 'PI' => 22, 'CE' => 23, 'RN' => 24, 'PB' => 25, 'PE' => 26, 'AL' => 27, 'SE' => 28, 'BA' => 29,
        'MG' => 31, 'ES' => 32, 'RJ' => 33, 'SP' => 35,
        'PR' => 41, 'SC' => 42, 'RS' => 43,
        'MS' => 50, 'MT' => 51, 'GO' => 52, 'DF' => 53,
    ];

    /**
     * Busca NF-e/CT-e no intervalo de datas informado.
     *
     * A API só suporta paginação por NSU (distNSU/ultNSU). Não existe filtro por
     * data no request — iteramos os lotes e filtramos localmente pela data de
     * emissão de cada documento resumido no docZip.
     *
     * Inicia a partir do último NSU salvo (ultimo_nsu_nfe) para o certificado.
     */
    public function buscarPorPeriodo(ClienteCertificadoNfse $certificado, string $dataInicio, string $dataFim): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $certificado->cliente->cpfcnpj ?? '');
        $cUFAutor = $this->cUFAutor($certificado);
        $tpAmb    = $certificado->ambiente === 'producao' ? 1 : 2;
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $documentos = [];
            $nsuAtual   = (int) $certificado->ultimo_nsu_nfe;
            $lotes      = 0;

            while ($lotes < self::MAX_LOTES) {
                $resp = $this->consultarNsu($endpoint, $tpAmb, $cUFAutor, $cnpj, $nsuAtual, $pemCert, $pemKey);

                $cStat = $resp['cStat'] ?? '';

                // 656 = consumo indevido (mesmo ultNSU repetido/consultas em excesso) — a Sefaz
                // bloqueia o CNPJ por um tempo. Não adianta retentar automaticamente.
                if ($cStat === '656') {
                    throw new \RuntimeException(
                        'A Sefaz bloqueou temporariamente as consultas deste certificado por uso indevido do NSU '
                        . '(reenvio do mesmo ultNSU). ' . ($resp['xMotivo'] ?: 'Aguarde o tempo indicado pela Sefaz antes de tentar novamente.')
                    );
                }

                // 137 = nenhum documento localizado (já está em dia com o servidor)
                if ($cStat === '137') {
                    // ultNSU da resposta confirma o ponto em que ficamos "em dia" — persiste mesmo sem documentos.
                    if (isset($resp['ultNSU'])) {
                        $certificado->update(['ultimo_nsu_nfe' => (int) $resp['ultNSU']]);
                    }
                    break;
                }

                if ($cStat !== '138') {
                    throw new \RuntimeException("Distribuição DFe retornou cStat {$cStat}: " . ($resp['xMotivo'] ?? 'motivo desconhecido'));
                }

                if ($resp['ultNSU'] === null || $resp['maxNSU'] === null) {
                    throw new \RuntimeException('Resposta cStat 138 sem ultNSU/maxNSU — não é possível paginar com segurança.');
                }

                $passouFim = false;

                foreach ($resp['docs'] as $doc) {
                    if ($doc['tipo'] === 'evento') {
                        continue; // eventos (cancelamento, manifestação) não viram linha própria por ora
                    }

                    $dataEmissao = substr($doc['dataEmissao'] ?? '', 0, 10);

                    if ($dataEmissao && $dataEmissao < $dataInicio) {
                        continue;
                    }

                    if ($dataEmissao && $dataEmissao > $dataFim) {
                        $passouFim = true;
                        continue;
                    }

                    $documentos[] = $doc;
                }

                // Avança pelo ultNSU retornado pela própria Sefaz (não pelo NSU do último doc do lote) —
                // é o valor que o protocolo exige usar na próxima consulta.
                $nsuAtual = (int) $resp['ultNSU'];
                $maxNSU   = (int) $resp['maxNSU'];

                // Persiste a cada lote processado, não só no final — evita reconsultar o mesmo
                // ultNSU (e levar novo bloqueio de "consumo indevido") caso um lote seguinte falhe.
                $certificado->update(['ultimo_nsu_nfe' => $nsuAtual]);

                if ($passouFim || $nsuAtual >= $maxNSU) {
                    break;
                }

                $lotes++;
            }
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        return $documentos;
    }

    private function cUFAutor(ClienteCertificadoNfse $certificado): int
    {
        $uf = strtoupper(trim($certificado->cliente->estado ?? ''));

        return self::UF_CODIGO[$uf] ?? 91; // 91 = "todas as UFs" (Ambiente Nacional), usado se UF não cadastrada/reconhecida
    }

    /**
     * Monta e envia o envelope SOAP distDFeInt, retorna o lote de documentos já parseado.
     */
    private function consultarNsu(string $endpoint, int $tpAmb, int $cUFAutor, string $cnpj, int $nsu, string $pemCert, string $pemKey): array
    {
        $ultNsu  = str_pad((string) $nsu, 15, '0', STR_PAD_LEFT);
        $versao  = self::VERSAO;
        $envelope = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Header>
    <nfeCabecMsg xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe">
      <cUF>{$cUFAutor}</cUF>
      <versaoDados>{$versao}</versaoDados>
    </nfeCabecMsg>
  </soap12:Header>
  <soap12:Body>
    <nfeDistDFeInteresse xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/NFeDistribuicaoDFe">
      <nfeDadosMsg>
        <distDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="{$versao}">

          <tpAmb>{$tpAmb}</tpAmb>
          <cUFAutor>{$cUFAutor}</cUFAutor>
          <CNPJ>{$cnpj}</CNPJ>
          <distNSU><ultNSU>{$ultNsu}</ultNSU></distNSU>
        </distDFeInt>
      </nfeDadosMsg>
    </nfeDistDFeInteresse>
  </soap12:Body>
</soap12:Envelope>
XML;

        $resposta = $this->requisicaoSoap($endpoint, $envelope, $pemCert, $pemKey);

        return $this->parseRetDistDFeInt($resposta);
    }

    private function requisicaoSoap(string $endpoint, string $envelope, string $pemCert, string $pemKey): string
    {
        Log::info('[NF-e] requisicaoSoap: enviando', ['url' => $endpoint]);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $envelope,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSLCERT        => $pemCert,
            CURLOPT_SSLKEY         => $pemKey,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/soap+xml; charset=utf-8; action="' . self::SOAP_ACTION . '"',
            ],
        ]);

        $resposta  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        unset($ch);

        Log::info('[NF-e] requisicaoSoap: resposta recebida', [
            'httpCode'  => $httpCode,
            'curlErrNo' => $curlErrNo,
            'curlError' => $curlError ?: null,
            'bodyLen'   => is_string($resposta) ? strlen($resposta) : 'false',
        ]);

        if ($resposta === false) {
            throw new \RuntimeException("Falha na conexão (cURL #{$curlErrNo}): {$curlError}");
        }

        if ($httpCode === 496) {
            throw new \RuntimeException('A API rejeitou o certificado (HTTP 496). Confirme que é ICP-Brasil A1/A3 com CNPJ e "Autenticação do Cliente".');
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("Webservice NFeDistribuicaoDFe retornou HTTP {$httpCode}: " . substr(strip_tags($resposta), 0, 300));
        }

        return $resposta;
    }

    /**
     * Extrai o retDistDFeInt e os docZip do envelope SOAP de resposta.
     * Usa local-name() no XPath — ignora namespaces (SOAP + schema NF-e diferem entre si).
     */
    private function parseRetDistDFeInt(string $soapXml): array
    {
        libxml_use_internal_errors(true);
        $obj = new \SimpleXMLElement($soapXml);

        $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='retDistDFeInt']/*[local-name()='{$tag}']")[0] ?? ''));

        $cStat   = $get('cStat');
        $xMotivo = $get('xMotivo');
        $ultNSU  = $get('ultNSU');
        $maxNSU  = $get('maxNSU');

        $docs = [];

        foreach ($obj->xpath("//*[local-name()='docZip']") as $docZip) {
            $nsu    = (string) $docZip->attributes()['NSU'];
            $schema = (string) $docZip->attributes()['schema'];
            $xml    = $this->descomprimirXml((string) $docZip);

            $docs[] = $this->normalizarDocumento($nsu, $schema, $xml);
        }

        return [
            'cStat'   => $cStat,
            'xMotivo' => $xMotivo,
            'ultNSU'  => $ultNSU !== '' ? (int) $ultNSU : null,
            'maxNSU'  => $maxNSU !== '' ? (int) $maxNSU : null,
            'docs'    => $docs,
        ];
    }

    /**
     * Normaliza um docZip já descomprimido em array para a view, cobrindo
     * resumos (resNFe/resCTe) e eventos (resEvento).
     */
    private function normalizarDocumento(string $nsu, string $schema, string $xml): array
    {
        if (str_starts_with($schema, 'resEvento')) {
            return ['nsu' => $nsu, 'tipo' => 'evento', 'xmlContent' => $xml];
        }

        $tipoDoc = str_starts_with($schema, 'resCTe') ? 'cte' : 'nfe';

        libxml_use_internal_errors(true);
        $obj = new \SimpleXMLElement($xml);
        $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

        return [
            'nsu'          => $nsu,
            'tipo'         => $tipoDoc,
            'chaveAcesso'  => $get('chNFe') ?: $get('chCTe'),
            'numero'       => $get('nNF') ?: $get('nCT'),
            'dataEmissao'  => $get('dhEmi'),
            'emitenteNome' => $this->utf8Safe($get('xNome')),
            'emitenteDoc'  => $get('CNPJ') ?: $get('CPF'),
            'valor'        => $get('vNF') ?: $get('vCT'),
            'situacao'     => $get('cSitDFe'),
            'xmlContent'   => $xml,
        ];
    }

    /** Garante que a string é UTF-8 válido — evita falha no json_encode. */
    private function utf8Safe(?string $str): ?string
    {
        if ($str === null) {
            return null;
        }

        $safe = mb_convert_encoding($str, 'UTF-8', 'UTF-8');

        if (json_encode($safe) === false) {
            $safe = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $str);
        }

        return $safe ?: null;
    }
}
