<?php

namespace App\Http\Controllers\Precificacao;

use App\Http\Controllers\Controller;
use App\Models\PrecificacaoNcmGrupo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PrecificacaoNcmGrupoController extends Controller
{
    public function index(Request $request): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $query = PrecificacaoNcmGrupo::with('itens')->orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)
                    ->orWhereHas('itens', function ($q2) use ($busca) {
                        $q2->where('ncm', 'like', $busca);
                    });
            });
        }

        $grupos = $query->paginate(30)->withQueryString();

        return view('precificacao.ncmGrupos.home', compact('grupos'));
    }

    public function formCreate(): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        return view('precificacao.ncmGrupos.partials.form', ['grupo' => null]);
    }

    public function formEdit(int $id): View
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $grupo = PrecificacaoNcmGrupo::with('itens')->findOrFail($id);

        return view('precificacao.ncmGrupos.partials.form', compact('grupo'));
    }

    public function save(Request $request): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $validator = $this->validarDados($request);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $ncms = $this->parseNcms($data['ncms']);
        $data['ativo'] = $request->boolean('ativo');
        unset($data['ncms']);

        $grupo = PrecificacaoNcmGrupo::create($data);
        $grupo->itens()->createMany(array_map(fn ($ncm) => ['ncm' => $ncm], $ncms));

        return Redirect::route('precificacao.ncmGrupos')->with('success', 'Grupo de NCM criado com sucesso.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        $grupo = PrecificacaoNcmGrupo::findOrFail($id);

        $validator = $this->validarDados($request);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();
        $ncms = $this->parseNcms($data['ncms']);
        $data['ativo'] = $request->boolean('ativo');
        unset($data['ncms']);

        $grupo->update($data);
        $grupo->itens()->delete();
        $grupo->itens()->createMany(array_map(fn ($ncm) => ['ncm' => $ncm], $ncms));

        return Redirect::route('precificacao.ncmGrupos')->with('success', 'Grupo de NCM atualizado com sucesso.');
    }

    public function delete(int $id): RedirectResponse
    {
        abort_if(! auth()->user()?->canGerenciarPrecificacao(), 403);

        PrecificacaoNcmGrupo::findOrFail($id)->delete();

        return Redirect::route('precificacao.ncmGrupos')->with('success', 'Grupo de NCM excluído com sucesso.');
    }

    private function validarDados(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($request->only(['nome', 'ativo', 'ncms']), [
            'nome' => ['required', 'string', 'max:255'],
            'ativo' => ['nullable'],
            'ncms' => ['required', 'string', function ($attribute, $value, $fail) {
                if (empty($this->parseNcms($value))) {
                    $fail('Informe ao menos um NCM.');
                }
            }],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function parseNcms(string $ncmsTexto): array
    {
        return collect(preg_split('/[\s,;]+/', $ncmsTexto))
            ->map(fn ($ncm) => trim((string) $ncm))
            ->filter(fn ($ncm) => $ncm !== '')
            ->unique()
            ->values()
            ->all();
    }
}
