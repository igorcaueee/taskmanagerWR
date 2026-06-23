<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tarefa extends Model
{
    protected $table = 'tarefas';

    protected $fillable = [
        'titulo',
        'descricao',
        'tipo_tarefa_id',
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
        'data_fim_recorrencia',
        'ciclo_id',
        'passou_ciclo',
        'requer_envio_arquivo',
        'ativo',
        'inativado_por',
        'inativado_em',
    ];

    protected $casts = [
        'data_vencimento' => 'date',
        'data_conclusao' => 'datetime',
        'data_proxima_geracao' => 'date',
        'data_fim_recorrencia' => 'date',
        'inativado_em' => 'datetime',
        'atrasada' => 'boolean',
        'recorrente' => 'boolean',
        'passou_ciclo' => 'boolean',
        'requer_envio_arquivo' => 'boolean',
        'ativo' => 'boolean',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'tarefa_cliente');
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

    public function uploads(): HasMany
    {
        return $this->hasMany(TarefaUpload::class);
    }

    public function tipoTarefa(): BelongsTo
    {
        return $this->belongsTo(TipoTarefa::class);
    }

    public function inativadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'inativado_por');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }
}
