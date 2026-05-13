<?php

namespace App\Http\Controllers;

use App\Models\Artigo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Exceptions\RateLimitedException;

class BlogController extends Controller
{
    // ─── Área interna ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $artigos = Artigo::with('autor')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('busca'), fn ($q) => $q->where('titulo', 'like', '%'.$request->busca.'%'))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('blog.index', compact('artigos'));
    }

    public function formCreate()
    {
        return view('blog.form', ['artigo' => null]);
    }

    public function formEdit(int $id)
    {
        $artigo = Artigo::findOrFail($id);

        return view('blog.form', compact('artigo'));
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'titulo'      => ['required', 'string', 'max:255'],
            'resumo'      => ['nullable', 'string', 'max:500'],
            'conteudo'    => ['required', 'string'],
            'imagem_capa' => ['nullable', 'url', 'max:500'],
            'status'      => ['required', 'in:rascunho,agendado,publicado'],
            'publicado_em' => ['nullable', 'date', 'required_if:status,agendado'],
        ]);

        $data['slug'] = Artigo::gerarSlug($data['titulo']);
        $data['autor_id'] = Auth::id();

        if ($data['status'] === 'publicado' && empty($data['publicado_em'])) {
            $data['publicado_em'] = now();
        }

        Artigo::create($data);

        return redirect()->route('blog.admin.index')->with('success', 'Artigo criado com sucesso!');
    }

    public function update(Request $request, int $id)
    {
        $artigo = Artigo::findOrFail($id);

        $data = $request->validate([
            'titulo'      => ['required', 'string', 'max:255'],
            'resumo'      => ['nullable', 'string', 'max:500'],
            'conteudo'    => ['required', 'string'],
            'imagem_capa' => ['nullable', 'url', 'max:500'],
            'status'      => ['required', 'in:rascunho,agendado,publicado'],
            'publicado_em' => ['nullable', 'date', 'required_if:status,agendado'],
        ]);

        if ($data['titulo'] !== $artigo->titulo) {
            $data['slug'] = Artigo::gerarSlug($data['titulo']);
        }

        if ($data['status'] === 'publicado' && empty($data['publicado_em']) && ! $artigo->publicado_em) {
            $data['publicado_em'] = now();
        }

        $artigo->update($data);

        return redirect()->route('blog.admin.index')->with('success', 'Artigo atualizado com sucesso!');
    }

    public function delete(int $id)
    {
        Artigo::findOrFail($id)->delete();

        return redirect()->route('blog.admin.index')->with('success', 'Artigo excluído.');
    }

    public function gerarIA(Request $request): JsonResponse
    {
        $request->validate([
            'titulo'         => ['required', 'string', 'max:255'],
            'tema'           => ['required', 'string', 'max:500'],
            'tom'            => ['required', 'in:formal,informal,educativo,persuasivo'],
            'palavras_chave' => ['nullable', 'string', 'max:300'],
        ]);

        $titulo = $request->string('titulo');
        $tema = $request->string('tema');
        $tom = $request->string('tom');
        $palavrasChave = $request->string('palavras_chave');

        $prompt = <<<PROMPT
Escreva um artigo completo de blog em português do Brasil para uma empresa de contabilidade e assessoria empresarial (WR Assessoria).

Título: {$titulo}
Tema / Ângulo: {$tema}
Tom: {$tom}
Palavras-chave: {$palavrasChave}

Estrutura obrigatória:
- Introdução cativante (2-3 parágrafos)
- 3 a 5 seções com subtítulos
- Conclusão com chamada para ação
- Use linguagem clara e acessível para empreendedores
- Não use markdown com asteriscos, use HTML simples: <h2>, <h3>, <p>, <ul>, <li>, <strong>
- Retorne APENAS o conteúdo HTML do artigo, sem <!DOCTYPE>, <html>, <head> ou <body>
PROMPT;

        try {
            $response = AnonymousAgent::make(
                instructions: 'Você é um redator especialista em conteúdo para escritórios de contabilidade e assessoria empresarial no Brasil. Escreva artigos informativos, claros e que gerem valor para empresários e empreendedores.',
            )->prompt(
                prompt: $prompt,
                provider: 'groq',
                model: 'llama-3.3-70b-versatile',
            );

            return response()->json(['conteudo' => $response->text]);
        } catch (RateLimitedException) {
            return response()->json(['error' => 'Serviço de IA sobrecarregado. Aguarde alguns segundos e tente novamente.'], 429);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Erro ao gerar conteúdo. Tente novamente.'], 500);
        }
    }

    // ─── Área pública ─────────────────────────────────────────────────────────

    public function publicIndex()
    {
        $artigos = Artigo::with('autor')
            ->publicados()
            ->orderByDesc('publicado_em')
            ->paginate(9);

        return view('blog.public.index', compact('artigos'));
    }

    public function publicShow(string $slug)
    {
        $artigo = Artigo::with('autor')
            ->publicados()
            ->where('slug', $slug)
            ->firstOrFail();

        $relacionados = Artigo::publicados()
            ->where('id', '!=', $artigo->id)
            ->orderByDesc('publicado_em')
            ->limit(3)
            ->get();

        return view('blog.public.show', compact('artigo', 'relacionados'));
    }
}
