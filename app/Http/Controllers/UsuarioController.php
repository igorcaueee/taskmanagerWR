<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Notificacao;
use App\Models\RelTarefa;
use App\Models\Tarefa;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    private const CARGOS = ['diretor', 'ti', 'supervisor', 'supervisor_geral', 'analista', 'assistente', 'auxiliar', 'resp_certificados'];

    /**
     * Regras compartilhadas entre criar e editar colaborador (a regra de senha
     * é adicionada por fora — obrigatória no cadastro, opcional na edição).
     *
     * @return array<string, mixed>
     */
    private function regrasColab(?int $ignoreId = null): array
    {
        $email = Rule::unique('usuarios', 'email');
        if ($ignoreId) {
            $email->ignore($ignoreId);
        }

        return [
            'nome' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', $email],
            'cargo' => ['required', Rule::in(self::CARGOS)],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'telefone' => ['nullable', 'string', 'max:20', 'regex:/^\(?\d{2}\)?[\s-]?\d{4,5}[\s-]?\d{4}$/'],
            'sexo' => ['nullable', Rule::in(['masculino', 'feminino', 'outro'])],
            'data_nascimento' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'data_registro' => ['nullable', 'date', 'before_or_equal:today'],
            'status' => ['nullable', 'boolean'],
            'departamento_id' => ['nullable', 'integer', 'exists:departamentos,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensagensColab(): array
    {
        return [
            'nome.required' => 'O nome do colaborador é obrigatório.',
            'nome.min' => 'O nome deve ter ao menos 2 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'E-mail inválido.',
            'email.unique' => 'Já existe um colaborador cadastrado com este e-mail.',
            'senha.required' => 'A senha é obrigatória.',
            'senha.min' => 'A senha deve ter ao menos 8 caracteres.',
            'cargo.required' => 'Selecione o cargo.',
            'cargo.in' => 'Cargo inválido.',
            'telefone.regex' => 'Telefone inválido. Use o formato (00) 00000-0000.',
            'sexo.in' => 'Sexo inválido.',
            'foto.image' => 'O arquivo enviado precisa ser uma imagem.',
            'foto.mimes' => 'A foto deve ser JPG, PNG ou WEBP.',
            'foto.max' => 'A foto não pode ultrapassar 2MB.',
            'data_nascimento.before' => 'A data de nascimento deve ser no passado.',
            'data_nascimento.date' => 'Data de nascimento inválida.',
            'data_registro.before_or_equal' => 'A data de registro não pode ser futura.',
            'data_registro.date' => 'Data de registro inválida.',
            'departamento_id.exists' => 'Departamento inválido.',
        ];
    }

    public function showColaboradores(Request $request)
    {
        $query = Usuario::with('departamento')->orderBy('nome');

        if ($request->filled('busca')) {
            $busca = '%'.$request->string('busca').'%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)
                    ->orWhere('email', 'like', $busca);
            });
        }

        if ($request->filled('cargo')) {
            $query->where('cargo', $request->input('cargo'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        $colaboradores = $query->get();

        return view('colaboradores.home', compact('colaboradores'));
    }

    public function formColabCreate(): View
    {
        $departamentos = Departamento::orderBy('nome')->get();

        return view('colaboradores.partials.formUsuario', ['colab' => null, 'departamentos' => $departamentos]);
    }

    public function formColabEdit(int $id): View
    {
        $colab = Usuario::findOrFail($id);
        $departamentos = Departamento::orderBy('nome')->get();

        return view('colaboradores.partials.formUsuario', compact('colab', 'departamentos'));
    }

    /**
     * Save a new colaborador (users table).
     */
    public function saveColab(Request $request)
    {
        $data = $request->only(['nome', 'email', 'senha', 'cargo', 'foto', 'telefone', 'sexo', 'data_nascimento', 'data_registro', 'status', 'departamento_id']);

        $validator = Validator::make(
            $data,
            $this->regrasColab() + ['senha' => ['required', 'string', 'min:8']],
            $this->mensagensColab(),
        );

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        $foto = $request->hasFile('foto') ? $request->file('foto')->store('colaboradores', 'public') : null;

        Usuario::create([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'senha' => Hash::make($data['senha']),
            'cargo' => $data['cargo'],
            'foto' => $foto,
            'telefone' => $data['telefone'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'data_registro' => $data['data_registro'] ?? null,
            'status' => isset($data['status']) ? (bool) $data['status'] : true,
            'departamento_id' => $data['departamento_id'] ?? null,
        ]);

        return Redirect::back()->with('success', 'Colaborador criado com sucesso.');
    }

    /**
     * Update an existing colaborador using Eloquent.
     */
    public function updateColab(Request $request, int $id): RedirectResponse
    {
        $usuario = Usuario::findOrFail($id);

        $data = $request->only(['nome', 'email', 'senha', 'cargo', 'foto', 'remover_foto', 'telefone', 'sexo', 'data_nascimento', 'data_registro', 'status', 'departamento_id']);

        $validator = Validator::make(
            $data,
            $this->regrasColab($id) + ['senha' => ['nullable', 'string', 'min:8']],
            $this->mensagensColab(),
        );

        if ($validator->fails()) {
            return Redirect::back()->with('error', $validator->errors()->first())->withInput();
        }

        // Ao desligar alguém, força a transferência das tarefas em aberto antes de desativar.
        $desativando = $usuario->status && ! (isset($data['status']) ? (bool) $data['status'] : false);
        if ($desativando) {
            $abertas = $this->tarefasAbertasQuery($id, 'responsavel_id')->count()
                + $this->tarefasAbertasQuery($id, 'supervisor_id')->count();

            if ($abertas > 0 && Auth::user()?->canTransferirTarefasColaborador()) {
                return Redirect::back()
                    ->with('error', "{$usuario->nome} possui {$abertas} tarefa(s) em aberto. Transfira as tarefas para outro colaborador antes de desativar.")
                    ->with('abrir_transferencia', $id);
            }
        }

        $update = [
            'nome' => $data['nome'],
            'email' => $data['email'],
            'cargo' => $data['cargo'],
            'telefone' => $data['telefone'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'data_registro' => $data['data_registro'] ?? null,
            'status' => isset($data['status']) ? (bool) $data['status'] : false,
            'departamento_id' => $data['departamento_id'] ?? null,
        ];

        if (! empty($data['senha'])) {
            $update['senha'] = Hash::make($data['senha']);
        }

        if ($request->hasFile('foto')) {
            if ($usuario->foto) {
                Storage::disk('public')->delete($usuario->foto);
            }
            $update['foto'] = $request->file('foto')->store('colaboradores', 'public');
        } elseif ($request->boolean('remover_foto') && $usuario->foto) {
            Storage::disk('public')->delete($usuario->foto);
            $update['foto'] = null;
        }

        $usuario->update($update);

        return Redirect::back()->with('success', 'Colaborador atualizado com sucesso.');
    }

    /**
     * Query das tarefas em aberto (ativas e não concluídas) em que o colaborador atua
     * numa determinada função (responsavel_id ou supervisor_id).
     */
    private function tarefasAbertasQuery(int $usuarioId, string $coluna)
    {
        return Tarefa::where($coluna, $usuarioId)
            ->where('ativo', true)
            ->whereNull('data_conclusao');
    }

    /**
     * Modal para transferir em massa as tarefas de um colaborador para outro
     * (usado ao desligar alguém da empresa).
     */
    public function formTransferirTarefas(Request $request, int $id): View
    {
        abort_unless($request->user()?->canTransferirTarefasColaborador(), 403);

        $colab = Usuario::findOrFail($id);

        $comoResponsavel = $this->tarefasAbertasQuery($id, 'responsavel_id')->count();
        $comoSupervisor = $this->tarefasAbertasQuery($id, 'supervisor_id')->count();

        $destinos = Usuario::where('status', true)
            ->where('id', '!=', $id)
            ->orderBy('nome')
            ->get();

        return view('colaboradores.partials.transferirTarefas', compact('colab', 'comoResponsavel', 'comoSupervisor', 'destinos'));
    }

    /**
     * Executa a transferência em massa das tarefas do colaborador para outro,
     * registrando o histórico e, opcionalmente, desativando o colaborador.
     */
    public function transferirTarefas(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->canTransferirTarefasColaborador(), 403);

        $colab = Usuario::findOrFail($id);

        $data = $request->validate([
            'novo_responsavel_id' => ['required', 'integer', 'exists:usuarios,id'],
            'incluir_supervisao' => ['nullable', 'boolean'],
            'desativar_colaborador' => ['nullable', 'boolean'],
        ], [
            'novo_responsavel_id.required' => 'Selecione o colaborador que vai receber as tarefas.',
            'novo_responsavel_id.exists' => 'Colaborador de destino inválido.',
        ]);

        if ((int) $data['novo_responsavel_id'] === $id) {
            return Redirect::back()->with('error', 'O colaborador de destino deve ser diferente do que está sendo desligado.');
        }

        $novo = Usuario::findOrFail((int) $data['novo_responsavel_id']);
        $autor = $request->user();
        $incluirSupervisao = $request->boolean('incluir_supervisao');

        $resultado = DB::transaction(function () use ($colab, $novo, $autor, $incluirSupervisao) {
            $tarefas = $this->tarefasAbertasQuery($colab->id, 'responsavel_id')->get();

            foreach ($tarefas as $tarefa) {
                $tarefa->update([
                    'responsavel_id' => $novo->id,
                    'departamento_id' => $novo->departamento_id ?? $tarefa->departamento_id,
                ]);

                RelTarefa::create([
                    'tarefa_id' => $tarefa->id,
                    'responsavel_anterior_id' => $colab->id,
                    'responsavel_novo_id' => $novo->id,
                    'alterado_por' => $autor->id,
                    'observacao' => "Transferência em massa ao desligar {$colab->nome} → {$novo->nome}",
                ]);
            }

            $supervisao = 0;
            if ($incluirSupervisao) {
                $supervisao = $this->tarefasAbertasQuery($colab->id, 'supervisor_id')
                    ->update(['supervisor_id' => $novo->id]);
            }

            return ['responsavel' => $tarefas->count(), 'supervisao' => $supervisao];
        });

        if ($resultado['responsavel'] > 0 && (int) $novo->id !== (int) $autor->id) {
            try {
                Notificacao::create([
                    'usuario_id' => $novo->id,
                    'tipo' => 'tarefa_atribuida',
                    'mensagem' => "{$autor->nome} transferiu {$resultado['responsavel']} tarefa(s) de {$colab->nome} para você.",
                ]);
            } catch (\Throwable $e) {
                Log::error('Falha ao criar notificação: '.$e->getMessage());
            }
        }

        if ($request->boolean('desativar_colaborador') && $colab->status) {
            $colab->update(['status' => false]);
        }

        $partes = ["{$resultado['responsavel']} tarefa(s) transferida(s) para {$novo->nome}"];
        if ($incluirSupervisao) {
            $partes[] = "{$resultado['supervisao']} com supervisão reatribuída";
        }
        if ($request->boolean('desativar_colaborador')) {
            $partes[] = "{$colab->nome} foi desativado";
        }

        return Redirect::route('colaboradores')->with('success', implode('. ', $partes).'.');
    }

    /**
     * Delete a colaborador by id using Eloquent.
     */
    public function deleteColab(int $id): RedirectResponse
    {
        $usuario = Usuario::findOrFail($id);

        if ($usuario->foto) {
            Storage::disk('public')->delete($usuario->foto);
        }

        $usuario->delete();

        return Redirect::back()->with('success', 'Colaborador excluído com sucesso.');
    }
}
