{{-- DANFSe v2.0 — Documento Auxiliar da NFS-e
     Layout conforme Nota Técnica SE/CGNFS-e nº 008/2026 (Anexo I).
     Gerado localmente (a API oficial de geração foi sobrestada em 01/07/2026). --}}
@php
    $fontDir = storage_path('fonts/danfse');
    $money = fn ($v) => $v === null ? '-' : 'R$ ' . number_format((float) $v, 2, ',', '.');
    $pct   = fn ($v) => $v === null ? '-' : number_format((float) $v, 2, ',', '.') . '%';
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<style>
    @font-face { font-family: 'arimo'; font-weight: normal; font-style: normal;
        src: url("{{ $fontDir }}/Arimo-Regular.ttf") format('truetype'); }
    @font-face { font-family: 'arimo'; font-weight: bold; font-style: normal;
        src: url("{{ $fontDir }}/Arimo-Bold.ttf") format('truetype'); }
    @font-face { font-family: 'arimo'; font-weight: normal; font-style: italic;
        src: url("{{ $fontDir }}/Arimo-Italic.ttf") format('truetype'); }
    @font-face { font-family: 'arimo'; font-weight: bold; font-style: italic;
        src: url("{{ $fontDir }}/Arimo-BoldItalic.ttf") format('truetype'); }

    @page { margin: 0.18cm; }
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body { font-family: 'arimo', sans-serif; color: #000; font-size: 7pt; line-height: 1.1; }

    .folha { border: 1pt solid #000; }

    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    td { vertical-align: top; padding: 1pt 3pt; overflow: hidden;
         border: 0.5pt solid #000; }

    /* título de bloco: 7pt bold caixa alta, fundo cinza 5% */
    .titulo { font-weight: bold; font-size: 7pt; text-transform: uppercase; background: #f2f2f2; }
    .sub    { text-align: center; font-weight: bold; }

    .lbl { font-weight: bold; font-size: 6pt; display: block; }
    .ident .lbl { font-size: 7pt; text-transform: uppercase; }
    .val { font-size: 7pt; display: block; word-wrap: break-word; }

    .sombra { background: #f2f2f2; }
    .center { text-align: center; }
    .b { font-weight: bold; }

    /* cabeçalho */
    .cab td { border: 0; padding: 2pt 4pt; background: #f2f2f2; }
    .cab { border-bottom: 0.5pt solid #000; }
    .cab .logo img { width: 3.7cm; }
    .cab .centro { text-align: center; }
    .cab .centro .t1, .cab .centro .t2 { font-weight: bold; font-size: 9pt; }
    .cab .centro .semvalor { font-weight: bold; font-size: 9pt; color: #e30613; }
    .cab .mun { font-size: 6pt; text-align: right; }
    .cab .mun .m1 { font-size: 8pt; }

    .qrbox { width: 2.05cm; text-align: center; }
    .qrbox img { width: 1.55cm; height: 1.55cm; }
    .qrbox .qrtxt { font-size: 5.5pt; line-height: 1.05; margin-top: 1pt; }
    .chave { font-size: 7pt; word-spacing: 1pt; }

    .desc { height: 1.1cm; }
    .infocompl { height: 1.4cm; font-size: 7pt; word-wrap: break-word; }
    .canhoto { height: 1.1cm; }

    .marcadagua {
        position: fixed; top: 42%; left: 0; width: 100%; text-align: center;
        font-size: 60pt; font-weight: bold; color: #a6a6a6;
        transform: rotate(-45deg); z-index: 1000;
    }
    .marcadagua.pequena { font-size: 30pt; }
</style>
</head>
<body>

@if ($marcaDagua)
    <div class="marcadagua {{ $marcaDagua === 'NFS-e SEM VALIDADE JURÍDICA' ? 'pequena' : '' }}">{{ $marcaDagua }}</div>
@endif

<div class="folha">

    {{-- ══════════ CABEÇALHO ══════════ --}}
    <table class="cab">
        <tr>
            <td class="logo" style="width:3.9cm">
                @if ($logoBase64)<img src="{{ $logoBase64 }}" alt="NFS-e">@else<span class="b" style="font-size:12pt">NFS-e</span>@endif
            </td>
            <td class="centro">
                <div class="t1">DANFSe v2.0</div>
                <div class="t2">Documento Auxiliar da NFS-e</div>
                @if ($marcaDagua === 'NFS-e SEM VALIDADE JURÍDICA')
                    <div class="semvalor">NFS-e SEM VALIDADE JURÍDICA</div>
                @endif
            </td>
            <td class="mun" style="width:5.1cm">
                <div class="m1">{{ $cabecalho['municipio'] }}</div>
                <div>Ambiente Gerador: {{ $cabecalho['ambGer'] }}</div>
                <div>Tipo de Ambiente: {{ $cabecalho['tpAmb'] }}</div>
            </td>
        </tr>
    </table>

    {{-- ══════════ DADOS DE IDENTIFICAÇÃO ══════════ --}}
    <table class="ident">
        <tr>
            <td colspan="3"><span class="lbl">Chave de Acesso da NFS-e</span><span class="val chave">{{ $identificacao['chave'] }}</span></td>
            <td rowspan="3" class="qrbox">
                <img src="{{ $qrCodeImg }}" alt="QR Code NFS-e">
                <div class="qrtxt">A autenticidade desta NFS-e pode ser verificada pela leitura deste código QR ou pela consulta da chave de acesso no portal nacional da NFS-e</div>
            </td>
        </tr>
        <tr>
            <td><span class="lbl">Número da NFS-e</span><span class="val">{{ $identificacao['nNFSe'] }}</span></td>
            <td><span class="lbl">Competência da NFS-e</span><span class="val">{{ $identificacao['dCompet'] }}</span></td>
            <td><span class="lbl">Data e Hora da Emissão da NFS-e</span><span class="val">{{ $identificacao['dhProc'] }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">Número da DPS</span><span class="val">{{ $identificacao['nDPS'] }}</span></td>
            <td><span class="lbl">Série da DPS</span><span class="val">{{ $identificacao['serie'] }}</span></td>
            <td><span class="lbl">Data e Hora da Emissão da DPS</span><span class="val">{{ $identificacao['dhEmiDps'] }}</span></td>
        </tr>
        <tr>
            <td class="sombra"><span class="lbl">Emitente da NFS-e</span><span class="val">{{ $identificacao['emitente'] }}</span></td>
            <td><span class="lbl">Situação da NFS-e</span><span class="val">{{ $identificacao['situacao'] }}</span></td>
            <td colspan="2"><span class="lbl">Finalidade</span><span class="val">{{ $identificacao['finalidade'] }}</span></td>
        </tr>
    </table>

    {{-- ══════════ PRESTADOR / FORNECEDOR ══════════ --}}
    <table>
        <tr>
            <td class="titulo" style="width:4.6cm">Prestador / Fornecedor</td>
            <td><span class="lbl">CNPJ / CPF / NIF</span><span class="val">{{ $prestador['doc'] }}</span></td>
            <td><span class="lbl">Indicador Municipal (Inscrição)</span><span class="val">{{ $prestador['im'] }}</span></td>
            <td><span class="lbl">Telefone</span><span class="val">{{ $prestador['fone'] }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">Nome / Nome Empresarial</span><span class="val">{{ $prestador['nome'] }}</span></td>
            <td><span class="lbl">Município / Sigla UF</span><span class="val">{{ $prestador['municipio'] }}</span></td>
            <td><span class="lbl">Código IBGE / CEP</span><span class="val">{{ $prestador['ibgeCep'] }}</span></td>
        </tr>
        <tr>
            <td colspan="3"><span class="lbl">Endereço</span><span class="val">{{ $prestador['logradouro'] }}</span></td>
            <td><span class="lbl">E-mail</span><span class="val">{{ $prestador['email'] }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">Simples Nacional na Data de Competência</span><span class="val">{{ $prestador['simplesNacional'] }}</span></td>
            <td colspan="2"><span class="lbl">Regime de Apuração Tributária pelo SN</span><span class="val">{{ $prestador['regimeApuracaoSN'] }}</span></td>
        </tr>
    </table>

    {{-- ══════════ TOMADOR / ADQUIRENTE ══════════ --}}
    @if ($tomador)
    <table>
        <tr>
            <td class="titulo" style="width:4.6cm">Tomador / Adquirente</td>
            <td><span class="lbl">CNPJ / CPF / NIF</span><span class="val">{{ $tomador['doc'] }}</span></td>
            <td><span class="lbl">Indicador Municipal (Inscrição)</span><span class="val">{{ $tomador['im'] }}</span></td>
            <td><span class="lbl">Telefone</span><span class="val">{{ $tomador['fone'] }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">Nome / Nome Empresarial</span><span class="val">{{ $tomador['nome'] }}</span></td>
            <td><span class="lbl">Município / Sigla UF</span><span class="val">{{ $tomador['municipio'] }}</span></td>
            <td><span class="lbl">Código IBGE / CEP</span><span class="val">{{ $tomador['ibgeCep'] }}</span></td>
        </tr>
        <tr>
            <td colspan="3"><span class="lbl">Endereço</span><span class="val">{{ $tomador['logradouro'] }}</span></td>
            <td><span class="lbl">E-mail</span><span class="val">{{ $tomador['email'] }}</span></td>
        </tr>
    </table>
    @else
    <table><tr><td class="sub">TOMADOR/ADQUIRENTE DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e</td></tr></table>
    @endif

    {{-- ══════════ DESTINATÁRIO DA OPERAÇÃO ══════════ --}}
    @if ($destIgualTomador)
    <table><tr><td class="sub">O DESTINATÁRIO É O PRÓPRIO TOMADOR/ADQUIRENTE DA OPERAÇÃO</td></tr></table>
    @elseif ($destinatario)
    <table>
        <tr>
            <td class="titulo" style="width:4.6cm">Destinatário da Operação</td>
            <td><span class="lbl">CNPJ / CPF / NIF</span><span class="val">{{ $destinatario['doc'] }}</span></td>
            <td><span class="lbl">Telefone</span><span class="val">{{ $destinatario['fone'] }}</span></td>
            <td><span class="lbl">Município / Sigla UF</span><span class="val">{{ $destinatario['municipio'] }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">Nome / Nome Empresarial</span><span class="val">{{ $destinatario['nome'] }}</span></td>
            <td><span class="lbl">Código IBGE / CEP</span><span class="val">{{ $destinatario['ibgeCep'] }}</span></td>
            <td><span class="lbl">E-mail</span><span class="val">{{ $destinatario['email'] }}</span></td>
        </tr>
        <tr><td colspan="4"><span class="lbl">Endereço</span><span class="val">{{ $destinatario['logradouro'] }}</span></td></tr>
    </table>
    @else
    <table><tr><td class="sub">DESTINATÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e</td></tr></table>
    @endif

    {{-- ══════════ INTERMEDIÁRIO DA OPERAÇÃO ══════════ --}}
    @if ($intermediario)
    <table>
        <tr>
            <td class="titulo" style="width:4.6cm">Intermediário da Operação</td>
            <td><span class="lbl">CNPJ / CPF / NIF</span><span class="val">{{ $intermediario['doc'] }}</span></td>
            <td><span class="lbl">Indicador Municipal (Inscrição)</span><span class="val">{{ $intermediario['im'] }}</span></td>
            <td><span class="lbl">Telefone</span><span class="val">{{ $intermediario['fone'] }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="lbl">Nome / Nome Empresarial</span><span class="val">{{ $intermediario['nome'] }}</span></td>
            <td><span class="lbl">Município / Sigla UF</span><span class="val">{{ $intermediario['municipio'] }}</span></td>
            <td><span class="lbl">Código IBGE / CEP</span><span class="val">{{ $intermediario['ibgeCep'] }}</span></td>
        </tr>
        <tr><td colspan="4"><span class="lbl">Endereço</span><span class="val">{{ $intermediario['logradouro'] }}</span></td></tr>
    </table>
    @else
    <table><tr><td class="sub">INTERMEDIÁRIO DA OPERAÇÃO NÃO IDENTIFICADO NA NFS-e</td></tr></table>
    @endif

    {{-- ══════════ SERVIÇO PRESTADO ══════════ --}}
    <table>
        <tr>
            <td class="titulo" style="width:4.6cm">Serviço Prestado</td>
            <td><span class="lbl">Código de Tributação Nacional / Municipal</span><span class="val">{{ $servico['codTrib'] }}</span></td>
            <td><span class="lbl">Código da NBS</span><span class="val">{{ $servico['cNBS'] }}</span></td>
            <td><span class="lbl">Local da Prestação / Sigla UF / País</span><span class="val">{{ $servico['localPrestacao'] }}</span></td>
        </tr>
        <tr><td colspan="4"><span class="val">{{ $servico['descCodTrib'] }}</span></td></tr>
        <tr><td colspan="4" class="desc"><span class="lbl">Descrição do Serviço</span><span class="val">{{ $servico['descServico'] }}</span></td></tr>
    </table>

    {{-- ══════════ TRIBUTAÇÃO MUNICIPAL (ISSQN) ══════════ --}}
    @if (! $issqn['aplica'])
    <table><tr><td class="sub">TRIBUTAÇÃO MUNICIPAL (ISSQN) - OPERAÇÃO NÃO SUJEITA AO ISSQN</td></tr></table>
    @else
    <table>
        <tr>
            <td class="titulo" style="width:4.6cm">Tributação Municipal (ISSQN)</td>
            <td colspan="2"><span class="lbl">Tipo de Tributação do ISSQN</span><span class="val">{{ $issqn['tipo'] }}</span></td>
            <td><span class="lbl">Município / Sigla UF / País de Incidência do ISSQN</span><span class="val">{{ $issqn['localIncidencia'] }}</span></td>
        </tr>
        @if ($issqn['showLinhaRegime'])
        <tr>
            <td><span class="lbl">Regime Especial de Tributação do ISSQN</span><span class="val">{{ $issqn['regimeEspecial'] }}</span></td>
            <td><span class="lbl">Tipo de Imunidade do ISSQN</span><span class="val">{{ $issqn['tipoImunidade'] }}</span></td>
            <td><span class="lbl">Suspensão da Exigibilidade do ISSQN</span><span class="val">{{ $issqn['suspensao'] }}</span></td>
            <td><span class="lbl">Número Processo Suspensão</span><span class="val">{{ $issqn['nProcesso'] }}</span></td>
        </tr>
        @endif
        @if ($issqn['showLinhaBeneficio'])
        <tr>
            <td><span class="lbl">Benefício Municipal</span><span class="val">{{ $issqn['beneficio'] }}</span></td>
            <td><span class="lbl">Cálculo do BM</span><span class="val">{{ $money($issqn['calcBM']) }}</span></td>
            <td><span class="lbl">Total Deduções/Reduções</span><span class="val">{{ $money($issqn['deducoesReducoes']) }}</span></td>
            <td><span class="lbl">Desconto Incondicionado</span><span class="val">{{ $money($issqn['descIncond']) }}</span></td>
        </tr>
        @endif
        <tr>
            <td><span class="lbl">BC ISSQN</span><span class="val">{{ $money($issqn['bcIssqn']) }}</span></td>
            <td><span class="lbl">Alíquota Aplicada</span><span class="val">{{ $pct($issqn['aliquota']) }}</span></td>
            <td><span class="lbl">Retenção do ISSQN</span><span class="val">{{ $issqn['retencao'] }}</span></td>
            <td><span class="lbl">ISSQN Apurado</span><span class="val">{{ $money($issqn['issqnApurado']) }}</span></td>
        </tr>
    </table>
    @endif

    {{-- ══════════ TRIBUTAÇÃO FEDERAL (EXCETO CBS) ══════════ --}}
    @if ($federal['imprime'])
    <table>
        <tr>
            <td class="titulo" style="width:4.6cm">Tributação Federal (Exceto CBS)</td>
            <td><span class="lbl">IRRF</span><span class="val">{{ $money($federal['irrf']) }}</span></td>
            <td><span class="lbl">Contribuição Previdenciária - Retida</span><span class="val">{{ $money($federal['previdenciaria']) }}</span></td>
            <td><span class="lbl">Contribuições Sociais - Retidas</span><span class="val">{{ $money($federal['sociais']) }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">PIS - Débito Apuração Própria</span><span class="val">{{ $money($federal['pis']) }}</span></td>
            <td><span class="lbl">COFINS - Débito Apuração Própria</span><span class="val">{{ $money($federal['cofins']) }}</span></td>
            <td colspan="2"><span class="lbl">Descrição Contrib. Sociais - Retidas</span><span class="val">{{ $federal['descRetPisCofins'] }}</span></td>
        </tr>
    </table>
    @endif

    {{-- ══════════ TRIBUTAÇÃO IBS / CBS ══════════ --}}
    <table>
        <tr>
            <td class="titulo" style="width:4.6cm">Tributação IBS / CBS</td>
            <td><span class="lbl">CST / cClassTrib</span><span class="val">{{ $ibscbs['cstClassTrib'] }}</span></td>
            <td colspan="2"><span class="lbl">Indicador de Operação / Cód. IBGE Incidência / Município Incidência / Sigla UF</span><span class="val">{{ $ibscbs['indOperacao'] }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">Exclusões e Reduções da Base de Cálculo</span><span class="val">{{ $money($ibscbs['exclusoesReducoes']) }}</span></td>
            <td><span class="lbl">Base de Cálculo Após Exclusões e Reduções</span><span class="val">{{ $money($ibscbs['bcAposReducoes']) }}</span></td>
            <td><span class="lbl">Red. Alíquota IBS UF / IBS Mun / CBS</span><span class="val">{{ $ibscbs['redAliquota'] }}</span></td>
            <td><span class="lbl">Alíquota IBS UF / IBS Mun</span><span class="val">{{ $ibscbs['aliqIbs'] }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">Alíq. Efetiva Municipal - IBS</span><span class="val">{{ $pct($ibscbs['aliqEfetMunIbs']) }}</span></td>
            <td><span class="lbl">Valor Apurado Municipal - IBS</span><span class="val">{{ $money($ibscbs['valorMunIbs']) }}</span></td>
            <td><span class="lbl">Alíq. Efetiva Estadual - IBS</span><span class="val">{{ $pct($ibscbs['aliqEfetUfIbs']) }}</span></td>
            <td><span class="lbl">Valor Apurado Estadual - IBS</span><span class="val">{{ $money($ibscbs['valorUfIbs']) }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">Valor Total Apurado - IBS</span><span class="val">{{ $money($ibscbs['valorTotalIbs']) }}</span></td>
            <td><span class="lbl">Alíquota - CBS</span><span class="val">{{ $pct($ibscbs['aliqCbs']) }}</span></td>
            <td><span class="lbl">Alíquota Efetiva - CBS</span><span class="val">{{ $pct($ibscbs['aliqEfetCbs']) }}</span></td>
            <td><span class="lbl">Valor Total Apurado - CBS</span><span class="val">{{ $money($ibscbs['valorTotalCbs']) }}</span></td>
        </tr>
    </table>

    {{-- ══════════ VALOR TOTAL DA NFS-e ══════════ --}}
    <table>
        <tr>
            <td class="titulo" style="width:4.6cm">Valor Total da NFS-e</td>
            <td><span class="lbl">Valor da Operação / Serviço</span><span class="val">{{ $money($totais['valorServico']) }}</span></td>
            <td><span class="lbl">Desconto Incondicionado</span><span class="val">{{ $money($totais['descIncond']) }}</span></td>
            <td><span class="lbl">Desconto Condicionado</span><span class="val">{{ $money($totais['descCond']) }}</span></td>
        </tr>
        <tr>
            <td><span class="lbl">Total das Retenções (ISSQN / Federais)</span><span class="val">{{ $money($totais['totalRetencoes']) }}</span></td>
            <td><span class="lbl">VALOR LÍQUIDO DA NFS-e</span><span class="val">{{ $money($totais['valorLiquido']) }}</span></td>
            <td><span class="lbl">Total do IBS/CBS</span><span class="val">{{ $money($totais['totalIbsCbs']) }}</span></td>
            <td class="sombra"><span class="lbl">VALOR LÍQUIDO DA NFS-e + IBS/CBS</span><span class="val">{{ $money($totais['valorLiquidoComIbsCbs']) }}</span></td>
        </tr>
    </table>

    {{-- ══════════ INFORMAÇÕES COMPLEMENTARES ══════════ --}}
    <table>
        <tr><td class="titulo">Informações Complementares</td></tr>
        <tr><td class="infocompl">{{ $infoCompl }}</td></tr>
    </table>

    {{-- ══════════ CANHOTO ══════════ --}}
    <table>
        <tr>
            <td class="canhoto" style="width:5.4cm"><span class="lbl">DATA CIENTIFICAÇÃO:</span></td>
            <td class="canhoto" style="width:5.4cm"><span class="lbl">IDENTIFICAÇÃO E ASSINATURA</span></td>
            <td class="canhoto"><span class="lbl">Nº NFS-e / CHAVE NFS-e</span><span class="val chave">{{ $canhoto['nNFSeChave'] }}</span></td>
        </tr>
    </table>

</div>
</body>
</html>
