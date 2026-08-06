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
 * Confirmado no XSD oficial (distCTeRS_v1.00.xsd): apesar do nome do arquivo e
 * da tabela do Boletim Técnico chamarem o elemento raiz de "distCTeRS", o XSD
 * declara `<xs:element name="distNFeRS" type="TDistCTeRS">` — a SEFAZ-RS
 * reaproveitou o schema do NF-e e esqueceu de renomear o elemento. É
 * `distNFeRS` (não `distCTeRS`) que precisa ir no XML, senão a Sefaz rejeita
 * com cStat 243 "XML Mal Formado".
 *
 * Nome do parâmetro do SOAP body confirmado via WSDL real (baixado com o
 * certificado da contabilidade através de `php artisan cte:buscar-wsdl`):
 * diferente do NF-e RS (wrapper `nfeDadosMsgDownload`), o CTeIntegracao usa
 * apenas `<xml>` como elemento — não existe um `cteDadosMsgDownload`.
 *
 * O atributo `versao` do `distNFeRS` é "1.00" — confirmado no XSD real
 * (dfe-portal.svrs.rs.gov.br/Schemas/PRCTE/leiauteDistCTeRS_v1.00.xsd), cujo
 * tipo TVerDFe restringe o valor ao padrão fixo "1\.00" (sem outra opção).
 * Tentativas de usar "4.00"/"2.00" (numa suposição equivocada de que a versão
 * do LAYOUT DE EMISSÃO do CT-e, essa sim atualizada para 4.00 desde 01/02/2024
 * por um sistema totalmente diferente, também se aplicaria a este wrapper de
 * distribuição para contabilistas) geram cStat 239 "Cabecalho - Versao do
 * arquivo XML nao suportada" — só passam da validação estrutural (243, quando
 * elemento/wrapper estava errado) para cair nessa checagem de versão inválida.
 */
class CteIntegracaoRsService
{
    use LidaComCertificadoPfx;

    const ENDPOINT_PRODUCAO    = 'https://dfe-servico.svrs.rs.gov.br/ws/CTeIntegracao/CTeIntegracao.asmx';
    const ENDPOINT_HOMOLOGACAO = 'https://dfe-servico-homologacao.svrs.rs.gov.br/ws/CTeIntegracao/CTeIntegracao.asmx';

    const SOAP_ACTION = 'http://www.portalfiscal.inf.br/cte/wsdl/CTeIntegracao/cteIntegracaoContab';

    const CUF_RS = 43;
    const MOD_CTE = 57;

    // Máximo de lotes buscados por chamada (chunk) — ver NfeService::MAX_LOTES_POR_CHUNK
    // para o motivo (evitar timeout de proxy/CDN numa sincronização longa).
    const MAX_LOTES_POR_CHUNK = 12;

    /**
     * Sincroniza uma fatia (chunk) dos CT-e novos de um cliente (CNPJ), a
     * partir do NSU indicado (ou do último salvo, se omitido), para a tabela
     * `documentos_fiscais`, usando o certificado da contabilidade.
     *
     * Retorna ['concluido' => bool, 'proximoNsu' => int, 'aviso' => ?string].
     */
    public function sincronizarChunk(CertificadoContabilidade $certificado, Cliente $cliente, ?int $nsuInicio = null): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $cliente->cpfcnpj ?? '');
        $tpAmb    = $certificado->ambiente === 'producao' ? 1 : 2;
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        $nsuAtual = $nsuInicio ?? (int) $cliente->ultimo_nsu_cte_rs;

        Log::debug('[CT-e RS] sincronizarChunk: iniciando', [
            'cliente_id'  => $cliente->id,
            'cnpj'        => $cnpj,
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
                $resp = $this->consultarNsu($endpoint, $tpAmb, $cnpj, $nsuAtual, $pemCert, $pemKey);

                Log::debug('[CT-e RS] sincronizarChunk: lote recebido', [
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
                        $nsuAtual = (int) $resp['ultNSU'];
                        $cliente->update(['ultimo_nsu_cte_rs' => $nsuAtual]);
                    }

                    $aviso = $resp['cStat'] === '678'
                        ? 'A Sefaz-RS rejeitou a sincronização de CT-e por "consumo indevido" — aguarde e tente '
                            . 'novamente mais tarde. Mostrando os documentos já sincronizados anteriormente.'
                        : 'Não há mais CT-e dentro do prazo de download. Mostrando os documentos já sincronizados.';
                    $concluido = true;
                    break;
                }

                if (empty($resp['docs'])) {
                    if (!empty($resp['ultNSU'])) {
                        $nsuAtual = (int) $resp['ultNSU'];
                        $cliente->update(['ultimo_nsu_cte_rs' => $nsuAtual]);
                    }
                    $concluido = true;
                    break;
                }

                $loteCheio = count($resp['docs']) >= 50; // maxOccurs do lote — pode haver mais

                foreach ($resp['docs'] as $doc) {
                    if ($doc['tipo'] === 'evento') {
                        $this->processarEvento($doc['xmlContent'] ?? '');
                        continue;
                    }

                    $this->persistir($cliente->id, $doc);
                }

                if (!empty($resp['ultNSU'])) {
                    $nsuAtual = (int) $resp['ultNSU'];
                    $cliente->update(['ultimo_nsu_cte_rs' => $nsuAtual]);
                }

                if (!$loteCheio) {
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

        Log::debug('[CT-e RS] sincronizarChunk: concluído', ['concluido' => $concluido, 'proximoNsu' => $nsuAtual, 'aviso' => $aviso]);

        return ['concluido' => $concluido, 'proximoNsu' => $nsuAtual, 'aviso' => $aviso];
    }

    private function consultarNsu(string $endpoint, int $tpAmb, string $cnpj, int $ultNSU, string $pemCert, string $pemKey): array
    {
        $cUF = self::CUF_RS;
        $mod = self::MOD_CTE;

        // Diferente do webservice de NF-e RS (SOAP 1.1): o CTeIntegracao exige SOAP 1.2 —
        // a Sefaz rejeita com "VersionMismatch" se enviado como 1.1.
        $envelope = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
  <soap12:Body>
    <cteIntegracaoContab xmlns="http://www.portalfiscal.inf.br/cte/wsdl/CTeIntegracao">
      <xml>
        <distNFeRS xmlns="http://www.portalfiscal.inf.br/cte" versao="1.00">
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
        </distNFeRS>
      </xml>
    </cteIntegracaoContab>
  </soap12:Body>
</soap12:Envelope>
XML;

        $resposta = $this->requisicaoSoap($endpoint, $envelope, $pemCert, $pemKey);

        return $this->parseRetDistCTeRS($resposta, $cnpj);
    }

    private function requisicaoSoap(string $endpoint, string $envelope, string $pemCert, string $pemKey): string
    {
        Log::debug('[CT-e RS] requisicaoSoap: enviando', ['url' => $endpoint]);

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
                'Content-Type: application/soap+xml; charset=utf-8; action="' . self::SOAP_ACTION . '"',
            ],
        ]);

        $resposta  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        unset($ch);

        Log::debug('[CT-e RS] requisicaoSoap: resposta recebida', [
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
    private function parseRetDistCTeRS(string $soapXml, string $cnpjCliente): array
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

                $docs[] = $this->normalizarDocumento($nsu, $chave, $schema, $xml, $cnpjCliente);
            }
        }

        return [
            'cStat'   => $cStat,
            'xMotivo' => $xMotivo,
            'ultNSU'  => $ultNSU,
            'docs'    => $docs,
        ];
    }

    private function normalizarDocumento(string $nsu, string $chave, string $schema, string $xml, string $cnpjCliente): array
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
            'papelCte'     => $this->identificarPapelCte($obj, $cnpjCliente),
            'xmlContent'   => $xml,
        ];
    }

    /**
     * Identifica o papel do cliente consultado dentro do CT-e (Emitente, Tomador,
     * Remetente, Destinatário, Expedidor ou Recebedor) — importante porque o
     * Tomador do Serviço (quem contratou e paga o frete) pode ser um terceiro
     * diferente de quem aparece como remetente/destinatário/expedidor/recebedor.
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

        // <toma> pode estar dentro do grupo toma3 (código 0-3, referenciando um dos
        // grupos acima) ou toma4 (tomador é um terceiro, com CNPJ/CPF próprio).
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
            $cnpjCliente === $emitCnpj                              => 'Emitente',
            $tomadorCnpj !== null && $cnpjCliente === $tomadorCnpj   => 'Tomador',
            $cnpjCliente === $remCnpj                                => 'Remetente',
            $cnpjCliente === $destCnpj                               => 'Destinatário',
            $cnpjCliente === $expedCnpj                              => 'Expedidor',
            $cnpjCliente === $recebCnpj                              => 'Recebedor',
            default                                                  => null,
        };
    }

    /**
     * Evento de cancelamento (tpEvento 110111) não vira linha própria — mas
     * precisa atualizar a `situacao` do documento original, senão ele
     * continua marcado como normal para sempre mesmo depois de cancelado.
     *
     * Também reescreve o cStat/xMotivo dentro do próprio xml_content salvo —
     * sem isso o XML baixado do cofre fica congelado no momento da
     * autorização original (cStat 100), e ferramentas que leem o XML puro
     * pra decidir a situação continuam vendo a nota como normal mesmo ela
     * estando cancelada no nosso banco.
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
            Log::debug('[CT-e RS] processarEvento: ignorado (não é cancelamento)', ['tpEvento' => $tpEvento ?: null]);
            return;
        }

        $chave = $get('chNFe') ?: $get('chCTe');

        if ($chave === '') {
            Log::warning('[CT-e RS] processarEvento: cancelamento sem chNFe/chCTe extraível', ['xmlSample' => substr($xml, 0, 500)]);
            return;
        }

        $update = ['situacao' => 'cancelada'];

        $existente = DocumentoFiscal::where('chave_acesso', $chave)->first();
        if ($existente && !empty($existente->xml_content)) {
            $update['xml_content'] = $this->marcarXmlComoCancelado($existente->xml_content);
        }

        $linhas = DocumentoFiscal::where('chave_acesso', $chave)->update($update);

        Log::info('[CT-e RS] processarEvento: cancelamento processado', ['chave' => $chave, 'linhas_afetadas' => $linhas]);
    }

    /**
     * Reescreve cStat/xMotivo dentro de protCTe (grupo infProt) para refletir
     * o cancelamento — mesma convenção usada por outros provedores (ex.:
     * SIEG): cStat 101 "Cancelamento homologado", em vez de manter o cStat
     * 100 "Autorizado" original congelado no XML.
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
                'origem'        => 'rs',
                'nsu'           => $doc['nsu'] ?? null,
                'numero'        => $doc['numero'] ?? null,
                'data_emissao'  => !empty($doc['dataEmissao']) ? substr($doc['dataEmissao'], 0, 10) : null,
                'emitente_nome' => $doc['emitenteNome'] ?? null,
                'emitente_doc'  => $doc['emitenteDoc'] ?? null,
                'valor'         => $doc['valor'] ?: null,
                // Um cancelamento já detectado (via processarEvento) não pode ser desfeito por uma
                // reissincronização do mesmo documento sem essa informação.
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
