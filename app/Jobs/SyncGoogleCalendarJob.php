<?php

namespace App\Jobs;

use App\Models\Usuario;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGoogleCalendarJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly int $usuarioId, public readonly int $monthsAhead = 2) {}

    public function handle(GoogleCalendarService $googleCalendar): void
    {
        $usuario = Usuario::find($this->usuarioId);

        if (! $usuario || ! $usuario->isGoogleConnected()) {
            return;
        }

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->addMonths($this->monthsAhead)->endOfMonth();

        $googleCalendar->pullEvents($usuario, $start, $end);
    }
}
