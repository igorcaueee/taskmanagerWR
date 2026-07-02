<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoRegimeTributario extends Model
{
    protected $table = 'historico_regime_tributario';

    protected $fillable = [
        'cliente_id',
        'regime_anterior',
        'regime_novo',
        'descricao',
        'alterado_por',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function alteradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'alterado_por');
    }
}
