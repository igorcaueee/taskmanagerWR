<?php

namespace App\Services;

use App\Models\DocumentoFiscal;

/**
 * Extrai de uma NF-e/NFC-e (XML completo, procNFe/NFe) uma linha por item
 * (produto), no formato usado pelo relatório fiscal de notas (Excel) que
 * replica as colunas do relatório que a contabilidade já usa hoje num
 * sistema pago (ver App\Exports\NfeRelatorioExport).
 *
 * paraRelatorio() cobre NF-e (mod 55) e NFC-e (mod 65), uma linha por item.
 * paraRelatorioCte() cobre CT-e (mod 57) à parte — não tem itens de produto,
 * então gera uma única linha por documento com os tributos a nível de
 * cabeçalho (ver método para detalhes).
 *
 * Quando o XML salvo é só o resumo da distribuição (resNFe, sem <det>) ou o
 * documento ainda não tem xml_content, a nota ainda entra no relatório com
 * os poucos campos de cabeçalho disponíveis (vindos da própria tabela
 * `documentos_fiscais`) e os campos de item/impostos em branco.
 */
class NfeXmlParser
{
    private const LOCAL_OPERACAO_LABEL = [
        '1' => '1-Operação interna',
        '2' => '2-Operação interestadual',
        '3' => '3-Operação com exterior',
    ];

    private const FINALIDADE_LABEL = [
        '1' => '1-Normal',
        '2' => '2-Complementar',
        '3' => '3-Ajuste',
        '4' => '4-Devolução/Retorno',
    ];

    private const CONS_FINAL_LABEL = [
        '0' => '0-Não',
        '1' => '1-Consumidor final',
    ];

    private const TIPO_OPER_LABEL = [
        '0' => 'Entrada',
        '1' => 'Saída',
    ];

    private const REG_TRIB_LABEL = [
        '1' => '1-Simples Nacional',
        '2' => '2-Simples Nacional - excesso de sublimite de receita bruta',
        '3' => '3-Regime Normal',
    ];

    private const SITUACAO_LABEL = [
        '1' => 'Autorizada',
        '2' => 'Cancelada',
        '3' => 'Denegada',
    ];

    /**
     * Extrai os metadados de cabeçalho de um XML de NF-e/NFC-e/CT-e completo
     * (nfeProc/cteProc ou o NFe/CTe assinado sem o wrapper de protocolo),
     * no mesmo formato de array usado por NfeService/CteDistribuicaoDFeService
     * antes de persistir em `documentos_fiscais` — usado pelo upload manual de
     * XMLs no Cofre Fiscal (CofreFiscalController::uploadZip), onde o XML vem
     * solto num .zip em vez de uma resposta da Sefaz já classificada.
     *
     * Retorna null quando o XML não é um NF-e/CT-e reconhecível (falta
     * infNFe/infCte ou não dá pra extrair uma chave de acesso de 44 dígitos).
     *
     * @return array<string, mixed>|null
     */
    public static function extrairMetadados(string $xml): ?array
    {
        libxml_use_internal_errors(true);

        try {
            $obj = new \SimpleXMLElement($xml);
        } catch (\Throwable) {
            return null;
        }

        $get = fn (string $tag) => trim((string) ($obj->xpath("//*[local-name()='{$tag}']")[0] ?? ''));

        // O emitente pode ser CPF (produtor rural): nesse caso <emit> não tem <CNPJ>, e um
        // xpath global "//CNPJ" acaba pegando o <dest> (o nosso cliente), invertendo a
        // direção entrada/saída (ver DocumentoFiscal::direcao). Os dados do emitente são
        // lidos escopados ao nó dele — <emit> no XML completo, <resNFe>/<resCTe> no resumo.
        $emitNode = $obj->xpath("//*[local-name()='emit']")[0]
            ?? $obj->xpath("//*[local-name()='resNFe']")[0]
            ?? $obj->xpath("//*[local-name()='resCTe']")[0]
            ?? null;
        $getEmit = function (string $tag) use ($emitNode, $get) {
            if ($emitNode === null) {
                return $get($tag);
            }

            return trim((string) ($emitNode->xpath(".//*[local-name()='{$tag}']")[0] ?? ''));
        };

        $isCte = count($obj->xpath("//*[local-name()='infCte']")) > 0;
        $isNfe = count($obj->xpath("//*[local-name()='infNFe']")) > 0;

        if (!$isCte && !$isNfe) {
            return null;
        }

        $chave = $isCte ? $get('chCTe') : $get('chNFe');

        // Documento assinado mas ainda sem protocolo de autorização (sem chNFe/chCTe em
        // infProt): a chave também está embutida no atributo Id de infNFe/infCte
        // (formato "NFe44dígitos"/"CTe44dígitos").
        if ($chave === '') {
            $idNode = $obj->xpath("//*[local-name()='" . ($isCte ? 'infCte' : 'infNFe') . "']/@Id")[0] ?? null;
            $chave = $idNode ? preg_replace('/\D/', '', (string) $idNode) : '';
        }

        if (strlen($chave) !== 44) {
            return null;
        }

        // Modelo embutido nas posições 21-22 da chave (55=NF-e, 65=NFC-e) — mais confiável
        // que a tag <mod>, que pode não existir dependendo do formato do XML (mesma
        // convenção usada em NfeIntegracaoRsService::normalizarDocumento).
        $modelo = substr($chave, 20, 2);
        $tipoDoc = $isCte ? 'cte' : ($modelo === '65' ? 'nfce' : 'nfe');

        $numero = $get('nNF') ?: $get('nCT');
        if (!$numero) {
            $numero = (string) (int) substr($chave, 25, 9);
        }

        $tpNfStr = $get('tpNF');
        $crtStr = $getEmit('CRT');
        $emitenteNome = trim(mb_convert_encoding($getEmit('xNome'), 'UTF-8', 'UTF-8'));

        // Mesmo raciocínio do $emitNode acima, mas pro lado do destinatário — usado pra
        // validar que o XML pertence ao cliente selecionado no upload do Cofre Fiscal
        // (o cliente pode ser tanto o emitente, numa saída, quanto o destinatário, numa
        // entrada).
        $destNode = $obj->xpath("//*[local-name()='dest']")[0] ?? null;
        $destinatarioDoc = $destNode !== null
            ? (trim((string) ($destNode->xpath(".//*[local-name()='CNPJ']")[0] ?? '')) ?: trim((string) ($destNode->xpath(".//*[local-name()='CPF']")[0] ?? '')))
            : '';

        return [
            'tipo'             => $tipoDoc,
            'chaveAcesso'      => $chave,
            'numero'           => $numero,
            'dataEmissao'      => $get('dhEmi'),
            'dataSaidaEntrada' => $get('dhSaiEnt') ?: $get('dSaiEnt'),
            'emitenteNome'     => $emitenteNome !== '' ? $emitenteNome : null,
            'emitenteDoc'      => $getEmit('CNPJ') ?: $getEmit('CPF'),
            'destinatarioDoc'  => $destinatarioDoc ?: null,
            'valor'            => $get('vNF') ?: $get('vCT'),
            'situacao'         => $get('cSitDFe') ?: $get('cSitCTe') ?: null,
            'tpNf'             => $tpNfStr !== '' ? (int) $tpNfStr : null,
            'emitenteCrt'      => $crtStr !== '' ? (int) $crtStr : null,
            'xmlContent'       => $xml,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function paraRelatorio(DocumentoFiscal $documento): array
    {
        $xml = $documento->xml_content;

        if (empty($xml)) {
            return [self::linhaSemDetalhes($documento)];
        }

        libxml_use_internal_errors(true);

        try {
            $raiz = new \SimpleXMLElement($xml);
        } catch (\Throwable) {
            return [self::linhaSemDetalhes($documento)];
        }

        $infNFe = self::descendente($raiz, 'infNFe');

        if (! $infNFe) {
            // resNFe (resumo) ou XML inesperado — sem itens disponíveis
            return [self::linhaSemDetalhes($documento)];
        }

        $cabecalho = self::extrairCabecalho($documento, $infNFe);
        $dets = self::filhos($infNFe, 'det');

        if (empty($dets)) {
            return [$cabecalho];
        }

        return array_map(fn (\SimpleXMLElement $det) => array_merge($cabecalho, self::extrairItem($det)), $dets);
    }

    /**
     * Extrai de um CT-e (XML completo, procCTe/CTe) uma única linha (CT-e não
     * tem itens de produto — os tributos ficam a nível de documento, dentro
     * de <imp>, não por <det> como na NF-e).
     */
    public static function paraRelatorioCte(DocumentoFiscal $documento): array
    {
        $xml = $documento->xml_content;

        if (empty($xml)) {
            return self::linhaCteSemDetalhes($documento);
        }

        libxml_use_internal_errors(true);

        try {
            $raiz = new \SimpleXMLElement($xml);
        } catch (\Throwable) {
            return self::linhaCteSemDetalhes($documento);
        }

        $infCte = self::descendente($raiz, 'infCte');

        if (! $infCte) {
            return self::linhaCteSemDetalhes($documento);
        }

        $ide = self::filho($infCte, 'ide');
        $emit = self::filho($infCte, 'emit');
        $rem = self::filho($infCte, 'rem');
        $dest = self::filho($infCte, 'dest');
        $enderEmit = self::filho($emit, 'enderEmit');
        $enderDest = self::filho($dest, 'enderDest');
        $vPrest = self::filho($infCte, 'vPrest');
        $imp = self::filho($infCte, 'imp');
        $icmsContainer = self::filho($imp, 'ICMS');
        $icms = self::primeiroFilho($icmsContainer);

        // Mesmo grupo da reforma tributária usado na NF-e (ver extrairItem) — a nível de
        // documento aqui, não por item, já que CT-e não tem <det>. Ainda sem exemplos reais
        // de XML de CT-e com esse grupo preenchido (transição da reforma em andamento); os
        // campos ficam em branco até o layout entrar em produção.
        $ibsCbs = self::filho($imp, 'IBSCBS');
        $gIbsCbs = self::filho($ibsCbs, 'gIBSCBS');
        $gIbsUF = self::filho($gIbsCbs, 'gIBSUF');
        $gIbsMun = self::filho($gIbsCbs, 'gIBSMun');
        $gCbs = self::filho($gIbsCbs, 'gCBS');

        [$dataEmis, $horaEmis] = self::dataHora(self::txt($ide, 'dhEmi'));

        $chaveAcesso = preg_replace('/^CTe/', '', trim((string) $infCte['Id'])) ?: $documento->chave_acesso;

        return [[
            'Chave_Acesso' => $chaveAcesso ?: $documento->chave_acesso,
            'Mod_Doc' => self::txt($ide, 'mod'),
            'Serie' => self::txt($ide, 'serie'),
            'Nº_Doc' => self::txt($ide, 'nCT'),
            'Data_Emis' => $dataEmis,
            'Hora_Emis' => $horaEmis,
            'Nat_Oper' => self::txt($ide, 'natOp'),
            'CFOP' => self::txt($ide, 'CFOP'),

            'CNPJ_Emit' => self::txt($emit, 'CNPJ') ?? self::txt($emit, 'CPF'),
            'Razao_Social_Emit' => self::txt($emit, 'xNome'),
            'IE_Emit' => self::txt($emit, 'IE'),
            'Munic_Emit' => self::txt($enderEmit, 'xMun'),
            'UF_Emit' => self::txt($enderEmit, 'UF'),

            'CNPJ/CPF_Rem' => self::txt($rem, 'CNPJ') ?? self::txt($rem, 'CPF'),
            'Razao/Nome_Rem' => self::txt($rem, 'xNome'),

            'CNPJ/CPF_Dest' => self::txt($dest, 'CNPJ') ?? self::txt($dest, 'CPF'),
            'Razao/Nome_Dest' => self::txt($dest, 'xNome'),
            'Munic_Dest' => self::txt($enderDest, 'xMun'),
            'UF_Dest' => self::txt($enderDest, 'UF'),

            'Munic_Ini' => self::txt($ide, 'xMunIni'),
            'UF_Ini' => self::txt($ide, 'UFIni'),
            'Munic_Fim' => self::txt($ide, 'xMunFim'),
            'UF_Fim' => self::txt($ide, 'UFFim'),

            'V_Prest' => self::num($vPrest, 'vTPrest'),
            'V_Receber' => self::num($vPrest, 'vRec'),

            'CST_ICMS' => self::txt($icms, 'CST'),
            'V_BC_ICMS' => self::num($icms, 'vBC'),
            '%_ICMS' => self::num($icms, 'pICMS'),
            'V_ICMS' => self::num($icms, 'vICMS'),

            'CST_IBS_CBS' => self::txt($ibsCbs, 'CST'),
            'Cod_Class_Trib_IBS_CBS' => self::txt($ibsCbs, 'cClassTrib'),
            'V_BC_IBS_CBS' => self::num($gIbsCbs, 'vBC'),
            '%_IBS_UF' => self::num($gIbsUF, 'pIBSUF'),
            'V_IBS_UF' => self::num($gIbsUF, 'vIBSUF'),
            '%_IBS_Mun' => self::num($gIbsMun, 'pIBSMun'),
            'V_IBS_Mun' => self::num($gIbsMun, 'vIBSMun'),
            '%_CBS' => self::num($gCbs, 'pCBS'),
            'V_CBS' => self::num($gCbs, 'vCBS'),

            'Status_Doc' => self::statusDoc($documento),
        ]];
    }

    private static function linhaCteSemDetalhes(DocumentoFiscal $documento): array
    {
        return [[
            'Chave_Acesso' => $documento->chave_acesso,
            'Mod_Doc' => '57',
            'Nº_Doc' => $documento->numero,
            'Data_Emis' => $documento->data_emissao?->format('d/m/Y'),
            'CNPJ_Emit' => $documento->emitente_doc,
            'Razao_Social_Emit' => $documento->emitente_nome,
            'V_Prest' => $documento->valor,
            'Status_Doc' => self::statusDoc($documento),
        ]];
    }

    // ─── Cabeçalho (dados que se repetem em todos os itens da nota) ───────────

    private static function extrairCabecalho(DocumentoFiscal $documento, \SimpleXMLElement $infNFe): array
    {
        $ide = self::filho($infNFe, 'ide');
        $emit = self::filho($infNFe, 'emit');
        $dest = self::filho($infNFe, 'dest');
        $transp = self::filho($infNFe, 'transp');
        $infAdic = self::filho($infNFe, 'infAdic');

        $enderEmit = self::filho($emit, 'enderEmit');
        $enderDest = self::filho($dest, 'enderDest');
        $transporta = self::filho($transp, 'transporta');
        $nfRef = self::filho($ide, 'NFref');
        $refNF = $nfRef ? self::filho($nfRef, 'refNF') : null;

        [$dataEmis, $horaEmis] = self::dataHora(self::txt($ide, 'dhEmi'));
        [$dataSaida, $horaSaida] = self::dataHora(self::txt($ide, 'dhSaiEnt'));

        $tpNF = self::txt($ide, 'tpNF');
        $idDest = self::txt($ide, 'idDest');
        $finNFe = self::txt($ide, 'finNFe');
        $indFinal = self::txt($ide, 'indFinal');
        $crt = self::txt($emit, 'CRT');

        $chaveAcesso = preg_replace('/^NFe/', '', trim((string) $infNFe['Id'])) ?: $documento->chave_acesso;

        return [
            'Chave_Acesso' => $chaveAcesso ?: $documento->chave_acesso,
            'Mod_Doc' => self::txt($ide, 'mod'),
            'Serie' => self::txt($ide, 'serie'),
            'Nº_Doc' => self::txt($ide, 'nNF'),
            'Data_Emis' => $dataEmis,
            'Hora_Emis' => $horaEmis,
            'Data_Saida' => $dataSaida,
            'Hora_Saida' => $horaSaida,
            'Local_da_Operação' => self::LOCAL_OPERACAO_LABEL[$idDest] ?? $idDest,
            'Finalidade' => self::FINALIDADE_LABEL[$finNFe] ?? $finNFe,
            'Indicador_Operação' => $tpNF,
            'Cons_Final' => self::CONS_FINAL_LABEL[$indFinal] ?? $indFinal,
            'Tipo_Oper' => self::TIPO_OPER_LABEL[$tpNF] ?? $tpNF,
            'Nat_Oper' => self::txt($ide, 'natOp'),

            'CNPJ_Emit' => self::txt($emit, 'CNPJ') ?? self::txt($emit, 'CPF'),
            'Razao_Social_Emit' => self::txt($emit, 'xNome'),
            'Bairro_Emit' => self::txt($enderEmit, 'xBairro'),
            'IE_Emit' => self::txt($emit, 'IE'),
            'Munic_Emit' => self::txt($enderEmit, 'xMun'),
            'UF_Emit' => self::txt($enderEmit, 'UF'),
            'Reg_Trib_Emit' => self::REG_TRIB_LABEL[$crt] ?? $crt,

            'CNPJ/CPF_Dest' => self::txt($dest, 'CNPJ') ?? self::txt($dest, 'CPF') ?? self::txt($dest, 'idEstrangeiro'),
            'Razao/Nome_Dest' => self::txt($dest, 'xNome'),
            'Bairro_Dest' => self::txt($enderDest, 'xBairro'),
            'IE_Dest' => self::txt($dest, 'IE'),
            'Munic_Dest' => self::txt($enderDest, 'xMun'),
            'UF_Dest' => self::txt($enderDest, 'UF'),

            'Mod_Frete' => self::txt($transp, 'modFrete'),
            'CNPJ_Transp' => self::txt($transporta, 'CNPJ') ?? self::txt($transporta, 'CPF'),
            'Razao/Nome_Transp' => self::txt($transporta, 'xNome'),
            'IE_Transp' => self::txt($transporta, 'IE'),
            'Munic_Transp' => self::txt($transporta, 'xMun'),
            'UF_Transp' => self::txt($transporta, 'UF'),

            'Dados_Adicionais_Interesse_Fisco' => self::txt($infAdic, 'infAdFisco'),
            'Dados_Adicionais_Interesse_Contribuinte' => self::txt($infAdic, 'infCpl'),

            'Chave_NFe_Devolvida' => $nfRef ? self::txt($nfRef, 'refNFe') : null,
            'Per_NFe_Devolv' => $refNF ? self::txt($refNF, 'AAMM') : null,
            'Nº_NFe_Devolv' => $refNF ? self::txt($refNF, 'nNF') : null,

            'Status_Doc' => self::statusDoc($documento),
        ];
    }

    // ─── Item (um <det> = um produto) ──────────────────────────────────────

    private static function extrairItem(\SimpleXMLElement $det): array
    {
        $prod = self::filho($det, 'prod');
        $imposto = self::filho($det, 'imposto');
        $impDevol = self::filho($det, 'impostoDevol');

        $icmsContainer = self::filho($imposto, 'ICMS');
        $icms = self::primeiroFilho($icmsContainer);
        $ipiContainer = self::filho($imposto, 'IPI');
        $ipiTrib = $ipiContainer ? (self::filho($ipiContainer, 'IPITrib') ?? self::filho($ipiContainer, 'IPINT')) : null;
        $pisContainer = self::filho($imposto, 'PIS');
        $pis = self::primeiroFilho($pisContainer);
        $cofinsContainer = self::filho($imposto, 'COFINS');
        $cofins = self::primeiroFilho($cofinsContainer);
        $issqn = self::filho($imposto, 'ISSQN');
        $icmsUFDest = self::filho($imposto, 'ICMSUFDest');
        $ipiDevol = self::filho($impDevol, 'IPI');

        // Reforma Tributária (NT 2023.004 e seguintes) — grupo novo dentro de <imposto>,
        // ao lado de ICMS/IPI/PIS/COFINS. Layout ainda em fase de transição em 2026: nas
        // notas emitidas antes da obrigatoriedade, esse grupo simplesmente não existe no
        // XML e todos os campos abaixo ficam em branco (comportamento esperado, não é bug).
        $ibsCbs = self::filho($imposto, 'IBSCBS');
        $gIbsCbs = self::filho($ibsCbs, 'gIBSCBS');
        $gIbsUF = self::filho($gIbsCbs, 'gIBSUF');
        $gIbsMun = self::filho($gIbsCbs, 'gIBSMun');
        $gCbs = self::filho($gIbsCbs, 'gCBS');
        $gIS = self::filho($ibsCbs, 'gIS');

        return [
            'Item' => (string) ($det['nItem'] ?? ''),
            'Cod_Prod' => self::txt($prod, 'cProd'),
            'Descr_Prod' => self::txt($prod, 'xProd'),
            'Cod_EAN' => self::txt($prod, 'cEAN'),
            'NCM' => self::txt($prod, 'NCM'),
            'CEST' => self::txt($prod, 'CEST'),
            'EXTIPI' => self::txt($prod, 'EXTIPI'),
            'CFOP' => self::txt($prod, 'CFOP'),
            'Und_Com' => self::txt($prod, 'uCom'),
            'Qtd_Com' => self::num($prod, 'qCom'),
            'V_Unit_Com' => self::num($prod, 'vUnCom'),
            'V_Prod' => self::num($prod, 'vProd'),
            'Cod_EAN_Trib' => self::txt($prod, 'cEANTrib'),
            'Und_Trib' => self::txt($prod, 'uTrib'),
            'Qtd_Trib' => self::num($prod, 'qTrib'),
            'V_Unit_Trib' => self::num($prod, 'vUnTrib'),
            'V_Desconto' => self::num($prod, 'vDesc'),
            'V_Frete' => self::num($prod, 'vFrete'),
            'V_Seg' => self::num($prod, 'vSeg'),
            'V_Outros' => self::num($prod, 'vOutro'),
            'Ind_Tot' => self::txt($prod, 'indTot'),
            'Ficha_Importação' => self::txt($prod, 'nFCI'),
            'V_Tot_Trib' => self::num($imposto, 'vTotTrib'),

            'Orig' => self::txt($icms, 'orig'),
            'CST/CSON' => self::txt($icms, 'CST') ?? self::txt($icms, 'CSOSN'),
            'Mod_BC_ICMS' => self::txt($icms, 'modBC'),
            '%_Red_BC_ICMS' => self::num($icms, 'pRedBC'),
            'V_BC_ICMS' => self::num($icms, 'vBC'),
            '%_ICMS' => self::num($icms, 'pICMS'),
            '%_ICMS_SN' => self::num($icms, 'pCredSN'),
            'V_ICMS_Op' => self::num($icms, 'vICMSOp'),
            'V_ICMS_Dif' => self::num($icms, 'vICMSDif'),
            'V_ICMS' => self::num($icms, 'vICMS'),
            'V_ICMS_SN' => self::num($icms, 'vCredICMSSN'),
            'V_BC_FCP' => self::num($icms, 'vBCFCP'),
            '%_FCP' => self::num($icms, 'pFCP'),
            'V_FCP' => self::num($icms, 'vFCP'),
            'V_ICMS_Deson' => self::num($icms, 'vICMSDeson'),
            'Mot_ICMS_Deson' => self::txt($icms, 'motDesICMS'),
            'Mod_BC_ST' => self::txt($icms, 'modBCST'),
            '%_MVA_ST' => self::num($icms, 'pMVAST'),
            '%_Red_BC_ST' => self::num($icms, 'pRedBCST'),
            'V_BC_ST' => self::num($icms, 'vBCST'),
            '%_ICMS_ST' => self::num($icms, 'pICMSST'),
            'V_ICMS_ST' => self::num($icms, 'vICMSST'),
            'V_BC_FCP_ST' => self::num($icms, 'vBCFCPST'),
            '%_FCP_ST' => self::num($icms, 'pFCPST'),
            'V_FCP_ST' => self::num($icms, 'vFCPST'),

            // Retenção de ICMS monofásico (combustíveis) referente a operação anterior —
            // grupo recente do schema (NT 2023.003), pouco usado fora do setor de combustíveis.
            'Qtd_Trib_Ret_Ant' => self::num($icms, 'qBCMonoRet'),
            '%_Rem_Ret_Ant' => self::num($icms, 'adRemICMSRet'),
            'V_ICMS_Ret_Ant_Monof' => self::num($icms, 'vICMSMonoRet'),

            'V_BC_ST_Ret' => self::num($icms, 'vBCSTRet'),
            '%_ST_Ret' => self::num($icms, 'pST'),
            'V_ICMS_ST_Ret' => self::num($icms, 'vICMSSTRet'),
            'V_BC_ST_FCP_Ret' => self::num($icms, 'vBCFCPSTRet'),
            '%_FCP_ST_Ret' => self::num($icms, 'pFCPSTRet'),
            'V_FCP_ST_Ret' => self::num($icms, 'vFCPSTRet'),

            'V_BC_ICMS_UF_Dest' => self::num($icmsUFDest, 'vBCUFDest'),
            '%_ICMS_UF_Dest' => self::num($icmsUFDest, 'pICMSUFDest'),
            '%_ICMS_UF_Inter' => self::num($icmsUFDest, 'pICMSInter'),
            '%_ICMS_UF_InterPart' => self::num($icmsUFDest, 'pICMSInterPart'),
            'V_ICMS_UF_Dest' => self::num($icmsUFDest, 'vICMSUFDest'),
            'V_ICMS_UF_Remet' => self::num($icmsUFDest, 'vICMSUFRemet'),
            'V_BC_FCP_UF_Dest' => self::num($icmsUFDest, 'vBCFCPUFDest'),
            '%_FCP_UF_Dest' => self::num($icmsUFDest, 'pFCPUFDest'),
            'V_FCP_UF_Dest' => self::num($icmsUFDest, 'vFCPUFDest'),

            'Cod_Enq_IPI' => self::txt($ipiContainer, 'clEnq'),
            'CST_IPI' => self::txt($ipiTrib, 'CST'),
            'V_BC_IPI' => self::num($ipiTrib, 'vBC'),
            '%_IPI' => self::num($ipiTrib, 'pIPI'),
            'V_IPI' => self::num($ipiTrib, 'vIPI'),
            'V_IPI_Devol' => self::num($ipiDevol, 'vIPIDevol'),

            'CST_PIS' => self::txt($pis, 'CST'),
            'V_BC_PIS' => self::num($pis, 'vBC'),
            '%_PIS' => self::num($pis, 'pPIS'),
            'V_PIS' => self::num($pis, 'vPIS'),

            'CST_COF' => self::txt($cofins, 'CST'),
            'V_BC_COF' => self::num($cofins, 'vBC'),
            '%_COF' => self::num($cofins, 'pCOFINS'),
            'V_COF' => self::num($cofins, 'vCOFINS'),

            'V_BC_ISS' => self::num($issqn, 'vBC'),
            '%_ISS' => self::num($issqn, 'vAliq'),
            'V_ISS' => self::num($issqn, 'vISSQN'),
            'Cod_Mun_FG' => self::txt($issqn, 'cMunFGISS'),
            'Cod_List_Serv' => self::txt($issqn, 'cListServ'),
            'V_Deducao_ISS' => self::num($issqn, 'vDeducao'),
            'V_Outras_Ret' => self::num($issqn, 'vOutro'),
            'V_Desc_Incond' => self::num($issqn, 'vDescIncond'),
            'V_Desc_Cond' => self::num($issqn, 'vDescCond'),
            'V_ISS_Ret' => self::num($issqn, 'vISSRet'),
            'Indicador_Exigib' => self::txt($issqn, 'indISS'),
            'Cod_Serv_Mun' => self::txt($issqn, 'cServico'),
            'Cod_Mun_Incid_ISS' => self::txt($issqn, 'cMun'),
            'Indicad_Incent_Fiscal' => self::txt($issqn, 'indIncentivo'),

            'Dados_Adicionais_Produto' => self::txt($det, 'infAdProd'),

            'Cod. Pedido' => self::txt($prod, 'xPed'),

            'CST_IBS_CBS' => self::txt($ibsCbs, 'CST'),
            'Cod_Class_Trib_IBS_CBS' => self::txt($ibsCbs, 'cClassTrib'),
            'V_BC_IBS_CBS' => self::num($gIbsCbs, 'vBC'),
            '%_IBS_UF' => self::num($gIbsUF, 'pIBSUF'),
            'V_IBS_UF' => self::num($gIbsUF, 'vIBSUF'),
            '%_IBS_Mun' => self::num($gIbsMun, 'pIBSMun'),
            'V_IBS_Mun' => self::num($gIbsMun, 'vIBSMun'),
            '%_CBS' => self::num($gCbs, 'pCBS'),
            'V_CBS' => self::num($gCbs, 'vCBS'),
            'CST_IS' => self::txt($gIS, 'CSTIS'),
            'V_BC_IS' => self::num($gIS, 'vBCIS'),
            '%_IS' => self::num($gIS, 'pIS'),
            'V_IS' => self::num($gIS, 'vIS'),
        ];
    }

    // ─── Fallback: documento sem XML completo (só resumo/resNFe) ─────────────

    private static function linhaSemDetalhes(DocumentoFiscal $documento): array
    {
        return [
            'Chave_Acesso' => $documento->chave_acesso,
            'Mod_Doc' => $documento->tipo === 'nfce' ? '65' : '55',
            'Nº_Doc' => $documento->numero,
            'Data_Emis' => $documento->data_emissao?->format('d/m/Y'),
            'Data_Saida' => $documento->data_saida_entrada?->format('d/m/Y'),
            'CNPJ_Emit' => $documento->emitente_doc,
            'Razao_Social_Emit' => $documento->emitente_nome,
            'Indicador_Operação' => $documento->tp_nf,
            'Tipo_Oper' => self::TIPO_OPER_LABEL[(string) $documento->tp_nf] ?? null,
            'Status_Doc' => self::statusDoc($documento),
        ];
    }

    private static function statusDoc(DocumentoFiscal $documento): string
    {
        if ($documento->situacao === 'cancelada') {
            return 'Cancelada';
        }

        return self::SITUACAO_LABEL[$documento->situacao] ?? ($documento->situacao ?: 'Autorizada');
    }

    // ─── Helpers de XML (namespace-agnósticos, via local-name()) ────────────

    private static function descendente(\SimpleXMLElement $ctx, string $tag): ?\SimpleXMLElement
    {
        $r = $ctx->xpath(".//*[local-name()='{$tag}']");

        return $r[0] ?? null;
    }

    private static function filho(?\SimpleXMLElement $ctx, string $tag): ?\SimpleXMLElement
    {
        if (! $ctx) {
            return null;
        }

        $r = $ctx->xpath("./*[local-name()='{$tag}']");

        return $r[0] ?? null;
    }

    /** @return \SimpleXMLElement[] */
    private static function filhos(?\SimpleXMLElement $ctx, string $tag): array
    {
        if (! $ctx) {
            return [];
        }

        return $ctx->xpath("./*[local-name()='{$tag}']") ?: [];
    }

    /** Primeiro filho direto, qualquer nome — usado para achar a variante de ICMS/PIS/COFINS. */
    private static function primeiroFilho(?\SimpleXMLElement $ctx): ?\SimpleXMLElement
    {
        if (! $ctx) {
            return null;
        }

        $r = $ctx->xpath('./*');

        return $r[0] ?? null;
    }

    private static function txt(?\SimpleXMLElement $ctx, string $tag): ?string
    {
        $n = self::filho($ctx, $tag);

        if ($n === null) {
            return null;
        }

        $v = trim((string) $n);

        return $v !== '' ? $v : null;
    }

    private static function num(?\SimpleXMLElement $ctx, string $tag): ?float
    {
        $v = self::txt($ctx, $tag);

        return $v !== null ? (float) $v : null;
    }

    /** @return array{0: ?string, 1: ?string} [data d/m/Y, hora H:i:s] a partir de um dhEmi/dhSaiEnt (ISO 8601). */
    private static function dataHora(?string $iso): array
    {
        if (! $iso) {
            return [null, null];
        }

        $partes = explode('T', $iso);
        $data = $partes[0] ?? null;
        $hora = isset($partes[1]) ? substr($partes[1], 0, 8) : null;

        if ($data) {
            $p = explode('-', $data);
            if (count($p) === 3) {
                [$y, $m, $d] = $p;
                $data = "{$d}/{$m}/{$y}";
            }
        }

        return [$data, $hora];
    }
}
