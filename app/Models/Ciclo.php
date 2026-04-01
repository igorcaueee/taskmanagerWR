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
     * Retorna (ou cria) o ciclo da semana seguinte.
     */
    public function proximo(): self
    {
        return self::findOrCreateForDate($this->data_inicio->copy()->addWeek());
    }

    /**
     * Retorna (ou cria) o ciclo da semana anterior.
     */
    public function anterior(): self
    {
        return self::findOrCreateForDate($this->data_inicio->copy()->subWeek());
    }

    /**
     * Encontra ou cria um ciclo semanal (seg–dom) para a data informada.
     */
    public static function findOrCreateForDate(Carbon $date): self
    {
        $monday = $date->copy()->startOfWeek(Carbon::MONDAY);
        $sunday = $monday->copy()->endOfWeek(Carbon::SUNDAY);

        return self::firstOrCreate(
            ['data_inicio' => $monday->toDateString()],
            [
                'nome' => 'Semana '.$monday->weekOfYear.' · '.$monday->format('d/m').' – '.$sunday->format('d/m/Y'),
                'data_fim' => $sunday->toDateString(),
            ]
        );
    }

    /**
     * Retorna (ou cria) o ciclo da semana corrente.
     */
    public static function current(): self
    {
        return self::findOrCreateForDate(now());
    }
}
