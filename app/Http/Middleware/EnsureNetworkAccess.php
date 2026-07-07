<?php

namespace App\Http\Middleware;

use App\Models\TentativaAcessoBloqueada;
use App\Services\AcessoRedeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNetworkAccess
{
    public function __construct(private AcessoRedeService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! $this->service->acessoPermitido(Auth::user(), $request->ip())) {
            TentativaAcessoBloqueada::create([
                'usuario_id' => Auth::id(),
                'ip'         => $request->ip(),
                'url'        => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Acesso permitido somente dentro da rede da WR. Solicite liberação a um Diretor ou TI.',
            ]);
        }

        return $next($request);
    }
}
