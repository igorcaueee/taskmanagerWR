<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ReinfFechamento;
use App\Services\Concerns\LidaComCertificadoPfx;
use Illuminate\Support\Facades\Log;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

/**
 * Orquestra o envio de eventos de fechamento do EFD-Reinf (R-2099/R-4099):
 * monta o XML (ReinfFechamentoXmlBuilderService), assina digitalmente,
 * envelopa em lote assíncrono, envia (ReinfService) e persiste o resultado
 * em ReinfFechamento. Consulta o protocolo separadamente (o envio só
 * confirma recepção do lote, não o processamento do evento).
 *
 * ATENÇÃO — envelope do lote (montarLote) modelado a partir do XSD de
 * comunicação da e-Financeira (envioLoteEventosAssincrono/retornoLoteEventos
 * Assincrono v1_0_0), que segue o mesmo padrão genérico de lote assíncrono
 * usado por várias obrigações do SPED — NÃO é o XSD oficial do REINF (que
 * usa namespace/tag raiz próprios, já confirmados no manual: <Reinf
 * xmlns=".../envioLoteEventosAssincrono/v1_00_00">). A estrutura interna
 * (loteEventosAssincrono > cnpjDeclarante + eventos > evento[id]) e os nomes
 * de tag do retorno (cdResposta, protocoloEnvio) foram herdados desse
 * schema-irmão por analogia — plausível, mas não 100% confirmado para o
 * REINF especificamente. Conferir no primeiro envio real em Produção
 * Restrita (a API retorna HTTP 422/415 com XML de erro se o envelope não
 * bater com o schema, o que ajuda a corrigir rapidamente).
 */
class ReinfEnvioService
{
    use LidaComCertificadoPfx;

    public function __construct(
        private ReinfService $reinfService,
        private ReinfFechamentoXmlBuilderService $xmlBuilder,
    ) {}

    /**
     * @param array{nome: string, cpf: string, telefone: string, email: string} $responsavel
     * @param array $dados Indicadores específicos do tipo de evento — ver
     *   ReinfFechamentoXmlBuilderService::montar().
     */
    public function enviarFechamento(Cliente $cliente, string $tipoEvento, string $periodoApuracao, array $responsavel, array $dados): ReinfFechamento
    {
        $certificado = $cliente->certificadoNfse;

        if (!$certificado) {
            throw new \RuntimeException("Cliente {$cliente->nome} não possui certificado digital cadastrado.");
        }

        if ($certificado->vencido()) {
            throw new \RuntimeException("Certificado digital do cliente {$cliente->nome} está vencido.");
        }

        $fechamento = ReinfFechamento::firstOrNew([
            'cliente_id' => $cliente->id,
            'tipo_evento' => $tipoEvento,
            'periodo_apuracao' => $periodoApuracao,
        ]);

        if ($fechamento->exists && in_array($fechamento->status, ['sucesso', 'processando'], true)) {
            throw new \RuntimeException("Já existe um fechamento {$tipoEvento} para {$periodoApuracao} com status '{$fechamento->status}'.");
        }

        $fechamento->ambiente = $certificado->ambiente;
        $fechamento->status = 'rascunho';
        $fechamento->save();

        $certPath = storage_path('app/' . $certificado->arquivo);
        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $xmlEvento = $this->xmlBuilder->montar($fechamento, $cliente, $responsavel, $dados);
            $idElemento = $fechamento->tipo_evento === 'R-2099' ? 'evtFechaEvPer' : 'evtFech';
            $xmlAssinado = $this->assinarComoIrmaoDaRaiz($xmlEvento, $idElemento, $pemCert, $pemKey);

            $fechamento->xml_evento = $xmlAssinado;

            $cnpjDeclarante = preg_replace('/\D/', '', $cliente->cpfcnpj);
            $xmlLote = $this->montarLote($xmlAssinado, $cnpjDeclarante);

            $resposta = $this->reinfService->enviarLote($certificado, $xmlLote, $pemCert, $pemKey);

            $fechamento->xml_retorno = $resposta['corpo'];
            $fechamento->cd_resposta = $this->extrairTagPorNomeLocal($resposta['corpo'], 'cdResposta');
            $fechamento->numero_protocolo = $this->extrairTagPorNomeLocal($resposta['corpo'], 'protocoloEnvio')
                ?? $this->extrairTagPorNomeLocal($resposta['corpo'], 'numeroProtocolo');

            if ($resposta['httpCode'] === 201 && $fechamento->numero_protocolo) {
                $fechamento->status = 'processando';
                $fechamento->erro_mensagem = null;
            } else {
                $fechamento->status = 'erro';
                $fechamento->erro_mensagem = "HTTP {$resposta['httpCode']}: " . substr($resposta['corpo'], 0, 2000);
            }

            $fechamento->save();
        } catch (\Throwable $e) {
            Log::error('[EFD-Reinf] enviarFechamento: falha', ['fechamento_id' => $fechamento->id, 'erro' => $e->getMessage()]);
            $fechamento->status = 'erro';
            $fechamento->erro_mensagem = $e->getMessage();
            $fechamento->save();
            throw $e;
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        return $fechamento;
    }

    public function consultarLote(ReinfFechamento $fechamento): ReinfFechamento
    {
        if (!$fechamento->numero_protocolo) {
            throw new \RuntimeException('Este fechamento ainda não tem número de protocolo — envie primeiro.');
        }

        $certificado = $fechamento->cliente->certificadoNfse;

        if (!$certificado) {
            throw new \RuntimeException('Cliente não possui certificado digital cadastrado.');
        }

        $certPath = storage_path('app/' . $certificado->arquivo);
        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $resposta = $this->reinfService->consultarLote($certificado, $fechamento->numero_protocolo, $pemCert, $pemKey);

            $fechamento->xml_retorno = $resposta['corpo'];
            $cdResposta = $this->extrairTagPorNomeLocal($resposta['corpo'], 'cdResposta');
            $fechamento->cd_resposta = $cdResposta;
            $fechamento->numero_recibo = $this->extrairTagPorNomeLocal($resposta['corpo'], 'numeroRecibo')
                ?? $this->extrairTagPorNomeLocal($resposta['corpo'], 'nrRecArqBase');

            $fechamento->status = match ((int) $cdResposta) {
                1 => 'processando',
                2 => 'sucesso',
                default => 'erro', // 3 (com ocorrências), 8, 9, 99
            };

            if ($fechamento->status === 'erro') {
                $fechamento->erro_mensagem = substr($resposta['corpo'], 0, 2000);
            }

            $fechamento->save();
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        return $fechamento;
    }

    /**
     * Assina o elemento do evento (ex: evtFechamento) mas — diferente de
     * AssinaXmlDigitalmente::assinarElemento (usada na NFS-e, onde a
     * <Signature> fica dentro do próprio elemento referenciado) — insere a
     * <Signature> como IRMÃ do elemento na raiz <Reinf>, conforme o diagrama
     * da seção 2.5 do manual do EFD-Reinf (Reinf > evtXXX + ds:Signature,
     * ambos filhos diretos de Reinf).
     */
    private function assinarComoIrmaoDaRaiz(string $xml, string $idElemento, string $caminhoPemCert, string $caminhoPemKey): string
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);

        $raiz = $dom->documentElement;
        $node = $dom->getElementsByTagName($idElemento)->item(0);

        if ($node === null) {
            throw new \RuntimeException("Elemento <{$idElemento}> não encontrado no XML para assinatura.");
        }

        $dsig = new XMLSecurityDSig();
        $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $dsig->addReferenceList(
            [$node],
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['id_name' => 'id', 'overwrite' => false]
        );

        $chave = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $chave->loadKey($caminhoPemKey, true);

        $dsig->sign($chave);
        $dsig->add509Cert(file_get_contents($caminhoPemCert), true, false, ['subjectName' => true]);
        $dsig->appendSignature($raiz);

        return $dom->saveXML();
    }

    /**
     * Envelope do lote assíncrono — namespace/tag raiz <Reinf> confirmados
     * no manual (seção 4.3); estrutura interna (loteEventosAssincrono >
     * cnpjDeclarante + eventos > evento[id]) por analogia ao XSD-irmão da
     * e-Financeira — ver ressalva no docblock da classe.
     */
    private function montarLote(string $xmlEventoAssinado, string $cnpjDeclarante): string
    {
        // Monta por concatenação de string (não via DOMDocument::importNode)
        // de propósito: importNode reatribui prefixo de namespace aos
        // elementos importados (viraria "default:evtFechaEvPer" etc), o que
        // muda a forma canônica (C14N preserva o prefixo original, não só a
        // URI) e invalidaria a assinatura já calculada. Concatenar preserva
        // os bytes assinados exatamente como foram canonicalizados.
        $eventoDom = new \DOMDocument();
        $eventoDom->loadXML($xmlEventoAssinado);
        $idEvento = $eventoDom->documentElement->firstChild?->getAttribute('id') ?? '';

        $xmlEventoSemProlog = preg_replace('/^<\?xml[^>]*\?>\s*/', '', $xmlEventoAssinado);

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Reinf xmlns="http://www.reinf.esocial.gov.br/schemas/envioLoteEventosAssincrono/v1_00_00">'
            . '<loteEventosAssincrono>'
            . '<cnpjDeclarante>' . htmlspecialchars($cnpjDeclarante, ENT_XML1) . '</cnpjDeclarante>'
            . '<eventos>'
            . '<evento id="' . htmlspecialchars($idEvento, ENT_XML1) . '">' . $xmlEventoSemProlog . '</evento>'
            . '</eventos>'
            . '</loteEventosAssincrono>'
            . '</Reinf>';
    }

    private function extrairTagPorNomeLocal(string $xml, string $nomeTag): ?string
    {
        $dom = new \DOMDocument();
        if (!@$dom->loadXML($xml)) {
            return null;
        }

        $xpath = new \DOMXPath($dom);
        $nos = $xpath->query("//*[local-name()='{$nomeTag}']");

        return $nos->length > 0 ? trim($nos->item(0)->textContent) : null;
    }
}
