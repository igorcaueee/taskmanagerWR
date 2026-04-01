<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Etapa;
use App\Models\Tarefa;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class PopularTarefasTeste extends Seeder
{
    /**
     * @var array<int, string>
     */
    private array $titulosPorDepartamento = [
        'RH/DP' => [
            'Enviar folha de pagamento',
            'Atualizar cadastro de funcionário',
            'Calcular férias do colaborador',
            'Emitir RAIS anual',
            'Registrar admissão no eSocial',
            'Processar rescisão contratual',
        ],
        'Fiscal' => [
            'Apurar ICMS do mês',
            'Transmitir SPED Fiscal',
            'Revisar notas fiscais de entrada',
            'Gerar DARF de PIS/COFINS',
            'Verificar obrigações do SIMPLES',
            'Conferir créditos de ICMS',
        ],
        'Contábil' => [
            'Fechar balancete mensal',
            'Conciliar contas bancárias',
            'Lançar depreciações do ativo',
            'Elaborar DRE trimestral',
            'Revisar plano de contas',
            'Registrar provisão de 13º salário',
        ],
        'Financeiro' => [
            'Controlar fluxo de caixa semanal',
            'Emitir boletos de cobrança',
            'Conferir extrato bancário',
            'Pagar guias de impostos',
            'Atualizar projeção orçamentária',
        ],
        'Registro de Empresas' => [
            'Protocolar abertura de empresa',
            'Regularizar CNPJ inativo',
            'Atualizar contrato social',
            'Solicitar certidão negativa',
            'Acompanhar registro na Junta Comercial',
        ],
        'Administrativo' => [
            'Organizar arquivo físico',
            'Renovar certificado digital',
            'Controlar vencimentos de contratos',
            'Atualizar procurações dos clientes',
        ],
    ];

    public function run(): void
    {
        // Garante que pré-requisitos existem
        $this->call([
            popularDepartamentos::class,
            popularEtapas::class,
        ]);

        $etapas = Etapa::orderBy('ordem')->get();
        $responsaveis = Usuario::all();
        $criador = $responsaveis->first();

        if (! $criador) {
            $this->command->warn('Nenhum usuário encontrado. Execute InitialUserSeeder primeiro.');

            return;
        }

        // Cria clientes de teste se não existir nenhum
        if (Cliente::count() === 0) {
            $clientesData = [
                ['nome' => 'Empresa Alpha Ltda', 'cnpj' => '11.222.333/0001-44', 'regime_tributario' => 'Lucro Presumido', 'status' => 'ativo'],
                ['nome' => 'Beta Comércio ME', 'cnpj' => '22.333.444/0001-55', 'regime_tributario' => 'Simples Nacional', 'status' => 'ativo'],
                ['nome' => 'Gama Serviços Eireli', 'cnpj' => '33.444.555/0001-66', 'regime_tributario' => 'Lucro Real', 'status' => 'ativo'],
                ['nome' => 'Delta Indústria S/A', 'cnpj' => '44.555.666/0001-77', 'regime_tributario' => 'Lucro Presumido', 'status' => 'ativo'],
            ];

            foreach ($clientesData as $data) {
                Cliente::firstOrCreate(['cnpj' => $data['cnpj']], $data);
            }
        }

        $clientes = Cliente::all();

        $hoje = now();
        $count = 0;

        foreach ($this->titulosPorDepartamento as $nomeDep => $titulos) {
            $departamento = Departamento::where('nome', $nomeDep)->first();

            if (! $departamento) {
                continue;
            }

            foreach ($titulos as $index => $titulo) {
                $etapa = $etapas->random();
                $responsavel = $responsaveis->random();
                $cliente = $clientes->random();
                $prioridade = ($index % 5) + 1;

                // Varia as datas: algumas atrasadas, algumas futuras
                $diasOffset = ($index % 3 === 0) ? -rand(1, 10) : rand(1, 30);

                Tarefa::create([
                    'titulo' => $titulo,
                    'descricao' => "Tarefa de teste do departamento {$nomeDep}.",
                    'cliente_id' => $cliente->id,
                    'departamento_id' => $departamento->id,
                    'etapa_id' => $etapa->id,
                    'responsavel_id' => $responsavel->id,
                    'criado_por' => $criador->id,
                    'data_vencimento' => $hoje->copy()->addDays($diasOffset)->toDateString(),
                    'prioridade' => $prioridade,
                    'atrasada' => $diasOffset < 0,
                ]);

                $count++;
            }
        }

        $this->command->info("✓ {$count} tarefas de teste criadas com sucesso.");
    }
}
