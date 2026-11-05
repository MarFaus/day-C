<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesOwnedEvent;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use ResolvesOwnedEvent;

    public function create(int $event)
    {
        return view('tickets.create', [
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

        $event->tickets()->create($data);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Ticket successfully created');
    }

    public function edit(int $event, int $ticket)
    {
        $event = $this->ownedEvent($event);
        $ticket = $event->tickets()->findOrFail($ticket);

        return view('tickets.edit', compact('event', 'ticket') + ['organizer' => $this->organizer()]);
    }

    public function update(Request $request, int $event, int $ticket)
    {
        $event = $this->ownedEvent($event);
        $ticket = $event->tickets()->findOrFail($ticket);
        $data = $this->validated($request);

        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $ticket->update($data);

        return redirect()
            ->route('admin.events.show', $event)
            ->with('success', 'Ticket successfully updated');
    }

    private function validated(Request $request): array|\Illuminate\Http\RedirectResponse
    {
        $errors = [];
        $name = trim((string) $request->input('name'));
        $cost = $request->input('cost');
        $rule = (string) $request->input('special_validity', '');

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }

        if ($cost === null || $cost === '' || ! is_numeric($cost) || (float) $cost < 0) {
            $errors['cost'] = 'Cost is required.';
        }

        $validity = null;

        if ($rule === 'date') {
            $validUntil = trim((string) $request->input('valid_until'));

            if (! $this->isValidDate($validUntil)) {
                $errors['valid_until'] = 'A valid date is required.';
            } else {
                $validity = json_encode([
                    'type' => 'date',
                    'date' => substr($validUntil, 0, 10),
                ]);
            }
        } elseif ($rule === 'amount') {
            $amount = $request->input('amount');

            if ($amount === null || $amount === '' || ! is_numeric($amount) || (int) $amount < 1) {
                $errors['amount'] = 'Maximum amount is required.';
            } else {
                $validity = json_encode([
                    'type' => 'amount',
                    'amount' => (int) $amount,
                ]);
            }
        }

        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        return [
            'name' => $name,
            'cost' => round((float) $cost, 2),
            'special_validity' => $validity,
        ];
    }

    private function isValidDate(string $value): bool
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return false;
        }

        try {
            Carbon::parse($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
