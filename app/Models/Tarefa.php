<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarefa extends Model
{
    protected $table = 'tarefas';

    protected $fillable = [
        'titulo',
        'descricao',
        'cliente_id',
        'departamento_id',
        'etapa_id',
        'responsavel_id',
        'supervisor_id',
        'criado_por',
        'data_vencimento',
        'data_conclusao',
        'prioridade',
        'atrasada',
        'recorrente',
        'frequencia',
        'intervalo',
        'tarefa_original_id',
        'data_proxima_geracao',
        'ciclo_id',
        'passou_ciclo',
    ];

    protected $casts = [
        'data_vencimento' => 'date',
        'data_conclusao' => 'datetime',
        'data_proxima_geracao' => 'date',
        'atrasada' => 'boolean',
        'recorrente' => 'boolean',
        'passou_ciclo' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function etapa(): BelongsTo
    {
        return $this->belongsTo(Etapa::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsavel_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'supervisor_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }

    public function tarefaOriginal(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class, 'tarefa_original_id');
    }

    public function historico(): HasMany
    {
        return $this->hasMany(RelTarefa::class);
    }

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class);
    }
}
