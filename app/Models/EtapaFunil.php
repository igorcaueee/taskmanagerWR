<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtapaFunil extends Model
{
    protected $table = 'etapas_funil';

    protected $fillable = [
        'nome',
        'ordem',
        'cor',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'etapa_funil_id');
    }
}
