<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Socio extends Model
{
    protected $table = 'socios';

    protected $fillable = [
        'cliente_id',
        'ordem',
        'nome',
        'telefone',
        'gmail',
        'participacao',
        'quotas_integralizadas',
    ];

    protected $casts = [
        'participacao' => 'decimal:4',
        'quotas_integralizadas' => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
