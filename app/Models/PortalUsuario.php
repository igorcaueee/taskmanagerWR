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
        'deve_trocar_senha',
        'ativo',
        'ultimo_acesso',
        'acesso_total',
        'pastas_permitidas',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'deve_trocar_senha' => 'boolean',
        'acesso_total' => 'boolean',
        'pastas_permitidas' => 'array',
        'ultimo_acesso' => 'datetime',
    ];

    public function temAcessoPasta(string $pasta): bool
    {
        if ($this->acesso_total) {
            return true;
        }

        return in_array($pasta, $this->pastas_permitidas ?? [], true);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function acessos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PortalUsuarioAcesso::class)->orderByDesc('created_at');
    }
}
