<?php

namespace App\Http\Controllers;

use App\Models\NotaEmitente;
use App\Models\NotaEmitida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class NotaEmitidaController extends Controller
{
    public function index(Request $request): View
    {
        [$dataInicio, $dataFim] = $this->resolverPeriodo($request);

        $emitentes = NotaEmitente::query()
            ->where('ativo', true)
            ->with('cliente:id,nome,cpfcnpj')
            ->when($request->filled('busca'), function ($q) use ($request) {
                $busca = $request->input('busca');
                $q->where(function ($q) use ($busca) {
                    $q->where('nome', 'like', '%'.$busca.'%')
                        ->orWhereHas('cliente', fn ($q) => $q->where('nome', 'like', '%'.$busca.'%'));
                });
            })
            ->get()
            ->sortBy('nome_exibicao')
            ->values();

        $contagens = NotaEmitida::query()
            ->selectRaw('emitente_id, COUNT(*) as total')
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->where('estornado', false)
            ->groupBy('emitente_id')
            ->pluck('total', 'emitente_id');

        return view('notas-emitidas.index', [
            'emitentes' => $emitentes,
            'contagens' => $contagens,
            'totalGeral' => $contagens->sum(),
            'porDia' => $this->contagemPorDia($emitentes->pluck('id'), $dataInicio, $dataFim),
            'periodo' => $request->input('periodo', 'hoje'),
            'dataInicio' => $request->input('data_inicio'),
            'dataFim' => $request->input('data_fim'),
            'busca' => $request->input('busca'),
        ]);
    }

    /**
     * Dashboard "Notas por dia": quantidade de notas lançadas (não estornadas) por
     * dia, dentro do mesmo período e do mesmo filtro de busca já aplicados à lista
     * de emitentes acima — um ponto por dia do intervalo, com dias sem lançamento
     * aparecendo zerados.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $emitenteIds
     * @return array<int, array{dia: string, total: int}>
     */
    private function contagemPorDia($emitenteIds, Carbon $dataInicio, Carbon $dataFim): array
    {
        $porDia = NotaEmitida::query()
            ->selectRaw('DATE(created_at) as dia, COUNT(*) as total')
            ->whereIn('emitente_id', $emitenteIds)
            ->whereBetween('created_at', [$dataInicio, $dataFim])
            ->where('estornado', false)
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $dias = [];
        $cursor = $dataInicio->copy()->startOfDay();
        $fim = $dataFim->copy()->startOfDay();

        while ($cursor <= $fim && count($dias) < 400) {
            $chave = $cursor->format('Y-m-d');
            $dias[] = ['dia' => $chave, 'total' => (int) ($porDia[$chave] ?? 0)];
            $cursor->addDay();
        }

        return $dias;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'emitente_id' => ['required', 'exists:nota_emitentes,id'],
        ]);

        NotaEmitida::create([
            'emitente_id' => $data['emitente_id'],
            'usuario_id' => $request->user()->id,
        ]);

        return response()->json(['total' => $this->totalHoje($data['emitente_id'])]);
    }

    public function estornar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'emitente_id' => ['required', 'exists:nota_emitentes,id'],
        ]);

        NotaEmitida::query()
            ->where('emitente_id', $data['emitente_id'])
            ->where('estornado', false)
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->latest('id')
            ->first()
            ?->update(['estornado' => true]);

        return response()->json(['total' => $this->totalHoje($data['emitente_id'])]);
    }

    private function totalHoje(int $emitenteId): int
    {
        return NotaEmitida::query()
            ->where('emitente_id', $emitenteId)
            ->where('estornado', false)
            ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
            ->count();
    }

    private function resolverPeriodo(Request $request): array
    {
        $periodo = $request->input('periodo', 'hoje');

        return match ($periodo) {
            'semana' => [now()->startOfWeek(), now()->endOfWeek()],
            'mes' => [now()->startOfMonth(), now()->endOfMonth()],
            'personalizado' => [
                Carbon::parse($request->input('data_inicio', now()->startOfMonth()))->startOfDay(),
                Carbon::parse($request->input('data_fim', now()->endOfMonth()))->endOfDay(),
            ],
            default => [now()->startOfDay(), now()->endOfDay()], // hoje
        };
    }
}
