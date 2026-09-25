<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Files\Base64Document;
use Laravel\Ai\Files\Base64Image;
use Smalot\PdfParser\Parser as PdfParser;

use function Laravel\Ai\agent;

/**
 * Lê uma guia/documento enviado ao portal com IA e identifica tipo, titular (CNPJ/CPF),
 * código de receita, vencimento e valor, para pré-preencher o upload e conferir o cliente.
 */
class AnalisadorDocumentoService
{
    // Modelo leve: extrair campos de uma guia não precisa de raciocínio longo, e o flash "pensante" passava de 60s
    private const MODELO = 'gemini-3.1-flash-lite-preview';

    private const TIMEOUT_SEGUNDOS = 20;

    private const MIN_CARACTERES_TEXTO = 80;

    private const MAX_CARACTERES_TEXTO = 15000;

    private const EXTENSOES_PDF = ['pdf'];

    private const EXTENSOES_IMAGEM = ['jpg', 'jpeg', 'png', 'webp'];

    private const NOMES_MESES = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    private const INSTRUCOES = <<<'TXT'
Você analisa documentos enviados por um escritório de contabilidade brasileiro ao portal dos clientes.
Identifique o documento e extraia os dados pedidos, lendo apenas o que está impresso nele — nunca invente valores.

Regras:
- "tipo_arquivo": "pagamento" para qualquer guia/boleto/documento de arrecadação (DARF, DARF DCTFWeb, DAS, DAS-MEI, GPS, FGTS Digital/GFD, GRF, DAE do eSocial, GNRE, GA/guia estadual de ICMS, guia de ISS/DAM, boleto, parcelamento);
  "contrato_social" para contrato social, alteração contratual, requerimento de empresário, estatuto ou ato constitutivo;
  "informacao" para todo o resto (relatórios, declarações, recibos, certidões, balancetes, folha, notas fiscais etc.).
- "tipo_documento": nome curto do documento, ex.: "DARF", "DARF DCTFWeb", "DAS", "DAS-MEI", "GPS", "FGTS Digital (GFD)", "DAE eSocial", "GNRE", "Guia ICMS", "Guia ISS", "Boleto", "Contrato Social", "Alteração Contratual", "Certidão", "Recibo", "Relatório".
- "cnpj_cpf_titular": o CNPJ ou CPF do contribuinte/titular/empresa a quem o documento pertence (só dígitos). Numa guia é o contribuinte que paga, nunca o órgão arrecadador, o banco ou o escritório de contabilidade. Num contrato social é a empresa (não os sócios). Vazio se não houver.
- "nome_titular": razão social ou nome do titular, como impresso.
- "codigos_receita": todos os códigos de receita impressos na guia, só os dígitos antes do hífen (ex.: "2172", "8109", "1082"). Em DARF da DCTFWeb liste cada código da composição. Vazio se não houver.
- "descricao": resumo curto do que é, ex.: "DARF COFINS - Lucro Presumido", "DAS Simples Nacional", "Alteração contratual nº 3".
- "competencia": período de apuração/competência no formato "MM/AAAA" (vazio se não houver).
- "data_vencimento": data de vencimento/pagar até no formato "AAAA-MM-DD" (vazio se não houver).
- "valor": valor total a pagar em número (ex.: 1523.45); 0 se não houver.
TXT;

    /**
     * @return array{
     *     analisado: bool,
     *     motivo: ?string,
     *     tipo_arquivo: ?string,
     *     tipo_documento: ?string,
     *     descricao: ?string,
     *     codigos_receita: array<int, array{codigo: string, descricao: ?string}>,
     *     cnpj_cpf: ?string,
     *     nome_titular: ?string,
     *     cliente: ?array{id: int, nome: string, temPasta: bool, filial: bool},
     *     pasta_categoria: ?string,
     *     pasta_periodo: ?string,
     *     data_vencimento: ?string,
     *     valor: ?float,
     * }
     */
    public function analisar(UploadedFile $arquivo): array
    {
        $extensao = strtolower($arquivo->getClientOriginalExtension());

        $texto = '';

        if (in_array($extensao, self::EXTENSOES_PDF, true)) {
            $texto = $this->textoPdf($arquivo);
            $dados = mb_strlen($texto) >= self::MIN_CARACTERES_TEXTO ? $this->lerTextoLocal($texto) : null;

            // Guias geradas pelos sistemas (SENDA, PGDAS, DCTFWeb...) saem completas da leitura local em menos de 1s.
            // A IA (que leva de 10 a 30s) só entra quando falta algo; PDF escaneado vai como anexo para ela ler a imagem.
            if (! $dados || ! $this->leituraLocalCompleta($dados)) {
                try {
                    $dados = $dados
                        ? $this->lerComIa("Texto extraído do documento:\n\n".mb_substr($texto, 0, self::MAX_CARACTERES_TEXTO))
                        : $this->lerComIa('Analise o documento anexo e extraia os dados.', Base64Document::fromUpload($arquivo, 'application/pdf'));
                } catch (\Throwable $e) {
                    // A API do Gemini às vezes trava sem responder; com texto em mãos, fica com a leitura local parcial
                    if (! $dados) {
                        throw $e;
                    }
                    report($e);
                }
            }
        } elseif (in_array($extensao, self::EXTENSOES_IMAGEM, true)) {
            $dados = $this->lerComIa('Analise o documento anexo e extraia os dados.', Base64Image::fromUpload($arquivo));
        } else {
            return $this->semAnalise('Só PDFs e imagens são analisados automaticamente.');
        }

        $documento = $this->somenteDigitos($dados['cnpj_cpf_titular'] ?? '');

        // PDF com um único CNPJ/CPF impresso: se a IA leu um número diferente, confia no texto do arquivo
        if ($texto !== '' && $documento !== '') {
            $impressos = $this->documentosNoTexto($texto);
            if (count($impressos) === 1 && $impressos[0] !== $documento) {
                $documento = $impressos[0];
            }
        }

        $codigos = collect($dados['codigos_receita'] ?? [])
            ->map(fn ($c) => $this->normalizarCodigo((string) $c))
            ->filter()
            ->unique()
            ->values()
            ->map(fn (string $c) => ['codigo' => $c, 'descricao' => config("codigos_receita.{$c}.descricao")])
            ->all();

        $codigoConhecido = collect($codigos)->first(fn ($c) => $c['descricao'] !== null);
        $tabela = $codigoConhecido ? config("codigos_receita.{$codigoConhecido['codigo']}") : null;

        $tipoArquivo = $dados['tipo_arquivo'] ?? null;
        $tipoDocumento = trim((string) ($dados['tipo_documento'] ?? '')) ?: ($tabela['guia'] ?? null);

        $descricao = $tabela
            ? $tabela['guia'].' - '.$tabela['descricao'].' ('.$codigoConhecido['codigo'].')'
            : (trim((string) ($dados['descricao'] ?? '')) ?: $tipoDocumento);

        $cliente = $documento ? $this->buscarCliente($documento) : null;

        return [
            'analisado' => true,
            'motivo' => null,
            'tipo_arquivo' => in_array($tipoArquivo, ['pagamento', 'contrato_social', 'informacao'], true) ? $tipoArquivo : null,
            'tipo_documento' => $tipoDocumento,
            'descricao' => $descricao ? mb_substr($descricao, 0, 255) : null,
            'codigos_receita' => $codigos,
            'cnpj_cpf' => $documento ?: null,
            'nome_titular' => trim((string) ($dados['nome_titular'] ?? '')) ?: ($cliente['nome'] ?? null),
            'cliente' => $cliente,
            'pasta_categoria' => $tabela['pasta'] ?? $this->pastaPorTipo($tipoArquivo, $tipoDocumento),
            'pasta_periodo' => $this->periodoPorCompetencia((string) ($dados['competencia'] ?? '')),
            'data_vencimento' => $this->dataValida((string) ($dados['data_vencimento'] ?? '')),
            'valor' => ($dados['valor'] ?? 0) > 0 ? round((float) $dados['valor'], 2) : null,
        ];
    }

    /**
     * O modelo preview do Gemini às vezes responde "overloaded"; tenta de novo antes de desistir.
     */
    private function lerComIa(string $prompt, Base64Document|Base64Image|null $anexo = null): array
    {
        return retry(2, fn () => $this->chamarIa($prompt, $anexo), 1500, fn ($e) => $e instanceof ProviderOverloadedException);
    }

    private function chamarIa(string $prompt, Base64Document|Base64Image|null $anexo): array
    {
        return agent(
            instructions: self::INSTRUCOES,
            schema: fn (JsonSchema $schema) => [
                'tipo_arquivo' => $schema->string()->enum(['pagamento', 'contrato_social', 'informacao'])->required(),
                'tipo_documento' => $schema->string()->required(),
                'cnpj_cpf_titular' => $schema->string()->required(),
                'nome_titular' => $schema->string()->required(),
                'codigos_receita' => $schema->array()->items($schema->string())->required(),
                'descricao' => $schema->string()->required(),
                'competencia' => $schema->string()->required(),
                'data_vencimento' => $schema->string()->required(),
                'valor' => $schema->number()->required(),
            ],
        )->prompt(
            prompt: $prompt,
            attachments: $anexo ? [$anexo] : [],
            provider: 'gemini',
            model: self::MODELO,
            timeout: self::TIMEOUT_SEGUNDOS,
        )->toArray();
    }

    /**
     * Leitura sem IA para PDFs com texto: CNPJ/CPF impresso, códigos da tabela, vencimento, valor e competência.
     * Devolve as mesmas chaves que a IA.
     */
    private function lerTextoLocal(string $texto): array
    {
        $codigos = [];
        if (preg_match_all('/^\s*(\d{4}|\d{6})(?:-\d{2})?\s+\D/m', $texto, $m)) {
            $codigos = array_values(array_unique(array_filter($m[1], fn ($c) => config("codigos_receita.{$c}") !== null)));
        }

        $vencimento = '';
        if (preg_match('/(?:vencimento|pagar\s+(?:este\s+documento\s+)?at[ée])\D{0,40}?(\d{2})\/(\d{2})\/(\d{4})/iu', $texto, $m)) {
            $vencimento = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        $valor = 0.0;
        if (preg_match('/valor(?:\s+total)?(?:\s+do\s+documento)?\s*:?\s*(?:R\$\s*)?([\d.]+,\d{2})/iu', $texto, $m)) {
            $valor = (float) str_replace(',', '.', str_replace('.', '', $m[1]));
        }

        $competencia = '';
        $meses = array_map(fn ($mes) => mb_strtolower($mes), self::NOMES_MESES);
        if (preg_match('/\bPA\s*:?\s*(\d{2})\/(\d{4})/u', $texto, $m)) {
            $competencia = "{$m[1]}/{$m[2]}";
        } elseif (preg_match('/\b('.implode('|', $meses).')\/(\d{4})/iu', $texto, $m)) {
            $competencia = sprintf('%02d/%s', array_search(mb_strtolower($m[1]), $meses, true) + 1, $m[2]);
        }

        $textoMinusculo = mb_strtolower($texto);
        $tipoDocumento = match (true) {
            str_contains($textoMinusculo, 'receitas federais') => 'DARF',
            str_contains($textoMinusculo, 'simples nacional') && str_contains($textoMinusculo, 'arrecada') => 'DAS',
            str_contains($textoMinusculo, 'contrato social') || str_contains($textoMinusculo, 'alteração contratual') => 'Contrato Social',
            default => '',
        };

        $tipoArquivo = match (true) {
            $tipoDocumento === 'Contrato Social' => 'contrato_social',
            $codigos !== [] || $tipoDocumento !== '' || str_contains($textoMinusculo, 'arrecada') => 'pagamento',
            default => 'informacao',
        };

        return [
            'tipo_arquivo' => $tipoArquivo,
            'tipo_documento' => $tipoDocumento,
            'cnpj_cpf_titular' => $this->documentosNoTexto($texto)[0] ?? '',
            'nome_titular' => '',
            'codigos_receita' => $codigos,
            'descricao' => $tipoDocumento,
            'competencia' => $competencia,
            'data_vencimento' => $vencimento,
            'valor' => $valor,
        ];
    }

    /**
     * A leitura local basta quando identificou o titular e, para guias, o que é, quando vence e quanto custa.
     */
    private function leituraLocalCompleta(array $dados): bool
    {
        if ($dados['cnpj_cpf_titular'] === '' || $dados['tipo_documento'] === '') {
            return false;
        }

        return $dados['tipo_arquivo'] !== 'pagamento'
            || ($dados['data_vencimento'] !== '' && $dados['valor'] > 0);
    }

    /**
     * Compara o CNPJ/CPF do documento com o do cliente.
     * Retorna 'confere', 'filial' (mesma raiz de CNPJ), 'diverge' ou null quando não dá pra comparar.
     */
    public function conferirCliente(?string $documento, Cliente $cliente): ?string
    {
        $doCliente = $this->somenteDigitos($cliente->cpfcnpj ?? '');

        if (! $documento || ! $doCliente) {
            return null;
        }

        if ($documento === $doCliente) {
            return 'confere';
        }

        if (strlen($documento) === 14 && strlen($doCliente) === 14 && substr($documento, 0, 8) === substr($doCliente, 0, 8)) {
            return 'filial';
        }

        return 'diverge';
    }

    /**
     * @return array{id: int, nome: string, temPasta: bool, filial: bool}|null
     */
    private function buscarCliente(string $documento): ?array
    {
        $clientes = Cliente::query()->whereNotNull('cpfcnpj')->get(['id', 'nome', 'cpfcnpj', 'pasta_arquivos']);

        $exato = $clientes->first(fn (Cliente $c) => $this->somenteDigitos($c->cpfcnpj) === $documento);
        $filial = null;

        if (! $exato && strlen($documento) === 14) {
            $raiz = substr($documento, 0, 8);
            $filial = $clientes->first(function (Cliente $c) use ($raiz) {
                $digitos = $this->somenteDigitos($c->cpfcnpj);

                return strlen($digitos) === 14 && substr($digitos, 0, 8) === $raiz;
            });
        }

        $cliente = $exato ?? $filial;

        return $cliente ? [
            'id' => $cliente->id,
            'nome' => $cliente->nome,
            'temPasta' => filled($cliente->pasta_arquivos),
            'filial' => ! $exato,
        ] : null;
    }

    private function textoPdf(UploadedFile $arquivo): string
    {
        try {
            return (new PdfParser)->parseFile($arquivo->getRealPath())->getText();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * CNPJs/CPFs formatados impressos no texto, na ordem em que aparecem.
     *
     * @return array<int, string>
     */
    private function documentosNoTexto(string $texto): array
    {
        preg_match_all('/\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}|\d{3}\.\d{3}\.\d{3}-\d{2}/', $texto, $matches);

        return array_values(array_unique(array_map(fn ($m) => $this->somenteDigitos($m), $matches[0])));
    }

    private function normalizarCodigo(string $codigo): ?string
    {
        $base = $this->somenteDigitos(explode('-', $codigo)[0]);

        if ($base === '') {
            return null;
        }

        // DARF usa 4 dígitos com zero à esquerda (ex.: 0561); GNRE usa 6
        return strlen($base) < 4 ? str_pad($base, 4, '0', STR_PAD_LEFT) : $base;
    }

    private function pastaPorTipo(?string $tipoArquivo, ?string $tipoDocumento): ?string
    {
        $tipoDocumento = mb_strtolower((string) $tipoDocumento);

        return match (true) {
            $tipoArquivo === 'contrato_social' => 'Contabilidade',
            str_contains($tipoDocumento, 'fgts'), str_contains($tipoDocumento, 'gps'), str_contains($tipoDocumento, 'esocial'), str_contains($tipoDocumento, 'folha') => 'Pessoal',
            $tipoArquivo === 'pagamento' => 'Fiscal',
            default => null,
        };
    }

    private function periodoPorCompetencia(string $competencia): ?string
    {
        if (! preg_match('/^(\d{1,2})\/(\d{4})$/', trim($competencia), $m) || (int) $m[1] < 1 || (int) $m[1] > 12) {
            return null;
        }

        return sprintf('%02d - %s %s', $m[1], self::NOMES_MESES[(int) $m[1] - 1], $m[2]);
    }

    private function dataValida(string $data): ?string
    {
        try {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? Carbon::createFromFormat('Y-m-d', $data)->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function somenteDigitos(?string $valor): string
    {
        return preg_replace('/\D/', '', (string) $valor);
    }

    private function semAnalise(string $motivo): array
    {
        return [
            'analisado' => false,
            'motivo' => $motivo,
            'tipo_arquivo' => null,
            'tipo_documento' => null,
            'descricao' => null,
            'codigos_receita' => [],
            'cnpj_cpf' => null,
            'nome_titular' => null,
            'cliente' => null,
            'pasta_categoria' => null,
            'pasta_periodo' => null,
            'data_vencimento' => null,
            'valor' => null,
        ];
    }
}
