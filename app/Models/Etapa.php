<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etapa extends Model
{
    protected $table = 'etapas';

    protected $fillable = ['nome', 'ordem', 'cor', 'visivel', 'computa_tempo_trabalho'];

    protected $casts = [
        'visivel' => 'boolean',
        'computa_tempo_trabalho' => 'boolean',
    ];
}
