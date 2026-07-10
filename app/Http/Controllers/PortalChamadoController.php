<?php

namespace App\Http\Controllers;

use App\Models\ChamadoDp;
use App\Models\Cliente;
use App\Models\Departamento;
use App\Models\Etapa;
use App\Models\PortalUsuario;
use App\Models\RelTarefa;
use App\Models\Tarefa;
use App\Models\TarefaUpload;
use App\Models\TipoTarefa;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PortalChamadoController extends Controller
{
    private const TIPOS = ['admissao', 'demissao'];

    public function index(): View
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        $chamados = ChamadoDp::where('cliente_id', $cliente->id)
            ->with('tarefa.etapa')
            ->orderByDesc('created_at')
            ->get();

        return view('portal.chamados.index', compact('cliente', 'portalUsuario', 'chamados'));
    }

    public function create(string $tipo): View
    {
        abort_unless(in_array($tipo, self::TIPOS, true), 404);

        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        return view('portal.chamados.create', compact('cliente', 'portalUsuario', 'tipo'));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();
        $cliente = $portalUsuario->cliente;

        $data = $request->only([
            'tipo', 'nome_colaborador', 'cpf', 'cargo_funcao', 'data_evento', 'motivo', 'observacoes',
        ]);

        $validator = Validator::make($data, [
            'tipo' => ['required', 'in:admissao,demissao'],
            'nome_colaborador' => ['required', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:20'],
            'cargo_funcao' => ['nullable', 'string', 'max:255'],
            'data_evento' => ['required', 'date'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
            'arquivos' => ['nullable', 'array'],
            'arquivos.*' => ['file', 'max:51200'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $dpDepartamento = Departamento::where('nome', 'RH/DP')->first();

        $supervisorDp = $dpDepartamento
            ? (Usuario::where('departamento_id', $dpDepartamento->id)->where('cargo', 'supervisor')->first()
                ?? Usuario::where('departamento_id', $dpDepartamento->id)->where('cargo', 'supervisor_geral')->first())
            : null;

        if (! $dpDepartamento || ! $supervisorDp) {
            Log::error('Chamado DP: não foi possível identificar o supervisor do departamento RH/DP para atribuir a tarefa.', [
                'cliente_id' => $cliente->id,
                'portal_usuario_id' => $portalUsuario->id,
            ]);

            return redirect()->back()->withInput()
                ->with('error', 'Não foi possível abrir o chamado no momento. Por favor, entre em contato com o suporte.');
        }

        $tipoTarefa = TipoTarefa::where('nome', $data['tipo'] === 'admissao' ? 'Admissão' : 'Demissão')->first();

        $tarefa = DB::transaction(function () use ($data, $cliente, $dpDepartamento, $supervisorDp, $tipoTarefa, $portalUsuario, $request) {
            $labelTipo = $data['tipo'] === 'admissao' ? 'Admissão' : 'Demissão';

            $tarefa = Tarefa::create([
                'titulo' => "{$labelTipo} - {$data['nome_colaborador']}",
                'descricao' => $data['observacoes'] ?? null,
                'tipo_tarefa_id' => $tipoTarefa?->id,
                'cliente_id' => $cliente->id,
                'departamento_id' => $dpDepartamento->id,
                'etapa_id' => Etapa::where('visivel', true)->orderBy('ordem')->value('id'),
                'responsavel_id' => $supervisorDp->id,
                'supervisor_id' => $supervisorDp->id,
                'criado_por' => $supervisorDp->id,
                'data_vencimento' => $data['data_evento'],
                'prioridade' => 3,
                'requer_envio_arquivo' => false,
            ]);

            $tarefa->clientes()->sync([$cliente->id]);

            RelTarefa::create([
                'tarefa_id' => $tarefa->id,
                'etapa_anterior_id' => null,
                'etapa_nova_id' => $tarefa->etapa_id,
                'responsavel_anterior_id' => null,
                'responsavel_novo_id' => $supervisorDp->id,
                'alterado_por' => $supervisorDp->id,
                'observacao' => "Chamado de {$labelTipo} aberto pelo cliente via portal.",
            ]);

            ChamadoDp::create([
                'tarefa_id' => $tarefa->id,
                'cliente_id' => $cliente->id,
                'portal_usuario_id' => $portalUsuario->id,
                'tipo' => $data['tipo'],
                'nome_colaborador' => $data['nome_colaborador'],
                'cpf' => $data['cpf'] ?? null,
                'cargo_funcao' => $data['cargo_funcao'] ?? null,
                'data_evento' => $data['data_evento'],
                'motivo' => $data['motivo'] ?? null,
                'observacoes' => $data['observacoes'] ?? null,
            ]);

            $this->anexarArquivos($request, $tarefa, $cliente, $supervisorDp, $labelTipo);

            return $tarefa;
        });

        return redirect()->route('portal.chamados.index')
            ->with('success', 'Chamado enviado com sucesso! Nossa equipe de DP já foi notificada.');
    }

    private function anexarArquivos(Request $request, Tarefa $tarefa, Cliente $cliente, Usuario $supervisorDp, string $labelTipo): void
    {
        if (! $request->hasFile('arquivos') || ! $cliente->pasta_arquivos) {
            return;
        }

        $categoria = 'Pessoal';
        $periodo = Str::slug("{$labelTipo}-{$tarefa->id}");

        $sharedRoot = rtrim(Storage::disk('shared')->path(''), '/');
        $pastaDestino = $sharedRoot.'/'.rtrim($cliente->pasta_arquivos, '/').'/Portal/'.$categoria.'/'.$periodo;

        if (! is_dir($pastaDestino)) {
            mkdir($pastaDestino, 0775, true);
        }

        foreach ($request->file('arquivos') as $arquivo) {
            if (! $arquivo->isValid()) {
                continue;
            }

            $nomeOriginal = $arquivo->getClientOriginalName();
            $nomeBase = pathinfo($nomeOriginal, PATHINFO_FILENAME);
            $extensao = pathinfo($nomeOriginal, PATHINFO_EXTENSION);
            $nomeArquivo = $nomeOriginal;

            if (file_exists($pastaDestino.'/'.$nomeArquivo)) {
                $nomeArquivo = $nomeBase.'_'.time().($extensao ? '.'.$extensao : '');
            }

            $destinoAbsoluto = $pastaDestino.'/'.$nomeArquivo;
            $arquivo->move($pastaDestino, $nomeArquivo);

            $caminhoDB = rtrim($cliente->pasta_arquivos, '/').'/Portal/'.$categoria.'/'.$periodo.'/'.$nomeArquivo;

            TarefaUpload::create([
                'tarefa_id' => $tarefa->id,
                'cliente_id' => $cliente->id,
                'enviado_por' => $supervisorDp->id,
                'arquivo_nome' => $nomeArquivo,
                'arquivo_path' => $caminhoDB,
                'pasta_categoria' => $categoria,
                'pasta_periodo' => $periodo,
                'tamanho' => file_exists($destinoAbsoluto) ? filesize($destinoAbsoluto) : 0,
                'mime_type' => $arquivo->getClientMimeType(),
            ]);
        }
    }
}
