<?php

namespace App\Services;

use App\Models\CertificadoContabilidade;
use App\Models\Cliente;
use App\Services\Concerns\LidaComCertificadoPfx;
use Illuminate\Support\Facades\Log;

/**
 * Busca NF-e e NFC-e via webservice de download para contabilistas da SEFAZ-RS
 * (NFeIntegracao, operação nfeIntegracaoContab — schema distNFeRS), usando o
 * certificado digital da própria contabilidade (não do cliente), autorizado
 * previamente via e-CAC pelos clientes cujo CNPJ se deseja consultar.
 *
 * Diferente do NfeService (Distribuição DFe nacional): aqui o NSU é por CNPJ
 * consultado, não por certificado, e o retorno vem em um único lote compactado
 * (loteDistComp) em vez de docZip individuais.
 *
 * ATENÇÃO: o SOAPAction/namespace exatos do envelope não puderam ser
 * confirmados via WSDL (protegido por mTLS, só acessível com certificado
 * válido). Os valores abaixo são a melhor estimativa a partir do schema
 * público (distNFeRS_v1.00.xsd) e precisam ser calibrados na primeira chamada
 * real — o log de 'requisicaoSoap: resposta recebida' mostra o corpo cru
 * retornado pela Sefaz, que costuma indicar o SOAPAction esperado em caso de
 * erro.
 */
class NfeIntegracaoRsService
{
    use LidaComCertificadoPfx;

    const ENDPOINT_PRODUCAO    = 'https://dfe-servico.svrs.rs.gov.br/WS/NFeIntegracao/NFeIntegracao.asmx';
    const ENDPOINT_HOMOLOGACAO = 'https://dfe-servico-homologacao.svrs.rs.gov.br/WS/NFeIntegracao/NFeIntegracao.asmx';

    const SOAP_ACTION = 'http://tempuri.org/nfeIntegracaoContab';

    const CUF_RS = 43;

    // Máximo de lotes buscados por requisição (proteção contra loop)
    const MAX_LOTES = 200;

    /**
     * Diagnóstico: baixa o WSDL real do endpoint usando o certificado da
     * contabilidade já configurado, para confirmar o SOAPAction/namespace
     * corretos (o WSDL é protegido por mTLS e não pôde ser inspecionado sem
     * um certificado válido).
     */
    public function buscarWsdl(CertificadoContabilidade $certificado): string
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $endpoint . '?wsdl',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSLCERT        => $pemCert,
                CURLOPT_SSLKEY         => $pemKey,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO         => resource_path('certificados-icp-brasil/dfe-rs-ca-bundle.pem'),
            ]);

            $resposta = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erro     = curl_error($ch);
            unset($ch);

            if ($resposta === false) {
                throw new \RuntimeException("Falha na conexão ao buscar WSDL: {$erro}");
            }

            Log::info('[NF-e RS] buscarWsdl', ['httpCode' => $httpCode, 'bodyLen' => strlen($resposta)]);

            return $resposta;
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }
    }

    /**
     * Busca NF-e/NFC-e de um cliente (CNPJ) no intervalo de datas informado,
     * usando o certificado da contabilidade.
     */
    public function buscarPorPeriodo(CertificadoContabilidade $certificado, Cliente $cliente, string $dataInicio, string $dataFim): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $cliente->cpfcnpj ?? '');
        $tpAmb    = $certificado->ambiente === 'producao' ? 1 : 2;
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        Log::info('[NF-e RS] buscarPorPeriodo: iniciando', [
            'cliente_id'  => $cliente->id,
            'cnpj'        => $cnpj,
            'tpAmb'       => $tpAmb,
            'endpoint'    => $endpoint,
            'nsu_inicial' => (int) $cliente->ultimo_nsu_nfe_rs,
            'data_inicio' => $dataInicio,
            'data_fim'    => $dataFim,
        ]);

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $documentos = [];
            $nsuAtual   = (int) $cliente->ultimo_nsu_nfe_rs;
            $lotes      = 0;

            while ($lotes < self::MAX_LOTES) {
                $resp = $this->consultarNsu($endpoint, $tpAmb, $cnpj, $nsuAtual, $pemCert, $pemKey);

                Log::info('[NF-e RS] buscarPorPeriodo: lote recebido', [
                    'lote'      => $lotes,
                    'nsu_usado' => $nsuAtual,
                    'cStat'     => $resp['cStat'],
                    'xMotivo'   => $resp['xMotivo'],
                    'ultNSU'    => $resp['ultNSU'],
                    'qtd_docs'  => count($resp['docs']),
                ]);

                if (empty($resp['docs'])) {
                    if (!empty($resp['ultNSU'])) {
                        $cliente->update(['ultimo_nsu_nfe_rs' => $resp['ultNSU']]);
                    }
                    break;
                }

                $loteCheio = count($resp['docs']) >= 50; // maxOccurs do lote — pode haver mais
                $passouFim = false;

                foreach ($resp['docs'] as $doc) {
                    if ($doc['tipo'] === 'evento') {
                        continue;
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

                if (!empty($resp['ultNSU'])) {
                    $nsuAtual = (int) $resp['ultNSU'];
                    $cliente->update(['ultimo_nsu_nfe_rs' => $nsuAtual]);
                }

                if ($passouFim || !$loteCheio) {
                    break;
                }

                $lotes++;
            }
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        $documentos = $this->deduplicarPorChave($documentos);

        Log::info('[NF-e RS] buscarPorPeriodo: concluído', ['total_documentos' => count($documentos)]);

        return $documentos;
    }

    private function consultarNsu(string $endpoint, int $tpAmb, string $cnpj, int $ultNSU, string $pemCert, string $pemKey): array
    {
        $cUF = self::CUF_RS;

        $envelope = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <nfeDistDFeInteresse xmlns="http://www.sefazrs.rs.gov.br/">
      <nfeDadosMsg>
        <distNFeRS xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.00">
          <tpAmb>{$tpAmb}</tpAmb>
          <verAplic>TaskManagerWR</verAplic>
          <cUF>{$cUF}</cUF>
          <CNPJ>{$cnpj}</CNPJ>
          <solRel>
            <indXML>1</indXML>
            <indEmit>3</indEmit>
            <indDest>3</indDest>
            <ultNSU>{$ultNSU}</ultNSU>
          </solRel>
        </distNFeRS>
      </nfeDadosMsg>
    </nfeDistDFeInteresse>
  </soap:Body>
</soap:Envelope>
XML;

        $resposta = $this->requisicaoSoap($endpoint, $envelope, $pemCert, $pemKey);

        return $this->parseRetDistNFeRS($resposta);
    }

    private function requisicaoSoap(string $endpoint, string $envelope, string $pemCert, string $pemKey): string
    {
        Log::info('[NF-e RS] requisicaoSoap: enviando', ['url' => $endpoint]);

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
            // O servidor da SEFAZ-RS usa um certificado TLS emitido pela cadeia
            // ICP-Brasil (AC SERPRO SSLv1 -> AC Raiz v10), que não está no bundle
            // de CAs padrão do sistema — sem isso, o cURL falha com #60 "unable
            // to get local issuer certificate".
            CURLOPT_CAINFO         => resource_path('certificados-icp-brasil/dfe-rs-ca-bundle.pem'),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: "' . self::SOAP_ACTION . '"',
            ],
        ]);

        $resposta  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        unset($ch);

        Log::info('[NF-e RS] requisicaoSoap: resposta recebida', [
            'httpCode'   => $httpCode,
            'curlErrNo'  => $curlErrNo,
            'curlError'  => $curlError ?: null,
            'bodyLen'    => is_string($resposta) ? strlen($resposta) : 'false',
            'bodySample' => is_string($resposta) ? substr($resposta, 0, 1500) : null,
        ]);

        if ($resposta === false) {
            throw new \RuntimeException("Falha na conexão (cURL #{$curlErrNo}): {$curlError}");
        }

        if ($httpCode === 496) {
            throw new \RuntimeException('A API rejeitou o certificado (HTTP 496). Confirme que é ICP-Brasil A1/A3 com CNPJ e "Autenticação do Cliente".');
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("Webservice NFeIntegracao (contabilistas RS) retornou HTTP {$httpCode}: " . substr(strip_tags($resposta), 0, 500));
        }

        return $resposta;
    }

    /**
     * Extrai o retDistNFeRS e os documentos do lote compactado (loteDistComp).
     */
    private function parseRetDistNFeRS(string $soapXml): array
    {
        libxml_use_internal_errors(true);
        $obj = new \SimpleXMLElement($soapXml);

        $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

        $cStat  = $get('cStat');
        $xMotivo = $get('xMotivo');
        $ultNSUStr = $get('ultNSU');
        $ultNSU = $ultNSUStr !== '' ? (int) $ultNSUStr : null;

        $loteComp = $get('loteDistComp');
        $docs = [];

        if ($loteComp !== '') {
            $loteXml = $this->descomprimirXml($loteComp);
            $loteObj = new \SimpleXMLElement($loteXml);

            foreach ($loteObj->xpath("//*[local-name()='proc']") as $proc) {
                $nsu    = (string) $proc->attributes()['NSU'];
                $chave  = (string) $proc->attributes()['chAcesso'];
                $schema = (string) $proc->attributes()['schema'];
                $xml    = $proc->asXML();

                $docs[] = $this->normalizarDocumento($nsu, $chave, $schema, $xml);
            }
        }

        return [
            'cStat'   => $cStat,
            'xMotivo' => $xMotivo,
            'ultNSU'  => $ultNSU,
            'docs'    => $docs,
        ];
    }

    /**
     * Classifica e normaliza um "proc" do lote. Como o schema não distingue
     * NF-e (modelo 55) de NFC-e (modelo 65), o modelo é inferido a partir da
     * chave de acesso (posições 21-22, 1-indexed).
     */
    private function normalizarDocumento(string $nsu, string $chave, string $schema, string $xml): array
    {
        if (str_contains($schema, 'Evento')) {
            return ['nsu' => $nsu, 'tipo' => 'evento', 'xmlContent' => $xml];
        }

        $modelo = strlen($chave) === 44 ? substr($chave, 20, 2) : null;
        $tipoDoc = $modelo === '65' ? 'nfce' : 'nfe';

        libxml_use_internal_errors(true);
        $obj = new \SimpleXMLElement($xml);
        $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

        $numero = $get('nNF');

        if (!$numero && $chave && strlen($chave) === 44) {
            $numero = (string) (int) substr($chave, 25, 9);
        }

        $dataEmissao  = $get('dhEmi');
        $emitenteNome = $get('xNome');
        $valor        = $get('vNF');

        if (!$dataEmissao && !$emitenteNome && !$valor) {
            Log::warning('[NF-e RS] normalizarDocumento: campos vazios após parse', [
                'nsu'       => $nsu,
                'schema'    => $schema,
                'xmlSample' => substr($xml, 0, 600),
            ]);
        }

        return [
            'nsu'          => $nsu,
            'tipo'         => $tipoDoc,
            'chaveAcesso'  => $chave,
            'numero'       => $numero,
            'dataEmissao'  => $dataEmissao,
            'emitenteNome' => $this->utf8Safe($emitenteNome),
            'emitenteDoc'  => $get('CNPJ') ?: $get('CPF'),
            'valor'        => $valor,
            'situacao'     => $get('cSitNFe'),
            'xmlContent'   => $xml,
        ];
    }

    private function deduplicarPorChave(array $documentos): array
    {
        $vistos = [];
        $unicos = [];

        foreach ($documentos as $doc) {
            $chave = $doc['chaveAcesso'] ?? $doc['nsu'];

            if (isset($vistos[$chave])) {
                continue;
            }

            $vistos[$chave] = true;
            $unicos[] = $doc;
        }

        return $unicos;
    }

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
