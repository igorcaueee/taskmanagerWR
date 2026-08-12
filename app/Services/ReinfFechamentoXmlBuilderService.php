<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ReinfFechamento;

/**
 * Monta o XML (ainda não assinado) dos eventos de fechamento do EFD-Reinf:
 * R-2099 (tag raiz evtFechaEvPer, fecha a série R-1000/R-2000) e R-4099 (tag
 * raiz evtFech, fecha/reabre a série R-4000).
 *
 * Estrutura conferida contra "Leiautes da EFD-Reinf versão 2.1.2b" (Anexo
 * principal + Anexo II regras de validação, out/2025). A versão do
 * namespace (v2_01_02) segue o padrão observado em erros de validação da
 * série R-1000/R-4000 relatados por terceiros — não há confirmação direta
 * no documento de leiaute para evtFechaEvPer/evtFech especificamente;
 * conferir contra o XSD antes do primeiro envio real.
 */
class ReinfFechamentoXmlBuilderService
{
    private const VERSAO_LEIAUTE = 'v2_01_02';
    private const VERSAO_APLICATIVO = 'TaskManagerWR-1.0';

    /**
     * @param array{nome: string, cpf: string, telefone: string, email: string} $responsavel
     * @param array $dados Para R-2099: ['evtServTm'=>'S'|'N', 'evtServPr'=>..., 'evtAssDespRec'=>...,
     *   'evtAssDespRep'=>..., 'evtComProd'=>..., 'evtCPRB'=>..., 'evtAquis'=>...] (os 7 indicadores).
     *   Para R-4099: ['fechRet' => '0'|'1'] (0 - Fechamento; 1 - Reabertura).
     */
    public function montar(ReinfFechamento $fechamento, Cliente $cliente, array $responsavel, array $dados): string
    {
        return match ($fechamento->tipo_evento) {
            'R-2099' => $this->montarR2099($fechamento, $cliente, $responsavel, $dados),
            'R-4099' => $this->montarR4099($fechamento, $cliente, $responsavel, $dados),
            default => throw new \InvalidArgumentException("Tipo de evento de fechamento não suportado: {$fechamento->tipo_evento}"),
        };
    }

    private const INDICADORES_R2099 = ['evtServTm', 'evtServPr', 'evtAssDespRec', 'evtAssDespRep', 'evtComProd', 'evtCPRB', 'evtAquis'];

    /** @param array{nome: string, cpf: string, telefone: string, email: string} $responsavel */
    private function montarR2099(ReinfFechamento $fechamento, Cliente $cliente, array $responsavel, array $dados): string
    {
        foreach (self::INDICADORES_R2099 as $indicador) {
            if (!in_array($dados[$indicador] ?? null, ['S', 'N'], true)) {
                throw new \InvalidArgumentException("Indicador {$indicador} obrigatório e deve ser 'S' ou 'N'.");
            }
        }

        $namespace = "http://www.reinf.esocial.gov.br/schemas/evtFechamento/" . self::VERSAO_LEIAUTE;

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $reinf = $dom->createElementNS($namespace, 'Reinf');
        $dom->appendChild($reinf);

        $evt = $dom->createElement('evtFechaEvPer');
        $evt->setAttribute('id', $this->montarIdEvento($cliente, $fechamento));
        $reinf->appendChild($evt);

        $evt->appendChild($this->montarIdeEvento($dom, $fechamento));
        $evt->appendChild($this->montarIdeContri($dom, $cliente));
        $evt->appendChild($this->montarIdeRespInf($dom, $responsavel));

        $infoFech = $dom->createElement('infoFech');
        foreach (self::INDICADORES_R2099 as $indicador) {
            $this->addTexto($dom, $infoFech, $indicador, $dados[$indicador]);
        }
        $evt->appendChild($infoFech);

        return $dom->saveXML();
    }

    /** @param array{nome: string, cpf: string, telefone: string, email: string} $responsavel */
    private function montarR4099(ReinfFechamento $fechamento, Cliente $cliente, array $responsavel, array $dados): string
    {
        if (!in_array($dados['fechRet'] ?? null, ['0', '1'], true)) {
            throw new \InvalidArgumentException("Indicador fechRet obrigatório e deve ser '0' (fechamento) ou '1' (reabertura).");
        }

        $namespace = "http://www.reinf.esocial.gov.br/schemas/evtFech/" . self::VERSAO_LEIAUTE;

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $reinf = $dom->createElementNS($namespace, 'Reinf');
        $dom->appendChild($reinf);

        $evt = $dom->createElement('evtFech');
        $evt->setAttribute('id', $this->montarIdEvento($cliente, $fechamento));
        $reinf->appendChild($evt);

        $evt->appendChild($this->montarIdeEvento($dom, $fechamento));
        $evt->appendChild($this->montarIdeContri($dom, $cliente));
        $evt->appendChild($this->montarIdeRespInf($dom, $responsavel));

        $infoFech = $dom->createElement('infoFech');
        $this->addTexto($dom, $infoFech, 'fechRet', $dados['fechRet']);
        $evt->appendChild($infoFech);

        return $dom->saveXML();
    }

    private function montarIdeEvento(\DOMDocument $dom, ReinfFechamento $fechamento): \DOMElement
    {
        $ideEvento = $dom->createElement('ideEvento');
        $this->addTexto($dom, $ideEvento, 'perApur', $fechamento->periodo_apuracao);
        $this->addTexto($dom, $ideEvento, 'tpAmb', $fechamento->ambiente === 'producao' ? '1' : '2');
        $this->addTexto($dom, $ideEvento, 'procEmi', '1'); // 1 - Aplicativo do contribuinte
        $this->addTexto($dom, $ideEvento, 'verProc', self::VERSAO_APLICATIVO);

        return $ideEvento;
    }

    private function montarIdeContri(\DOMDocument $dom, Cliente $cliente): \DOMElement
    {
        $ideContri = $dom->createElement('ideContri');
        $this->addTexto($dom, $ideContri, 'tpInsc', $cliente->tipo === 'PF' ? '2' : '1');
        $this->addTexto($dom, $ideContri, 'nrInsc', preg_replace('/\D/', '', $cliente->cpfcnpj));

        return $ideContri;
    }

    /** @param array{nome: string, cpf: string, telefone: string, email: string} $responsavel */
    private function montarIdeRespInf(\DOMDocument $dom, array $responsavel): \DOMElement
    {
        $ideRespInf = $dom->createElement('ideRespInf');
        $this->addTexto($dom, $ideRespInf, 'nmResp', $responsavel['nome']);
        $this->addTexto($dom, $ideRespInf, 'cpfResp', preg_replace('/\D/', '', $responsavel['cpf']));
        $this->addTexto($dom, $ideRespInf, 'telefone', preg_replace('/\D/', '', $responsavel['telefone']));
        $this->addTexto($dom, $ideRespInf, 'email', $responsavel['email']);

        return $ideRespInf;
    }

    /**
     * Formato do Id do evento (manual, seção "Identificação do evento"):
     * "ID" + T(1) + CNPJ/CPF com zeros à direita (14) + AAAAMMDD(8) + HHMMSS(6) + QQQQQ(5) = 36 posições.
     */
    private function montarIdEvento(Cliente $cliente, ReinfFechamento $fechamento): string
    {
        $tipoInscricao = $cliente->tipo === 'PF' ? '2' : '1';
        $inscricao = str_pad(preg_replace('/\D/', '', $cliente->cpfcnpj), 14, '0', STR_PAD_RIGHT);
        $agora = now();
        $sequencia = str_pad((string) $fechamento->id, 5, '0', STR_PAD_LEFT);

        return 'ID' . $tipoInscricao . $inscricao . $agora->format('Ymd') . $agora->format('His') . $sequencia;
    }

    private function addTexto(\DOMDocument $dom, \DOMElement $pai, string $nome, string $valor): void
    {
        $pai->appendChild($dom->createElement($nome, htmlspecialchars($valor, ENT_XML1)));
    }
}
