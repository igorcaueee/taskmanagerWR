<?php

namespace App\Services;

use App\Models\ClienteCertificadoNfse;
use App\Models\DocumentoFiscal;
use App\Services\Concerns\LidaComCertificadoPfx;
use Illuminate\Support\Facades\Log;

/**
 * Busca CT-e via webservice nacional CTeDistribuicaoDFe (Nota Técnica 2015.002),
 * usando o mesmo certificado digital do próprio cliente já cadastrado para
 * NFS-e/NF-e nacional (mTLS por CNPJ) — não depende do certificado da
 * contabilidade nem do webservice CTeIntegracao da SEFAZ-RS (ver
 * CteIntegracaoRsService, atualmente bloqueado por cStat 239 sem solução).
 *
 * Diferente do CTeIntegracao (contabilista): a distribuição nacional só
 * entrega CT-e em que o CNPJ consultado NÃO é o emitente — ou seja, CT-e que
 * a própria empresa emitiu (caso ela seja transportadora) não aparece aqui,
 * só os que ela recebe como destinatário/remetente/expedidor/recebedor/
 * tomador ou terceiro autorizado (grupo autXML). Ver NT 2015.002 seção 1.
 *
 * Estrutura do envelope confirmada via nfephp-org/sped-cte (Tools::sefazDistDFe):
 * SOAP 1.2, SEM SOAP Header (diferente do NFeDistribuicaoDFe nacional, que usa
 * nfeCabecMsg), wrapper cteDistDFeInteresse > cteDadosMsg > distDFeInt, com o
 * elemento raiz "distDFeInt" (mesmo nome genérico usado pelo NF-e, mas aqui no
 * namespace http://www.portalfiscal.inf.br/cte).
 */
class CteDistribuicaoDFeService
{
    use LidaComCertificadoPfx;

    const ENDPOINT_PRODUCAO    = 'https://www1.cte.fazenda.gov.br/CTeDistribuicaoDFe/CTeDistribuicaoDFe.asmx';
    const ENDPOINT_HOMOLOGACAO = 'https://hom1.cte.fazenda.gov.br/CTeDistribuicaoDFe/CTeDistribuicaoDFe.asmx';

    const SOAP_ACTION = 'http://www.portalfiscal.inf.br/cte/wsdl/CTeDistribuicaoDFe/cteDistDFeInteresse';
    const VERSAO       = '1.00';

    // Máximo de lotes buscados por chamada (chunk) — ver NfeService::MAX_LOTES_POR_CHUNK.
    const MAX_LOTES_POR_CHUNK = 12;

    // Código IBGE da UF — usado em cUFAutor. Mesma tabela usada em NfeService.
    const UF_CODIGO = [
        'RO' => 11, 'AC' => 12, 'AM' => 13, 'RR' => 14, 'PA' => 15, 'AP' => 16, 'TO' => 17,
        'MA' => 21, 'PI' => 22, 'CE' => 23, 'RN' => 24, 'PB' => 25, 'PE' => 26, 'AL' => 27, 'SE' => 28, 'BA' => 29,
        'MG' => 31, 'ES' => 32, 'RJ' => 33, 'SP' => 35,
        'PR' => 41, 'SC' => 42, 'RS' => 43,
        'MS' => 50, 'MT' => 51, 'GO' => 52, 'DF' => 53,
    ];

    /**
     * Sincroniza uma fatia (chunk) dos CT-e novos deste certificado, a partir
     * do NSU indicado (ou do último salvo, se omitido), para a tabela
     * `documentos_fiscais`. Mesmo padrão de chunking do NfeService — evita
     * timeout de proxy/CDN numa sincronização longa.
     *
     * Retorna ['concluido' => bool, 'proximoNsu' => int, 'aviso' => ?string].
     */
    public function sincronizarChunk(ClienteCertificadoNfse $certificado, ?int $nsuInicio = null): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $certificado->cliente->cpfcnpj ?? '');
        $cUFAutor = $this->cUFAutor($certificado);
        $tpAmb    = $certificado->ambiente === 'producao' ? 1 : 2;
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        $nsuAtual = $nsuInicio ?? (int) $certificado->ultimo_nsu_cte;

        Log::debug('[CT-e Nacional] sincronizarChunk: iniciando', [
            'cliente_id'  => $certificado->cliente_id,
            'cnpj'        => $cnpj,
            'cUFAutor'    => $cUFAutor,
            'tpAmb'       => $tpAmb,
            'endpoint'    => $endpoint,
            'nsu_inicial' => $nsuAtual,
        ]);

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        $aviso     = null;
        $concluido = false;

        try {
            $lotes = 0;

            while ($lotes < self::MAX_LOTES_POR_CHUNK) {
                $resp = $this->consultarNsu($endpoint, $tpAmb, $cUFAutor, $cnpj, $nsuAtual, $pemCert, $pemKey);

                $cStat = $resp['cStat'] ?? '';

                Log::debug('[CT-e Nacional] sincronizarChunk: lote recebido', [
                    'lote'      => $lotes,
                    'nsu_usado' => $nsuAtual,
                    'cStat'     => $cStat,
                    'xMotivo'   => $resp['xMotivo'] ?? null,
                    'ultNSU'    => $resp['ultNSU'] ?? null,
                    'maxNSU'    => $resp['maxNSU'] ?? null,
                    'qtd_docs'  => count($resp['docs'] ?? []),
                ]);

                // 656 = consumo indevido — mesmo comportamento do NfeService: autocalibra
                // o NSU com o que a própria rejeição informa e para sem erro fatal.
                if ($cStat === '656') {
                    if (!empty($resp['ultNSU'])) {
                        $nsuAtual = (int) $resp['ultNSU'];
                        $certificado->update(['ultimo_nsu_cte' => $nsuAtual]);
                    }

                    $aviso = 'A Sefaz rejeitou a sincronização de CT-e por "consumo indevido" — provavelmente porque outro '
                        . 'sistema já consulta a distribuição de DF-e deste CNPJ. Mostrando os documentos já sincronizados '
                        . 'anteriormente.';
                    $concluido = true;
                    break;
                }

                if ($cStat === '137') {
                    if (isset($resp['ultNSU'])) {
                        $nsuAtual = (int) $resp['ultNSU'];
                        $certificado->update(['ultimo_nsu_cte' => $nsuAtual]);
                    }
                    $concluido = true;
                    break;
                }

                if ($cStat !== '138') {
                    $aviso = "Distribuição de CT-e retornou cStat {$cStat}: " . ($resp['xMotivo'] ?? 'motivo desconhecido')
                        . ' — mostrando os documentos já sincronizados anteriormente.';
                    $concluido = true;
                    break;
                }

                if ($resp['ultNSU'] === null || $resp['maxNSU'] === null) {
                    $aviso = 'Resposta cStat 138 sem ultNSU/maxNSU — não foi possível paginar com segurança.';
                    $concluido = true;
                    break;
                }

                foreach ($resp['docs'] as $doc) {
                    if ($doc['tipo'] === 'evento') {
                        $this->processarEvento($doc['xmlContent'] ?? '');
                        continue;
                    }

                    $this->persistir($certificado->cliente_id, $doc);
                }

                $nsuAtual = (int) $resp['ultNSU'];
                $maxNSU   = (int) $resp['maxNSU'];

                $certificado->update(['ultimo_nsu_cte' => $nsuAtual]);

                if ($nsuAtual >= $maxNSU) {
                    $concluido = true;
                    break;
                }

                $lotes++;
            }
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        Log::debug('[CT-e Nacional] sincronizarChunk: concluído', ['concluido' => $concluido, 'proximoNsu' => $nsuAtual, 'aviso' => $aviso]);

        return ['concluido' => $concluido, 'proximoNsu' => $nsuAtual, 'aviso' => $aviso];
    }

    private function cUFAutor(ClienteCertificadoNfse $certificado): int
    {
        $uf = strtoupper(trim($certificado->cliente->estado ?? ''));

        return self::UF_CODIGO[$uf] ?? 91; // 91 = "todas as UFs" (Ambiente Nacional)
    }

    /**
     * Monta e envia o envelope SOAP distDFeInt, retorna o lote de documentos já parseado.
     */
    private function consultarNsu(string $endpoint, int $tpAmb, int $cUFAutor, string $cnpj, int $nsu, string $pemCert, string $pemKey): array
    {
        $versao  = self::VERSAO;
        // Mesmo padrão do NfeService: o tipo TNSU exige 15 dígitos fixos (zero à
        // esquerda), apesar da NT 2015.002 documentar o tamanho como "1-15" —
        // mandar sem padding ("0" em vez de "000000000000000") gera cStat 215
        // "Falha no esquema xml".
        $ultNsu  = str_pad((string) $nsu, 15, '0', STR_PAD_LEFT);
        $envelope = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Header>
    <cteCabecMsg xmlns="http://www.portalfiscal.inf.br/cte/wsdl/CTeDistribuicaoDFe">
      <cUF>{$cUFAutor}</cUF>
      <versaoDados>{$versao}</versaoDados>
    </cteCabecMsg>
  </soap12:Header>
  <soap12:Body>
    <cteDistDFeInteresse xmlns="http://www.portalfiscal.inf.br/cte/wsdl/CTeDistribuicaoDFe">
      <cteDadosMsg>
        <distDFeInt xmlns="http://www.portalfiscal.inf.br/cte" versao="{$versao}">
          <tpAmb>{$tpAmb}</tpAmb>
          <cUFAutor>{$cUFAutor}</cUFAutor>
          <CNPJ>{$cnpj}</CNPJ>
          <distNSU><ultNSU>{$ultNsu}</ultNSU></distNSU>
        </distDFeInt>
      </cteDadosMsg>
    </cteDistDFeInteresse>
  </soap12:Body>
</soap12:Envelope>
XML;

        $resposta = $this->requisicaoSoap($endpoint, $envelope, $pemCert, $pemKey);

        return $this->parseRetDistDFeInt($resposta, $cnpj);
    }

    private function requisicaoSoap(string $endpoint, string $envelope, string $pemCert, string $pemKey): string
    {
        Log::debug('[CT-e Nacional] requisicaoSoap: enviando', ['url' => $endpoint]);

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
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/soap+xml; charset=utf-8; action="' . self::SOAP_ACTION . '"',
            ],
        ]);

        $resposta  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        unset($ch);

        Log::debug('[CT-e Nacional] requisicaoSoap: resposta recebida', [
            'httpCode'   => $httpCode,
            'curlErrNo'  => $curlErrNo,
            'curlError'  => $curlError ?: null,
            'bodyLen'    => is_string($resposta) ? strlen($resposta) : 'false',
            'bodySample' => is_string($resposta) ? substr($resposta, 0, 800) : null,
        ]);

        if ($resposta === false) {
            throw new \RuntimeException("Falha na conexão (cURL #{$curlErrNo}): {$curlError}");
        }

        if ($httpCode === 496) {
            throw new \RuntimeException('A API rejeitou o certificado (HTTP 496). Confirme que é ICP-Brasil A1/A3 com CNPJ e "Autenticação do Cliente".');
        }

        if ($httpCode >= 400) {
            throw new \RuntimeException("Webservice CTeDistribuicaoDFe retornou HTTP {$httpCode}: " . substr(strip_tags($resposta), 0, 300));
        }

        return $resposta;
    }

    /**
     * Extrai o retDistDFeInt e os docZip do envelope SOAP de resposta.
     */
    private function parseRetDistDFeInt(string $soapXml, string $cnpjCliente): array
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

            $docs[] = $this->normalizarDocumento($nsu, $schema, $xml, $cnpjCliente);
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
     * resumos (resCTe) e eventos (procEventoCTe).
     */
    private function normalizarDocumento(string $nsu, string $schema, string $xml, string $cnpjCliente): array
    {
        if (str_contains($schema, 'Evento')) {
            return ['nsu' => $nsu, 'tipo' => 'evento', 'xmlContent' => $xml];
        }

        libxml_use_internal_errors(true);
        $obj = new \SimpleXMLElement($xml);
        $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

        $chave  = $get('chCTe');
        $numero = $get('nCT');

        // resCTe (resumo) não traz nCT — o número do documento fica embutido
        // na própria chave de acesso (posições 26 a 34, 1-indexed).
        if (!$numero && $chave && strlen($chave) === 44) {
            $numero = (string) (int) substr($chave, 25, 9);
        }

        $dataEmissao  = $get('dhEmi');
        $emitenteNome = $get('xNome');
        $valor        = $get('vCT') ?: $get('vTPrest');

        if (!$dataEmissao && !$emitenteNome && !$valor) {
            Log::warning('[CT-e Nacional] normalizarDocumento: campos vazios após parse', [
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
            'papelCte'     => $this->identificarPapelCte($obj, $cnpjCliente),
            'xmlContent'   => $xml,
        ];
    }

    /**
     * Identifica o papel do cliente consultado dentro do CT-e (Emitente, Tomador,
     * Remetente, Destinatário, Expedidor ou Recebedor) — ver mesma lógica em
     * CteIntegracaoRsService::identificarPapelCte.
     */
    private function identificarPapelCte(\SimpleXMLElement $obj, string $cnpjCliente): ?string
    {
        if ($cnpjCliente === '') {
            return null;
        }

        $docCnpj = fn(string $grupo) => trim((string) ($obj->xpath("//*[local-name()='{$grupo}']/*[local-name()='CNPJ']")[0] ?? ''));

        $emitCnpj  = $docCnpj('emit');
        $remCnpj   = $docCnpj('rem');
        $destCnpj  = $docCnpj('dest');
        $expedCnpj = $docCnpj('exped');
        $recebCnpj = $docCnpj('receb');

        $tomaNos    = $obj->xpath("//*[local-name()='toma']");
        $tomaCodigo = $tomaNos ? trim((string) $tomaNos[0]) : '';

        $tomadorCnpj = match ($tomaCodigo) {
            '0'     => $remCnpj,
            '1'     => $expedCnpj,
            '2'     => $recebCnpj,
            '3'     => $destCnpj,
            '4'     => $docCnpj('toma4'),
            default => null,
        };

        return match (true) {
            $cnpjCliente === $emitCnpj                            => 'Emitente',
            $tomadorCnpj !== null && $cnpjCliente === $tomadorCnpj => 'Tomador',
            $cnpjCliente === $remCnpj                              => 'Remetente',
            $cnpjCliente === $destCnpj                             => 'Destinatário',
            $cnpjCliente === $expedCnpj                            => 'Expedidor',
            $cnpjCliente === $recebCnpj                            => 'Recebedor',
            default                                                => null,
        };
    }

    /**
     * Evento de cancelamento (tpEvento 110111) não vira linha própria — mas
     * precisa atualizar a `situacao` do documento original, senão ele
     * continua marcado como normal para sempre mesmo depois de cancelado.
     *
     * Também reescreve o cStat/xMotivo dentro do próprio xml_content salvo —
     * ver mesmo padrão em NfeService::marcarXmlComoCancelado.
     */
    private function processarEvento(string $xml): void
    {
        if ($xml === '') {
            return;
        }

        libxml_use_internal_errors(true);
        $obj = new \SimpleXMLElement($xml);
        $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

        $tpEvento = $get('tpEvento');

        if ($tpEvento !== '110111') {
            Log::debug('[CT-e Nacional] processarEvento: ignorado (não é cancelamento)', ['tpEvento' => $tpEvento ?: null]);
            return;
        }

        $chave = $get('chCTe') ?: $get('chNFe');

        if ($chave === '') {
            Log::warning('[CT-e Nacional] processarEvento: cancelamento sem chCTe extraível', ['xmlSample' => substr($xml, 0, 500)]);
            return;
        }

        $update = ['situacao' => 'cancelada'];

        $existente = DocumentoFiscal::where('chave_acesso', $chave)->first();
        if ($existente && !empty($existente->xml_content)) {
            $update['xml_content'] = $this->marcarXmlComoCancelado($existente->xml_content);
        }

        $linhas = DocumentoFiscal::where('chave_acesso', $chave)->update($update);

        Log::info('[CT-e Nacional] processarEvento: cancelamento processado', ['chave' => $chave, 'linhas_afetadas' => $linhas]);
    }

    /**
     * Reescreve cStat/xMotivo dentro de protCTe (grupo infProt) para refletir
     * o cancelamento — mesma convenção usada por outros provedores (ex.:
     * SIEG): cStat 101 "Cancelamento homologado".
     */
    private function marcarXmlComoCancelado(string $xml): string
    {
        libxml_use_internal_errors(true);
        $obj = @new \SimpleXMLElement($xml);

        if (!$obj) {
            return $xml;
        }

        $cStatNos   = $obj->xpath("//*[local-name()='infProt']/*[local-name()='cStat']");
        $xMotivoNos = $obj->xpath("//*[local-name()='infProt']/*[local-name()='xMotivo']");

        if (empty($cStatNos)) {
            return $xml;
        }

        $cStatNos[0][0] = '101';

        if (!empty($xMotivoNos)) {
            $xMotivoNos[0][0] = 'Cancelamento homologado';
        }

        return $obj->asXML() ?: $xml;
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
                'origem'        => 'nacional',
                'nsu'           => $doc['nsu'] ?? null,
                'numero'        => $doc['numero'] ?? null,
                'data_emissao'  => !empty($doc['dataEmissao']) ? substr($doc['dataEmissao'], 0, 10) : null,
                'emitente_nome' => $doc['emitenteNome'] ?? null,
                'emitente_doc'  => $doc['emitenteDoc'] ?? null,
                'valor'         => $doc['valor'] ?: null,
                'situacao'      => $existente?->situacao === 'cancelada' ? 'cancelada' : ($doc['situacao'] ?? null),
                'papel_cte'     => $doc['papelCte'] ?? null,
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
