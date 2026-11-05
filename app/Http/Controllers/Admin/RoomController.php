<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesOwnedEvent;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    use ResolvesOwnedEvent;

    public function create(int $event)
    {
        $event = $this->ownedEvent($event);

        return view('rooms.create', [
            'organizer' => $this->organizer(),
            'event' => $event,
            'channels' => $event->channels()->get(),
        ]);
    }

    public function store(Request $request, int $event)
    {
        $event = $this->ownedEvent($event);
        $data = $this->validated($request, $event->id);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        Room::query()->create($data);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Room successfully created');
    }

    public function edit(int $event, int $room)
    {
        $event = $this->ownedEvent($event);

        $room = Room::query()
            ->whereHas('channel', fn ($query) => $query->where('event_id', $event->id))
            ->findOrFail($room);

        return view('rooms.edit', [
            'organizer' => $this->organizer(),
            'event' => $event,
            'room' => $room,
            'channels' => $event->channels()->get(),
        ]);
    }

    public function update(Request $request, int $event, int $room)
    {
        $event = $this->ownedEvent($event);

        $room = Room::query()
            ->whereHas('channel', fn ($query) => $query->where('event_id', $event->id))
            ->findOrFail($room);

        $data = $this->validated($request, $event->id);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $room->update($data);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Room successfully updated');
    }

    private function validated(Request $request, int $eventId): array|\Illuminate\Http\RedirectResponse
    {
        $errors = [];
        $name = trim((string) $request->input('name'));
        $capacity = $request->input('capacity');
        $channelId = $request->input('channel');

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        $channelExists = $eventId && \App\Models\Channel::query()
            ->where('event_id', $eventId)
            ->whereKey($channelId)
            ->exists();

        if (! $channelExists) {
            $errors['channel'] = 'Channel is required.';
        }

        if ($capacity === null || $capacity === '' || ! is_numeric($capacity) || (int) $capacity < 1) {
            $errors['capacity'] = 'Capacity is required.';
        }

        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        return [
            'channel_id' => (int) $channelId,
            'name' => $name,
            'capacity' => (int) $capacity,
        ];
    }
}
