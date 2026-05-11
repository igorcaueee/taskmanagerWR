<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Segmentacao extends Model
{
    protected $table = 'segmentacoes';

    protected $fillable = ['nome'];

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }
}
