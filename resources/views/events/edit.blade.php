@extends('layouts.admin')

@section('title', 'Edit event')

@section('content')
    <div class="border-bottom mb-3 pt-3 pb-2">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center">
            <h1 class="h2">{{ $event->name }}</h1>
        </div>
        <span class="h6">{{ $event->date?->format('F j, Y') }}</span>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">Please correct the highlighted fields.</div>
    @endif

    <form class="needs-validation" method="POST" action="{{ route('admin.events.update', $event) }}">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-12 col-lg-4 mb-3">
                <label for="inputName">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="inputName" name="name" value="{{ old('name', $event->name) }}">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-4 mb-3">
                <label for="inputSlug">Slug</label>
                <input type="text" class="form-control @error('slug') is-invalid @enderror"
                       id="inputSlug" name="slug" value="{{ old('slug', $event->slug) }}">
                @error('slug')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-4 mb-3">
                <label for="inputDate">Date</label>
                <input type="text" class="form-control @error('date') is-invalid @enderror"
                       id="inputDate" name="date" placeholder="yyyy-mm-dd"
                       value="{{ old('date', $event->date?->format('Y-m-d')) }}">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr class="mb-4">
        <button class="btn btn-primary" type="submit">Save</button>
        <a href="{{ route('admin.events.show', $event) }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
