<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ciclo extends Model
{
    protected $table = 'ciclos';

    protected $fillable = ['nome', 'data_inicio', 'data_fim'];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    public function tarefas(): HasMany
    {
        return $this->hasMany(Tarefa::class);
    }

    /**
     * Retorna 'passado', 'atual' ou 'proximo' com base na data de hoje.
     */
    public function getStatusAttribute(): string
    {
        $today = now()->toDateString();

        if ($this->data_fim->toDateString() < $today) {
            return 'passado';
        }

        if ($this->data_inicio->toDateString() > $today) {
            return 'proximo';
        }

        return 'atual';
    }

    /**
     * Retorna (ou cria) o ciclo do mês seguinte.
     */
    public function proximo(): self
    {
        return self::findOrCreateForDate($this->data_inicio->copy()->addMonth());
    }

    /**
     * Retorna (ou cria) o ciclo do mês anterior.
     */
    public function anterior(): self
    {
        return self::findOrCreateForDate($this->data_inicio->copy()->subMonth());
    }

    /**
     * Encontra ou cria um ciclo mensal (1º ao último dia) para a data informada.
     */
    public static function findOrCreateForDate(Carbon $date): self
    {
        $inicio = $date->copy()->startOfMonth();
        $fim = $inicio->copy()->endOfMonth();

        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                  'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

        return self::firstOrCreate(
            ['data_inicio' => $inicio->toDateString()],
            [
                'nome' => $meses[$inicio->month - 1].' '.$inicio->year,
                'data_fim' => $fim->toDateString(),
            ]
        );
    }

    /**
     * Retorna (ou cria) o ciclo do mês corrente.
     */
    public static function current(): self
    {
        return self::findOrCreateForDate(now());
    }
}
