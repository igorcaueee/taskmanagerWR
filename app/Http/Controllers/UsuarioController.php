<?php

namespace App\Http\Controllers;

use App\Models\Departamento;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    private const CARGOS = ['diretor', 'ti', 'supervisor', 'supervisor_geral', 'analista', 'assistente', 'auxiliar'];

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
