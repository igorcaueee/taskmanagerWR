<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteCertificadoNfse extends Model
{
    protected $table = 'cliente_certificados_nfse';

    protected $fillable = [
        'cliente_id',
        'arquivo',
        'senha',
        'ambiente',
        'ultimo_nsu',
        'vencimento',
    ];

    protected $casts = [
        'senha'      => 'encrypted',
        'vencimento' => 'date',
        'ultimo_nsu' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vencido(): bool
    {
        return $this->vencimento && $this->vencimento->isPast();
    }

    public function venceEm30Dias(): bool
    {
        return $this->vencimento
            && !$this->vencido()
            && $this->vencimento->greaterThan(now())
            && now()->diffInDays($this->vencimento) <= 30;
    }
}
