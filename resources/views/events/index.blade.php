@extends('layouts.admin')

@section('title', 'Manage events')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Events</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group mr-2">
                <a href="{{ route('admin.events.create') }}" class="btn btn-sm btn-outline-secondary">Create new event</a>
            </div>
        </div>
    </div>

    <div class="row events">
        @forelse ($events as $item)
            <div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <a href="{{ route('admin.events.show', $item) }}" class="btn text-left event">
                        <div class="card-body">
                            <h5 class="card-title">{{ $item->name }}</h5>
                            <p class="card-subtitle">{{ $item->date?->format('F j, Y') }}</p>
                            <hr>
                            <p class="card-text">
                                {{ number_format($registrationCounts[$item->id] ?? 0) }} registrations
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light border">
                    You have not created any events yet. Use <strong>Create new event</strong> to get started.
                </div>
            </div>
        @endforelse
    </div>
@endsection
