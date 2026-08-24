<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureColaboradoresAccess;
use App\Http\Middleware\EnsureColaboradoresEdit;
use App\Http\Middleware\EnsureDiretor;
use App\Http\Middleware\EnsureEmailMarketing;
use App\Http\Middleware\EnsureNetworkAccess;
use App\Http\Middleware\EnsurePrecificacaoAccess;
use App\Http\Middleware\EnsureReinfAccess;
use App\Http\Middleware\EnsureTiAccess;
use App\Http\Middleware\PortalAuth;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
        $middleware->web(append: EnsureNetworkAccess::class);
        $middleware->alias([
            'diretor' => EnsureDiretor::class,
            'email-marketing' => EnsureEmailMarketing::class,
            'colaboradores' => EnsureColaboradoresAccess::class,
            'colaboradores.edit' => EnsureColaboradoresEdit::class,
            'admin' => EnsureAdmin::class,
            'reinf-access' => EnsureReinfAccess::class,
            'ti-access' => EnsureTiAccess::class,
            'portal.auth' => PortalAuth::class,
            'portal.precificacao' => EnsurePrecificacaoAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $e): void {
        //
    })->create();
