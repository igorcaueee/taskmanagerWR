<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'usuarios';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nome',
        'email',
        'senha',
        'cargo',
        'foto',
        'telefone',
        'sexo',
        'data_nascimento',
        'data_registro',
        'status',
        'departamento_id',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'google_calendar_id',
        'google_last_synced_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'data_nascimento' => 'date',
        'data_registro' => 'date',
        'google_token_expires_at' => 'datetime',
        'google_last_synced_at' => 'datetime',
    ];

    /**
     * Get the password for the user (Auth expects this).
     */
    public function getAuthPassword(): string
    {
        return (string) $this->senha;
    }

    /**
     * Return the password column name used by the framework.
     */
    public function getAuthPasswordName(): string
    {
        return 'senha';
    }

    /**
     * Provide a `name` attribute for compatibility with templates.
     */
    public function getNameAttribute(): ?string
    {
        return $this->nome;
    }

    /**
     * URL pública da foto do colaborador.
     */
    public function getFotoUrlAttribute(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }

    /**
     * Hide sensitive attributes when serializing.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'senha',
        'google_access_token',
        'google_refresh_token',
    ];

    public function isGoogleConnected(): bool
    {
        return ! empty($this->google_access_token);
    }

    public function isDiretor(): bool
    {
        return $this->cargo === 'diretor';
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class);
    }

    public function conversas(): BelongsToMany
    {
        return $this->belongsToMany(Conversa::class, 'conversa_participantes')
            ->withPivot(['ultima_mensagem_lida_id', 'lida_em', 'is_admin'])
            ->withTimestamps();
    }

    // Apenas Diretor vê o Funil de Vendas
    public function canVerFunil(): bool
    {
        return $this->cargo === 'diretor';
    }

    // Diretor, TI e Supervisor podem ver colaboradores
    public function canVerColaboradores(): bool
    {
        return in_array($this->cargo, ['diretor', 'ti', 'supervisor']);
    }

    // Diretor, TI, Supervisor e Supervisor Geral podem ver as tarefas de todos os usuários
    public function canVerTodasTarefas(): bool
    {
        return in_array($this->cargo, ['diretor', 'ti', 'supervisor', 'supervisor_geral']);
    }

    // Apenas Diretor e TI podem criar/editar/excluir colaboradores
    public function canEditarColaboradores(): bool
    {
        return in_array($this->cargo, ['diretor', 'ti']);
    }

    // Apenas Diretor vê o campo honorário (dados financeiros sensíveis)
    public function canVerHonorario(): bool
    {
        return $this->cargo === 'diretor';
    }

    // TI, Assistente, Auxiliar e Supervisor Geral não veem faturamento
    public function canVerFaturamento(): bool
    {
        return ! in_array($this->cargo, ['ti', 'assistente', 'auxiliar', 'supervisor_geral']);
    }

    // Assistente, Auxiliar e Supervisor Geral não podem criar/editar/excluir clientes
    public function canEditarClientes(): bool
    {
        return ! in_array($this->cargo, ['assistente', 'auxiliar', 'supervisor_geral']);
    }

    // Apenas Diretor gerencia produtos
    public function canGerenciarProdutos(): bool
    {
        return $this->cargo === 'diretor';
    }

    // Apenas Diretor gerencia possibilidades
    public function canGerenciarPossibilidades(): bool
    {
        return $this->cargo === 'diretor';
    }

    // Assistente, Auxiliar e Supervisor Geral só editam tarefas onde são responsáveis
    public function canEditarQualquerTarefa(): bool
    {
        return ! in_array($this->cargo, ['assistente', 'auxiliar', 'supervisor_geral']);
    }

    // Apenas Diretor, TI, Supervisor Geral ou o criador da tarefa podem inativar
    public function canInativarTarefa(Tarefa $tarefa): bool
    {
        return in_array($this->cargo, ['diretor', 'ti', 'supervisor_geral']) || (int) $tarefa->criado_por === (int) $this->id;
    }

    // Apenas Diretor e TI podem excluir tarefas permanentemente
    public function canExcluirTarefa(): bool
    {
        return in_array($this->cargo, ['diretor', 'ti']);
    }

    // Diretor e TI gerenciam o blog
    public function canGerenciarBlog(): bool
    {
        return in_array($this->cargo, ['diretor', 'ti']);
    }

    // Diretor e TI acessam o E-mail Marketing
    public function canEmailMarketing(): bool
    {
        return in_array($this->cargo, ['diretor', 'ti']);
    }

    // Apenas Diretor e TI podem renomear pastas da raiz (pastas de cliente)
    public function canRenomearPastaRaiz(): bool
    {
        return in_array($this->cargo, ['diretor', 'ti']);
    }

    // Diagnóstico IDE e Classificação — apenas Diretor e TI
    public function canVerClassificacao(): bool
    {
        return in_array($this->cargo, ['diretor', 'ti']);
    }

    // Assistente, Auxiliar e Supervisor Geral não veem o menu de Relatórios
    public function canVerRelatorios(): bool
    {
        return ! in_array($this->cargo, ['assistente', 'auxiliar', 'supervisor_geral']);
    }

    // Assistente, Auxiliar e Supervisor Geral não veem Produtos e Possibilidades no menu Cadastros
    public function canVerProdutosPossibilidades(): bool
    {
        return ! in_array($this->cargo, ['assistente', 'auxiliar', 'supervisor_geral']);
    }

    // Diretor e TI gerenciam restrições de acesso por rede e liberações externas
    public function canGerenciarAcessoExterno(): bool
    {
        return in_array($this->cargo, ['diretor', 'ti']);
    }
}
