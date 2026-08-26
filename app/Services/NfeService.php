<?php

namespace App\Services;

use App\Models\ClienteCertificadoNfse;
use App\Models\DocumentoFiscal;
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

    // Máximo de lotes buscados por chamada (chunk). Assim como na NFS-e, uma
    // sincronização inteira (potencialmente centenas de lotes) numa única
    // requisição HTTP corre o risco de ser derrubada pelo Cloudflare (HTTP 524)
    // antes do backend terminar — por isso cada chamada processa só uma fatia,
    // e quem orquestra a busca completa (o front-end) chama repetidas vezes.
    const MAX_LOTES_POR_CHUNK = 12;

    // Código IBGE da UF — usado em cUFAutor. Índice: sigla da UF cadastrada no cliente.
    const UF_CODIGO = [
        'RO' => 11, 'AC' => 12, 'AM' => 13, 'RR' => 14, 'PA' => 15, 'AP' => 16, 'TO' => 17,
        'MA' => 21, 'PI' => 22, 'CE' => 23, 'RN' => 24, 'PB' => 25, 'PE' => 26, 'AL' => 27, 'SE' => 28, 'BA' => 29,
        'MG' => 31, 'ES' => 32, 'RJ' => 33, 'SP' => 35,
        'PR' => 41, 'SC' => 42, 'RS' => 43,
        'MS' => 50, 'MT' => 51, 'GO' => 52, 'DF' => 53,
    ];

    /**
     * Sincroniza uma fatia (chunk) dos NF-e/CT-e novos deste certificado, a
     * partir do NSU indicado (ou do último salvo, se omitido), para a tabela
     * `documentos_fiscais`. Não filtra por data — a API só suporta paginação
     * por NSU, e todo documento novo (não-evento) é persistido, já que a
     * distribuição é um feed incremental: um documento não capturado numa
     * sincronização não volta a aparecer numa consulta futura.
     *
     * Processa no máximo MAX_LOTES_POR_CHUNK lotes por chamada — o chamador
     * (controller/front-end) deve repetir a chamada passando 'proximoNsu' como
     * novo nsuInicio até 'concluido' vir true.
     *
     * Retorna ['concluido' => bool, 'proximoNsu' => int, 'aviso' => ?string] —
     * erros esperados da Sefaz (consumo indevido, etc.) não lançam exceção,
     * viram aviso com concluido=true, para que o chamador pare de tentar.
     */
    public function sincronizarChunk(ClienteCertificadoNfse $certificado, ?int $nsuInicio = null): array
    {
        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $certificado->cliente->cpfcnpj ?? '');
        $cUFAutor = $this->cUFAutor($certificado);
        $tpAmb    = $certificado->ambiente === 'producao' ? 1 : 2;
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        $nsuAtual = $nsuInicio ?? (int) $certificado->ultimo_nsu_nfe;

        Log::debug('[NF-e] sincronizarChunk: iniciando', [
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

                Log::debug('[NF-e] sincronizarChunk: lote recebido', [
                    'lote'      => $lotes,
                    'nsu_usado' => $nsuAtual,
                    'cStat'     => $cStat,
                    'xMotivo'   => $resp['xMotivo'] ?? null,
                    'ultNSU'    => $resp['ultNSU'] ?? null,
                    'maxNSU'    => $resp['maxNSU'] ?? null,
                    'qtd_docs'  => count($resp['docs'] ?? []),
                ]);

                // 656 = consumo indevido — geralmente porque outro sistema (contábil, ERP etc.)
                // já consome a distribuição deste CNPJ e a Sefaz está bem à frente do NSU que
                // tínhamos (0 na primeira consulta). A própria resposta de rejeição já traz o
                // ultNSU correto — persistimos aqui para autocalibrar a próxima tentativa. Não é
                // fatal: interrompe a sincronização, mas o chamador ainda responde com o que já
                // está salvo em `documentos_fiscais`.
                if ($cStat === '656') {
                    if (!empty($resp['ultNSU'])) {
                        $nsuAtual = (int) $resp['ultNSU'];
                        $certificado->update(['ultimo_nsu_nfe' => $nsuAtual]);
                    }

                    $aviso = 'A Sefaz rejeitou a sincronização por "consumo indevido" — provavelmente porque outro '
                        . 'sistema (contábil, ERP etc.) já consulta a distribuição de DF-e deste CNPJ. '
                        . 'Sincronizamos o NSU correto' . (!empty($resp['ultNSU']) ? " ({$resp['ultNSU']})" : '')
                        . ' para a próxima tentativa — mostrando os documentos já sincronizados anteriormente.';
                    $concluido = true;
                    break;
                }

                // 137 = nenhum documento localizado neste NSU — pode ser apenas um gap
                // (NSUs pulados que não pertencem a este interessado), não necessariamente
                // o fim do feed. Só é seguro concluir quando ultNSU alcançou maxNSU.
                if ($cStat === '137') {
                    if (isset($resp['ultNSU'])) {
                        $nsuAtual = (int) $resp['ultNSU'];
                        $certificado->update(['ultimo_nsu_nfe' => $nsuAtual]);
                    }

                    $maxNSUResp = isset($resp['maxNSU']) ? (int) $resp['maxNSU'] : null;

                    if ($maxNSUResp !== null && $nsuAtual < $maxNSUResp) {
                        $lotes++;
                        continue;
                    }

                    $concluido = true;
                    break;
                }

                if ($cStat !== '138') {
                    $aviso = "Distribuição DFe retornou cStat {$cStat}: " . ($resp['xMotivo'] ?? 'motivo desconhecido')
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
                        // Eventos não viram linha própria, mas cancelamento atualiza a situação
                        // do documento original (senão ele fica "normal" pra sempre no cofre).
                        $this->processarEvento($doc['xmlContent'] ?? '');
                        continue;
                    }

                    $this->persistir($certificado->cliente_id, 'nacional', $doc);
                }

                // Avança pelo ultNSU retornado pela própria Sefaz (não pelo NSU do último doc do lote) —
                // é o valor que o protocolo exige usar na próxima consulta.
                $nsuAtual = (int) $resp['ultNSU'];
                $maxNSU   = (int) $resp['maxNSU'];

                // Persiste a cada lote processado, não só no final — evita reconsultar o mesmo
                // ultNSU (e levar novo bloqueio de "consumo indevido") caso um lote seguinte falhe.
                $certificado->update(['ultimo_nsu_nfe' => $nsuAtual]);

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

        Log::debug('[NF-e] sincronizarChunk: concluído', ['concluido' => $concluido, 'proximoNsu' => $nsuAtual, 'aviso' => $aviso]);

        return ['concluido' => $concluido, 'proximoNsu' => $nsuAtual, 'aviso' => $aviso];
    }

    /**
     * Busca um único documento diretamente pela chave de acesso (consulta
     * `consChNFe` do distDFeInt, em vez do feed sequencial por NSU) e salva
     * no cofre — útil pra recuperar notas antigas pontuais sem resincronizar
     * o histórico inteiro de um cliente (ex.: migrando de outro provedor tipo
     * SIEG e só precisando de alguns casos específicos). Sem garantia de
     * funcionar para documentos muito antigos: a Sefaz pode não manter o
     * registro indefinidamente fora da janela normal de distribuição — teste
     * caso a caso.
     */
    public function buscarPorChave(ClienteCertificadoNfse $certificado, string $chaveAcesso): array
    {
        $chaveAcesso = preg_replace('/\D/', '', $chaveAcesso);

        if (strlen($chaveAcesso) !== 44) {
            return ['sucesso' => false, 'mensagem' => 'Chave de acesso inválida — precisa ter 44 dígitos.'];
        }

        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $certificado->cliente->cpfcnpj ?? '');
        $cUFAutor = $this->cUFAutor($certificado);
        $tpAmb    = $certificado->ambiente === 'producao' ? 1 : 2;
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $resp = $this->consultarPorChave($endpoint, $tpAmb, $cUFAutor, $cnpj, $chaveAcesso, $pemCert, $pemKey);

            Log::info('[NF-e] buscarPorChave', [
                'cliente_id' => $certificado->cliente_id,
                'chave'      => $chaveAcesso,
                'cStat'      => $resp['cStat'],
                'xMotivo'    => $resp['xMotivo'],
                'qtd_docs'   => count($resp['docs']),
            ]);

            if ($resp['cStat'] !== '138') {
                return ['sucesso' => false, 'mensagem' => "Sefaz retornou: {$resp['xMotivo']} (cStat {$resp['cStat']})"];
            }

            $docsSalvos = 0;

            foreach ($resp['docs'] as $doc) {
                if ($doc['tipo'] === 'evento') {
                    $this->processarEvento($doc['xmlContent'] ?? '');
                    continue;
                }

                $this->persistir($certificado->cliente_id, 'nacional', $doc);
                $docsSalvos++;
            }

            if ($docsSalvos === 0) {
                return ['sucesso' => false, 'mensagem' => 'A Sefaz encontrou a chave, mas só retornou eventos (ex.: cancelamento), não o documento em si.'];
            }

            return ['sucesso' => true, 'mensagem' => 'Documento encontrado e salvo no cofre com sucesso.'];
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }
    }

    /**
     * Grava ou atualiza um documento normalizado (ver normalizarDocumento) na
     * tabela `documentos_fiscais`, identificado pela chave de acesso.
     *
     * A Sefaz pode distribuir o mesmo documento mais de uma vez sob NSUs
     * diferentes (ex.: resumo resNFe/resCTe e, separadamente, o XML completo
     * procNFe/procCTe) — só sobrescreve um registro já existente se a nova
     * versão tiver tantos ou mais campos preenchidos, para não perder dados.
     */
    /**
     * Evento de cancelamento (tpEvento 110111) não vira linha própria — mas
     * precisa atualizar a `situacao` do documento original, senão ele
     * continua marcado como normal para sempre mesmo depois de cancelado. Se
     * o documento original ainda não tiver sido sincronizado, a atualização
     * simplesmente não afeta nenhuma linha (fica pendente até ele existir).
     *
     * Também reescreve o cStat/xMotivo dentro do próprio xml_content salvo —
     * sem isso o XML baixado do cofre fica congelado no momento da
     * autorização original (cStat 100), e ferramentas que leem o XML puro
     * pra decidir a situação (ex.: importação no Domínio) continuam vendo a
     * nota como normal mesmo ela estando cancelada no nosso banco.
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
            Log::debug('[NF-e] processarEvento: ignorado (não é cancelamento)', ['tpEvento' => $tpEvento ?: null]);
            return;
        }

        $chave = $get('chNFe') ?: $get('chCTe');

        if ($chave === '') {
            Log::warning('[NF-e] processarEvento: cancelamento sem chNFe/chCTe extraível', ['xmlSample' => substr($xml, 0, 500)]);
            return;
        }

        $update = ['situacao' => 'cancelada'];

        $existente = DocumentoFiscal::where('chave_acesso', $chave)->first();
        if ($existente && !empty($existente->xml_content)) {
            $update['xml_content'] = $this->marcarXmlComoCancelado($existente->xml_content);
        }

        $linhas = DocumentoFiscal::where('chave_acesso', $chave)->update($update);

        Log::info('[NF-e] processarEvento: cancelamento processado', ['chave' => $chave, 'linhas_afetadas' => $linhas]);
    }

    /**
     * Reescreve cStat/xMotivo dentro de protNFe/protCTe (grupo infProt) para
     * refletir o cancelamento — mesma convenção usada por outros provedores
     * (ex.: SIEG): cStat 101 "Cancelamento ... homologado", em vez de manter
     * o cStat 100 "Autorizado" original congelado no XML.
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

    private function persistir(int $clienteId, string $origem, array $doc): void
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
                'tipo'          => $doc['tipo'],
                'origem'        => $origem,
                'nsu'           => $doc['nsu'] ?? null,
                'numero'        => $doc['numero'] ?? null,
                'data_emissao'       => !empty($doc['dataEmissao']) ? substr($doc['dataEmissao'], 0, 10) : null,
                'data_saida_entrada' => !empty($doc['dataSaidaEntrada']) ? substr($doc['dataSaidaEntrada'], 0, 10) : null,
                'emitente_nome'      => $doc['emitenteNome'] ?? null,
                'emitente_doc'       => $doc['emitenteDoc'] ?? null,
                'valor'              => $doc['valor'] ?: null,
                // Um cancelamento já detectado (via processarEvento) não pode ser desfeito por uma
                // reissincronização do mesmo documento sem essa informação (resumo/XML completo não
                // carregam o cancelamento, só o evento separado carrega).
                'situacao'           => $existente?->situacao === 'cancelada' ? 'cancelada' : ($doc['situacao'] ?? null),
                'tp_nf'              => $doc['tpNf'] ?? $existente?->tp_nf,
                'xml_content'        => $doc['xmlContent'] ?? null,
            ]
        );
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
        $tagDoc  = strlen($cnpj) === 11 ? 'CPF' : 'CNPJ';
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
          <{$tagDoc}>{$cnpj}</{$tagDoc}>
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

    /**
     * Mesmo envelope de consultarNsu, trocando <distNSU> por <consChNFe> —
     * consulta um único documento pela chave em vez do feed sequencial.
     */
    private function consultarPorChave(string $endpoint, int $tpAmb, int $cUFAutor, string $cnpj, string $chaveAcesso, string $pemCert, string $pemKey): array
    {
        $versao = self::VERSAO;
        $tagDoc = strlen($cnpj) === 11 ? 'CPF' : 'CNPJ';
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
          <{$tagDoc}>{$cnpj}</{$tagDoc}>
          <consChNFe><chNFe>{$chaveAcesso}</chNFe></consChNFe>
        </distDFeInt>
      </nfeDadosMsg>
    </nfeDistDFeInteresse>
  </soap12:Body>
</soap12:Envelope>
XML;

        // TEMP DEBUG — remover depois de diagnosticar o cStat 215 recorrente em buscarPorChave
        Log::debug('[NF-e] consultarPorChave: envelope montado', [
            'cnpj'      => $cnpj,
            'strlenCnpj' => strlen($cnpj),
            'tagDoc'    => $tagDoc,
            'envelope'  => $envelope,
        ]);

        $resposta = $this->requisicaoSoap($endpoint, $envelope, $pemCert, $pemKey);

        return $this->parseRetDistDFeInt($resposta);
    }

    private function requisicaoSoap(string $endpoint, string $envelope, string $pemCert, string $pemKey): string
    {
        Log::debug('[NF-e] requisicaoSoap: enviando', ['url' => $endpoint]);

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

        Log::debug('[NF-e] requisicaoSoap: resposta recebida', [
            'httpCode'   => $httpCode,
            'curlErrNo'  => $curlErrNo,
            'curlError'  => $curlError ?: null,
            'bodyLen'    => is_string($resposta) ? strlen($resposta) : 'false',
            // Amostra do início do corpo — cStat/xMotivo/ultNSU/maxNSU vêm antes dos docZip (base64 grande)
            'bodySample' => is_string($resposta) ? substr($resposta, 0, 800) : null,
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
        // Cobre tanto o resumo (resEvento) quanto o evento completo (procEventoNFe/procEventoCTe),
        // que a Sefaz também retorna via docZip (ex.: Ciência da Operação, tpEvento 210210).
        if (str_contains($schema, 'Evento')) {
            return ['nsu' => $nsu, 'tipo' => 'evento', 'xmlContent' => $xml];
        }

        $tipoDoc = str_starts_with($schema, 'resCTe') ? 'cte' : 'nfe';

        libxml_use_internal_errors(true);
        $obj = new \SimpleXMLElement($xml);
        $get = fn(string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

        $chave  = $get('chNFe') ?: $get('chCTe');
        $numero = $get('nNF') ?: $get('nCT');

        // resNFe/resCTe (resumo) não trazem nNF/nCT — o número do documento fica embutido
        // na própria chave de acesso (posições 26 a 34, 1-indexed).
        if (!$numero && $chave && strlen($chave) === 44) {
            $numero = (string) (int) substr($chave, 25, 9);
        }

        $dataEmissao      = $get('dhEmi');
        $dataSaidaEntrada = $get('dhSaiEnt') ?: $get('dSaiEnt');
        $emitenteNome     = $get('xNome');
        $valor            = $get('vNF') ?: $get('vCT');
        $tpNfStr          = $get('tpNF');

        if (!$dataEmissao && !$emitenteNome && !$valor) {
            Log::warning('[NF-e] normalizarDocumento: campos vazios após parse', [
                'nsu'       => $nsu,
                'schema'    => $schema,
                'xmlSample' => substr($xml, 0, 600),
            ]);
        }

        return [
            'nsu'          => $nsu,
            'tipo'         => $tipoDoc,
            'chaveAcesso'  => $chave,
            'numero'           => $numero,
            'dataEmissao'      => $dataEmissao,
            'dataSaidaEntrada' => $dataSaidaEntrada,
            'emitenteNome'     => $this->utf8Safe($emitenteNome),
            'emitenteDoc'      => $get('CNPJ') ?: $get('CPF'),
            'valor'            => $valor,
            // cSitDFe só existe em resumos (resNFe/resCTe) — uma consulta direta por chave
            // (consChNFe) traz o documento completo, sem essa tag; normaliza pra null (não
            // string vazia) pra não gravar lixo em `situacao`.
            'situacao'         => $get('cSitDFe') ?: null,
            'tpNf'             => $tpNfStr !== '' ? (int) $tpNfStr : null,
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
