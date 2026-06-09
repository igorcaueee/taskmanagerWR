<?php

namespace Database\Seeders;

use App\Models\Questionario;
use App\Models\QuestionarioOpcao;
use App\Models\QuestionarioPergunta;
use Illuminate\Database\Seeder;

class QuestionarioIDESeeder extends Seeder
{
    public function run(): void
    {
        if (Questionario::where('slug', 'ide-diagnostico-empresarial')->exists()) {
            return;
        }

        $questionario = Questionario::create([
            'titulo'    => 'Questionário IDE – Índice de Dinheiro Escondido Empresarial®',
            'descricao' => 'Avalia a maturidade financeira, tributária, gerencial e de lucratividade da empresa e identifica oportunidades de recuperação de resultados.',
            'slug'      => 'ide-diagnostico-empresarial',
            'ativo'     => true,
        ]);

        $perguntas = [
            // Financeiro (peso 25%)
            ['categoria' => 'financeiro', 'texto' => 'Existe fluxo de caixa projetado para os próximos 90 dias?'],
            ['categoria' => 'financeiro', 'texto' => 'O empresário sabe quanto possui de capital de giro?'],
            ['categoria' => 'financeiro', 'texto' => 'Existe orçamento anual formalizado?'],
            ['categoria' => 'financeiro', 'texto' => 'O pró-labore é definido e separado das finanças da empresa?'],
            ['categoria' => 'financeiro', 'texto' => 'A empresa acompanha indicadores financeiros mensalmente?'],
            // Tributário (peso 25%)
            ['categoria' => 'tributario', 'texto' => 'A empresa realizou planejamento tributário nos últimos 2 anos?'],
            ['categoria' => 'tributario', 'texto' => 'Existem débitos tributários em aberto?'],
            ['categoria' => 'tributario', 'texto' => 'Os impostos são pagos em dia?'],
            ['categoria' => 'tributario', 'texto' => 'A empresa possui acompanhamento mensal dos tributos?'],
            ['categoria' => 'tributario', 'texto' => 'Já foi realizada revisão fiscal nos últimos 3 anos?'],
            // Endividamento (peso 15%)
            ['categoria' => 'endividamento', 'texto' => 'As parcelas bancárias estão sob controle (representam percentual saudável do faturamento)?'],
            ['categoria' => 'endividamento', 'texto' => 'Existem empréstimos emergenciais ativos?'],
            ['categoria' => 'endividamento', 'texto' => 'A empresa conhece seu índice de endividamento?'],
            ['categoria' => 'endividamento', 'texto' => 'A empresa possui restrições em órgãos de crédito?'],
            // Gestão (peso 15%)
            ['categoria' => 'gestao', 'texto' => 'A empresa possui metas anuais definidas?'],
            ['categoria' => 'gestao', 'texto' => 'Possui indicadores de desempenho (KPIs)?'],
            ['categoria' => 'gestao', 'texto' => 'Existe organograma definido?'],
            ['categoria' => 'gestao', 'texto' => 'Existem reuniões gerenciais periódicas?'],
            // Lucratividade (peso 20%)
            ['categoria' => 'lucratividade', 'texto' => 'O empresário conhece sua margem líquida?'],
            ['categoria' => 'lucratividade', 'texto' => 'Conhece o lucro por produto ou serviço?'],
            ['categoria' => 'lucratividade', 'texto' => 'Possui formação de preço estruturada?'],
            ['categoria' => 'lucratividade', 'texto' => 'Analisa mensalmente seus custos?'],
        ];

        $opcoes = [
            ['texto' => 'Não / Ruim',    'pontos' => 0, 'ordem' => 1],
            ['texto' => 'Parcial',        'pontos' => 5, 'ordem' => 2],
            ['texto' => 'Sim / Adequado', 'pontos' => 10, 'ordem' => 3],
        ];

        foreach ($perguntas as $ordem => $dados) {
            $pergunta = QuestionarioPergunta::create([
                'questionario_id' => $questionario->id,
                'categoria'       => $dados['categoria'],
                'texto'           => $dados['texto'],
                'ordem'           => $ordem + 1,
            ]);

            foreach ($opcoes as $opcao) {
                QuestionarioOpcao::create([
                    'pergunta_id' => $pergunta->id,
                    'texto'       => $opcao['texto'],
                    'pontos'      => $opcao['pontos'],
                    'ordem'       => $opcao['ordem'],
                ]);
            }
        }
    }
}
