<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificacaoController extends Controller
{
    public function index(): JsonResponse
    {
        $notificacoes = Notificacao::where('usuario_id', Auth::id())
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'tipo', 'mensagem', 'tarefa_id', 'lida', 'created_at']);

        $naoLidas = $notificacoes->where('lida', false)->count();

        return response()->json([
            'notificacoes' => $notificacoes,
            'nao_lidas' => $naoLidas,
        ]);
    }

    public function marcarLida(int $id): JsonResponse
    {
        Notificacao::where('id', $id)
            ->where('usuario_id', Auth::id())
            ->update(['lida' => true]);

        return response()->json(['success' => true]);
    }

    public function marcarTodasLidas(): JsonResponse
    {
        Notificacao::where('usuario_id', Auth::id())
            ->where('lida', false)
            ->update(['lida' => true]);

        return response()->json(['success' => true]);
    }
}
