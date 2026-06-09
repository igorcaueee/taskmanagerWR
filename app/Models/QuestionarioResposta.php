<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionarioResposta extends Model
{
    protected $table = 'questionario_respostas';

    protected $fillable = [
        'questionario_id', 'lead_id', 'cliente_id',
        'respondente_nome', 'respondente_email', 'respondente_empresa',
        'respondente_segmento', 'faturamento_mensal', 'num_colaboradores',
        'pontuacao_total', 'classificacao', 'token', 'finalizado',
    ];

    protected $casts = [
        'finalizado' => 'boolean',
        'faturamento_mensal' => 'decimal:2',
        'pontuacao_total' => 'decimal:2',
    ];

    public function questionario(): BelongsTo
    {
        return $this->belongsTo(Questionario::class, 'questionario_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(QuestionarioRespostaItem::class, 'resposta_id');
    }

    public static function classificarIDE(float $pontos): string
    {
        return match(true) {
            $pontos <= 40 => 'Muito Dinheiro Escondido',
            $pontos <= 60 => 'Dinheiro Escondido Relevante',
            $pontos <= 80 => 'Boa Gestão',
            default       => 'Alta Performance',
        };
    }

    public function corClassificacao(): string
    {
        return match($this->classificacao) {
            'Muito Dinheiro Escondido'    => 'red',
            'Dinheiro Escondido Relevante' => 'orange',
            'Boa Gestão'                  => 'blue',
            'Alta Performance'            => 'green',
            default                       => 'gray',
        };
    }

    // Calcula a pontuação IDE com pesos por categoria
    public function calcularPontuacao(): float
    {
        $pesos = [
            'financeiro'    => 0.25,
            'tributario'    => 0.25,
            'lucratividade' => 0.20,
            'endividamento' => 0.15,
            'gestao'        => 0.15,
        ];

        $itens = $this->itens()->with('pergunta')->get();
        $porCategoria = [];

        foreach ($itens as $item) {
            $cat = $item->pergunta->categoria;
            $porCategoria[$cat][] = $item->pontos;
        }

        $ide = 0;
        foreach ($pesos as $cat => $peso) {
            if (empty($porCategoria[$cat])) {
                continue;
            }
            $pontos = $porCategoria[$cat];
            $max = count($pontos) * 10;
            $nota = ($max > 0) ? (array_sum($pontos) / $max) * 100 : 0;
            $ide += $nota * $peso;
        }

        return round($ide, 2);
    }
}
