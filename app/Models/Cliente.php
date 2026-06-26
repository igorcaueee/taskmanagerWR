<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Cliente extends Authenticatable
{
    use HasFactory;

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
        'senha_portal',
        'senha_portal_plain',
        'portal_ativo',
        'portal_ultimo_acesso',
        'acesso_extrato',
        // Conta Azul
        'conta_azul_conectada',
        'conta_azul_client_id',
        'conta_azul_access_token',
        'conta_azul_refresh_token',
        'conta_azul_token_expira_em',
        'conta_azul_ultima_sincronizacao',
    ];

    protected $casts = [
        'vencimento_certificado'        => 'date',
        'cliente_desde'                 => 'date',
        'dataabertura'                  => 'date',
        'data_encerramento'             => 'date',
        'capital_social'                => 'decimal:2',
        'portal_ativo'                  => 'boolean',
        'portal_ultimo_acesso'          => 'datetime',
        'acesso_extrato'                => 'boolean',
        'senha_portal_plain'            => 'encrypted',
        // Conta Azul
        'conta_azul_conectada'          => 'boolean',
        'conta_azul_access_token'       => 'encrypted',
        'conta_azul_refresh_token'      => 'encrypted',
        'conta_azul_token_expira_em'    => 'datetime',
        'conta_azul_ultima_sincronizacao' => 'datetime',
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

    public function possibilidades(): BelongsToMany
    {
        return $this->belongsToMany(Possibilidade::class, 'cliente_possibilidade');
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

    public function certificadoNfse(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClienteCertificadoNfse::class);
    }

    // ─── Conta Azul ──────────────────────────────────────────────────────────

    public function contasFinanceiras(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ContaFinanceira::class);
    }

    public function categoriasFinanceiras(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CategoriaFinanceira::class);
    }

    public function centrosCusto(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CentroCusto::class);
    }

    public function lancamentosFinanceiros(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LancamentoFinanceiro::class);
    }

    public function produtosFinanceiros(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProdutoFinanceiro::class);
    }

    public function contaAzulTokenExpirado(): bool
    {
        if (! $this->conta_azul_token_expira_em) {
            return true;
        }

        return $this->conta_azul_token_expira_em->isPast();
    }

    public function statusContaAzul(): string
    {
        if (! $this->conta_azul_conectada) {
            return 'desconectado';
        }

        if ($this->contaAzulTokenExpirado()) {
            return 'expirado';
        }

        return 'conectado';
    }
}
