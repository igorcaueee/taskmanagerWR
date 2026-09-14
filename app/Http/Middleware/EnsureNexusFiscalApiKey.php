<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticação server-to-server simples (API key fixa) para a API de leitura
 * do cofre fiscal consumida pelo Nexus. Não usa sessão/cookie/CSRF — a chave
 * vem sempre no header X-Api-Key e é comparada em tempo constante.
 */
class EnsureNexusFiscalApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = (string) config('services.nexus_fiscal.api_key');
        $informada = (string) $request->header('X-Api-Key', '');

        if ($apiKey === '' || ! hash_equals($apiKey, $informada)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return $next($request);
    }
}
