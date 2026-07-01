<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedePermitida extends Model
{
    use HasFactory;

    protected $table = 'redes_permitidas';

    protected $fillable = [
        'cidr',
        'descricao',
        'ativo',
        'criado_por',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por');
    }
}
