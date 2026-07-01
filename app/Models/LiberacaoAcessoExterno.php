<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiberacaoAcessoExterno extends Model
{
    use HasFactory;

    protected $table = 'liberacoes_acesso_externo';

    protected $fillable = [
        'usuario_id',
        'liberado_por_id',
        'expira_em',
        'motivo',
        'ativo',
    ];

    protected $casts = [
        'expira_em' => 'datetime',
        'ativo' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function liberadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'liberado_por_id');
    }

    public function estaValida(): bool
    {
        return $this->ativo && ($this->expira_em === null || $this->expira_em->isFuture());
    }
}
