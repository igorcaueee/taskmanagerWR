<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoFunil extends Model
{
    protected $table = 'historico_funil';

    protected $fillable = [
        'lead_id',
        'etapa_anterior_id',
        'etapa_nova_id',
        'descricao',
        'alterado_por',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function etapaAnterior(): BelongsTo
    {
        return $this->belongsTo(EtapaFunil::class, 'etapa_anterior_id');
    }

    public function etapaNova(): BelongsTo
    {
        return $this->belongsTo(EtapaFunil::class, 'etapa_nova_id');
    }

    public function alteradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'alterado_por');
    }
}
