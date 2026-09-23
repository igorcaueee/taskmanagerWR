<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um registro por campo alterado numa edição de St RegraCest pela tela
 * "Regras cadastradas" — ver App\Http\Controllers\IcmsStRegraController.
 */
class StRegraCestHistorico extends Model
{
    protected $table = 'st_regras_cest_historico';

    protected $fillable = [
        'st_regra_cest_id',
        'campo',
        'valor_anterior',
        'valor_novo',
        'editado_por',
        'editado_em',
    ];

    protected $casts = [
        'editado_em' => 'datetime',
    ];

    public function regra(): BelongsTo
    {
        return $this->belongsTo(StRegraCest::class, 'st_regra_cest_id');
    }
}
