<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NfseEmissao extends Model
{
    protected $table = 'nfse_emissoes';

    protected $fillable = [
        'cliente_id',
        'ambiente',
        'serie',
        'numero',
        'status',
        'tomador_tipo_doc',
        'tomador_cpf_cnpj',
        'tomador_nome',
        'tomador_email',
        'tomador_cep',
        'tomador_logradouro',
        'tomador_numero',
        'tomador_complemento',
        'tomador_bairro',
        'tomador_codigo_municipio_ibge',
        'codigo_tributacao_nacional',
        'descricao_servico',
        'codigo_municipio_prestacao',
        'valor_servico',
        'aliquota',
        'valor_iss',
        'iss_retido',
        'trib_issqn',
        'desconto_incondicional',
        'dcompet',
        'chave_acesso',
        'numero_nfse',
        'xml_dps',
        'xml_nfse',
        'erro_mensagem',
        'chave_nfse_substituida',
    ];

    protected $casts = [
        'valor_servico' => 'decimal:2',
        'aliquota' => 'decimal:2',
        'valor_iss' => 'decimal:2',
        'desconto_incondicional' => 'decimal:2',
        'iss_retido' => 'boolean',
        'trib_issqn' => 'integer',
        'dcompet' => 'date',
        'numero' => 'integer',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(NfseEvento::class);
    }
}
