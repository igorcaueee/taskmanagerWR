<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log de execução do cron de sincronização de NF-e/NFC-e/CT-e via SEFAZ-RS
 * por cliente/fase — alimenta o histórico de sucesso/erro de cada rodada.
 */
class SincronizacaoFiscalRs extends Model
{
    protected $table = 'sincronizacoes_fiscais_rs';

    protected $fillable = [
        'cliente_id',
        'fase',
        'status',
        'mensagem_erro',
        'executado_em',
    ];

    protected $casts = [
        'executado_em' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
