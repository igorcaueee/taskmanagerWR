<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Limite de concorrência para não estourar o rate limit da API Integra Contador (SERPRO)
        // ao processar toda a carteira de clientes em lote.
        RateLimiter::for('integra-contador', function () {
            return Limit::perMinute(30);
        });
    }
}
