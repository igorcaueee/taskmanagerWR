<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReinfAccess
{
    /**
     * Permite acesso apenas para Diretor e TI — EFD-Reinf ainda em desenvolvimento.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->canAcessarReinf()) {
            abort(403);
        }

        return $next($request);
    }
}
