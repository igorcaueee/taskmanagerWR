<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTiAccess
{
    /**
     * Permite acesso apenas para TI — configuração da API (certificado/chaves SERPRO).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->canAcessarConfiguracaoApi()) {
            abort(403);
        }

        return $next($request);
    }
}
