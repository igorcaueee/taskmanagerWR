<?php

namespace App\Http\Controllers;

use App\Models\LiberacaoAcessoExterno;
use App\Models\RedePermitida;
use App\Models\TentativaAcessoBloqueada;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcessoExternoController extends Controller
{
    public function index()
    {
        $redes = RedePermitida::with('criadoPor')->orderByDesc('created_at')->get();

        $liberacoes = LiberacaoAcessoExterno::with(['usuario', 'liberadoPor'])
            ->where('ativo', true)
            ->orderByDesc('created_at')
            ->get();

        $historico = LiberacaoAcessoExterno::with(['usuario', 'liberadoPor'])
            ->where('ativo', false)
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        $usuarios = Usuario::where('cargo', '!=', 'diretor')
            ->orderBy('nome')
            ->get();

        $tentativas = TentativaAcessoBloqueada::with('usuario')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        // IPs com 5+ tentativas nas últimas 24h são sinalizados como possível ameaça
        $ipsSuspeitos = TentativaAcessoBloqueada::where('created_at', '>=', now()->subDay())
            ->selectRaw('ip, count(*) as total')
            ->groupBy('ip')
            ->having('total', '>=', 5)
            ->orderByDesc('total')
            ->get();

        return view('acesso-externo.index', compact('redes', 'liberacoes', 'historico', 'usuarios', 'tentativas', 'ipsSuspeitos'));
    }

    public function storeRede(Request $request)
    {
        $data = $request->validate([
            'cidr'      => ['required', 'string', 'max:50'],
            'descricao' => ['nullable', 'string', 'max:150'],
        ]);

        RedePermitida::create([
            ...$data,
            'ativo'     => true,
            'criado_por' => Auth::id(),
        ]);

        return redirect()->route('acesso-externo.index')->with('success', 'Rede cadastrada com sucesso.');
    }

    public function toggleRede(RedePermitida $rede)
    {
        $rede->update(['ativo' => ! $rede->ativo]);

        $estado = $rede->ativo ? 'ativada' : 'desativada';

        return redirect()->route('acesso-externo.index')->with('success', "Rede {$estado} com sucesso.");
    }

    public function destroyRede(RedePermitida $rede)
    {
        $rede->delete();

        return redirect()->route('acesso-externo.index')->with('success', 'Rede removida.');
    }

    public function storeLiberacao(Request $request)
    {
        $data = $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'expira_em'  => ['nullable', 'date', 'after:now'],
            'motivo'     => ['nullable', 'string', 'max:255'],
        ]);

        LiberacaoAcessoExterno::create([
            'usuario_id'     => $data['usuario_id'],
            'liberado_por_id' => Auth::id(),
            'expira_em'      => $data['expira_em'] ?? null,
            'motivo'         => $data['motivo'] ?? null,
            'ativo'          => true,
        ]);

        return redirect()->route('acesso-externo.index')->with('success', 'Liberação concedida com sucesso.');
    }

    public function revogarLiberacao(LiberacaoAcessoExterno $liberacao)
    {
        $liberacao->update(['ativo' => false]);

        return redirect()->route('acesso-externo.index')->with('success', 'Liberação revogada.');
    }
}
