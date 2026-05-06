<?php

use App\Http\Middleware\EnsureColaboradoresAccess;
use App\Http\Middleware\EnsureDiretor;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'diretor'        => EnsureDiretor::class,
            'colaboradores'  => EnsureColaboradoresAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $e): void {
        //
    })->create();
