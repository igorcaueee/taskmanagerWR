<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nome',
        'descricao',
        'cpfcnpj',
        'regime_tributario',
        'cidade',
        'estado',
        'status',
        'cliente_desde',
        'dataabertura',
    ];
}
