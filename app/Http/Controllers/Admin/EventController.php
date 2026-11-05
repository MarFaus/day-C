<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesOwnedEvent;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ResolvesOwnedEvent;

    public function index()
    {
        $organizer = $this->organizer();

        $events = Event::query()
            ->where('organizer_id', $organizer->id)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $registrationCounts = $events->mapWithKeys(
            fn (Event $event) => [$event->id => $event->registrationCount()]
        );

        return view('events.index', compact('organizer', 'events', 'registrationCounts'));
    }

    public function create()
    {
        return view('events.create', ['organizer' => $this->organizer()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $event = Event::query()->create([
            'organizer_id' => session('organizer_id'),
            'name' => $data['name'],
            'slug' => $data['slug'],
            'date' => $data['date'],
        ]);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Event successfully created');
    }

    public function show(int $event)
    {
        $event = $this->ownedEvent($event);

        $event->load(['channels.rooms.sessions', 'tickets']);
        $registrationCount = $event->registrationCount();

        $sessions = $event->channels
            ->flatMap(fn ($channel) => $channel->rooms)
            ->flatMap(fn ($room) => $room->sessions)
            ->sortBy('start')
            ->values();

        $eventRating = \App\Models\EventRating::query()
            ->where('event_id', $event->id)
            ->avg('rating');

        $ratingCount = \App\Models\EventRating::query()
            ->where('event_id', $event->id)
            ->count();

        return view('events.show', compact(
            'event',
            'registrationCount',
            'sessions',
            'eventRating',
            'ratingCount'
        ));
    }

    public function edit(int $event)
    {
        return view('events.edit', [
            'organizer' => $this->organizer(),
            'event' => $this->ownedEvent($event),
        ]);
    }

    public function update(Request $request, int $event)
    {
        $event = $this->ownedEvent($event);
        $data = $this->validated($request, $event->id);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $event->update($data);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Event successfully updated');
    }

    /**
     * Validate the event form and return either the clean data or a redirect
     * containing the exact messages required by the specification.
     */
    private function validated(Request $request, ?int $ignoreId = null): array|\Illuminate\Http\RedirectResponse
    {
        $name = trim((string) $request->input('name'));
        $slug = trim((string) $request->input('slug'));
        $date = trim((string) $request->input('date'));
        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        $slugMessage = "Slug must not be empty and only contain a-z, 0-9 and '-'";

        if ($slug === '' || ! preg_match('/^[a-z0-9-]+$/', $slug)) {
            $errors['slug'] = $slugMessage;
        } else {
            $exists = Event::query()
                ->where('organizer_id', session('organizer_id'))
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists();

            if ($exists) {
                $errors['slug'] = 'Slug is already used';
            }
        }

        if (! $this->isValidDate($date)) {
            $errors['date'] = 'Date is required (yyyy-mm-dd).';
        }

        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        return compact('name', 'slug', 'date');
    }

    private function isValidDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Throwable) {
            return false;
        }

        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
