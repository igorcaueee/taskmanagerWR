<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Certificado digital da própria contabilidade (não do cliente), usado no
 * webservice NFeIntegracao (SEFAZ-RS) para baixar NF-e/NFC-e de todos os
 * clientes que autorizaram o acesso via e-CAC. Espera-se um único registro.
 */
class CertificadoContabilidade extends Model
{
    protected $table = 'certificados_contabilidade';

    protected $fillable = [
        'arquivo',
        'senha',
        'ambiente',
        'vencimento',
    ];

    protected $casts = [
        'senha'      => 'encrypted',
        'vencimento' => 'date',
    ];

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
