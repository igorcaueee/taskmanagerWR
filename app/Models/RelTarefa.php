<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelTarefa extends Model
{
    protected $table = 'reltarefas';

    protected $fillable = [
        'tarefa_id',
        'etapa_anterior_id',
        'etapa_nova_id',
        'alterado_por',
    ];

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function etapaAnterior(): BelongsTo
    {
        return $this->belongsTo(Etapa::class, 'etapa_anterior_id');
    }

    public function etapaNova(): BelongsTo
    {
        return $this->belongsTo(Etapa::class, 'etapa_nova_id');
    }

    public function alteradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'alterado_por');
    }
}
