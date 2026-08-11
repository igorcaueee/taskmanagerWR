<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\NfseEmissao;
use App\Models\NfseEvento;
use App\Services\Concerns\AssinaXmlDigitalmente;
use App\Services\Concerns\GeraChaveNfse;
use App\Services\Concerns\LidaComCertificadoPfx;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orquestra a emissão de NFS-e via Sistema Nacional NFS-e: monta a DPS
 * (NfseDpsBuilderService), assina digitalmente (AssinaXmlDigitalmente) e envia
 * ao ADN (NfseService::enviarDps), persistindo o resultado em NfseEmissao.
 *
 * Pré-requisitos de um cliente para emitir: `dadosFiscaisNfse` completos
 * (ClienteDadosFiscaisNfse::completo()) e `certificadoNfse` válido (não vencido).
 */
class NfseEmissaoService
{
    use LidaComCertificadoPfx;
    use AssinaXmlDigitalmente;
    use GeraChaveNfse;

    public function __construct(
        private NfseService $nfseService,
        private NfseDpsBuilderService $dpsBuilder,
    ) {}

    /**
     * @param array $tomador ['tipo_doc','cpf_cnpj','nome','email','cep','logradouro','numero','complemento','bairro','codigo_municipio_ibge']
     * @param array $servico ['codigo_tributacao_nacional','descricao','codigo_municipio_prestacao']
     * @param array $valores ['valor_servico','aliquota','iss_retido','trib_issqn','desconto_incondicional','dcompet']
     */
    public function emitir(Cliente $cliente, array $tomador, array $servico, array $valores, ?string $chaveNfseSubstituida = null): NfseEmissao
    {
        $dadosFiscais = $cliente->dadosFiscaisNfse;
        $certificado  = $cliente->certificadoNfse;

        if (!$dadosFiscais || !$dadosFiscais->completo()) {
            throw new \RuntimeException('Dados fiscais do cliente incompletos para emissão de NFS-e (inscrição municipal/endereço/código IBGE).');
        }

        if (!$certificado) {
            throw new \RuntimeException('Cliente não possui certificado digital cadastrado.');
        }

        if ($certificado->vencido()) {
            throw new \RuntimeException('Certificado digital do cliente está vencido.');
        }

        $emissao = DB::transaction(function () use ($cliente, $tomador, $servico, $valores, $dadosFiscais, $certificado, $chaveNfseSubstituida) {
            $numero = $dadosFiscais->proximo_numero_dps;
            $dadosFiscais->increment('proximo_numero_dps');

            return NfseEmissao::create([
                'cliente_id' => $cliente->id,
                'ambiente' => $certificado->ambiente,
                'serie' => $dadosFiscais->serie_dps,
                'numero' => $numero,
                'status' => 'rascunho',
                'tomador_tipo_doc' => $tomador['tipo_doc'],
                'tomador_cpf_cnpj' => preg_replace('/\D/', '', $tomador['cpf_cnpj']),
                'tomador_nome' => $tomador['nome'],
                'tomador_email' => $tomador['email'] ?? null,
                'tomador_cep' => isset($tomador['cep']) ? preg_replace('/\D/', '', $tomador['cep']) : null,
                'tomador_logradouro' => $tomador['logradouro'] ?? null,
                'tomador_numero' => $tomador['numero'] ?? null,
                'tomador_complemento' => $tomador['complemento'] ?? null,
                'tomador_bairro' => $tomador['bairro'] ?? null,
                'tomador_codigo_municipio_ibge' => $tomador['codigo_municipio_ibge'] ?? null,
                'codigo_tributacao_nacional' => $servico['codigo_tributacao_nacional'],
                'descricao_servico' => $servico['descricao'],
                'codigo_municipio_prestacao' => $servico['codigo_municipio_prestacao'] ?? $dadosFiscais->codigo_municipio_ibge,
                'valor_servico' => $valores['valor_servico'],
                'aliquota' => $valores['aliquota'] ?? null,
                'iss_retido' => $valores['iss_retido'] ?? false,
                'trib_issqn' => $valores['trib_issqn'] ?? 1,
                'desconto_incondicional' => $valores['desconto_incondicional'] ?? null,
                'dcompet' => $valores['dcompet'],
                'chave_nfse_substituida' => $chaveNfseSubstituida,
            ]);
        });

        $certPath = storage_path('app/' . $certificado->arquivo);
        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $xmlDps = $this->dpsBuilder->montar($emissao, $dadosFiscais);
            $xmlAssinado = $this->assinarElemento($xmlDps, 'infDPS', 'id', $pemCert, $pemKey);

            $emissao->xml_dps = $xmlAssinado;

            $resposta = $this->nfseService->enviarDps($certificado, $xmlAssinado);

            $emissao->chave_acesso = $this->extrairChaveAcesso($resposta);
            $emissao->numero_nfse = $this->extrairNumeroNfse($resposta);
            $emissao->xml_nfse = $this->extrairXmlNfse($resposta);
            $emissao->status = $emissao->chave_acesso ? 'autorizada' : 'rejeitada';
            $emissao->erro_mensagem = $emissao->chave_acesso ? null : json_encode($resposta);
            $emissao->save();
        } catch (\Throwable $e) {
            Log::error('[NFS-e Emissão] Falha ao emitir', ['emissao_id' => $emissao->id, 'erro' => $e->getMessage()]);
            $emissao->status = 'rejeitada';
            $emissao->erro_mensagem = $e->getMessage();
            $emissao->save();
            throw $e;
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        return $emissao;
    }

    public function cancelar(NfseEmissao $emissao, string $motivo): NfseEvento
    {
        if ($emissao->status !== 'autorizada') {
            throw new \RuntimeException('Somente NFS-e autorizadas podem ser canceladas.');
        }

        $certificado = $emissao->cliente->certificadoNfse;

        if (!$certificado) {
            throw new \RuntimeException('Cliente não possui certificado digital cadastrado.');
        }

        $evento = NfseEvento::create([
            'nfse_emissao_id' => $emissao->id,
            'tipo_evento' => 'cancelamento',
            'motivo' => $motivo,
            'status' => 'enviado',
        ]);

        $certPath = storage_path('app/' . $certificado->arquivo);
        [$pemCert, $pemKey, $tempFiles] = $this->extrairPem($certPath, $certificado->senha);

        try {
            $xmlEvento = $this->montarXmlEventoCancelamento($emissao, $motivo, $certificado->ambiente);
            $xmlAssinado = $this->assinarElemento($xmlEvento, 'infPedidoRegEvento', 'id', $pemCert, $pemKey);

            $evento->xml_evento = $xmlAssinado;

            $resposta = $this->nfseService->enviarEvento($certificado, $emissao->chave_acesso, $xmlAssinado);

            $evento->resposta = json_encode($resposta);
            $evento->status = 'aceito';
            $evento->save();

            $emissao->status = 'cancelada';
            $emissao->save();
        } catch (\Throwable $e) {
            Log::error('[NFS-e Evento] Falha ao cancelar', ['emissao_id' => $emissao->id, 'erro' => $e->getMessage()]);
            $evento->status = 'rejeitado';
            $evento->resposta = $e->getMessage();
            $evento->save();
            throw $e;
        } finally {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }
        }

        return $evento;
    }

    public function substituir(NfseEmissao $emissaoOriginal, array $tomador, array $servico, array $valores): NfseEmissao
    {
        if ($emissaoOriginal->status !== 'autorizada') {
            throw new \RuntimeException('Somente NFS-e autorizadas podem ser substituídas.');
        }

        $novaEmissao = $this->emitir(
            $emissaoOriginal->cliente,
            $tomador,
            $servico,
            $valores,
            $emissaoOriginal->chave_acesso
        );

        if ($novaEmissao->status === 'autorizada') {
            $emissaoOriginal->status = 'substituida';
            $emissaoOriginal->save();
        }

        return $novaEmissao;
    }

    /**
     * NOTA: estrutura provisória — o Anexo II (leiaute de eventos) não foi
     * consultado ainda. Confirmar nomes de elementos/tipo de evento de
     * cancelamento antes de considerar este método pronto para produção.
     */
    private function montarXmlEventoCancelamento(NfseEmissao $emissao, string $motivo, string $ambiente): string
    {
        $cliente = $emissao->cliente;
        $documento = preg_replace('/\D/', '', $cliente->cpfcnpj);
        $ambienteCodigo = $ambiente === 'producao' ? '1' : '2';

        $dom = new \DOMDocument('1.0', 'UTF-8');

        $raiz = $dom->createElement('pedidoRegistroEvento');
        $raiz->setAttribute('versao', '1.00');
        $dom->appendChild($raiz);

        $inf = $dom->createElement('infPedidoRegEvento');
        $inf->setAttribute('id', 'PRE' . $emissao->chave_acesso . now()->format('YmdHis'));
        $raiz->appendChild($inf);

        $inf->appendChild($dom->createElement('tpAmb', $ambienteCodigo));
        $inf->appendChild($dom->createElement('verAplic', 'TaskManagerWR-1.0'));
        $inf->appendChild($dom->createElement('dhEvento', now()->toIso8601String()));
        $inf->appendChild($dom->createElement($cliente->tipo === 'PF' ? 'CPFAutor' : 'CNPJAutor', $documento));
        $inf->appendChild($dom->createElement('chNFSe', $emissao->chave_acesso));
        $inf->appendChild($dom->createElement('nPedRegEvento', '1'));

        $evento = $dom->createElement('eCancelamento');
        $evento->appendChild($dom->createElement('cMotivo', '99'));
        $evento->appendChild($dom->createElement('xMotivo', htmlspecialchars($motivo, ENT_XML1)));
        $inf->appendChild($evento);

        return $dom->saveXML();
    }
}
