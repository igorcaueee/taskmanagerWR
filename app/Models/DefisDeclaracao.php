<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * DEFIS (declaração anual do Simples Nacional) em rascunho ou já transmitida
 * — 1 registro por cliente+ano, suporta só um estabelecimento (a matriz).
 */
class DefisDeclaracao extends Model
{
    protected $table = 'defis_declaracoes';

    protected $fillable = [
        'cliente_id',
        'ano_calendario',
        'inatividade',
        'ganho_capital',
        'qtd_empregado_inicial',
        'qtd_empregado_final',
        'lucro_contabil',
        'receita_exportacao_direta',
        'participacao_cotas_tesouraria',
        'ganho_renda_variavel',
        'estoque_inicial',
        'estoque_final',
        'saldo_caixa_inicial',
        'saldo_caixa_final',
        'aquisicoes_mercado_interno',
        'importacoes',
        'total_entradas_por_transferencia',
        'total_saidas_por_transferencia',
        'total_devolucoes_vendas',
        'total_entradas',
        'total_devolucoes_compras',
        'total_despesas',
        'iss_retidos_fonte',
        'prestacoes_servico_comunicacao',
        'prestacoes_servico_transporte',
        'status',
        'id_defis',
        'mensagem_erro',
        'transmitido_em',
    ];

    protected $casts = [
        'transmitido_em' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function socios(): HasMany
    {
        return $this->hasMany(DefisSocio::class);
    }
}
