<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @property-read PortalUsuario|null $visualizadoPor */
/** @property-read PortalUsuario|null $baixadoPor */
class TarefaUpload extends Model
{
    protected $table = 'tarefa_uploads';

    protected $fillable = [
        'tarefa_id',
        'cliente_id',
        'origem',
        'enviado_por',
        'enviado_por_portal_usuario_id',
        'arquivo_nome',
        'arquivo_path',
        'pasta_categoria',
        'pasta_periodo',
        'tipo_arquivo',
        'descricao_documento',
        'data_vencimento',
        'valor',
        'pago_em',
        'pago_por',
        'tamanho',
        'mime_type',
        'baixado_em',
        'baixado_por',
        'visualizado_em',
        'visualizado_por',
    ];

    protected $casts = [
        'baixado_em' => 'datetime',
        'visualizado_em' => 'datetime',
        'pago_em' => 'datetime',
        'data_vencimento' => 'date',
        'valor' => 'decimal:2',
        'tamanho' => 'integer',
    ];

    public function tarefa(): BelongsTo
    {
        return $this->belongsTo(Tarefa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'enviado_por');
    }

    public function enviadoPorPortalUsuario(): BelongsTo
    {
        return $this->belongsTo(PortalUsuario::class, 'enviado_por_portal_usuario_id');
    }

    public function visualizadoPor(): BelongsTo
    {
        return $this->belongsTo(PortalUsuario::class, 'visualizado_por');
    }

    public function baixadoPor(): BelongsTo
    {
        return $this->belongsTo(PortalUsuario::class, 'baixado_por');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(TarefaUploadEvento::class, 'tarefa_upload_id')->orderBy('created_at');
    }

    public function pagoPor(): BelongsTo
    {
        return $this->belongsTo(PortalUsuario::class, 'pago_por');
    }

    public function foiBaixado(): bool
    {
        return ! is_null($this->baixado_em);
    }

    public function foiEnviadoPeloCliente(): bool
    {
        return $this->origem === 'cliente';
    }

    public function foiVisualizado(): bool
    {
        return ! is_null($this->visualizado_em);
    }

    public function foiPago(): bool
    {
        return ! is_null($this->pago_em);
    }

    public function estaVencido(): bool
    {
        // Vence hoje ainda não é vencido: isPast() comparava com a meia-noite e marcava o dia do vencimento como atrasado
        return $this->data_vencimento && ! $this->foiPago() && $this->data_vencimento->lt(today());
    }

    public function venceHoje(): bool
    {
        return $this->data_vencimento && ! $this->foiPago() && $this->data_vencimento->isToday();
    }

    /**
     * Tag "Novo" no portal: enviado pela WR nos últimos 2 dias e ainda não aberto nem baixado pelo cliente.
     */
    public function ehNovo(): bool
    {
        return ! $this->foiEnviadoPeloCliente()
            && ! $this->foiVisualizado()
            && ! $this->foiBaixado()
            && $this->created_at?->gte(now()->subDays(2));
    }

    public function labelTipoArquivo(): string
    {
        return match ($this->tipo_arquivo) {
            'pagamento' => 'Pagamento',
            'contrato_social' => 'Contrato Social',
            'informacao' => 'Informação',
            default => '—',
        };
    }

    public function tamanhoFormatado(): string
    {
        if ($this->tamanho >= 1_048_576) {
            return number_format($this->tamanho / 1_048_576, 1).' MB';
        }

        if ($this->tamanho >= 1_024) {
            return number_format($this->tamanho / 1_024, 1).' KB';
        }

        return $this->tamanho.' B';
    }
}
