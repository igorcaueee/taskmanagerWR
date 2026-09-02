<?php

namespace App\Services;

use App\Models\CertificadoContabilidade;
use App\Models\Cliente;
use App\Models\DocumentoFiscal;
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
 * O campo `mod` (55=NF-e, 65=NFC-e) é opcional no schema `distNFeRS`, mas
 * omiti-lo faz a Sefaz-RS devolver só NF-e — por isso rodamos duas
 * sincronizações independentes por cliente (uma por modelo), cada uma com seu
 * próprio NSU (são sequências separadas na base da SEFAZ-RS).
 *
 * SOAPAction e nomes de elementos confirmados via WSDL real (baixado com o
 * certificado da contabilidade através de buscarWsdl()): operação
 * nfeIntegracaoContab, wrapper nfeDadosMsgDownload, namespace
 * http://www.portalfiscal.inf.br/nfe/wsdl/NFeIntegracao.
 */
class NfeIntegracaoRsService
{
    use LidaComCertificadoPfx;

    const ENDPOINT_PRODUCAO    = 'https://dfe-servico.svrs.rs.gov.br/WS/NFeIntegracao/NFeIntegracao.asmx';
    const ENDPOINT_HOMOLOGACAO = 'https://dfe-servico-homologacao.svrs.rs.gov.br/WS/NFeIntegracao/NFeIntegracao.asmx';

    const SOAP_ACTION = 'http://www.portalfiscal.inf.br/nfe/wsdl/NFeIntegracao/nfeIntegracaoContab';

    const CUF_RS = 43;

    const MOD_NFE  = '55';
    const MOD_NFCE = '65';

    // Coluna de NSU por modelo — sequências independentes na SEFAZ-RS.
    const CAMPO_NSU = [
        self::MOD_NFE  => 'ultimo_nsu_nfe_rs',
        self::MOD_NFCE => 'ultimo_nsu_nfce_rs',
    ];

    // Máximo de lotes buscados por chamada (chunk) — ver NfeService::MAX_LOTES_POR_CHUNK
    // para o motivo (evitar timeout de proxy/CDN numa sincronização longa).
    const MAX_LOTES_POR_CHUNK = 12;

    /**
     * Sincroniza uma fatia (chunk) de um único modelo (NF-e ou NFC-e) de um
     * cliente (CNPJ), a partir do NSU indicado (ou do último salvo daquele
     * modelo, se omitido), para a tabela `documentos_fiscais`, usando o
     * certificado da contabilidade. Não filtra por data (ver NfeService).
     *
     * NF-e e NFC-e têm sequências de NSU independentes na SEFAZ-RS — por isso
     * o chamador (controller/front-end) sincroniza um modelo de cada vez,
     * repetindo a chamada com 'proximoNsu' até 'concluido' vir true, e só
     * então passa para o outro modelo.
     *
     * $modoBackfill: mesmo propósito do CteIntegracaoRsService::sincronizarChunk
     * — usado pela reconsulta de janela (fiscal:reconsultar-notas-rs), impede
     * que o checkpoint (ultimo_nsu_nfe_rs/ultimo_nsu_nfce_rs) regrida quando a
     * reconsulta começa num NSU anterior ao já processado.
     *
     * Retorna ['concluido' => bool, 'proximoNsu' => int, 'aviso' => ?string].
     */
    public function sincronizarChunk(CertificadoContabilidade $certificado, Cliente $cliente, string $mod, ?int $nsuInicio = null, bool $modoBackfill = false): array
    {
        $campoNsu = self::CAMPO_NSU[$mod] ?? throw new \InvalidArgumentException("Modelo inválido: {$mod}");

        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $cliente->cpfcnpj ?? '');
        $tpAmb    = $certificado->ambiente === 'producao' ? 1 : 2;
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        $nsuAtual = $nsuInicio ?? (int) $cliente->{$campoNsu};

        Log::debug('[NF-e RS] sincronizarChunk: iniciando', [
            'cliente_id'  => $cliente->id,
            'cnpj'        => $cnpj,
            'mod'         => $mod,
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
                $resp = $this->consultarNsu($endpoint, $tpAmb, $cnpj, $mod, $nsuAtual, $pemCert, $pemKey);

                Log::debug('[NF-e RS] sincronizarChunk: lote recebido', [
                    'mod'       => $mod,
                    'lote'      => $lotes,
                    'nsu_usado' => $nsuAtual,
                    'cStat'     => $resp['cStat'],
                    'xMotivo'   => $resp['xMotivo'],
                    'ultNSU'    => $resp['ultNSU'],
                    'qtd_docs'  => count($resp['docs']),
                ]);

                if ($resp['cStat'] === '678') {
                    if (!empty($resp['ultNSU'])) {
                        $nsuAtual = (int) $resp['ultNSU'];
                        $this->atualizarCheckpoint($cliente, $campoNsu, $nsuAtual, $modoBackfill);
                    }

                    $aviso = 'A Sefaz-RS rejeitou a sincronização de ' . ($mod === self::MOD_NFCE ? 'NFC-e' : 'NF-e')
                        . ' por "consumo indevido" — aguarde e tente novamente mais tarde. Mostrando os '
                        . 'documentos já sincronizados anteriormente.';
                    $concluido = true;
                    break;
                }

                // Lote sem documentos: pode ser o fim do feed OU um "buraco" na sequência de
                // NSU (a SVRS avisa que a entrega não é sequencial). Se o ultNSURet ainda
                // avançou, há mais coisa depois do buraco — continua paginando; só conclui
                // quando o ultNSURet trava (nada além do NSU já consultado). Antes o código
                // parava no primeiro buraco e nunca alcançava as notas seguintes.
                if (empty($resp['docs'])) {
                    $nsuAnterior = $nsuAtual;

                    if (!empty($resp['ultNSU'])) {
                        $nsuAtual = (int) $resp['ultNSU'];
                        $this->atualizarCheckpoint($cliente, $campoNsu, $nsuAtual, $modoBackfill);
                    }

                    if ($nsuAtual <= $nsuAnterior) {
                        $concluido = true;
                        break;
                    }

                    $lotes++;
                    continue;
                }

                foreach ($resp['docs'] as $doc) {
                    if ($doc['tipo'] === 'evento') {
                        $this->processarEvento($doc['xmlContent'] ?? '');
                        continue;
                    }

                    Log::debug('[NF-e RS] sincronizarChunk: doc no lote', [
                        'mod'         => $mod,
                        'chave'       => $doc['chaveAcesso'] ?? null,
                        'tipo'        => $doc['tipo'] ?? null,
                        'numero'      => $doc['numero'] ?? null,
                        'dataEmissao' => $doc['dataEmissao'] ?? null,
                    ]);

                    $this->persistir($cliente->id, $doc);
                }

                // A Sefaz-RS NÃO garante 50 docs por lote até o fim do feed — a própria SVRS
                // reconhece que a entrega de NSU não é sequencial (ver ReconsultarNotasFiscaisRs).
                // Um lote curto no meio do feed não significa fim: antes o código parava aí
                // (count < 50) e deixava pra trás tudo que vinha depois — inclusive furando a
                // reconsulta de janela, que quase sempre lê lotes esparsos. Só é seguro parar
                // quando o ultNSURet deixa de avançar (nada depois do NSU já consultado).
                $nsuAnterior = $nsuAtual;

                if (!empty($resp['ultNSU'])) {
                    $nsuAtual = (int) $resp['ultNSU'];
                    $this->atualizarCheckpoint($cliente, $campoNsu, $nsuAtual, $modoBackfill);
                }

                if ($nsuAtual <= $nsuAnterior) {
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

        Log::debug('[NF-e RS] sincronizarChunk: concluído', ['mod' => $mod, 'concluido' => $concluido, 'proximoNsu' => $nsuAtual, 'aviso' => $aviso]);

        return ['concluido' => $concluido, 'proximoNsu' => $nsuAtual, 'aviso' => $aviso];
    }

    /**
     * Grava o novo NSU no checkpoint do cliente/modelo — em modo backfill, só
     * grava se o novo valor for maior que o atual (mesma lógica de
     * CteIntegracaoRsService::atualizarCheckpoint).
     */
    private function atualizarCheckpoint(Cliente $cliente, string $campoNsu, int $novoNsu, bool $modoBackfill): void
    {
        if ($modoBackfill && $novoNsu <= (int) $cliente->{$campoNsu}) {
            return;
        }

        $cliente->update([$campoNsu => $novoNsu]);
    }

    /**
     * Busca uma única NF-e/NFC-e diretamente pela chave de acesso (consulta
     * `solDFe`, mesmo padrão do CteIntegracaoRsService::buscarPorChave) e
     * salva no cofre — útil pra recuperar notas antigas pontuais sem
     * resincronizar o histórico inteiro de um cliente (ex.: migrando de outro
     * provedor tipo SIEG e só precisando de alguns casos específicos). O
     * modelo (NF-e/NFC-e) é inferido da própria chave (posições 21-22).
     */
    public function buscarPorChave(CertificadoContabilidade $certificado, Cliente $cliente, string $chaveAcesso): array
    {
        $chaveAcesso = preg_replace('/\D/', '', $chaveAcesso);

        if (strlen($chaveAcesso) !== 44) {
            return ['sucesso' => false, 'mensagem' => 'Chave de acesso inválida — precisa ter 44 dígitos.'];
        }

        $mod = substr($chaveAcesso, 20, 2) === self::MOD_NFCE ? self::MOD_NFCE : self::MOD_NFE;

        $certPath = storage_path('app/' . $certificado->arquivo);
        $cnpj     = preg_replace('/[.\-\/\s]/', '', $cliente->cpfcnpj ?? '');
        $tpAmb    = $certificado->ambiente === 'producao' ? 1 : 2;
        $endpoint = $certificado->ambiente === 'producao' ? self::ENDPOINT_PRODUCAO : self::ENDPOINT_HOMOLOGACAO;

        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $resp = $this->consultarPorChave($endpoint, $tpAmb, $cnpj, $mod, $chaveAcesso, $pemCert, $pemKey);

            Log::info('[NF-e RS] buscarPorChave', [
                'cliente_id' => $cliente->id,
                'chave'      => $chaveAcesso,
                'mod'        => $mod,
                'cStat'      => $resp['cStat'],
                'xMotivo'    => $resp['xMotivo'],
                'qtd_docs'   => count($resp['docs']),
            ]);

            if (empty($resp['docs'])) {
                return ['sucesso' => false, 'mensagem' => "Sefaz-RS não encontrou a chave: {$resp['xMotivo']} (cStat {$resp['cStat']})"];
            }

            $docsSalvos = 0;

            foreach ($resp['docs'] as $doc) {
                if ($doc['tipo'] === 'evento') {
                    $this->processarEvento($doc['xmlContent'] ?? '');
                    continue;
                }

                $this->persistir($cliente->id, $doc);
                $docsSalvos++;
            }

            if ($docsSalvos === 0) {
                return ['sucesso' => false, 'mensagem' => 'A Sefaz-RS encontrou a chave, mas só retornou eventos (ex.: cancelamento), não o documento em si.'];
            }

            return ['sucesso' => true, 'mensagem' => 'Documento encontrado e salvo no cofre com sucesso.'];
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }
    }

    /**
     * Evento de cancelamento (tpEvento 110111) não vira linha própria — mas
     * precisa atualizar a `situacao` do documento original, senão ele
     * continua marcado como normal para sempre mesmo depois de cancelado.
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
            Log::debug('[NF-e RS] processarEvento: ignorado (não é cancelamento)', ['tpEvento' => $tpEvento ?: null]);
            return;
        }

        $chave = $get('chNFe') ?: $get('chCTe');

        if ($chave === '') {
            Log::warning('[NF-e RS] processarEvento: cancelamento sem chNFe/chCTe extraível', ['xmlSample' => substr($xml, 0, 500)]);
            return;
        }

        $update = ['situacao' => 'cancelada'];

        $existente = DocumentoFiscal::where('chave_acesso', $chave)->first();
        if ($existente && !empty($existente->xml_content)) {
            $update['xml_content'] = $this->marcarXmlComoCancelado($existente->xml_content);
        }

        $linhas = DocumentoFiscal::where('chave_acesso', $chave)->update($update);

        Log::info('[NF-e RS] processarEvento: cancelamento processado', ['chave' => $chave, 'linhas_afetadas' => $linhas]);
    }

    /**
     * Reescreve cStat/xMotivo dentro de protNFe (grupo infProt) para refletir
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
     * Grava ou atualiza um documento normalizado na tabela `documentos_fiscais`,
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
                'tipo'          => $doc['tipo'],
                'origem'        => 'rs',
                'nsu'           => $doc['nsu'] ?? null,
                'numero'        => $doc['numero'] ?? null,
                'data_emissao'       => !empty($doc['dataEmissao']) ? substr($doc['dataEmissao'], 0, 10) : null,
                'data_saida_entrada' => !empty($doc['dataSaidaEntrada']) ? substr($doc['dataSaidaEntrada'], 0, 10) : null,
                'emitente_nome'      => $doc['emitenteNome'] ?? null,
                'emitente_doc'       => $doc['emitenteDoc'] ?? null,
                'valor'              => $doc['valor'] ?: null,
                // Um cancelamento já detectado (via processarEvento) não pode ser desfeito por uma
                // reissincronização do mesmo documento sem essa informação.
                'situacao'           => $existente?->situacao === 'cancelada' ? 'cancelada' : ($doc['situacao'] ?? null),
                'tp_nf'              => $doc['tpNf'] ?? $existente?->tp_nf,
                'emitente_crt'       => $doc['emitenteCrt'] ?? $existente?->emitente_crt,
                'xml_content'        => $doc['xmlContent'] ?? null,
            ]
        );
    }

    private function consultarNsu(string $endpoint, int $tpAmb, string $cnpj, string $mod, int $ultNSU, string $pemCert, string $pemKey): array
    {
        $cUF = self::CUF_RS;

        // Diferente do webservice nacional (distDFeInt), o schema do distNFeRS
        // não tem <xs:choice> CNPJ/CPF — só existe o elemento <CNPJ>, mesmo
        // quando o valor é um CPF (11 dígitos). Usar <CPF> aqui gera cStat 215.
        $envelope = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <nfeIntegracaoContab xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/NFeIntegracao">
      <nfeDadosMsgDownload>
        <distNFeRS xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.00">
          <tpAmb>{$tpAmb}</tpAmb>
          <verAplic>TaskManagerWR</verAplic>
          <cUF>{$cUF}</cUF>
          <CNPJ>{$cnpj}</CNPJ>
          <mod>{$mod}</mod>
          <solRel>
            <indXML>1</indXML>
            <indEmit>7</indEmit>
            <indDest>7</indDest>
            <ultNSU>{$ultNSU}</ultNSU>
          </solRel>
        </distNFeRS>
      </nfeDadosMsgDownload>
    </nfeIntegracaoContab>
  </soap:Body>
</soap:Envelope>
XML;

        $resposta = $this->requisicaoSoap($endpoint, $envelope, $pemCert, $pemKey);

        return $this->parseRetDistNFeRS($resposta);
    }

    /**
     * Mesmo envelope de consultarNsu, trocando <solRel> por <solDFe> —
     * consulta um único documento pela chave em vez do feed sequencial.
     */
    private function consultarPorChave(string $endpoint, int $tpAmb, string $cnpj, string $mod, string $chaveAcesso, string $pemCert, string $pemKey): array
    {
        $cUF = self::CUF_RS;

        $envelope = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <nfeIntegracaoContab xmlns="http://www.portalfiscal.inf.br/nfe/wsdl/NFeIntegracao">
      <nfeDadosMsgDownload>
        <distNFeRS xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.00">
          <tpAmb>{$tpAmb}</tpAmb>
          <verAplic>TaskManagerWR</verAplic>
          <cUF>{$cUF}</cUF>
          <CNPJ>{$cnpj}</CNPJ>
          <mod>{$mod}</mod>
          <solDFe>
            <chAcesso>{$chaveAcesso}</chAcesso>
          </solDFe>
        </distNFeRS>
      </nfeDadosMsgDownload>
    </nfeIntegracaoContab>
  </soap:Body>
</soap:Envelope>
XML;

        $resposta = $this->requisicaoSoap($endpoint, $envelope, $pemCert, $pemKey);

        return $this->parseRetDistNFeRS($resposta);
    }

    private function requisicaoSoap(string $endpoint, string $envelope, string $pemCert, string $pemKey): string
    {
        Log::debug('[NF-e RS] requisicaoSoap: enviando', ['url' => $endpoint]);

        // A Sefaz-RS rejeita (cStat 588) qualquer espaço/quebra de linha entre
        // tags — o envelope é escrito formatado no código por legibilidade,
        // mas precisa ser compactado antes de ir para a rede.
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

        Log::debug('[NF-e RS] requisicaoSoap: resposta recebida', [
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
        // ultNSU no retorno é só o eco do que foi enviado na requisição — o NSU real de onde
        // continuar (para não reprocessar o mesmo lote infinitamente) vem em ultNSURet.
        $ultNSUStr = $get('ultNSURet') ?: $get('ultNSU');
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
                // A SEFAZ-RS envolve cada documento num <proc NSU="..." chAcesso="..." schema="...">
                // que não faz parte do padrão da NF-e (não é aceito por outros sistemas, ex. Econet).
                // Extraímos apenas o elemento filho real (nfeProc/resNFe/procEventoNFe/etc.).
                $xml = $this->extrairFilhoXml($proc);

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
     * Retorna o XML do primeiro elemento filho de um nó (ex.: <nfeProc> dentro de <proc>),
     * descartando o elemento pai/wrapper e seus atributos (NSU, chAcesso, schema).
     * Usa DOM em vez de SimpleXML::children() porque o filho costuma declarar seu próprio
     * namespace (xmlns do portalfiscal), diferente do namespace do elemento pai.
     */
    private function extrairFilhoXml(\SimpleXMLElement $elemento): string
    {
        $domElemento = dom_import_simplexml($elemento);

        foreach ($domElemento->childNodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                return $node->ownerDocument->saveXML($node);
            }
        }

        return $elemento->asXML();
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

        // O emitente pode ser CPF (produtor rural — comum no RS): nesse caso <emit> não tem
        // <CNPJ>, e um xpath global "//CNPJ" acaba pegando o <dest> (o nosso cliente), o que
        // inverte a direção entrada/saída (ver DocumentoFiscal::direcao). Por isso os dados
        // do emitente são lidos escopados ao nó dele — <emit> no XML completo, <resNFe> no
        // resumo, onde CNPJ/CPF/xNome/CRT são filhos diretos.
        $emitNode = $obj->xpath("//*[local-name()='emit']")[0]
            ?? $obj->xpath("//*[local-name()='resNFe']")[0]
            ?? null;
        $getEmit = function (string $tag) use ($emitNode, $get) {
            if ($emitNode === null) {
                return $get($tag);
            }

            return trim((string) ($emitNode->xpath(".//*[local-name()='{$tag}']")[0] ?? ''));
        };

        $numero = $get('nNF');

        if (!$numero && $chave && strlen($chave) === 44) {
            $numero = (string) (int) substr($chave, 25, 9);
        }

        $dataEmissao      = $get('dhEmi');
        $dataSaidaEntrada = $get('dhSaiEnt') ?: $get('dSaiEnt');
        $emitenteNome     = $getEmit('xNome');
        $valor            = $get('vNF');
        $tpNfStr          = $get('tpNF');
        $crtStr           = $getEmit('CRT');

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
            'numero'            => $numero,
            'dataEmissao'       => $dataEmissao,
            'dataSaidaEntrada'  => $dataSaidaEntrada,
            'emitenteNome'      => $this->utf8Safe($emitenteNome),
            'emitenteDoc'       => $getEmit('CNPJ') ?: $getEmit('CPF'),
            'valor'             => $valor,
            // cSitNFe só existe em resumos — uma consulta direta por chave (solDFe) traz o
            // documento completo, sem essa tag; normaliza pra null (não string vazia).
            'situacao'          => $get('cSitNFe') ?: null,
            'tpNf'              => $tpNfStr !== '' ? (int) $tpNfStr : null,
            'emitenteCrt'       => $crtStr !== '' ? (int) $crtStr : null,
            'xmlContent'        => $xml,
        ];
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
