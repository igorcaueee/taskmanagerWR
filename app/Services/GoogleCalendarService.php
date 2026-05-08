<?php

namespace App\Services;

use App\Models\Compromisso;
use App\Models\Usuario;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Exception;
use Illuminate\Support\Collection;

class GoogleCalendarService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client;
        $this->client->setApplicationName('TaskManager WR');
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->setScopes([Calendar::CALENDAR]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
    }

    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    public function exchangeCode(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new \RuntimeException('Erro ao obter token do Google: '.$token['error_description'] ?? $token['error']);
        }

        return $token;
    }

    public function setUserToken(Usuario $usuario): void
    {
        $token = [
            'access_token' => $usuario->google_access_token,
            'refresh_token' => $usuario->google_refresh_token,
            'expires_in' => $usuario->google_token_expires_at
                ? now()->diffInSeconds($usuario->google_token_expires_at, false)
                : 0,
        ];

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired() && $usuario->google_refresh_token) {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($usuario->google_refresh_token);

            $usuario->update([
                'google_access_token' => $newToken['access_token'],
                'google_token_expires_at' => Carbon::now()->addSeconds($newToken['expires_in'] ?? 3600),
            ]);

            $this->client->setAccessToken($newToken);
        }
    }

    public function createEvent(Usuario $usuario, Compromisso $compromisso): ?string
    {
        $this->setUserToken($usuario);
        $service = new Calendar($this->client);
        $calendarId = $usuario->google_calendar_id ?? 'primary';

        $event = $this->buildEvent($compromisso);

        $created = $service->events->insert($calendarId, $event);

        return $created->getId();
    }

    public function updateEvent(Usuario $usuario, Compromisso $compromisso): void
    {
        if (! $compromisso->google_event_id) {
            $eventId = $this->createEvent($usuario, $compromisso);
            $compromisso->updateQuietly(['google_event_id' => $eventId]);

            return;
        }

        $this->setUserToken($usuario);
        $service = new Calendar($this->client);
        $calendarId = $usuario->google_calendar_id ?? 'primary';

        $event = $this->buildEvent($compromisso);

        try {
            $service->events->update($calendarId, $compromisso->google_event_id, $event);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                $eventId = $this->createEvent($usuario, $compromisso);
                $compromisso->updateQuietly(['google_event_id' => $eventId]);
            } else {
                throw $e;
            }
        }
    }

    public function deleteEvent(Usuario $usuario, string $googleEventId): void
    {
        $this->setUserToken($usuario);
        $service = new Calendar($this->client);
        $calendarId = $usuario->google_calendar_id ?? 'primary';

        try {
            $service->events->delete($calendarId, $googleEventId);
        } catch (Exception $e) {
            if ($e->getCode() !== 404 && $e->getCode() !== 410) {
                throw $e;
            }
        }
    }

    /**
     * Pull events from Google Calendar and upsert them locally.
     *
     * @return Collection<int, Compromisso>
     */
    public function pullEvents(Usuario $usuario, Carbon $start, Carbon $end): Collection
    {
        $this->setUserToken($usuario);
        $service = new Calendar($this->client);
        $calendarId = $usuario->google_calendar_id ?? 'primary';

        $results = $service->events->listEvents($calendarId, [
            'timeMin' => $start->toRfc3339String(),
            'timeMax' => $end->toRfc3339String(),
            'singleEvents' => true,
            'orderBy' => 'startTime',
        ]);

        $synced = collect();

        foreach ($results->getItems() as $googleEvent) {
            /** @var Event $googleEvent */
            if ($googleEvent->getStatus() === 'cancelled') {
                Compromisso::where('google_event_id', $googleEvent->getId())->delete();

                continue;
            }

            $startData = $googleEvent->getStart();
            $data = $startData->getDateTime()
                ? Carbon::parse($startData->getDateTime())
                : Carbon::parse($startData->getDate());

            $hora = $startData->getDateTime()
                ? $data->format('H:i:s')
                : null;

            $compromisso = Compromisso::updateOrCreate(
                ['google_event_id' => $googleEvent->getId()],
                [
                    'titulo' => $googleEvent->getSummary() ?? '(Sem título)',
                    'descricao' => $googleEvent->getDescription(),
                    'data' => $data->toDateString(),
                    'hora' => $hora,
                    'cor' => $this->resolveGoogleColor($googleEvent->getColorId()),
                    'criado_por' => $usuario->id,
                ]
            );

            $synced->push($compromisso);
        }

        $usuario->update(['google_last_synced_at' => now()]);

        return $synced;
    }

    private function buildEvent(Compromisso $compromisso): Event
    {
        $event = new Event([
            'summary' => $compromisso->titulo,
            'description' => $compromisso->descricao,
        ]);

        if ($compromisso->hora) {
            $dateTime = Carbon::parse($compromisso->data->toDateString().' '.$compromisso->hora);
            $start = new EventDateTime(['dateTime' => $dateTime->toRfc3339String(), 'timeZone' => config('app.timezone')]);
            $end = new EventDateTime(['dateTime' => $dateTime->addHour()->toRfc3339String(), 'timeZone' => config('app.timezone')]);
        } else {
            $start = new EventDateTime(['date' => $compromisso->data->toDateString()]);
            $end = new EventDateTime(['date' => $compromisso->data->toDateString()]);
        }

        $event->setStart($start);
        $event->setEnd($end);
        $event->setColorId($this->resolveLocalColor($compromisso->cor));

        return $event;
    }

    /**
     * Map a hex color to the closest Google Calendar colorId (1–11).
     */
    private function resolveLocalColor(?string $hex): string
    {
        $map = [
            '#ef4444' => '11', // Vermelho → Tomato
            '#f87171' => '11', // Salmão → Tomato
            '#f97316' => '6',  // Laranja → Tangerine
            '#f59e0b' => '5',  // Amarelo → Banana
            '#16a34a' => '2',  // Verde Escuro → Sage
            '#10b981' => '2',  // Verde → Sage
            '#14b8a6' => '7',  // Teal → Peacock
            '#3b82f6' => '1',  // Azul → Blueberry
            '#8b5cf6' => '3',  // Roxo → Grape
            '#6366f1' => '9',  // Índigo → Blueberry
            '#a855f7' => '3',  // Violeta → Grape
        ];

        return $map[$hex] ?? '1';
    }

    /**
     * Map a Google Calendar colorId back to a local hex color.
     */
    private function resolveGoogleColor(?string $colorId): string
    {
        $map = [
            '1' => '#3b82f6', // Lavender → Azul
            '2' => '#10b981', // Sage → Verde
            '3' => '#8b5cf6', // Grape → Roxo
            '4' => '#f87171', // Flamingo → Salmão
            '5' => '#f59e0b', // Banana → Amarelo
            '6' => '#f97316', // Tangerine → Laranja
            '7' => '#14b8a6', // Peacock → Teal
            '8' => '#6366f1', // Graphite → Índigo
            '9' => '#6366f1', // Blueberry → Índigo
            '10' => '#16a34a', // Basil → Verde Escuro
            '11' => '#ef4444', // Tomato → Vermelho
        ];

        return $map[$colorId ?? '1'] ?? '#3b82f6';
    }
}
