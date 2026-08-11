<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfseEvento extends Model
{
    protected $table = 'nfse_eventos';

    protected $fillable = [
        'nfse_emissao_id',
        'tipo_evento',
        'motivo',
        'xml_evento',
        'resposta',
        'status',
    ];

    public function emissao(): BelongsTo
    {
        return $this->belongsTo(NfseEmissao::class, 'nfse_emissao_id');
    }
}
