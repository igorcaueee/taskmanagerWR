<?php

namespace App\Http\Controllers;

use App\Events\MensagemEnviada;
use App\Events\MensagemLida;
use App\Events\UsuarioDigitando;
use App\Models\Conversa;
use App\Models\Mensagem;
use App\Models\MensagemAnexo;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function index(): View
    {
        return view('chat.index');
    }

    public function conversas(): JsonResponse
    {
        $usuario = Auth::user();

        $conversas = $usuario->conversas()
            ->with(['usuarios:id,nome,foto', 'ultimaMensagem.anexos'])
            ->get()
            ->map(function (Conversa $conversa) use ($usuario) {
                return [
                    'id' => $conversa->id,
                    'tipo' => $conversa->tipo,
                    'nome' => $conversa->nomeParaUsuario($usuario),
                    'foto_url' => $conversa->fotoUrlParaUsuario($usuario),
                    'ultima_mensagem' => $this->previewMensagem($conversa->ultimaMensagem),
                    'ultima_mensagem_em' => $conversa->ultimaMensagem?->created_at?->toIso8601String(),
                    'ultima_mensagem_usuario_id' => $conversa->ultimaMensagem?->usuario_id,
                    'nao_lidas' => $conversa->naoLidasPara($usuario),
                ];
            })
            ->sortByDesc('ultima_mensagem_em')
            ->values();

        return response()->json(['conversas' => $conversas]);
    }

    protected function previewMensagem(?Mensagem $mensagem): ?string
    {
        if (! $mensagem) {
            return null;
        }

        if ($mensagem->texto) {
            return $mensagem->texto;
        }

        if ($mensagem->anexos->isNotEmpty()) {
            return $mensagem->anexos->first()->tipo === 'imagem' ? '📷 Foto' : '📎 Anexo';
        }

        return null;
    }

    public function naoLidasTotal(): JsonResponse
    {
        $usuario = Auth::user();

        $total = $usuario->conversas()
            ->with(['participantes' => fn ($q) => $q->where('usuario_id', $usuario->id)])
            ->get()
            ->sum(fn (Conversa $conversa) => $conversa->naoLidasPara($usuario));

        return response()->json(['nao_lidas' => $total]);
    }

    public function mensagens(Conversa $conversa): JsonResponse
    {
        $this->abortSeNaoParticipante($conversa);

        $mensagens = $conversa->mensagens()
            ->with(['usuario:id,nome,foto', 'anexos'])
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->sortBy('id')
            ->values()
            ->map(fn (Mensagem $mensagem) => $this->formatarMensagem($mensagem));

        return response()->json(['mensagens' => $mensagens]);
    }

    public function abrirIndividual(Usuario $usuario): JsonResponse
    {
        $euId = Auth::id();

        if ($usuario->id === $euId) {
            abort(422, 'Não é possível iniciar uma conversa consigo mesmo.');
        }

        $conversa = Conversa::where('tipo', 'individual')
            ->whereHas('usuarios', fn ($q) => $q->where('usuario_id', $euId))
            ->whereHas('usuarios', fn ($q) => $q->where('usuario_id', $usuario->id))
            ->first();

        if (! $conversa) {
            $conversa = DB::transaction(function () use ($euId, $usuario) {
                $conversa = Conversa::create([
                    'tipo' => 'individual',
                    'criado_por' => $euId,
                ]);

                $conversa->participantes()->createMany([
                    ['usuario_id' => $euId],
                    ['usuario_id' => $usuario->id],
                ]);

                return $conversa;
            });
        }

        return response()->json(['conversa_id' => $conversa->id]);
    }

    public function storeGrupo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'usuarios' => ['required', 'array', 'min:1'],
            'usuarios.*' => ['integer', 'exists:usuarios,id'],
        ]);

        $euId = Auth::id();
        $foto = $request->hasFile('foto') ? $request->file('foto')->store('grupos-chat', 'public') : null;

        $conversa = DB::transaction(function () use ($data, $foto, $euId) {
            $conversa = Conversa::create([
                'tipo' => 'grupo',
                'nome' => $data['nome'],
                'foto' => $foto,
                'criado_por' => $euId,
            ]);

            $conversa->participantes()->create(['usuario_id' => $euId, 'is_admin' => true]);

            foreach (array_unique($data['usuarios']) as $usuarioId) {
                if ((int) $usuarioId === $euId) {
                    continue;
                }
                $conversa->participantes()->create(['usuario_id' => $usuarioId]);
            }

            return $conversa;
        });

        return response()->json(['conversa_id' => $conversa->id]);
    }

    public function enviarMensagem(Request $request, Conversa $conversa): JsonResponse
    {
        $this->abortSeNaoParticipante($conversa);

        $data = $request->validate([
            'texto' => ['nullable', 'string', 'max:5000'],
            'anexos' => ['nullable', 'array'],
            'anexos.*' => ['file', 'max:10240'],
        ]);

        if (empty($data['texto']) && ! $request->hasFile('anexos')) {
            abort(422, 'Envie um texto ou ao menos um anexo.');
        }

        $mensagem = DB::transaction(function () use ($request, $conversa, $data) {
            $mensagem = $conversa->mensagens()->create([
                'usuario_id' => Auth::id(),
                'texto' => $data['texto'] ?? null,
            ]);

            foreach ($request->file('anexos', []) as $arquivo) {
                $caminho = $arquivo->store('chat-anexos', 'local');

                $mensagem->anexos()->create([
                    'caminho' => $caminho,
                    'nome_original' => $arquivo->getClientOriginalName(),
                    'mime_type' => $arquivo->getMimeType(),
                    'tamanho' => $arquivo->getSize(),
                    'tipo' => str_starts_with((string) $arquivo->getMimeType(), 'image/') ? 'imagem' : 'arquivo',
                ]);
            }

            return $mensagem;
        });

        $conversa->participantes()
            ->where('usuario_id', Auth::id())
            ->update(['ultima_mensagem_lida_id' => $mensagem->id, 'lida_em' => now()]);

        $participanteIds = $conversa->participantes()->pluck('usuario_id')->all();

        broadcast(new MensagemEnviada($mensagem, $participanteIds));

        return response()->json(['mensagem' => $this->formatarMensagem($mensagem->fresh(['usuario', 'anexos']))]);
    }

    public function marcarLida(Conversa $conversa): JsonResponse
    {
        $this->abortSeNaoParticipante($conversa);

        $ultimaMensagemId = $conversa->mensagens()->max('id');

        $conversa->participantes()
            ->where('usuario_id', Auth::id())
            ->update(['ultima_mensagem_lida_id' => $ultimaMensagemId, 'lida_em' => now()]);

        broadcast(new MensagemLida($conversa->id, Auth::id(), $ultimaMensagemId));

        return response()->json(['success' => true]);
    }

    public function digitando(Request $request, Conversa $conversa): JsonResponse
    {
        $this->abortSeNaoParticipante($conversa);

        broadcast(new UsuarioDigitando(Auth::user(), $conversa->id, $request->boolean('digitando')));

        return response()->json(['success' => true]);
    }

    public function usuariosParaChat(Request $request): JsonResponse
    {
        $busca = trim((string) $request->query('q', ''));

        $usuarios = Usuario::query()
            ->where('id', '!=', Auth::id())
            ->when($busca !== '', fn ($q) => $q->where('nome', 'like', "%{$busca}%"))
            ->orderBy('nome')
            ->limit(30)
            ->get(['id', 'nome', 'foto', 'cargo']);

        return response()->json($usuarios->map(fn (Usuario $u) => [
            'id' => $u->id,
            'nome' => $u->nome,
            'foto_url' => $u->foto_url,
            'cargo' => $u->cargo,
        ]));
    }

    public function download(MensagemAnexo $anexo): StreamedResponse
    {
        $anexo->loadMissing('mensagem.conversa');

        $this->abortSeNaoParticipante($anexo->mensagem->conversa);

        return Storage::disk('local')->response($anexo->caminho, $anexo->nome_original);
    }

    protected function abortSeNaoParticipante(Conversa $conversa): void
    {
        $ehParticipante = $conversa->participantes()
            ->where('usuario_id', Auth::id())
            ->exists();

        abort_unless($ehParticipante, 403);
    }

    protected function formatarMensagem(Mensagem $mensagem): array
    {
        return [
            'id' => $mensagem->id,
            'conversa_id' => $mensagem->conversa_id,
            'texto' => $mensagem->texto,
            'usuario' => [
                'id' => $mensagem->usuario->id,
                'nome' => $mensagem->usuario->nome,
                'foto_url' => $mensagem->usuario->foto_url,
            ],
            'anexos' => $mensagem->anexos->map(fn (MensagemAnexo $anexo) => [
                'id' => $anexo->id,
                'url' => $anexo->url,
                'nome_original' => $anexo->nome_original,
                'tipo' => $anexo->tipo,
            ]),
            'created_at' => $mensagem->created_at->toIso8601String(),
        ];
    }
}
