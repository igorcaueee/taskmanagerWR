<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleCalendarController extends Controller
{
    public function __construct(private readonly GoogleCalendarService $googleCalendar) {}

    public function redirect(): RedirectResponse
    {
        return redirect($this->googleCalendar->getAuthUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('agenda')->with('error', 'Conexão com o Google cancelada.');
        }

        $code = $request->string('code')->toString();

        try {
            $token = $this->googleCalendar->exchangeCode($code);
        } catch (\RuntimeException $e) {
            return redirect()->route('agenda')->with('error', 'Não foi possível conectar ao Google: '.$e->getMessage());
        }

        $usuario = Auth::user();
        $usuario->update([
            'google_access_token' => $token['access_token'],
            'google_refresh_token' => $token['refresh_token'] ?? $usuario->google_refresh_token,
            'google_token_expires_at' => Carbon::now()->addSeconds($token['expires_in'] ?? 3600),
            'google_calendar_id' => 'primary',
        ]);

        return redirect()->route('agenda')->with('success', 'Google Calendar conectado com sucesso!');
    }

    public function disconnect(): RedirectResponse
    {
        Auth::user()->update([
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'google_last_synced_at' => null,
        ]);

        return redirect()->route('agenda')->with('success', 'Google Calendar desconectado.');
    }

    public function sync(): RedirectResponse
    {
        $usuario = Auth::user();

        if (! $usuario->isGoogleConnected()) {
            return redirect()->route('agenda')->with('error', 'Google Calendar não está conectado.');
        }

        try {
            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->addMonths(2)->endOfMonth();

            $this->googleCalendar->pullEvents($usuario, $start, $end);
        } catch (\Exception $e) {
            return redirect()->route('agenda')->with('error', 'Erro ao sincronizar: '.$e->getMessage());
        }

        return redirect()->route('agenda')->with('success', 'Agenda sincronizada com o Google Calendar!');
    }
}
