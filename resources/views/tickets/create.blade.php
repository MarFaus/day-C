@extends('layouts.admin')

@section('title', 'Create ticket')

@section('content')
    <div class="border-bottom mb-3 pt-3 pb-2">
        <h1 class="h2">{{ $event->name }}</h1>
        <span class="h6">{{ $event->date?->format('F j, Y') }}</span>
    </div>

    <div class="mb-3 pt-3 pb-2">
        <h2 class="h4">Create new ticket</h2>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">Please correct the highlighted fields.</div>
    @endif

    <form class="needs-validation" method="POST" action="{{ route('admin.tickets.store', $event) }}">
        @csrf
        @include('tickets._fields', ['ticket' => null])

        <hr class="mb-4">
        <button class="btn btn-primary" type="submit">Save ticket</button>
        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
