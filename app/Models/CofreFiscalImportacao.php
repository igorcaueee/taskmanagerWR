<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Acompanha o progresso de uma importação assíncrona de .zip no Cofre Fiscal
 * (ver ImportarCofreFiscalZipJob) — o front-end faz polling nesses campos
 * enquanto o Job roda na fila, em vez de esperar a requisição HTTP original.
 */
class CofreFiscalImportacao extends Model
{
    use HasFactory;

    // Sem isso o Eloquent pluraliza "CofreFiscalImportacao" (regra em inglês) pra
    // "cofre_fiscal_importacaos" — a migration criou "cofre_fiscal_importacoes" (plural
    // certo em português), então o Model ficava buscando uma tabela que não existe.
    protected $table = 'cofre_fiscal_importacoes';

    protected $fillable = [
        'cliente_id',
        'usuario_id',
        'arquivo_path',
        'arquivo_nome_original',
        'status',
        'total_xmls',
        'processados',
        'importados',
        'atualizados',
        'ignorados_invalidos',
        'ignorados_outro_cliente',
        'ignorados_cnpj_divergente',
        'erro',
        'iniciado_em',
        'finalizado_em',
    ];

    protected function casts(): array
    {
        return [
            'iniciado_em' => 'datetime',
            'finalizado_em' => 'datetime',
        ];
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}
