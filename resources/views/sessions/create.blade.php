@extends('layouts.admin')

@section('title', 'Create session')

@section('content')
    <div class="border-bottom mb-3 pt-3 pb-2">
        <h1 class="h2">{{ $event->name }}</h1>
        <span class="h6">{{ $event->date?->format('F j, Y') }}</span>
    </div>

    <div class="mb-3 pt-3 pb-2">
        <h2 class="h4">Create new session</h2>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">Please correct the highlighted fields.</div>
    @endif

    <form class="needs-validation" method="POST" action="{{ route('admin.sessions.store', $event) }}">
        @csrf
        @include('sessions._fields', ['session' => null, 'rooms' => $rooms])

        <hr class="mb-4">
        <button class="btn btn-primary" type="submit">Save session</button>
        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
