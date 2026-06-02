<?php

namespace App\Http\Controllers;

use App\Models\Artigo;
use App\Models\Cliente;
use App\Models\PortalUsuario;
use App\Models\TarefaUpload;
use App\Models\TarefaUploadEvento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortalController extends Controller
{
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

        return view('portal.arquivos', compact('cliente', 'arvore', 'portalUsuario'));
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
