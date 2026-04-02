<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Etapa extends Model
{
    protected $table = 'etapas';

    protected $fillable = ['nome', 'ordem', 'cor', 'visivel'];

    protected $casts = ['visivel' => 'boolean'];
}
