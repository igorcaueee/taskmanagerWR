<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoTarefaRegra extends Model
{
    protected $table = 'tipo_tarefa_regra';

    protected $fillable = [
        'tipo_tarefa_id',
        'regime_tributario',
        'cnae_prefixos',
        'frequencia',
        'dia_vencimento',
        'departamento_id',
        'responsavel_id',
        'ativo',
    ];

    protected $casts = [
        'cnae_prefixos' => 'array',
        'ativo' => 'boolean',
    ];

    public function tipoTarefa(): BelongsTo
    {
        return $this->belongsTo(TipoTarefa::class);
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'responsavel_id');
    }

    /**
     * Verifica se esta regra se aplica a um cliente, considerando regime e CNAE.
     */
    public function aplicaAoCliente(Cliente $cliente): bool
    {
        if (! $this->ativo) {
            return false;
        }

        if ($this->regime_tributario
            && mb_strtoupper(trim($this->regime_tributario)) !== mb_strtoupper(trim((string) $cliente->regime_tributario))) {
            return false;
        }

        $prefixos = array_filter(array_map('trim', $this->cnae_prefixos ?? []));

        if (empty($prefixos)) {
            return true;
        }

        $cnaesCliente = collect([$cliente->cnae_principal])
            ->merge($cliente->cnae_secundarios ?? [])
            ->filter()
            ->map(fn ($c) => preg_replace('/\D/', '', (string) $c))
            ->all();

        foreach ($cnaesCliente as $cnae) {
            foreach ($prefixos as $prefixo) {
                $prefixoNum = preg_replace('/\D/', '', $prefixo);

                if ($prefixoNum !== '' && str_starts_with($cnae, $prefixoNum)) {
                    return true;
                }
            }
        }

        return false;
    }
}
