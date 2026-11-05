<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesOwnedEvent;
use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    use ResolvesOwnedEvent;

    public function create(int $event)
    {
        $event = $this->ownedEvent($event);

        return view('sessions.create', [
            'organizer' => $this->organizer(),
            'event' => $event,
            'rooms' => $this->roomOptions($event),
        ]);
    }

    public function store(Request $request, int $event)
    {
        $event = $this->ownedEvent($event);
        $result = $this->validated($request, $event->id);

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        EventSession::query()->create($result);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Session successfully created');
    }

    public function edit(int $event, int $session)
    {
        $event = $this->ownedEvent($event);
        $session = $this->sessionOf($event, $session);

        return view('sessions.edit', [
            'organizer' => $this->organizer(),
            'event' => $event,
            'session' => $session,
            'rooms' => $this->roomOptions($event),
        ]);
    }

    public function update(Request $request, int $event, int $session)
    {
        $event = $this->ownedEvent($event);
        $session = $this->sessionOf($event, $session);
        $result = $this->validated($request, $event->id, $session->id);

        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            return $result;
        }

        $session->update($result);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Session successfully updated');
    }

    private function sessionOf($event, int $sessionId): EventSession
    {
        $session = EventSession::query()
            ->whereHas('room.channel', fn ($query) => $query->where('event_id', $event->id))
            ->findOrFail($sessionId);

        return $session;
    }

    private function roomOptions(Event $event): array
    {
        return $event->channels()
            ->with('rooms')
            ->get()
            ->flatMap(fn (Channel $channel) => $channel->rooms->map(
                fn (Room $room) => [
                    'id' => $room->id,
                    'label' => $room->name.' / '.$channel->name,
                ]
            ))
            ->values()
            ->all();
    }

    private function validated(Request $request, int $eventId, ?int $ignoreId = null): array|\Illuminate\Http\RedirectResponse
    {
        $errors = [];
        $type = (string) $request->input('type', 'talk');
        $title = trim((string) $request->input('title'));
        $speaker = trim((string) $request->input('speaker'));
        $description = trim((string) $request->input('description'));
        $roomId = $request->input('room');
        $cost = $request->input('cost');
        $startRaw = trim((string) $request->input('start'));
        $endRaw = trim((string) $request->input('end'));

        if (! in_array($type, ['talk', 'workshop'], true)) {
            $errors['type'] = 'Type is invalid.';
        }

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        }

        if ($speaker === '') {
            $errors['speaker'] = 'Speaker is required.';
        }

        if ($description === '') {
            $errors['description'] = 'Description is required.';
        }

        $room = $this->roomForEvent($roomId, $eventId);
        if (! $room) {
            $errors['room'] = 'Room is required.';
        }

        $start = $this->parseDate($startRaw);
        $end = $this->parseDate($endRaw);

        if (! $start) {
            $errors['start'] = 'Start is required (yyyy-mm-dd HH:MM).';
        }

        if (! $end) {
            $errors['end'] = 'End is required (yyyy-mm-dd HH:MM).';
        }

        if ($start && $end && $end->lessThanOrEqualTo($start)) {
            $errors['end'] = 'End must be after start.';
        }

        if ($room && $start && $end) {
            $conflict = EventSession::query()
                ->where('room_id', $room->id)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('start', '<', $end->format('Y-m-d H:i:s'))
                ->where('end', '>', $start->format('Y-m-d H:i:s'))
                ->exists();

            if ($conflict) {
                return back()
                    ->withInput()
                    ->withErrors(['room' => 'Room already booked during this time']);
            }
        }

        if ($type === 'workshop' && ($cost === null || $cost === '')) {
            $cost = 0;
        }

        if ($cost !== null && $cost !== '' && (! is_numeric($cost) || (float) $cost < 0)) {
            $errors['cost'] = 'Cost must be a positive number.';
        }

        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        return [
            'room_id' => $room->id,
            'title' => $title,
            'description' => $description,
            'speaker' => $speaker,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
            'type' => $type,
            'cost' => $type === 'talk' ? null : round((float) $cost, 2),
        ];
    }

    private function roomForEvent($roomId, int $eventId): ?Room
    {
        if (! $roomId) {
            return null;
        }

        return Room::query()
            ->whereHas('channel', fn ($query) => $query->where('event_id', $eventId))
            ->find($roomId);
    }

    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d H:i', 'Y-m-d H:i:s', 'Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date;
                }
            } catch (\Throwable) {
                // try the next format
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
