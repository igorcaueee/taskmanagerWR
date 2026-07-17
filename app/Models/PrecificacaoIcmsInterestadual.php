<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecificacaoIcmsInterestadual extends Model
{
    protected $table = 'precificacao_icms_interestadual';

    protected $fillable = [
        'uf_origem',
        'uf_destino',
        'aliquota',
    ];

    protected $casts = [
        'aliquota' => 'decimal:2',
    ];
}
