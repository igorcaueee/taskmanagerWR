<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('campanhas:enviar-agendadas')->everyMinute();
Schedule::command('ciclos:passar-afazer')->weeklyOn(1, '07:00');
