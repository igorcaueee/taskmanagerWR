<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChamadoDp extends Model
{
    protected $table = 'chamados_dp';

    protected $fillable = [
        'tarefa_id',
        'cliente_id',
        'portal_usuario_id',
        'tipo',
        'nome_colaborador',
        'cpf',
        'cargo_funcao',
        'data_evento',
        'motivo',
        'observacoes',
    ];

    protected $casts = [
        'data_evento' => 'date',
    ];

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function portalUsuario(): BelongsTo
    {
        return $this->belongsTo(PortalUsuario::class);
    }

    public function labelTipo(): string
    {
        return match ($this->tipo) {
            'admissao' => 'Admissão',
            'demissao' => 'Demissão',
            default => '—',
        };
    }
}
