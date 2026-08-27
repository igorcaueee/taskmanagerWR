<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\TipoTarefa;
use App\Models\TipoTarefaRegra;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TipoTarefaController extends Controller
{
    public const REGIMES = ['Simples Nacional', 'Lucro Presumido', 'Lucro Real', 'MEI', 'Associação'];

    /** Chave usada no formulário para a regra que vale para qualquer regime (regime_tributario = null). */
    public const REGIME_QUALQUER = '__qualquer__';

    public function index(Request $request): View
    {
        $query = TipoTarefa::orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where('nome', 'like', $busca);
        }

        $tipos = $query->withCount('tarefas')->with('regras')->get();

        return view('tipos-tarefa.home', compact('tipos'));
    }

    public function formCreate(): View
    {
        return view('tipos-tarefa.partials.formTipoTarefa', [
            'tipo' => null,
            'regimesDisponiveis' => self::REGIMES,
            'regimeQualquer' => self::REGIME_QUALQUER,
            'departamentos' => Departamento::orderBy('nome')->get(),
            'usuarios' => Usuario::orderBy('nome')->get(),
        ]);
    }

    public function formEdit(int $id): View
    {
        $tipo = TipoTarefa::with('regras')->findOrFail($id);

        return view('tipos-tarefa.partials.formTipoTarefa', [
            'tipo' => $tipo,
            'regimesDisponiveis' => self::REGIMES,
            'regimeQualquer' => self::REGIME_QUALQUER,
            'departamentos' => Departamento::orderBy('nome')->get(),
            'usuarios' => Usuario::orderBy('nome')->get(),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->only(['nome', 'titulo_padrao', 'data_vencimento']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'titulo_padrao' => ['nullable', 'string', 'max:255'],
            'data_vencimento' => ['nullable', 'date'],
        ], [
            'nome.required' => 'O nome do tipo é obrigatório.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $tipo = TipoTarefa::create($data);

        $this->sincronizarRegras($tipo, $request);

        return Redirect::route('tipos-tarefa.index')->with('success', 'Tipo de tarefa criado com sucesso.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $tipo = TipoTarefa::findOrFail($id);

        $data = $request->only(['nome', 'titulo_padrao', 'data_vencimento']);

        $validator = Validator::make($data, [
            'nome' => ['required', 'string', 'max:255'],
            'titulo_padrao' => ['nullable', 'string', 'max:255'],
            'data_vencimento' => ['nullable', 'date'],
        ], [
            'nome.required' => 'O nome do tipo é obrigatório.',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withErrors($validator)->withInput();
        }

        $tipo->update($data);

        $this->sincronizarRegras($tipo, $request);

        return Redirect::route('tipos-tarefa.index')->with('success', 'Tipo de tarefa atualizado com sucesso.');
    }

    private function sincronizarRegras(TipoTarefa $tipo, Request $request): void
    {
        $regrasInput = $request->input('regras', []);

        $chaves = array_merge([self::REGIME_QUALQUER], self::REGIMES);

        foreach ($chaves as $chave) {
            $regime = $chave === self::REGIME_QUALQUER ? null : $chave;
            $config = $regrasInput[$chave] ?? null;
            $ativo = filter_var($config['ativo'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $base = TipoTarefaRegra::where('tipo_tarefa_id', $tipo->id);
            $regime === null ? $base->whereNull('regime_tributario') : $base->where('regime_tributario', $regime);

            if (! $ativo) {
                (clone $base)->delete();

                continue;
            }

            $prefixos = collect(preg_split('/[\s,;]+/', (string) ($config['cnae_prefixos'] ?? '')))
                ->map(fn ($p) => preg_replace('/\D/', '', $p))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $attrs = [
                'cnae_prefixos' => $prefixos ?: null,
                'frequencia' => $config['frequencia'] ?? 'mensal',
                'dia_vencimento' => $config['dia_vencimento'] ?: null,
                'departamento_id' => $config['departamento_id'] ?: null,
                'responsavel_id' => $config['responsavel_id'] ?: null,
                'ativo' => true,
            ];

            if ($regra = (clone $base)->first()) {
                $regra->update($attrs);
            } else {
                TipoTarefaRegra::create($attrs + [
                    'tipo_tarefa_id' => $tipo->id,
                    'regime_tributario' => $regime,
                ]);
            }
        }
    }

    public function delete(int $id): RedirectResponse
    {
        $tipo = TipoTarefa::withCount('tarefas')->findOrFail($id);

        if ($tipo->tarefas_count > 0) {
            return Redirect::back()->with('error', "Não é possível excluir: existem {$tipo->tarefas_count} tarefa(s) vinculada(s) a este tipo.");
        }

        $tipo->delete();

        return Redirect::route('tipos-tarefa.index')->with('success', 'Tipo de tarefa excluído com sucesso.');
    }
}
