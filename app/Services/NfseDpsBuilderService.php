<?php

namespace App\Services;

use App\Models\ClienteDadosFiscaisNfse;
use App\Models\NfseEmissao;
use Carbon\Carbon;

/**
 * Monta o XML da DPS (Declaração de Prestação de Serviços) a ser assinada e
 * enviada ao Sistema Nacional NFS-e (POST /nfse), conforme o leiaute oficial
 * (Anexo I - LeiautesRN_DPS_NFSe-SNNFSe, elemento NFSe/infNFSe/DPS/infDPS).
 *
 * Escopo V1: apenas ISSQN clássico. O bloco IBSCBS (Reforma Tributária) e o
 * detalhamento do "Total de Tributos" (Lei 12.741/2012) ficam fora — usamos
 * indTotTrib = 0 (contribuinte opta por não informar os valores estimados).
 *
 * ATENÇÃO: namespace do XSD, formato exato do payload de envio (POST /nfse) e
 * o código ISO/BACEN de país usado (cPaisPrestacao) ainda precisam ser
 * confirmados contra o Swagger de homologação antes de ir para produção —
 * ver "Itens em aberto" no plano de implementação.
 */
class NfseDpsBuilderService
{
    private const NAMESPACE_DPS = 'http://www.sped.fazenda.gov.br/nfse';
    private const VERSAO_DPS = '1.00';
    private const CODIGO_PAIS_BRASIL = '1058';

    public function montar(NfseEmissao $emissao, ClienteDadosFiscaisNfse $dadosFiscaisPrestador): string
    {
        $cliente = $emissao->cliente;

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $dps = $dom->createElementNS(self::NAMESPACE_DPS, 'DPS');
        $dps->setAttribute('versao', self::VERSAO_DPS);
        $dom->appendChild($dps);

        $infDPS = $dom->createElement('infDPS');
        $infDPS->setAttribute('id', $this->montarIdDps($emissao, $dadosFiscaisPrestador, $cliente));
        $dps->appendChild($infDPS);

        $ambiente = $emissao->ambiente === 'producao' ? '1' : '2';

        $this->addTexto($dom, $infDPS, 'tpAmb', $ambiente);
        $this->addTexto($dom, $infDPS, 'dhEmi', Carbon::now()->toIso8601String());
        $this->addTexto($dom, $infDPS, 'verAplic', 'TaskManagerWR-1.0');
        $this->addTexto($dom, $infDPS, 'serie', $emissao->serie);
        $this->addTexto($dom, $infDPS, 'nDPS', (string) $emissao->numero);
        $this->addTexto($dom, $infDPS, 'dCompet', $emissao->dcompet->format('Y-m-d'));
        $this->addTexto($dom, $infDPS, 'tpEmit', '1'); // 1 - Prestador
        $this->addTexto($dom, $infDPS, 'cLocEmi', $dadosFiscaisPrestador->codigo_municipio_ibge);

        if ($emissao->chave_nfse_substituida) {
            $subst = $dom->createElement('subst');
            $this->addTexto($dom, $subst, 'chSubstda', $emissao->chave_nfse_substituida);
            $this->addTexto($dom, $subst, 'cMotivo', '99');
            $this->addTexto($dom, $subst, 'xMotivo', 'Substituição de NFS-e emitida via TaskManagerWR');
            $infDPS->appendChild($subst);
        }

        $infDPS->appendChild($this->montarPrestador($dom, $cliente, $dadosFiscaisPrestador));
        $infDPS->appendChild($this->montarTomador($dom, $emissao));
        $infDPS->appendChild($this->montarServico($dom, $emissao));
        $infDPS->appendChild($this->montarValores($dom, $emissao));

        return $dom->saveXML();
    }

    private function montarIdDps(NfseEmissao $emissao, ClienteDadosFiscaisNfse $dadosFiscaisPrestador, $cliente): string
    {
        $tipoInscricao = $cliente->tipo === 'PF' ? '2' : '1'; // 1-CNPJ, 2-CPF
        $inscricaoFederal = str_pad(preg_replace('/\D/', '', $cliente->cpfcnpj), 14, '0', STR_PAD_LEFT);
        $serie = str_pad($emissao->serie, 5, '0', STR_PAD_LEFT);
        $numero = str_pad((string) $emissao->numero, 15, '0', STR_PAD_LEFT);

        return 'DPS'
            . $dadosFiscaisPrestador->codigo_municipio_ibge
            . $tipoInscricao
            . $inscricaoFederal
            . $serie
            . $numero;
    }

    private function montarPrestador(\DOMDocument $dom, $cliente, ClienteDadosFiscaisNfse $dadosFiscaisPrestador): \DOMElement
    {
        $prest = $dom->createElement('prest');

        $documento = preg_replace('/\D/', '', $cliente->cpfcnpj);
        if ($cliente->tipo === 'PF') {
            $this->addTexto($dom, $prest, 'CPF', $documento);
        } else {
            $this->addTexto($dom, $prest, 'CNPJ', $documento);
        }

        if (filled($dadosFiscaisPrestador->inscricao_municipal)) {
            $this->addTexto($dom, $prest, 'IM', $dadosFiscaisPrestador->inscricao_municipal);
        }

        $this->addTexto($dom, $prest, 'xNome', $cliente->nome);

        $end = $dom->createElement('end');
        $endNac = $dom->createElement('endNac');
        $this->addTexto($dom, $endNac, 'cMun', $dadosFiscaisPrestador->codigo_municipio_ibge);
        $this->addTexto($dom, $endNac, 'CEP', preg_replace('/\D/', '', $dadosFiscaisPrestador->cep));
        $end->appendChild($endNac);
        $this->addTexto($dom, $end, 'xLgr', $dadosFiscaisPrestador->logradouro);
        $this->addTexto($dom, $end, 'nro', $dadosFiscaisPrestador->numero);
        if (filled($dadosFiscaisPrestador->complemento)) {
            $this->addTexto($dom, $end, 'xCpl', $dadosFiscaisPrestador->complemento);
        }
        $this->addTexto($dom, $end, 'xBairro', $dadosFiscaisPrestador->bairro);
        $prest->appendChild($end);

        $regTrib = $dom->createElement('regTrib');
        $this->addTexto($dom, $regTrib, 'opSimpNac', $this->mapearOpSimpNac($cliente->regime_tributario));
        $this->addTexto($dom, $regTrib, 'regEspTrib', '0'); // 0 - Nenhum
        $prest->appendChild($regTrib);

        return $prest;
    }

    private function montarTomador(\DOMDocument $dom, NfseEmissao $emissao): \DOMElement
    {
        $toma = $dom->createElement('toma');

        $documento = preg_replace('/\D/', '', $emissao->tomador_cpf_cnpj);
        if ($emissao->tomador_tipo_doc === 'CPF') {
            $this->addTexto($dom, $toma, 'CPF', $documento);
        } else {
            $this->addTexto($dom, $toma, 'CNPJ', $documento);
        }

        $this->addTexto($dom, $toma, 'xNome', $emissao->tomador_nome);

        if (filled($emissao->tomador_cep) && filled($emissao->tomador_logradouro)) {
            $end = $dom->createElement('end');
            $endNac = $dom->createElement('endNac');
            $this->addTexto($dom, $endNac, 'cMun', $emissao->tomador_codigo_municipio_ibge);
            $this->addTexto($dom, $endNac, 'CEP', preg_replace('/\D/', '', $emissao->tomador_cep));
            $end->appendChild($endNac);
            $this->addTexto($dom, $end, 'xLgr', $emissao->tomador_logradouro);
            $this->addTexto($dom, $end, 'nro', $emissao->tomador_numero);
            if (filled($emissao->tomador_complemento)) {
                $this->addTexto($dom, $end, 'xCpl', $emissao->tomador_complemento);
            }
            $this->addTexto($dom, $end, 'xBairro', $emissao->tomador_bairro);
            $toma->appendChild($end);
        }

        if (filled($emissao->tomador_email)) {
            $this->addTexto($dom, $toma, 'email', $emissao->tomador_email);
        }

        return $toma;
    }

    private function montarServico(\DOMDocument $dom, NfseEmissao $emissao): \DOMElement
    {
        $serv = $dom->createElement('serv');

        $locPrest = $dom->createElement('locPrest');
        $this->addTexto($dom, $locPrest, 'cLocPrestacao', $emissao->codigo_municipio_prestacao);
        $this->addTexto($dom, $locPrest, 'cPaisPrestacao', self::CODIGO_PAIS_BRASIL);
        $serv->appendChild($locPrest);

        $cServ = $dom->createElement('cServ');
        $this->addTexto($dom, $cServ, 'cTribNac', $emissao->codigo_tributacao_nacional);
        $this->addTexto($dom, $cServ, 'xDescServ', $emissao->descricao_servico);
        $serv->appendChild($cServ);

        return $serv;
    }

    private function montarValores(\DOMDocument $dom, NfseEmissao $emissao): \DOMElement
    {
        $valores = $dom->createElement('valores');

        $vServPrest = $dom->createElement('vServPrest');
        $this->addTexto($dom, $vServPrest, 'vServ', number_format((float) $emissao->valor_servico, 2, '.', ''));
        $valores->appendChild($vServPrest);

        if ($emissao->desconto_incondicional > 0) {
            $vDesc = $dom->createElement('vDescCondIncond');
            $this->addTexto($dom, $vDesc, 'vDescIncond', number_format((float) $emissao->desconto_incondicional, 2, '.', ''));
            $valores->appendChild($vDesc);
        }

        $trib = $dom->createElement('trib');
        $tribMun = $dom->createElement('tribMun');
        $this->addTexto($dom, $tribMun, 'tribISSQN', (string) $emissao->trib_issqn);
        $this->addTexto($dom, $tribMun, 'tpRetISSQN', $emissao->iss_retido ? '2' : '1');
        // Alíquota só faz sentido quando a operação é tributável (1) — imunidade,
        // exportação e não incidência não têm ISSQN a recolher.
        if ($emissao->trib_issqn === 1 && $emissao->aliquota !== null) {
            $this->addTexto($dom, $tribMun, 'pAliq', number_format((float) $emissao->aliquota, 2, '.', ''));
        }
        $trib->appendChild($tribMun);

        $totTrib = $dom->createElement('totTrib');
        $this->addTexto($dom, $totTrib, 'indTotTrib', '0'); // opta por não informar tributos estimados
        $trib->appendChild($totTrib);

        $valores->appendChild($trib);

        return $valores;
    }

    private function mapearOpSimpNac(?string $regimeTributario): string
    {
        return match ($regimeTributario) {
            'MEI' => '2',
            'Simples Nacional' => '3',
            default => '1', // Lucro Presumido / Lucro Real / não optante
        };
    }

    private function addTexto(\DOMDocument $dom, \DOMElement $pai, string $nome, ?string $valor): void
    {
        $pai->appendChild($dom->createElement($nome, htmlspecialchars((string) $valor, ENT_XML1)));
    }
}
