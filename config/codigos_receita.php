<?php

/*
|--------------------------------------------------------------------------
| Códigos de receita de guias de pagamento
|--------------------------------------------------------------------------
|
| Usada pela análise de documentos com IA (App\Services\AnalisadorDocumentoService)
| para dar nome à guia pelo código impresso nela e sugerir a pasta do portal.
| Chave = código de receita sem a variação (ex.: "1082" cobre 1082-01, 1082-21...).
| Na DCTFWeb o DARF traz vários códigos numa guia só; o primeiro reconhecido nomeia a guia.
|
| Fontes: SIEF Receitas (siefreceitas.receita.economia.gov.br), tabela de códigos
| da DCTFWeb e tabela de códigos GPS do INSS.
|
*/

return [

    // ── DARF — PIS / COFINS ──────────────────────────────────────────────────
    '2172' => ['descricao' => 'COFINS - Faturamento (cumulativo / Lucro Presumido)', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '8109' => ['descricao' => 'PIS - Faturamento (cumulativo / Lucro Presumido)', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '5856' => ['descricao' => 'COFINS - Não cumulativa (Lucro Real)', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '6912' => ['descricao' => 'PIS - Não cumulativo (Lucro Real)', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '8301' => ['descricao' => 'PIS - Folha de salários', 'guia' => 'DARF', 'pasta' => 'Pessoal'],

    // ── DARF — IRPJ / CSLL ───────────────────────────────────────────────────
    '2089' => ['descricao' => 'IRPJ - Lucro Presumido', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '2372' => ['descricao' => 'CSLL - Lucro Presumido', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '0220' => ['descricao' => 'IRPJ - Lucro Real trimestral', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '6012' => ['descricao' => 'CSLL - Lucro Real trimestral', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '2362' => ['descricao' => 'IRPJ - Lucro Real estimativa mensal', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '2484' => ['descricao' => 'CSLL - Lucro Real estimativa mensal', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '2430' => ['descricao' => 'IRPJ - Lucro Real ajuste anual', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '6773' => ['descricao' => 'CSLL - Lucro Real ajuste anual', 'guia' => 'DARF', 'pasta' => 'Fiscal'],

    // ── DARF — IRRF / retenções ──────────────────────────────────────────────
    '0561' => ['descricao' => 'IRRF - Rendimentos do trabalho assalariado', 'guia' => 'DARF', 'pasta' => 'Pessoal'],
    '0588' => ['descricao' => 'IRRF - Trabalho sem vínculo empregatício', 'guia' => 'DARF', 'pasta' => 'Pessoal'],
    '3562' => ['descricao' => 'IRRF - Participação nos lucros (PLR)', 'guia' => 'DARF', 'pasta' => 'Pessoal'],
    '1708' => ['descricao' => 'IRRF - Serviços profissionais prestados por PJ', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '3208' => ['descricao' => 'IRRF - Aluguéis e royalties pagos a PF', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '3280' => ['descricao' => 'IRRF - Serviços de cooperativas de trabalho', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '8045' => ['descricao' => 'IRRF - Comissões, corretagens e outros rendimentos', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '5706' => ['descricao' => 'IRRF - Juros sobre capital próprio', 'guia' => 'DARF', 'pasta' => 'Contabilidade'],
    '0473' => ['descricao' => 'IRRF - Rendimentos de residentes no exterior', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '5952' => ['descricao' => 'CSRF - Retenção PIS/COFINS/CSLL sobre serviços', 'guia' => 'DARF', 'pasta' => 'Fiscal'],

    // ── DARF — Pessoa física ─────────────────────────────────────────────────
    '0190' => ['descricao' => 'IRPF - Carnê-Leão', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '0211' => ['descricao' => 'IRPF - Quota da declaração de ajuste anual', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '4600' => ['descricao' => 'IRPF - Ganho de capital na alienação de bens', 'guia' => 'DARF', 'pasta' => 'Fiscal'],
    '6015' => ['descricao' => 'IRPF - Ganhos líquidos em bolsa (renda variável)', 'guia' => 'DARF', 'pasta' => 'Fiscal'],

    // ── DARF DCTFWeb — Contribuições previdenciárias ─────────────────────────
    '1082' => ['descricao' => 'CP Segurados - Empregados/avulsos', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1099' => ['descricao' => 'CP Segurados - Contribuintes individuais', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1138' => ['descricao' => 'CP Patronal - Empregados/avulsos', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1141' => ['descricao' => 'CP Patronal - Adicional GILRAT', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1646' => ['descricao' => 'CP Patronal - GILRAT ajustado', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1162' => ['descricao' => 'CP Patronal - Retenção Lei 9.711/98 (cessão de mão de obra)', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Fiscal'],
    '2985' => ['descricao' => 'CPRB - Art. 7º da Lei 12.546/2011', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '2991' => ['descricao' => 'CPRB - Art. 8º da Lei 12.546/2011', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1170' => ['descricao' => 'CP Terceiros - Salário-educação', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1176' => ['descricao' => 'CP Terceiros - INCRA', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1181' => ['descricao' => 'CP Terceiros - SENAI', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1184' => ['descricao' => 'CP Terceiros - SESI', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1191' => ['descricao' => 'CP Terceiros - SENAC', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1196' => ['descricao' => 'CP Terceiros - SESC', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1200' => ['descricao' => 'CP Terceiros - SEBRAE', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1218' => ['descricao' => 'CP Terceiros - SEST', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1221' => ['descricao' => 'CP Terceiros - SENAT', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],
    '1225' => ['descricao' => 'CP Terceiros - SESCOOP', 'guia' => 'DARF DCTFWeb', 'pasta' => 'Pessoal'],

    // ── FGTS Digital (GFD) ───────────────────────────────────────────────────
    '1718' => ['descricao' => 'FGTS - Depósito mensal', 'guia' => 'FGTS Digital (GFD)', 'pasta' => 'Pessoal'],
    '1251' => ['descricao' => 'FGTS - Depósito compensatório', 'guia' => 'FGTS Digital (GFD)', 'pasta' => 'Pessoal'],
    '1253' => ['descricao' => 'FGTS - Multa rescisória', 'guia' => 'FGTS Digital (GFD)', 'pasta' => 'Pessoal'],
    '1254' => ['descricao' => 'FGTS - Contribuição social 10%', 'guia' => 'FGTS Digital (GFD)', 'pasta' => 'Pessoal'],
    '1719' => ['descricao' => 'FGTS - Encargos', 'guia' => 'FGTS Digital (GFD)', 'pasta' => 'Pessoal'],

    // ── GPS (INSS) ───────────────────────────────────────────────────────────
    '1007' => ['descricao' => 'INSS - Contribuinte individual mensal', 'guia' => 'GPS', 'pasta' => 'Pessoal'],
    '1163' => ['descricao' => 'INSS - Contribuinte individual plano simplificado', 'guia' => 'GPS', 'pasta' => 'Pessoal'],
    '1406' => ['descricao' => 'INSS - Facultativo mensal', 'guia' => 'GPS', 'pasta' => 'Pessoal'],
    '1473' => ['descricao' => 'INSS - Facultativo plano simplificado', 'guia' => 'GPS', 'pasta' => 'Pessoal'],
    '1929' => ['descricao' => 'INSS - Facultativo baixa renda', 'guia' => 'GPS', 'pasta' => 'Pessoal'],
    '2003' => ['descricao' => 'INSS - Empresa optante pelo Simples Nacional', 'guia' => 'GPS', 'pasta' => 'Pessoal'],
    '2100' => ['descricao' => 'INSS - Empresas em geral', 'guia' => 'GPS', 'pasta' => 'Pessoal'],
    '2631' => ['descricao' => 'INSS - Retenção sobre NF de prestadora de serviço', 'guia' => 'GPS', 'pasta' => 'Fiscal'],

    // ── GNRE — ICMS ──────────────────────────────────────────────────────────
    '100013' => ['descricao' => 'ICMS Comunicação', 'guia' => 'GNRE', 'pasta' => 'Fiscal'],
    '100021' => ['descricao' => 'ICMS Energia Elétrica', 'guia' => 'GNRE', 'pasta' => 'Fiscal'],
    '100030' => ['descricao' => 'ICMS Transporte', 'guia' => 'GNRE', 'pasta' => 'Fiscal'],
    '100048' => ['descricao' => 'ICMS Substituição Tributária por Apuração', 'guia' => 'GNRE', 'pasta' => 'Fiscal'],
    '100056' => ['descricao' => 'ICMS Importação', 'guia' => 'GNRE', 'pasta' => 'Fiscal'],
    '100080' => ['descricao' => 'ICMS Recolhimentos Especiais', 'guia' => 'GNRE', 'pasta' => 'Fiscal'],
    '100099' => ['descricao' => 'ICMS Substituição Tributária por Operação', 'guia' => 'GNRE', 'pasta' => 'Fiscal'],
    '100102' => ['descricao' => 'ICMS DIFAL - Consumidor final não contribuinte (EC 87/15)', 'guia' => 'GNRE', 'pasta' => 'Fiscal'],
    '100129' => ['descricao' => 'ICMS FCP - Fundo de Combate à Pobreza', 'guia' => 'GNRE', 'pasta' => 'Fiscal'],

];
