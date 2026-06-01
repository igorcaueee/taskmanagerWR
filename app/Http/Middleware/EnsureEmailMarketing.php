<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailMarketing
{
    /**
     * Permite acesso apenas para Diretor e TI.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! $usuario->canEmailMarketing()) {
            abort(403);
        }

        return $next($request);
    }
}
