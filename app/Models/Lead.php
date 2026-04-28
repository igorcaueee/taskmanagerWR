<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $table = 'leads';

    protected $fillable = [
        'nome',
        'email',
        'telefone',
        'empresa',
        'tipo',
        'cpfcnpj',
        'faturamento',
        'honorario',
        'possibilidade',
        'etapa_funil_id',
        'responsavel_id',
        'origem',
        'observacoes',
        'convertido_cliente_id',
    ];

    protected $casts = [
        'faturamento' => 'decimal:2',
        'honorario' => 'decimal:2',
        'tipo' => 'integer',
    ];

    public function etapaFunil(): BelongsTo
    {
        return $this->belongsTo(EtapaFunil::class, 'etapa_funil_id');
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsavel_id');
    }

    public function convertidoCliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'convertido_cliente_id');
    }

    public function historico(): HasMany
    {
        return $this->hasMany(HistoricoFunil::class, 'lead_id');
    }

    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'lead_produto');
    }
}
