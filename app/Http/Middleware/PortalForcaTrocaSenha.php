<?php

namespace App\Http\Middleware;

use App\Models\PortalUsuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redireciona pra tela de troca de senha sempre que o usuário do portal loga
 * com uma senha definida por um admin (criação ou reset) — ver deve_trocar_senha
 * em PortalUsuario. Fica dentro do grupo `portal.auth`, mas fora dele ficam as
 * próprias rotas de trocar-senha/logout, senão o cliente ficaria preso em loop.
 */
class PortalForcaTrocaSenha
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();

        if ($portalUsuario->deve_trocar_senha) {
            return redirect()->route('portal.trocar-senha');
        }

        return $next($request);
    }
}
