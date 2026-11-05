@extends('layouts.admin')

@section('title', 'Edit channel')

@section('content')
    <div class="border-bottom mb-3 pt-3 pb-2">
        <h1 class="h2">{{ $event->name }}</h1>
        <span class="h6">{{ $event->date?->format('F j, Y') }}</span>
    </div>

    <div class="mb-3 pt-3 pb-2">
        <h2 class="h4">Edit channel</h2>
    </div>

    <form class="needs-validation" method="POST" action="{{ route('admin.channels.update', [$event, $channel]) }}">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-12 col-lg-4 mb-3">
                <label for="inputName">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="inputName" name="name" value="{{ old('name', $channel->name) }}">
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <hr class="mb-4">
        <button class="btn btn-primary" type="submit">Save channel</button>
        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
