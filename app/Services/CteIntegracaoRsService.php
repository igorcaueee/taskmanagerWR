<?php

namespace App\Services;

use App\Models\CertificadoContabilidade;
use App\Models\Cliente;
use App\Models\DocumentoFiscal;
use App\Services\Concerns\LidaComCertificadoPfx;
use Illuminate\Support\Facades\Log;

/**
 * Busca CT-e via webservice de download para contabilistas da SEFAZ-RS
 * (CTeIntegracao, operação cteIntegracaoContab — schema distCTeRS), usando o
 * certificado digital da própria contabilidade, autorizado previamente via
 * e-CAC pelos clientes cujo CNPJ se deseja consultar (autorização "Conhecimento
 * de Transporte Eletrônico: Autoriza Distribuição para Contabilista",
 * separada da autorização de NF-e).
 *
 * Análogo ao NfeIntegracaoRsService, mas para CT-e (modelo 57): a requisição
 * tem indEmit/indToma (não indDest) e o campo obrigatório mod=57.
 *
 * Nome do wrapper SOAP (nfeDadosMsgDownload / cteDadosMsgDownload) e cStat de
 * sucesso (117/118, diferente do 137/138 do NF-e RS) foram documentados no
 * Boletim Técnico RS-2015/001, mas o nome exato do elemento wrapper não consta
 * no BT/XSD — segue o padrão de nomenclatura da SVRS por analogia. Deve ser
 * confirmado contra o WSDL real (CTeIntegracao.asmx?wsdl) assim que houver
 * autorização eletrônica de CT-e liberada para um cliente de teste.
 */
class CteIntegracaoRsService
{
    use LidaComCertificadoPfx;

    const ENDPOINT_PRODUCAO    = 'https://dfe-servico.svrs.rs.gov.br/ws/CTeIntegracao/CTeIntegracao.asmx';
    const ENDPOINT_HOMOLOGACAO = 'https://dfe-servico-homologacao.svrs.rs.gov.br/ws/CTeIntegracao/CTeIntegracao.asmx';

    const SOAP_ACTION = 'http://www.portalfiscal.inf.br/cte/wsdl/CTeIntegracao/cteIntegracaoContab';

    const CUF_RS = 43;
    const MOD_CTE = 57;

    // Máximo de lotes buscados por requisição (proteção contra loop)
    const MAX_LOTES = 200;

    /**
     * Sincroniza os CT-e novos de um cliente (CNPJ), a partir do último NSU
     * salvo, para a tabela `documentos_fiscais`, usando o certificado da
     * contabilidade.
     *
     * Retorna ['sincronizado' => bool, 'aviso' => ?string].
     */
    public function sincronizar(CertificadoContabilidade $certificado, Cliente $cliente): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $cliente->cpfcnpj ?? '');
        $tpAmb    = $certificado->ambiente === 'producao' ? 1 : 2;
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        Log::info('[CT-e RS] sincronizar: iniciando', [
            'cliente_id'  => $cliente->id,
            'cnpj'        => $cnpj,
            'tpAmb'       => $tpAmb,
            'endpoint'    => $endpoint,
            'nsu_inicial' => (int) $cliente->ultimo_nsu_cte_rs,
        ]);

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        $aviso = null;

        try {
            $nsuAtual = (int) $cliente->ultimo_nsu_cte_rs;
            $lotes    = 0;

            while ($lotes < self::MAX_LOTES) {
                $resp = $this->consultarNsu($endpoint, $tpAmb, $cnpj, $nsuAtual, $pemCert, $pemKey);

                Log::info('[CT-e RS] sincronizar: lote recebido', [
                    'lote'      => $lotes,
                    'nsu_usado' => $nsuAtual,
                    'cStat'     => $resp['cStat'],
                    'xMotivo'   => $resp['xMotivo'],
                    'ultNSU'    => $resp['ultNSU'],
                    'qtd_docs'  => count($resp['docs']),
                ]);

                // 678 = consumo indevido; 8005 = fora do prazo de download (janela de ~3 meses).
                if (in_array($resp['cStat'], ['678', '8005'], true)) {
                    if (!empty($resp['ultNSU'])) {
                        $cliente->update(['ultimo_nsu_cte_rs' => $resp['ultNSU']]);
                    }

                    $aviso = $resp['cStat'] === '678'
                        ? 'A Sefaz-RS rejeitou a sincronização de CT-e por "consumo indevido" — aguarde e tente '
                            . 'novamente mais tarde. Mostrando os documentos já sincronizados anteriormente.'
                        : 'Não há mais CT-e dentro do prazo de download. Mostrando os documentos já sincronizados.';
                    break;
                }

                if (empty($resp['docs'])) {
                    if (!empty($resp['ultNSU'])) {
                        $cliente->update(['ultimo_nsu_cte_rs' => $resp['ultNSU']]);
                    }
                    break;
                }

                $loteCheio = count($resp['docs']) >= 50; // maxOccurs do lote — pode haver mais

                foreach ($resp['docs'] as $doc) {
                    if ($doc['tipo'] === 'evento') {
                        continue;
                    }

                    $this->persistir($cliente->id, $doc);
                }

                if (!empty($resp['ultNSU'])) {
                    $nsuAtual = (int) $resp['ultNSU'];
                    $cliente->update(['ultimo_nsu_cte_rs' => $nsuAtual]);
                }

                if (!$loteCheio) {
                    break;
                }

                $lotes++;
            }
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        Log::info('[CT-e RS] sincronizar: concluído', ['aviso' => $aviso]);

        return ['sincronizado' => $aviso === null, 'aviso' => $aviso];
    }

    private function consultarNsu(string $endpoint, int $tpAmb, string $cnpj, int $ultNSU, string $pemCert, string $pemKey): array
    {
        $cUF = self::CUF_RS;
        $mod = self::MOD_CTE;

        $envelope = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <cteIntegracaoContab xmlns="http://www.portalfiscal.inf.br/cte/wsdl/CTeIntegracao">
      <cteDadosMsgDownload>
        <distCTeRS xmlns="http://www.portalfiscal.inf.br/cte" versao="1.00">
          <tpAmb>{$tpAmb}</tpAmb>
          <verAplic>TaskManagerWR</verAplic>
          <cUF>{$cUF}</cUF>
          <CNPJ>{$cnpj}</CNPJ>
          <mod>{$mod}</mod>
          <solRel>
            <indXML>1</indXML>
            <indEmit>3</indEmit>
            <indToma>3</indToma>
            <ultNSU>{$ultNSU}</ultNSU>
          </solRel>
        </distCTeRS>
      </cteDadosMsgDownload>
    </cteIntegracaoContab>
  </soap:Body>
</soap:Envelope>
XML;

        $resposta = $this->requisicaoSoap($endpoint, $envelope, $pemCert, $pemKey);

        return $this->parseRetDistCTeRS($resposta);
    }

    private function requisicaoSoap(string $endpoint, string $envelope, string $pemCert, string $pemKey): string
    {
        Log::info('[CT-e RS] requisicaoSoap: enviando', ['url' => $endpoint]);

        // Mesma exigência do webservice de NF-e RS: sem espaço/quebra de linha entre tags.
        $envelope = trim(preg_replace('/>\s+</', '><', $envelope));

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
            // Mesma infraestrutura SVRS do webservice de NF-e RS — mesmo bundle de CA.
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

        Log::info('[CT-e RS] requisicaoSoap: resposta recebida', [
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
            throw new \RuntimeException("Webservice CTeIntegracao (contabilistas RS) retornou HTTP {$httpCode}: " . substr(strip_tags($resposta), 0, 500));
        }

        return $resposta;
    }

    /**
     * Extrai o retDistCTeRS e os documentos do lote compactado (loteDistComp).
     */
    private function parseRetDistCTeRS(string $soapXml): array
    {
        libxml_use_internal_errors(true);
        $obj = new \SimpleXMLElement($soapXml);

        $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

        $cStat     = $get('cStat');
        $xMotivo   = $get('xMotivo');
        $ultNSUStr = $get('ultNSURet') ?: $get('ultNSU');
        $ultNSU    = $ultNSUStr !== '' ? (int) $ultNSUStr : null;

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

    private function normalizarDocumento(string $nsu, string $chave, string $schema, string $xml): array
    {
        if (str_contains($schema, 'Evento')) {
            return ['nsu' => $nsu, 'tipo' => 'evento', 'xmlContent' => $xml];
        }

        libxml_use_internal_errors(true);
        $obj = new \SimpleXMLElement($xml);
        $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

        $numero = $get('nCT');

        if (!$numero && $chave && strlen($chave) === 44) {
            $numero = (string) (int) substr($chave, 25, 9);
        }

        $dataEmissao  = $get('dhEmi');
        $emitenteNome = $get('xNome');
        $valor        = $get('vCT') ?: $get('vTPrest');

        if (!$dataEmissao && !$emitenteNome && !$valor) {
            Log::warning('[CT-e RS] normalizarDocumento: campos vazios após parse', [
                'nsu'       => $nsu,
                'schema'    => $schema,
                'xmlSample' => substr($xml, 0, 600),
            ]);
        }

        return [
            'nsu'          => $nsu,
            'tipo'         => 'cte',
            'chaveAcesso'  => $chave,
            'numero'       => $numero,
            'dataEmissao'  => $dataEmissao,
            'emitenteNome' => $this->utf8Safe($emitenteNome),
            'emitenteDoc'  => $get('CNPJ') ?: $get('CPF'),
            'valor'        => $valor,
            'situacao'     => $get('cSitCTe'),
            'xmlContent'   => $xml,
        ];
    }

    /**
     * Grava ou atualiza um CT-e normalizado na tabela `documentos_fiscais`,
     * identificado pela chave de acesso — só sobrescreve um registro existente
     * se a nova versão tiver tantos ou mais campos preenchidos.
     */
    private function persistir(int $clienteId, array $doc): void
    {
        if (empty($doc['chaveAcesso'])) {
            return;
        }

        $existente = DocumentoFiscal::where('chave_acesso', $doc['chaveAcesso'])->first();

        if ($existente) {
            $scoreNovo = (int) !empty($doc['numero']) + (int) !empty($doc['dataEmissao'])
                + (int) !empty($doc['emitenteNome']) + (int) !empty($doc['valor']);
            $scoreExistente = (int) !empty($existente->numero) + (int) !empty($existente->data_emissao)
                + (int) !empty($existente->emitente_nome) + (int) !empty($existente->valor);

            if ($scoreNovo < $scoreExistente) {
                return;
            }
        }

        DocumentoFiscal::updateOrCreate(
            ['chave_acesso' => $doc['chaveAcesso']],
            [
                'cliente_id'    => $clienteId,
                'tipo'          => 'cte',
                'origem'        => 'rs',
                'nsu'           => $doc['nsu'] ?? null,
                'numero'        => $doc['numero'] ?? null,
                'data_emissao'  => !empty($doc['dataEmissao']) ? substr($doc['dataEmissao'], 0, 10) : null,
                'emitente_nome' => $doc['emitenteNome'] ?? null,
                'emitente_doc'  => $doc['emitenteDoc'] ?? null,
                'valor'         => $doc['valor'] ?: null,
                'situacao'      => $doc['situacao'] ?? null,
                'xml_content'   => $doc['xmlContent'] ?? null,
            ]
        );
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
