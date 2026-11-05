@extends('layouts.admin')

@section('title', $event->name)

@section('content')
    <div class="border-bottom mb-3 pt-3 pb-2 event-title">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2">{{ $event->name }}</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group mr-2">
                    <a href="{{ route('admin.events.edit', $event) }}" class="btn btn-sm btn-outline-secondary">Edit event</a>
                </div>
            </div>
        </div>
        <span class="h6">{{ $event->date?->format('F j, Y') }} · {{ $event->slug }}</span>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1">Registrations</h6>
                    <p class="h3 mb-0">{{ number_format($registrationCount) }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1">Sessions</h6>
                    <p class="h3 mb-0">{{ $sessions->count() }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-1">Event rating</h6>
                    <p class="h3 mb-0">
                        {{ $ratingCount ? number_format((float) $eventRating, 1) : '—' }}
                        @if ($ratingCount)
                            <small class="text-muted h6">/ 5 ({{ $ratingCount }})</small>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tickets --}}
    <div id="tickets" class="mb-3 pt-3 pb-2">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h2 class="h4">Tickets</h2>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group mr-2">
                    <a href="{{ route('admin.tickets.create', $event) }}" class="btn btn-sm btn-outline-secondary">
                        Create new ticket
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row tickets">
        @forelse ($event->tickets as $ticket)
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm ticket">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title">{{ $ticket->name }}</h5>
                            <a class="small" href="{{ route('admin.tickets.edit', [$event, $ticket]) }}">Edit</a>
                        </div>
                        <p class="card-text">{{ rtrim(rtrim(number_format($ticket->cost, 2, '.', ''), '0'), '.') }}.-</p>
                        <p class="card-text text-muted">
                            {{ $ticket->description() ?? 'No special validity rule' }}
                        </p>
                        <p class="card-text small {{ $ticket->isAvailable() ? 'text-success' : 'text-danger' }}">
                            {{ $ticket->isAvailable() ? 'Available' : 'Not available' }}
                        </p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><p class="text-muted">No tickets yet.</p></div>
        @endforelse
    </div>

    {{-- Sessions --}}
    <div id="sessions" class="mb-3 pt-3 pb-2">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h2 class="h4">Sessions</h2>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group mr-2">
                    <a href="{{ route('admin.sessions.create', $event) }}" class="btn btn-sm btn-outline-secondary">
                        Create new session
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive sessions">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Time</th>
                <th>Type</th>
                <th class="w-100">Title</th>
                <th>Speaker</th>
                <th>Channel</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($sessions as $session)
                <tr>
                    <td class="text-nowrap">
                        {{ $session->start->format('H:i') }} - {{ $session->end->format('H:i') }}
                    </td>
                    <td>{{ ucfirst($session->type) }}</td>
                    <td>
                        <a href="{{ route('admin.sessions.edit', [$event, $session]) }}">{{ $session->title }}</a>
                        @if ($session->type === 'workshop' && $session->cost !== null)
                            <span class="text-muted small">· {{ $session->cost }}.-</span>
                        @endif
                    </td>
                    <td class="text-nowrap">{{ $session->speaker }}</td>
                    <td class="text-nowrap">
                        {{ $session->room->channel->name }} / {{ $session->room->name }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted">No sessions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Channels --}}
    <div id="channels" class="mb-3 pt-3 pb-2">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h2 class="h4">Channels</h2>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group mr-2">
                    <a href="{{ route('admin.channels.create', $event) }}" class="btn btn-sm btn-outline-secondary">
                        Create new channel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row channels">
        @forelse ($event->channels as $channel)
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm channel">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="card-title">{{ $channel->name }}</h5>
                            <a class="small" href="{{ route('admin.channels.edit', [$event, $channel]) }}">Edit</a>
                        </div>
                        <p class="card-text">
                            {{ $channel->rooms->sum(fn ($room) => $room->sessions->count()) }} sessions,
                            {{ $channel->rooms->count() }} {{ \Illuminate\Support\Str::plural('room', $channel->rooms->count()) }}
                        </p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><p class="text-muted">No channels yet.</p></div>
        @endforelse
    </div>

    {{-- Rooms --}}
    <div id="rooms" class="mb-3 pt-3 pb-2">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h2 class="h4">Rooms</h2>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group mr-2">
                    <a href="{{ route('admin.rooms.create', $event) }}" class="btn btn-sm btn-outline-secondary">
                        Create new room
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive rooms">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Name</th>
                <th>Channel</th>
                <th>Capacity</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse ($event->channels->flatMap->rooms as $room)
                <tr>
                    <td>{{ $room->name }}</td>
                    <td>{{ $room->channel->name }}</td>
                    <td>{{ number_format($room->capacity) }}</td>
                    <td class="text-right">
                        <a class="small" href="{{ route('admin.rooms.edit', [$event, $room]) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-muted">No rooms yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <p class="mt-4">
        <a href="{{ route('admin.reports.room-capacity', $event) }}">Open room capacity report &rarr;</a>
    </p>
@endsection
