<?php

namespace App\Http\Controllers;

use App\Models\Artigo;
use App\Models\Cliente;
use App\Models\PortalUsuario;
use App\Models\TarefaUpload;
use App\Models\TarefaUploadEvento;
use App\Services\Base44DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortalController extends Controller
{
    public function __construct(private readonly Base44DashboardService $base44DashboardService)
    {
    }

    public function dashboard(): View
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        $artigos = Artigo::publicados()
            ->orderByDesc('publicado_em')
            ->limit(3)
            ->get();

        return view('portal.dashboard', compact('cliente', 'artigos', 'portalUsuario'));
    }

    public function blog(Request $request): View
    {
        $artigos = Artigo::publicados()
            ->when($request->filled('busca'), fn ($q) => $q->where('titulo', 'like', '%'.$request->busca.'%'))
            ->orderByDesc('publicado_em')
            ->paginate(12)
            ->withQueryString();

        return view('portal.blog.index', compact('artigos'));
    }

    public function artigoShow(string $slug): View
    {
        $artigo = Artigo::publicados()->where('slug', $slug)->firstOrFail();

        return view('portal.blog.show', compact('artigo'));
    }

    public function arquivos(): View
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        $arvore = $this->listarArquivosCliente($cliente, $portalUsuario);

        // Enriquecer arquivos com metadados do banco
        $uploads = TarefaUpload::where('cliente_id', $cliente->id)
            ->get()
            ->keyBy('arquivo_nome');

        return view('portal.arquivos', compact('cliente', 'arvore', 'portalUsuario', 'uploads'));
    }

    public function dashboardFiscal(): View
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        $resultado = $cliente->cpfcnpj
            ? $this->base44DashboardService->buscarDashboardFiscal($cliente->cpfcnpj)
            : ['ok' => false, 'data' => null, 'error' => 'Cliente sem CNPJ cadastrado.'];

        $dashboard = $resultado['data'] ? $this->base44DashboardService->normalizar($resultado['data']) : null;

        return view('portal.dashboard-fiscal', [
            'cliente' => $cliente,
            'portalUsuario' => $portalUsuario,
            'dashboard' => $dashboard,
            'erro' => $resultado['error'],
        ]);
    }

    public function enviarArquivoCliente(Request $request): JsonResponse
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        abort_unless($cliente->pode_enviar_documentos, 403);

        $validator = validator($request->all(), [
            'arquivo' => ['required', 'file', 'max:51200'],
            'pasta_categoria' => ['required', 'string', 'in:Contabilidade,Financeiro,Fiscal,Patrimônio,Pessoal'],
            'pasta_periodo' => ['required', 'string', 'max:50', 'regex:/^[\w\s\-\.]+$/u'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        if (! $cliente->pasta_arquivos) {
            return response()->json(['error' => 'Cliente sem pasta de arquivos configurada.'], 422);
        }

        $arquivo = $request->file('arquivo');
        $nomeOriginal = $arquivo->getClientOriginalName();
        $categoria = $request->input('pasta_categoria');
        $periodo = $request->input('pasta_periodo');

        $sharedRoot = rtrim(Storage::disk('shared')->path(''), '/');
        $pastaPortal = $sharedRoot.'/'.rtrim($cliente->pasta_arquivos, '/').'/Portal/'.$categoria.'/'.$periodo;

        if (! is_dir($pastaPortal)) {
            mkdir($pastaPortal, 0775, true);
        }

        $nomeBase = pathinfo($nomeOriginal, PATHINFO_FILENAME);
        $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
        $nomeArquivo = $nomeOriginal;

        if (file_exists($pastaPortal.'/'.$nomeArquivo)) {
            $nomeArquivo = $nomeBase.'_'.time().($extensao ? '.'.$extensao : '');
        }

        $destinoAbsoluto = $pastaPortal.'/'.$nomeArquivo;
        $arquivo->move($pastaPortal, $nomeArquivo);

        $caminhoDB = rtrim($cliente->pasta_arquivos, '/').'/Portal/'.$categoria.'/'.$periodo.'/'.$nomeArquivo;

        TarefaUpload::create([
            'cliente_id' => $cliente->id,
            'origem' => 'cliente',
            'enviado_por_portal_usuario_id' => $portalUsuario->id,
            'arquivo_nome' => $nomeArquivo,
            'arquivo_path' => $caminhoDB,
            'pasta_categoria' => $categoria,
            'pasta_periodo' => $periodo,
            'tamanho' => file_exists($destinoAbsoluto) ? filesize($destinoAbsoluto) : 0,
            'mime_type' => $arquivo->getClientMimeType(),
        ]);

        return response()->json(['success' => true, 'nome' => $nomeArquivo]);
    }

    public function agenda(Request $request): View
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        $mes = $request->integer('mes', now()->month);
        $ano = $request->integer('ano', now()->year);

        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        $primeiroDia = Carbon::create($ano, $mes, 1);
        $ultimoDia = $primeiroDia->copy()->endOfMonth();

        // Constrói as semanas do mês (domingo a sábado)
        $semanas = [];
        $diaInicio = $primeiroDia->copy()->startOfWeek(Carbon::SUNDAY);
        $diaFim = $ultimoDia->copy()->endOfWeek(Carbon::SATURDAY);
        $dia = $diaInicio->copy();

        while ($dia <= $diaFim) {
            $semana = [];
            for ($i = 0; $i < 7; $i++) {
                $semana[] = $dia->copy();
                $dia->addDay();
            }
            $semanas[] = $semana;
        }

        $mesAnterior = $primeiroDia->copy()->subMonth();
        $proximoMes = $primeiroDia->copy()->addMonth();

        // Eventos do intervalo exibido no calendário (inclui dias de meses adjacentes nas bordas)
        $eventosPorDia = TarefaUpload::where('cliente_id', $cliente->id)
            ->whereNotNull('data_vencimento')
            ->whereBetween('data_vencimento', [$diaInicio->toDateString(), $diaFim->toDateString()])
            ->orderBy('data_vencimento')
            ->get()
            ->groupBy(fn (TarefaUpload $u) => $u->data_vencimento->format('Y-m-d'));

        $eventosJson = $eventosPorDia->map(fn ($eventos) => $eventos->map(fn (TarefaUpload $e) => [
            'id' => $e->id,
            'nome' => $e->arquivo_nome,
            'tipo' => $e->tipo_arquivo,
            'tipoLabel' => $e->labelTipoArquivo(),
            'valor' => $e->valor,
            'vencimento' => $e->data_vencimento?->format('d/m/Y'),
            'pago' => $e->foiPago(),
            'pagoEm' => $e->pago_em?->format('d/m/Y H:i'),
            'vencido' => $e->estaVencido(),
            'categoria' => $e->pasta_categoria,
            'periodo' => $e->pasta_periodo,
            'path' => trim(($e->pasta_categoria ?? '').'/'.($e->pasta_periodo ?? '').'/'.$e->arquivo_nome, '/'),
        ]));

        return view('portal.agenda', compact(
            'cliente', 'portalUsuario', 'primeiroDia', 'semanas', 'mesAnterior', 'proximoMes', 'eventosPorDia', 'eventosJson',
        ));
    }

    public function marcarPago(Request $request, TarefaUpload $upload): JsonResponse
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();

        abort_unless($upload->cliente_id === $portalUsuario->cliente_id, 403);
        abort_unless($upload->tipo_arquivo === 'pagamento', 422);

        if ($upload->pago_em) {
            return response()->json(['error' => 'Arquivo já marcado como pago.'], 422);
        }

        $upload->update([
            'pago_em' => now(),
            'pago_por' => $portalUsuario->id,
        ]);

        return response()->json(['success' => true, 'pago_em' => $upload->pago_em->format('d/m/Y H:i')]);
    }

    public function downloadArquivo(Request $request): BinaryFileResponse
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        $filename = $request->query('file');

        $caminhoAbsoluto = $this->resolverCaminhoArquivo($cliente, $filename);

        abort_unless($caminhoAbsoluto !== null && file_exists($caminhoAbsoluto), 404);

        // Registrar download no histórico de uploads
        $basename = basename($caminhoAbsoluto);
        $upload = TarefaUpload::where('cliente_id', $cliente->id)
            ->where('arquivo_nome', $basename)
            ->first();

        if ($upload) {
            TarefaUploadEvento::create([
                'tarefa_upload_id' => $upload->id,
                'portal_usuario_id' => $portalUsuario->id,
                'tipo' => 'baixou',
                'created_at' => now(),
            ]);

            if (! $upload->baixado_em) {
                $upload->update(['baixado_em' => now(), 'baixado_por' => $portalUsuario->id]);
            }
        }

        return response()->file($caminhoAbsoluto, [
            'Content-Disposition' => 'attachment; filename="'.basename($caminhoAbsoluto).'"',
        ]);
    }

    public function visualizarArquivo(Request $request): BinaryFileResponse
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        $filename = $request->query('file');

        $caminhoAbsoluto = $this->resolverCaminhoArquivo($cliente, $filename);

        abort_unless($caminhoAbsoluto !== null && file_exists($caminhoAbsoluto), 404);

        // Registrar visualização no histórico de uploads (sempre)
        $basename = basename($caminhoAbsoluto);
        $upload = TarefaUpload::where('cliente_id', $cliente->id)
            ->where('arquivo_nome', $basename)
            ->first();

        if ($upload) {
            TarefaUploadEvento::create([
                'tarefa_upload_id' => $upload->id,
                'portal_usuario_id' => $portalUsuario->id,
                'tipo' => 'visualizou',
                'created_at' => now(),
            ]);

            if (! $upload->visualizado_em) {
                $upload->update(['visualizado_em' => now(), 'visualizado_por' => $portalUsuario->id]);
            }
        }

        return response()->file($caminhoAbsoluto, [
            'Content-Disposition' => 'inline; filename="'.$basename.'"',
        ]);
    }

    /**
     * Lista arquivos organizados por categoria > período.
     *
     * @return array<string, array<string, array<int, array{nome: string, tamanho: string, modificado: string, extensao: string, path: string}>>>
     */
    private function listarArquivosCliente(Cliente $cliente, ?PortalUsuario $portalUsuario = null): array
    {
        if (! $cliente->pasta_arquivos) {
            return [];
        }

        $sharedRoot = rtrim(Storage::disk('shared')->path(''), '/');
        $pastaPortal = $sharedRoot.'/'.rtrim($cliente->pasta_arquivos, '/').'/Portal';

        if (! is_dir($pastaPortal)) {
            return [];
        }

        $categorias = ['Contabilidade', 'Financeiro', 'Fiscal', 'Patrimônio', 'Pessoal'];
        $arvore = [];

        foreach ($categorias as $categoria) {
            // Filtrar por permissão de pasta quando o usuário não tem acesso total
            if ($portalUsuario !== null && ! $portalUsuario->temAcessoPasta($categoria)) {
                continue;
            }

            $pastaCategoria = $pastaPortal.'/'.$categoria;

            if (! is_dir($pastaCategoria)) {
                continue;
            }

            $arvore[$categoria] = [];

            foreach (new \DirectoryIterator($pastaCategoria) as $periodoDir) {
                if ($periodoDir->isDot() || ! $periodoDir->isDir()) {
                    continue;
                }

                $periodo = $periodoDir->getFilename();
                $pastaPeriodo = $pastaCategoria.'/'.$periodo;
                $arquivos = [];

                foreach (new \DirectoryIterator($pastaPeriodo) as $arquivo) {
                    if ($arquivo->isDot() || $arquivo->isDir()) {
                        continue;
                    }

                    $arquivos[] = [
                        'nome' => $arquivo->getFilename(),
                        'tamanho' => $this->formatarTamanho($arquivo->getSize()),
                        'modificado' => date('d/m/Y H:i', $arquivo->getMTime()),
                        'extensao' => strtolower($arquivo->getExtension()),
                        'path' => $categoria.'/'.$periodo.'/'.$arquivo->getFilename(),
                    ];
                }

                if (! empty($arquivos)) {
                    usort($arquivos, fn ($a, $b) => strcmp($a['nome'], $b['nome']));
                    $arvore[$categoria][$periodo] = $arquivos;
                }
            }

            // Ordenar períodos (mais recente primeiro usando nome do diretório)
            if (! empty($arvore[$categoria])) {
                krsort($arvore[$categoria]);
            } else {
                unset($arvore[$categoria]);
            }
        }

        return $arvore;
    }

    private function resolverCaminhoArquivo(Cliente $cliente, ?string $filename): ?string
    {
        if (! $filename || ! $cliente->pasta_arquivos) {
            return null;
        }

        $sharedRoot = rtrim(Storage::disk('shared')->path(''), '/');
        $pastaPortalBase = $sharedRoot.'/'.rtrim($cliente->pasta_arquivos, '/').'/Portal';

        // Normaliza separadores e resolve o caminho real
        $caminhoRelativo = ltrim(str_replace(['\\', '..'], ['/', ''], $filename), '/');
        $caminhoAbsoluto = $pastaPortalBase.'/'.$caminhoRelativo;
        $caminhoReal = realpath($caminhoAbsoluto);

        // Garante que o caminho resolvido está dentro da pasta Portal do cliente (previne path traversal)
        $pastaPortalReal = realpath($pastaPortalBase);
        if (! $caminhoReal || ! $pastaPortalReal || ! str_starts_with($caminhoReal, $pastaPortalReal.'/')) {
            return null;
        }

        return $caminhoReal;
    }

    private function formatarTamanho(int $bytes): string
    {
        if ($bytes >= 1_048_576) {
            return number_format($bytes / 1_048_576, 1).' MB';
        }

        if ($bytes >= 1_024) {
            return number_format($bytes / 1_024, 1).' KB';
        }

        return $bytes.' B';
    }
}
