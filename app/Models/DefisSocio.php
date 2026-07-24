<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um sócio dentro de uma DefisDeclaracao.
 */
class DefisSocio extends Model
{
    protected $table = 'defis_socios';

    protected $fillable = [
        'defis_declaracao_id',
        'cpf',
        'rendimentos_isentos',
        'rendimentos_tributaveis',
        'participacao_capital_social',
        'ir_retido_fonte',
    ];

    public function declaracao(): BelongsTo
    {
        return $this->belongsTo(DefisDeclaracao::class, 'defis_declaracao_id');
    }
}
