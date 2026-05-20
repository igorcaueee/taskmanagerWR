<?php

namespace App\Http\Controllers;

use App\Models\Artigo;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PortalController extends Controller
{
    public function dashboard(): View
    {
        /** @var Cliente $cliente */
        $cliente = Auth::guard('portal')->user();

        $artigos = Artigo::publicados()
            ->orderByDesc('publicado_em')
            ->limit(3)
            ->get();

        return view('portal.dashboard', compact('cliente', 'artigos'));
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
        /** @var Cliente $cliente */
        $cliente = Auth::guard('portal')->user();

        $arquivos = $this->listarArquivosCliente($cliente);

        return view('portal.arquivos', compact('cliente', 'arquivos'));
    }

    public function downloadArquivo(Request $request): BinaryFileResponse
    {
        /** @var Cliente $cliente */
        $cliente = Auth::guard('portal')->user();

        $filename = $request->query('file');

        $caminhoAbsoluto = $this->resolverCaminhoArquivo($cliente, $filename);

        abort_unless($caminhoAbsoluto !== null && file_exists($caminhoAbsoluto), 404);

        return response()->file($caminhoAbsoluto, [
            'Content-Disposition' => 'attachment; filename="'.basename($caminhoAbsoluto).'"',
        ]);
    }

    /**
     * Lista todos os arquivos da pasta Portal/ do cliente.
     *
     * @return array<int, array{nome: string, tamanho: string, modificado: string, extensao: string}>
     */
    private function listarArquivosCliente(Cliente $cliente): array
    {
        if (! $cliente->pasta_arquivos) {
            return [];
        }

        $pastaPortal = rtrim($cliente->pasta_arquivos, '/').'/Portal';

        if (! is_dir($pastaPortal)) {
            return [];
        }

        $arquivos = [];

        foreach (new \DirectoryIterator($pastaPortal) as $arquivo) {
            if ($arquivo->isDot() || $arquivo->isDir()) {
                continue;
            }

            $arquivos[] = [
                'nome' => $arquivo->getFilename(),
                'tamanho' => $this->formatarTamanho($arquivo->getSize()),
                'modificado' => date('d/m/Y H:i', $arquivo->getMTime()),
                'extensao' => strtolower($arquivo->getExtension()),
            ];
        }

        usort($arquivos, fn ($a, $b) => strcmp($a['nome'], $b['nome']));

        return $arquivos;
    }

    private function resolverCaminhoArquivo(Cliente $cliente, ?string $filename): ?string
    {
        if (! $filename || ! $cliente->pasta_arquivos) {
            return null;
        }

        // Previne path traversal
        $basename = basename($filename);

        $caminho = rtrim($cliente->pasta_arquivos, '/').'/Portal/'.$basename;

        return realpath($caminho) ?: null;
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
