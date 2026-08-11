<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dados fiscais do cliente como prestador de serviço na emissão de NFS-e
 * (DPS/infDPS/prest) — inscrição municipal, endereço e código IBGE.
 */
class ClienteDadosFiscaisNfse extends Model
{
    protected $table = 'cliente_dados_fiscais_nfse';

    protected $fillable = [
        'cliente_id',
        'inscricao_municipal',
        'codigo_municipio_ibge',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'proximo_numero_dps',
        'serie_dps',
    ];

    protected $casts = [
        'proximo_numero_dps' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function completo(): bool
    {
        return filled($this->inscricao_municipal)
            && filled($this->codigo_municipio_ibge)
            && filled($this->cep)
            && filled($this->logradouro)
            && filled($this->numero)
            && filled($this->bairro);
    }
}
