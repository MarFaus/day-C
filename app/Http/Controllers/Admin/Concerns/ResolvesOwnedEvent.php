<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Event;
use App\Models\Organizer;

trait ResolvesOwnedEvent
{
    protected function organizer(): Organizer
    {
        return Organizer::query()->findOrFail(session('organizer_id'));
    }

    protected function ownedEvent(int $id): Event
    {
        $event = Event::query()
            ->where('organizer_id', session('organizer_id'))
            ->find($id);

        abort_if(! $event, 404);

        return $event;
    }
}
