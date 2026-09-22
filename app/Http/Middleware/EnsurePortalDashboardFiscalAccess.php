<?php

namespace App\Http\Middleware;

use App\Models\PortalUsuario;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalDashboardFiscalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var PortalUsuario $portalUsuario */
        $portalUsuario = Auth::guard('portal')->user();

        abort_unless($portalUsuario->cliente->hasProduto('Dashboard Fiscal'), 403);

        return $next($request);
    }
}
