<?php

namespace App\Http\Controllers;

use App\Models\InstrucaoLiri;
use App\Services\ExtratorTextoArquivoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class InstrucaoLiriController extends Controller
{
    public function index(): View
    {
        abort_if(! auth()->user()?->canGerenciarInstrucoesLiri(), 403);

        $instrucoes = InstrucaoLiri::with('autor')
            ->orderByDesc('created_at')
            ->get();

        return view('liri.index', compact('instrucoes'));
    }

    public function formCreate(): View
    {
        abort_if(! auth()->user()?->canGerenciarInstrucoesLiri(), 403);

        return view('liri.form', ['instrucao' => null]);
    }

    public function store(Request $request, ExtratorTextoArquivoService $extrator): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarInstrucoesLiri(), 403);

        $validator = $this->validador($request);

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        $data = $validator->validated();
        $data['autor_id'] = Auth::id();

        $erro = $this->processarArquivo($request, $data, $extrator);

        if ($erro) {
            return Redirect::back()->with('error', $erro)->withInput();
        }

        InstrucaoLiri::create($data);

        return Redirect::route('liri.instrucoes.index')
            ->with('success', 'Instrução adicionada com sucesso.');
    }

    public function formEdit(int $id): View
    {
        abort_if(! auth()->user()?->canGerenciarInstrucoesLiri(), 403);

        $instrucao = InstrucaoLiri::findOrFail($id);

        return view('liri.form', compact('instrucao'));
    }

    public function update(Request $request, int $id, ExtratorTextoArquivoService $extrator): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarInstrucoesLiri(), 403);

        $instrucao = InstrucaoLiri::findOrFail($id);

        $validator = $this->validador($request);

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        $data = $validator->validated();

        $erro = $this->processarArquivo($request, $data, $extrator, $instrucao);

        if ($erro) {
            return Redirect::back()->with('error', $erro)->withInput();
        }

        if (empty($data['conteudo']) && $instrucao->conteudo) {
            $data['conteudo'] = $instrucao->conteudo;
        }

        $instrucao->update($data);

        return Redirect::route('liri.instrucoes.index')
            ->with('success', 'Instrução atualizada com sucesso.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarInstrucoesLiri(), 403);

        $instrucao = InstrucaoLiri::findOrFail($id);

        if ($instrucao->arquivo_path) {
            Storage::disk('local')->delete($instrucao->arquivo_path);
        }

        $instrucao->delete();

        return Redirect::route('liri.instrucoes.index')
            ->with('success', 'Instrução removida com sucesso.');
    }

    private function validador(Request $request): \Illuminate\Validation\Validator
    {
        return Validator::make($request->all(), [
            'titulo' => ['required', 'string', 'max:255'],
            'conteudo' => ['nullable', 'string'],
            'arquivo' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
        ], [
            'titulo.required' => 'O título é obrigatório.',
            'arquivo.mimes' => 'Envie um arquivo PDF, DOC ou DOCX.',
            'arquivo.max' => 'O arquivo deve ter no máximo 10MB.',
        ]);
    }

    /**
     * Faz o upload do arquivo (se houver) e extrai o texto para o campo `conteudo`.
     * Retorna uma mensagem de erro em caso de falha, ou null em caso de sucesso.
     *
     * @param  array<string, mixed>  $data
     */
    private function processarArquivo(Request $request, array &$data, ExtratorTextoArquivoService $extrator, ?InstrucaoLiri $instrucao = null): ?string
    {
        if (! $request->hasFile('arquivo')) {
            if (empty($data['conteudo']) && ! ($instrucao?->conteudo)) {
                return 'Informe um conteúdo ou envie um arquivo.';
            }

            return null;
        }

        $arquivo = $request->file('arquivo');
        $textoExtraido = $extrator->extrair($arquivo);

        if (empty(trim($textoExtraido)) && empty($data['conteudo'])) {
            return 'Não foi possível extrair texto do arquivo enviado. Cole o conteúdo manualmente.';
        }

        if ($instrucao?->arquivo_path) {
            Storage::disk('local')->delete($instrucao->arquivo_path);
        }

        $data['arquivo_nome'] = $arquivo->getClientOriginalName();
        $data['arquivo_path'] = $arquivo->store('liri-instrucoes', 'local');
        $data['conteudo'] = trim(($data['conteudo'] ?? '')."\n\n".$textoExtraido);

        return null;
    }
}
