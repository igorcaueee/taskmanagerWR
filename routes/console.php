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
// Roda depois da sincronização normal (que pode ir até 07:00) pra não disputar o
// certificado compartilhado da contabilidade e gerar bloqueio por "consumo indevido".
Schedule::command('fiscal:reconsultar-notas-rs')->dailyAt('07:15')->withoutOverlapping()->runInBackground();
// Depois da reconsulta (07:15), pra já auditar com o dia sincronizado.
Schedule::command('fiscal:alertas-nfe')->dailyAt('07:45')->withoutOverlapping()->runInBackground();
