<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\EventRating;
use App\Models\EventSession;
use App\Models\Organizer;
use App\Models\Registration;
use App\Models\SessionRating;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendeeApiController extends Controller
{
    public function events(): JsonResponse
    {
        $from = config('app.events_upcoming_from')
            ? Carbon::parse(config('app.events_upcoming_from'))->startOfDay()
            : Carbon::today();

        $events = Event::query()
            ->with('organizer')
            ->whereDate('date', '>=', $from->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'events' => $events->map(fn (Event $event) => [
                'id' => (int) $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'date' => $event->date->format('Y-m-d'),
                'organizer' => [
                    'id' => (int) $event->organizer->id,
                    'name' => $event->organizer->name,
                    'slug' => $event->organizer->slug,
                ],
            ])->all(),
        ]);
    }

    public function event(string $organizerSlug, string $eventSlug): JsonResponse
    {
        $organizer = Organizer::query()->where('slug', $organizerSlug)->first();

        if (! $organizer) {
            return response()->json(['message' => 'Organizer not found'], 404);
        }

        $event = Event::query()
            ->where('organizer_id', $organizer->id)
            ->where('slug', $eventSlug)
            ->first();

        if (! $event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $event->load(['channels.rooms.sessions', 'tickets']);

        return response()->json($this->eventDetailPayload($event));
    }

    public function login(Request $request): JsonResponse
    {
        $lastname = trim((string) $request->input('lastname'));
        $code = trim((string) $request->input('registration_code'));

        if ($lastname === '' || $code === '') {
            return response()->json(['message' => 'Invalid login'], 401);
        }

        $attendee = Attendee::query()
            ->where('lastname', $lastname)
            ->where('registration_code', $code)
            ->first();

        if (! $attendee) {
            return response()->json(['message' => 'Invalid login'], 401);
        }

        $token = md5($attendee->username);
        $attendee->forceFill(['login_token' => $token])->save();

        return response()->json([
            'firstname' => $attendee->firstname,
            'lastname' => $attendee->lastname,
            'username' => $attendee->username,
            'email' => $attendee->email,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = (string) $request->query('token', '');

        $attendee = $token !== ''
            ? Attendee::query()->where('login_token', $token)->first()
            : null;

        if (! $attendee) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $attendee->forceFill(['login_token' => null])->save();

        return response()->json(['message' => 'Logout success']);
    }

    public function register(Request $request, string $organizerSlug, string $eventSlug): JsonResponse
    {
        $attendee = $this->attendeeFromToken($request);

        if (! $attendee) {
            return response()->json(['message' => 'User not logged in'], 401);
        }

        $organizer = Organizer::query()->where('slug', $organizerSlug)->first();

        if (! $organizer) {
            return response()->json(['message' => 'Organizer not found'], 404);
        }

        $event = Event::query()
            ->where('organizer_id', $organizer->id)
            ->where('slug', $eventSlug)
            ->first();

        if (! $event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $alreadyRegistered = Registration::query()
            ->join('event_tickets', 'event_tickets.id', '=', 'registrations.ticket_id')
            ->where('registrations.attendee_id', $attendee->id)
            ->where('event_tickets.event_id', $event->id)
            ->exists();

        if ($alreadyRegistered) {
            return response()->json(['message' => 'User already registered'], 401);
        }

        $ticket = $event->tickets()->find((int) $request->input('ticket_id'));

        if (! $ticket || ! $ticket->isAvailable()) {
            return response()->json(['message' => 'Ticket is no longer available'], 401);
        }

        $sessionIds = collect($request->input('session_ids', []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $validSessionIds = EventSession::query()
            ->whereIn('sessions.id', $sessionIds)
            ->whereHas('room.channel', fn ($query) => $query->where('event_id', $event->id))
            ->pluck('sessions.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        DB::transaction(function () use ($attendee, $ticket, $validSessionIds) {
            $registration = Registration::query()->create([
                'attendee_id' => $attendee->id,
                'ticket_id' => $ticket->id,
                'registration_time' => Carbon::now(),
            ]);

            foreach ($validSessionIds as $sessionId) {
                $registration->sessionRegistrations()->create(['session_id' => $sessionId]);
            }
        });

        return response()->json(['message' => 'Registration successful']);
    }

    public function registrations(Request $request): JsonResponse
    {
        $attendee = $this->attendeeFromToken($request);

        if (! $attendee) {
            return response()->json(['message' => 'User not logged in'], 401);
        }

        $registrations = Registration::query()
            ->where('attendee_id', $attendee->id)
            ->orderBy('id')
            ->get();

        $payload = $registrations->map(function (Registration $registration) {
            $event = $registration->ticket?->event;

            if (! $event) {
                return null;
            }

            return [
                'event' => [
                    'id' => (int) $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                    'date' => $event->date->format('Y-m-d'),
                    'organizer' => [
                        'id' => (int) $event->organizer->id,
                        'name' => $event->organizer->name,
                        'slug' => $event->organizer->slug,
                    ],
                ],
                'session_ids' => $registration->sessionIds(),
            ];
        })->filter()->values();

        return response()->json(['registrations' => $payload->all()]);
    }

    public function ratings(Request $request): JsonResponse
    {
        $attendee = $this->attendeeFromToken($request);

        if (! $attendee) {
            return response()->json(['message' => 'User not logged in'], 401);
        }

        return response()->json([
            'events' => EventRating::query()
                ->where('attendee_id', $attendee->id)
                ->get(['event_id', 'rating'])
                ->map(fn ($rating) => ['event_id' => (int) $rating->event_id, 'rating' => (int) $rating->rating])
                ->all(),
            'sessions' => SessionRating::query()
                ->where('attendee_id', $attendee->id)
                ->get(['session_id', 'rating'])
                ->map(fn ($rating) => ['session_id' => (int) $rating->session_id, 'rating' => (int) $rating->rating])
                ->all(),
        ]);
    }

    public function rateEvent(Request $request, int $event): JsonResponse
    {
        $attendee = $this->attendeeFromToken($request);

        if (! $attendee) {
            return response()->json(['message' => 'User not logged in'], 401);
        }

        $rating = filter_var($request->input('rating'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5],
        ]);

        if ($rating === false) {
            return response()->json(['message' => 'Rating must be a number between 1 and 5'], 422);
        }

        $model = Event::query()->find($event);

        if (! $model) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $registered = Registration::query()
            ->join('event_tickets', 'event_tickets.id', '=', 'registrations.ticket_id')
            ->where('registrations.attendee_id', $attendee->id)
            ->where('event_tickets.event_id', $model->id)
            ->exists();

        if (! $registered) {
            return response()->json(['message' => 'You can only rate events you attended'], 403);
        }

        EventRating::query()->updateOrCreate(
            ['attendee_id' => $attendee->id, 'event_id' => $model->id],
            ['rating' => $rating, 'created_at' => Carbon::now()]
        );

        return response()->json(['message' => 'Rating saved']);
    }

    public function rateSession(Request $request, int $session): JsonResponse
    {
        $attendee = $this->attendeeFromToken($request);

        if (! $attendee) {
            return response()->json(['message' => 'User not logged in'], 401);
        }

        $rating = filter_var($request->input('rating'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5],
        ]);

        if ($rating === false) {
            return response()->json(['message' => 'Rating must be a number between 1 and 5'], 422);
        }

        $model = EventSession::query()->find($session);

        if (! $model) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $event = $model->event();
        $registered = $event && Registration::query()
            ->join('event_tickets', 'event_tickets.id', '=', 'registrations.ticket_id')
            ->where('registrations.attendee_id', $attendee->id)
            ->where('event_tickets.event_id', $event->id)
            ->exists();

        if (! $registered) {
            return response()->json(['message' => 'You can only rate sessions you attended'], 403);
        }

        SessionRating::query()->updateOrCreate(
            ['attendee_id' => $attendee->id, 'session_id' => $model->id],
            ['rating' => $rating, 'created_at' => Carbon::now()]
        );

        return response()->json(['message' => 'Rating saved']);
    }

    private function attendeeFromToken(Request $request): ?Attendee
    {
        $token = (string) $request->query('token', '');

        if ($token === '') {
            return null;
        }

        return Attendee::query()->where('login_token', $token)->first();
    }

    private function eventDetailPayload(Event $event): array
    {
        $channels = $event->channels
            ->map(fn ($channel) => [
                'id' => (int) $channel->id,
                'name' => $channel->name,
                'rooms' => $channel->rooms
                    ->map(fn ($room) => [
                        'id' => (int) $room->id,
                        'name' => $room->name,
                        'sessions' => $room->sessions
                            ->sortBy('id')
                            ->values()
                            ->map(fn (EventSession $session) => [
                                'id' => (int) $session->id,
                                'title' => $session->title,
                                'description' => $session->description,
                                'speaker' => $session->speaker,
                                'start' => $session->start->format('Y-m-d H:i:s'),
                                'end' => $session->end->format('Y-m-d H:i:s'),
                                'type' => $session->type,
                                'cost' => $session->cost === null ? null : (float) $session->cost,
                            ])
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        $tickets = $event->tickets
            ->map(fn (Ticket $ticket) => [
                'id' => (int) $ticket->id,
                'name' => $ticket->name,
                'description' => $ticket->description(),
                'cost' => $ticket->cost === null ? null : (float) $ticket->cost,
                'available' => $ticket->isAvailable(),
            ])
            ->values()
            ->all();

        return [
            'id' => (int) $event->id,
            'name' => $event->name,
            'slug' => $event->slug,
            'date' => $event->date->format('Y-m-d'),
            'channels' => $channels,
            'tickets' => $tickets,
        ];
    }
}
