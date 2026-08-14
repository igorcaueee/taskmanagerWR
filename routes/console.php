<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('campanhas:enviar-agendadas')->everyMinute();
Schedule::command('ciclos:passar-afazer')->monthlyOn(1, '07:00');
Schedule::command('certificados:verificar')->dailyAt('08:00');
Schedule::command('fiscal:sincronizar-notas-rs')->dailyAt('18:30')->withoutOverlapping()->runInBackground();
