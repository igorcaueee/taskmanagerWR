<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureColaboradoresAccess
{
    /**
     * Permite acesso apenas para Diretor, TI e Supervisor.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->canVerColaboradores()) {
            abort(403);
        }

        return $next($request);
    }
}
