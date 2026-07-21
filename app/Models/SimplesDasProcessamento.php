<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log de execução do processamento mensal de DAS por cliente/período —
 * alimenta o painel de acompanhamento e evita retransmitir declaração já enviada.
 */
class SimplesDasProcessamento extends Model
{
    protected $table = 'simples_das_processamentos';

    protected $fillable = [
        'cliente_id',
        'periodo_apuracao',
        'status',
        'mensagem_erro',
        'numero_recibo',
        'das_pdf_path',
        'processado_em',
    ];

    protected $casts = [
        'processado_em' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
