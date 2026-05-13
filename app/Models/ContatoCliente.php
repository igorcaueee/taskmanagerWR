<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContatoCliente extends Model
{
    protected $table = 'contato_clientes';

    protected $fillable = [
        'cliente_id',
        'nome',
        'telefone',
        'gmail',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
