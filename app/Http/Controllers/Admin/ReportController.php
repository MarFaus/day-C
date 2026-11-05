<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesOwnedEvent;
use App\Http\Controllers\Controller;
use App\Models\Channel;

class ReportController extends Controller
{
    use ResolvesOwnedEvent;

    public function roomCapacity(int $event)
    {
        $event = $this->ownedEvent($event);
        $event->load('channels.rooms.sessions');

        $registrationCount = $event->registrationCount();

        $rows = $event->channels
            ->flatMap(fn (Channel $channel) => $channel->rooms->flatMap(
                fn ($room) => $room->sessions->map(function ($session) use ($room, $registrationCount) {
                    $attendees = $session->type === 'talk'
                        ? $registrationCount
                        : $session->registrations()->count();

                    return [
                        'id' => $session->id,
                        'title' => $session->title,
                        'room' => $room->name,
                        'capacity' => (int) $room->capacity,
                        'attendees' => $attendees,
                        'exceeded' => $attendees > (int) $room->capacity,
                    ];
                })
            ))
            ->sortBy('title')
            ->values();

        return view('reports.room-capacity', compact('event', 'rows'));
    }
}
