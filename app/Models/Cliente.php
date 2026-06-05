<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Cliente extends Authenticatable
{
    protected $table = 'clientes';

    protected $hidden = ['senha_portal', 'senha_portal_plain'];

    protected $fillable = [
        'nome',
        'logo',
        'pasta_arquivos',
        'segmentacao_id',
        'atividade',
        'descricao',
        'cpfcnpj',
        'tipo',
        'regime_tributario',
        'cidade',
        'estado',
        'status',
        'motivo_encerramento',
        'data_encerramento',
        'fator_r',
        'cliente_desde',
        'dataabertura',
        'vencimento_certificado',
        'faturamento',
        'servico',
        'honorario',
        'capital_social',
        'possibilidade',
        'senha_portal',
        'senha_portal_plain',
        'portal_ativo',
        'portal_ultimo_acesso',
        'acesso_extrato',
    ];

    protected $casts = [
        'vencimento_certificado' => 'date',
        'cliente_desde' => 'date',
        'dataabertura' => 'date',
        'data_encerramento' => 'date',
        'capital_social' => 'decimal:2',
        'portal_ativo' => 'boolean',
        'portal_ultimo_acesso' => 'datetime',
        'acesso_extrato' => 'boolean',
        'senha_portal_plain' => 'encrypted',
    ];

    /**
     * Mapeia o campo usado pelo guard de autenticação para a senha.
     */
    public function getAuthPassword(): string
    {
        return $this->senha_portal ?? '';
    }

    public function segmentacao(): BelongsTo
    {
        return $this->belongsTo(Segmentacao::class);
    }

    public function produtos(): BelongsToMany
    {
        return $this->belongsToMany(Produto::class, 'cliente_produto');
    }

    public function socios(): HasMany
    {
        return $this->hasMany(Socio::class)->orderBy('ordem');
    }

    public function contatoClientes(): HasMany
    {
        return $this->hasMany(ContatoCliente::class);
    }

    public function conhecimentos(): HasMany
    {
        return $this->hasMany(ClienteConhecimento::class)->orderByDesc('created_at');
    }

    public function portalUsuarios(): HasMany
    {
        return $this->hasMany(PortalUsuario::class);
    }
}
