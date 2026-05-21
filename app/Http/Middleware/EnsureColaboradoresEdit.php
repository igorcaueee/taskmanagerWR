<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureColaboradoresEdit
{
    /**
     * Permite edição apenas para Diretor e TI. Supervisor só visualiza.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->canEditarColaboradores()) {
            abort(403);
        }

        return $next($request);
    }
}
