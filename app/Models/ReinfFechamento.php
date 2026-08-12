<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReinfFechamento extends Model
{
    protected $table = 'reinf_fechamentos';

    protected $fillable = [
        'cliente_id',
        'tipo_evento',
        'periodo_apuracao',
        'ambiente',
        'status',
        'id_evento',
        'numero_protocolo',
        'numero_recibo',
        'cd_resposta',
        'xml_evento',
        'xml_retorno',
        'erro_mensagem',
    ];

    protected $casts = [
        'cd_resposta' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
