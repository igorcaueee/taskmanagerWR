<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Emissão de certificado digital para um cliente (e-CNPJ / e-CPF).
 * Substitui a planilha manual de controle.
 */
class CertificadoEmissao extends Model
{
    protected $table = 'certificado_emissoes';

    protected $fillable = [
        'data_emissao',
        'cliente_id',
        'cliente_nome',
        'cliente_documento',
        'modelo',
        'numero_pedido',
        'forma_emissao',
        'valor',
        'pagamento',
        'situacao',
        'certificadora',
        'vencimento',
        'observacao',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'vencimento'   => 'date',
        'valor'        => 'decimal:2',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function getClienteWrAttribute(): bool
    {
        return ! is_null($this->cliente_id);
    }

    public function vencido(): bool
    {
        return $this->vencimento && $this->vencimento->isPast();
    }

    public function venceEm30Dias(): bool
    {
        return $this->vencimento
            && ! $this->vencido()
            && now()->diffInDays($this->vencimento) <= 30;
    }
}
