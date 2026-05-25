<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;

class PortalUsuario extends Authenticatable
{
    use HasFactory;

    protected $table = 'portal_usuarios';

    protected $fillable = [
        'cliente_id',
        'nome',
        'username',
        'email',
        'telefone',
        'password',
        'ativo',
        'ultimo_acesso',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ultimo_acesso' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
