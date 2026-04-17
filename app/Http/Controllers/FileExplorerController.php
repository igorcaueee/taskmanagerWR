<?php

namespace App\Http\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        })->filter()->sortBy('name');

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
        })->filter()->sortBy('name');

        $items = $directories->merge($files)->values();

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

        return view('arquivos.index', compact('items', 'path', 'breadcrumbs'));
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
        $request->validate([
            'path' => ['nullable', 'string'],
            'files' => ['required', 'array'],
            'files.*' => ['required', 'file', 'max:102400'], // 100 MB max
        ]);

        $path = $this->safePath($request->input('path'));

        foreach ($request->file('files') as $file) {
            $filename = $file->getClientOriginalName();
            $this->disk()->putFileAs($path, $file, $filename);
        }

        return response()->json(['success' => true]);
    }

    public function createFolder(Request $request): JsonResponse
    {
        $request->validate([
            'path' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $path = $this->safePath($request->input('path'));
        $name = str_replace(['/', '\\', '..'], '', $request->input('name'));

        $fullPath = $path === '' ? $name : $path.'/'.$name;

        if ($this->disk()->directoryExists($fullPath)) {
            return response()->json(['error' => 'Pasta já existe.'], 422);
        }

        $this->disk()->makeDirectory($fullPath);

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
}
