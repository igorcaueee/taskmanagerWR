<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureColaboradoresAccess;
use App\Http\Middleware\EnsureColaboradoresEdit;
use App\Http\Middleware\EnsureDiretor;
use App\Http\Middleware\EnsureEmailMarketing;
use App\Http\Middleware\PortalAuth;
use App\Http\Middleware\SecurityHeaders;
use App\Jobs\SincronizarEmpresaContaAzul;
use App\Models\Cliente;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('certificados:verificar')->dailyAt('08:00');

        // Sincroniza Conta Azul a cada 2 horas para empresas conectadas
        $schedule->call(function () {
            Cliente::where('conta_azul_conectada', true)->each(
                fn (Cliente $c) => SincronizarEmpresaContaAzul::dispatch($c->id)
            );
        })->everyTwoHours()->name('conta-azul:sync-all')->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'diretor' => EnsureDiretor::class,
            'email-marketing' => EnsureEmailMarketing::class,
            'colaboradores' => EnsureColaboradoresAccess::class,
            'colaboradores.edit' => EnsureColaboradoresEdit::class,
            'admin' => EnsureAdmin::class,
            'portal.auth' => PortalAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $e): void {
        //
    })->create();
