<?php

namespace App\Http\Controllers;

use App\Mail\ArquivoCompartilhadoMail;
use App\Models\Cliente;
use App\Models\PortalUsuario;
use App\Models\TarefaUpload;
use App\Models\TarefaUploadEvento;
use App\Models\Usuario;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileExplorerController extends Controller
{
    private function disk(): FilesystemAdapter
    {
        return Storage::disk('shared');
    }

    /**
     * Sanitise the requested path so it never escapes the shared root.
     */
    private function safePath(?string $path): string
    {
        $path = trim($path ?? '', '/');
        $path = str_replace('\\', '/', $path);

        // Remove any ".." segments to prevent directory traversal
        $segments = array_filter(explode('/', $path), function (string $seg): bool {
            return $seg !== '' && $seg !== '.' && $seg !== '..';
        });

        return implode('/', $segments);
    }

    public function index(Request $request): View
    {
        $path = $this->safePath($request->query('path'));

        $disk = $this->disk();

        if (! $disk->directoryExists($path === '' ? '.' : $path) && $path !== '') {
            abort(404);
        }

        $directories = collect($disk->directories($path))->map(function (string $dir): ?array {
            try {
                return [
                    'name' => basename($dir),
                    'path' => $dir,
                    'type' => 'folder',
                    'size' => null,
                    'lastModified' => $this->disk()->lastModified($dir),
                ];
            } catch (\Throwable) {
                return null;
            }
        })->filter();

        $files = collect($disk->files($path))->map(function (string $file): ?array {
            try {
                return [
                    'name' => basename($file),
                    'path' => $file,
                    'type' => 'file',
                    'size' => $this->disk()->size($file),
                    'lastModified' => $this->disk()->lastModified($file),
                ];
            } catch (\Throwable) {
                return null;
            }
        })->filter();

        // Sorting — folders always on top, then files
        $sortBy = in_array($request->query('sort'), ['name', 'lastModified', 'size']) ? $request->query('sort') : 'name';
        $sortDir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

        $sort = fn ($collection) => $sortDir === 'asc'
            ? $collection->sortBy($sortBy, SORT_NATURAL | SORT_FLAG_CASE)
            : $collection->sortByDesc($sortBy, SORT_NATURAL | SORT_FLAG_CASE);

        $items = $sort($directories)->values()->merge($sort($files)->values())->values();

        // Search filter (applied before pagination so it covers all pages)
        $search = trim((string) $request->query('busca', ''));
        if ($search !== '') {
            $items = $items->filter(function (array $item) use ($search): bool {
                return str_contains(mb_strtolower($item['name']), mb_strtolower($search));
            })->values();
        }

        // Pagination
        $perPage = 30;
        $currentPage = max(1, (int) $request->query('page', 1));
        $paginatedItems = new LengthAwarePaginator(
            $items->forPage($currentPage, $perPage),
            $items->count(),
            $perPage,
            $currentPage,
            ['query' => array_filter(['path' => $path ?: null, 'busca' => $search ?: null, 'sort' => $sortBy !== 'name' ? $sortBy : null, 'dir' => $sortDir !== 'asc' ? $sortDir : null])]
        );
        $paginatedItems->setPath(route('arquivos'));

        // Build breadcrumbs
        $breadcrumbs = [];
        if ($path !== '') {
            $parts = explode('/', $path);
            $accumulated = '';
            foreach ($parts as $part) {
                $accumulated = $accumulated === '' ? $part : $accumulated.'/'.$part;
                $breadcrumbs[] = ['name' => $part, 'path' => $accumulated];
            }
        }

        return view('arquivos.index', compact('paginatedItems', 'path', 'breadcrumbs', 'sortBy', 'sortDir'));
    }

    public function download(Request $request): StreamedResponse
    {
        $path = $this->safePath($request->query('path'));

        if ($path === '' || ! $this->disk()->exists($path)) {
            abort(404);
        }

        return $this->disk()->download($path, basename($path));
    }

    public function upload(Request $request): JsonResponse
    {
        Log::info('[Arquivos] Upload iniciado', [
            'path' => $request->input('path'),
            'disk_root' => config('filesystems.disks.shared.root'),
            'files' => collect($request->file('files') ?? [])->map(fn ($f) => $f?->getClientOriginalName())->toArray(),
        ]);

        $request->validate([
            'path' => ['nullable', 'string'],
            'files' => ['required', 'array'],
            'files.*' => ['required', 'file', 'max:102400'], // 100 MB max
        ]);

        $blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'sh', 'bash', 'exe', 'bat', 'cmd', 'ps1', 'py', 'rb', 'pl', 'cgi', 'htaccess'];

        foreach ($request->file('files') as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, $blockedExtensions, true)) {
                return response()->json(['error' => "Tipo de arquivo não permitido: .{$ext}"], 422);
            }
        }

        $path = $this->safePath($request->input('path'));

        // Detectar se o upload é dentro da pasta Portal/ de um cliente
        $pathParts = array_values(array_filter(explode('/', $path)));
        $clienteDoUpload = null;
        if (count($pathParts) >= 2 && strtolower($pathParts[1]) === 'portal') {
            $clienteDoUpload = Cliente::where('pasta_arquivos', $pathParts[0])->first();
        }

        foreach ($request->file('files') as $file) {
            $filename = $file->getClientOriginalName();
            try {
                $result = $this->disk()->putFileAs($path, $file, $filename);
                Log::info('[Arquivos] Upload concluído', ['file' => $filename, 'result' => $result]);

                if ($clienteDoUpload) {
                    TarefaUpload::create([
                        'tarefa_id' => null,
                        'cliente_id' => $clienteDoUpload->id,
                        'enviado_por' => Auth::id(),
                        'arquivo_nome' => $filename,
                        'arquivo_path' => ($path !== '' ? $path.'/' : '').$filename,
                        'tamanho' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('[Arquivos] Erro no upload', ['file' => $filename, 'error' => $e->getMessage()]);

                return response()->json(['error' => 'Erro ao enviar '.$filename.': '.$e->getMessage()], 500);
            }
        }

        return response()->json(['success' => true]);
    }

    public function createFolder(Request $request): JsonResponse
    {
        Log::info('[Arquivos] Criar pasta iniciado', [
            'path' => $request->input('path'),
            'name' => $request->input('name'),
            'disk_root' => config('filesystems.disks.shared.root'),
        ]);

        $request->validate([
            'path' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $path = $this->safePath($request->input('path'));
        $name = str_replace(['/', '\\', '..'], '', $request->input('name'));

        $fullPath = $path === '' ? $name : $path.'/'.$name;
        Log::info('[Arquivos] fullPath resolvido', ['fullPath' => $fullPath]);

        if ($this->disk()->directoryExists($fullPath)) {
            Log::warning('[Arquivos] Pasta já existe', ['fullPath' => $fullPath]);

            return response()->json(['error' => 'Pasta já existe.'], 422);
        }

        try {
            $result = $this->disk()->makeDirectory($fullPath);
            if (! $result) {
                Log::error('[Arquivos] makeDirectory retornou false', ['fullPath' => $fullPath, 'disk_root' => config('filesystems.disks.shared.root')]);

                return response()->json(['error' => 'Não foi possível criar a pasta. Verifique permissões no servidor.'], 500);
            }
            Log::info('[Arquivos] Pasta criada com sucesso', ['fullPath' => $fullPath]);
        } catch (\Throwable $e) {
            Log::error('[Arquivos] Erro ao criar pasta', ['fullPath' => $fullPath, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Não foi possível criar a pasta: '.$e->getMessage()], 500);
        }

        return response()->json(['success' => true]);
    }

    public function rename(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
            'newName' => ['required', 'string', 'max:255'],
        ]);

        $oldPath = $this->safePath($request->input('path'));
        $newName = str_replace(['/', '\\', '..'], '', $request->input('newName'));
        $parentDir = dirname($oldPath);
        $newPath = ($parentDir === '.' ? '' : $parentDir.'/').$newName;

        // Root-level folders (client folders) can only be renamed by diretor and ti
        $isRootFolder = $parentDir === '.' && $this->disk()->directoryExists($oldPath);
        if ($isRootFolder && ! Auth::user()?->canRenomearPastaRaiz()) {
            return response()->json(['error' => 'Sem permissão para renomear pastas de cliente.'], 403);
        }

        if ($oldPath === '' || (! $this->disk()->exists($oldPath) && ! $this->disk()->directoryExists($oldPath))) {
            return response()->json(['error' => 'Arquivo ou pasta não encontrado.'], 404);
        }

        if ($this->disk()->exists($newPath) || $this->disk()->directoryExists($newPath)) {
            return response()->json(['error' => 'Já existe um item com esse nome.'], 422);
        }

        $this->disk()->move($oldPath, $newPath);

        return response()->json(['success' => true]);
    }

    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
            'type' => ['required', 'in:file,folder'],
        ]);

        $path = $this->safePath($request->input('path'));

        if ($path === '') {
            return response()->json(['error' => 'Não é possível excluir a raiz.'], 422);
        }

        if ($request->input('type') === 'folder') {
            $this->disk()->deleteDirectory($path);
        } else {
            $this->disk()->delete($path);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Gera uma URL temporária assinada (24h) para download público do arquivo.
     */
    public function gerarLinkPublico(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['required', 'string'],
        ]);

        $path = $this->safePath($request->input('path'));

        if ($path === '' || ! $this->disk()->exists($path)) {
            return response()->json(['error' => 'Arquivo não encontrado.'], 404);
        }

        $link = URL::temporarySignedRoute(
            'arquivos.downloadPublico',
            now()->addHours(24),
            ['path' => $path]
        );

        return response()->json(['link' => $link, 'nome' => basename($path)]);
    }

    /**
     * Download público via URL assinada temporária (sem necessidade de login).
     */
    public function downloadPublico(Request $request): StreamedResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link inválido ou expirado.');
        }

        $path = $this->safePath($request->query('path'));

        if ($path === '' || ! $this->disk()->exists($path)) {
            abort(404);
        }

        // Registrar download no histórico do upload, se existir
        $upload = TarefaUpload::where('arquivo_path', $path)->first();
        if ($upload) {
            TarefaUploadEvento::create([
                'tarefa_upload_id' => $upload->id,
                'portal_usuario_id' => null,
                'tipo' => 'baixou',
                'created_at' => now(),
            ]);

            if (! $upload->baixado_em) {
                $upload->update(['baixado_em' => now()]);
            }
        }

        return $this->disk()->download($path, basename($path));
    }

    /**
     * Envia o link de download do arquivo por e-mail.
     * Aceita `usuario_id` (Usuario interno) ou `portal_usuario_id` (PortalUsuario do cliente).
     */
    public function enviarEmail(Request $request): JsonResponse
    {
        Log::info('[Email] Requisição recebida', $request->only(['path', 'usuario_id', 'portal_usuario_id']));

        $request->validate([
            'path' => ['required', 'string'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'portal_usuario_id' => ['nullable', 'integer', 'exists:portal_usuarios,id'],
        ]);

        if (! $request->filled('usuario_id') && ! $request->filled('portal_usuario_id')) {
            return response()->json(['error' => 'Informe o destinatário.'], 422);
        }

        $path = $this->safePath($request->input('path'));

        Log::info('[Email] Path após safePath', ['path' => $path, 'exists' => $this->disk()->exists($path)]);

        if ($path === '' || ! $this->disk()->exists($path)) {
            Log::warning('[Email] Arquivo não encontrado no disco', ['path' => $path]);

            return response()->json(['error' => 'Arquivo não encontrado.'], 404);
        }

        if ($request->filled('portal_usuario_id')) {
            $destinatario = PortalUsuario::findOrFail($request->input('portal_usuario_id'));
        } else {
            $destinatario = Usuario::findOrFail($request->input('usuario_id'));
        }

        if (! $destinatario->email) {
            return response()->json(['error' => 'Este usuário não possui e-mail cadastrado.'], 422);
        }

        $link = URL::temporarySignedRoute(
            'arquivos.downloadPublico',
            now()->addHours(24),
            ['path' => $path]
        );

        $nomeRemetente = Auth::user()?->nome ?? 'Equipe WR';
        $nomeArquivo = basename($path);

        Log::info('[Email] Enviando arquivo compartilhado', [
            'destinatario_email' => $destinatario->email,
            'arquivo' => $nomeArquivo,
        ]);

        try {
            Mail::to($destinatario->email, $destinatario->nome)
                ->send(new ArquivoCompartilhadoMail(
                    nomeDestinatario: $destinatario->nome,
                    nomeArquivo: $nomeArquivo,
                    linkDownload: $link,
                    nomeRemetente: $nomeRemetente,
                ));

            Log::info('[Email] Arquivo compartilhado enviado com sucesso', [
                'destinatario_email' => $destinatario->email,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Email] Falha ao enviar arquivo compartilhado', [
                'destinatario_email' => $destinatario->email,
                'erro' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Falha ao enviar o e-mail. Tente novamente.'], 500);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Retorna os usuários do portal de um cliente específico.
     */
    public function portalUsuariosDoCliente(int $clienteId): JsonResponse
    {
        $usuarios = PortalUsuario::query()
            ->where('cliente_id', $clienteId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get(['id', 'nome', 'email', 'telefone']);

        return response()->json($usuarios);
    }
}
