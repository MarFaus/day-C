<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesOwnedEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    use ResolvesOwnedEvent;

    public function create(int $event)
    {
        return view('channels.create', [
            'organizer' => $this->organizer(),
            'event' => $this->ownedEvent($event),
        ]);
    }

    public function store(Request $request, int $event)
    {
        $event = $this->ownedEvent($event);
        $data = $this->validated($request);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $event->channels()->create($data);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Channel successfully created');
    }

    public function edit(int $event, int $channel)
    {
        $event = $this->ownedEvent($event);
        $channel = $event->channels()->findOrFail($channel);

        return view('channels.edit', compact('event', 'channel') + ['organizer' => $this->organizer()]);
    }

    public function update(Request $request, int $event, int $channel)
    {
        $event = $this->ownedEvent($event);
        $channel = $event->channels()->findOrFail($channel);
        $data = $this->validated($request);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $channel->update($data);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Channel successfully updated');
    }

    private function validated(Request $request): array|\Illuminate\Http\RedirectResponse
    {
        $name = trim((string) $request->input('name'));

        if ($name === '') {
            return back()->withInput()->withErrors(['name' => 'Name is required.']);
        }

        return compact('name');
    }
}
