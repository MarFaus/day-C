@extends('layouts.admin')

@section('title', 'Create event')

@section('content')
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Events</h1>
    </div>

    <div class="mb-3 pt-3 pb-2">
        <h2 class="h4">Create new event</h2>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">Please correct the highlighted fields.</div>
    @endif

    <form class="needs-validation" method="POST" action="{{ route('admin.events.store') }}">
        @csrf

        <div class="row">
            <div class="col-12 col-lg-4 mb-3">
                <label for="inputName">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                       id="inputName" name="name" value="{{ old('name') }}">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-4 mb-3">
                <label for="inputSlug">Slug</label>
                <input type="text" class="form-control @error('slug') is-invalid @enderror"
                       id="inputSlug" name="slug" value="{{ old('slug') }}">
                @error('slug')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-4 mb-3">
                <label for="inputDate">Date</label>
                <input type="text" class="form-control @error('date') is-invalid @enderror"
                       id="inputDate" name="date" placeholder="yyyy-mm-dd" value="{{ old('date') }}">
                @error('date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <hr class="mb-4">
        <button class="btn btn-primary" type="submit">Save event</button>
        <a href="{{ route('admin.events.index') }}" class="btn btn-link">Cancel</a>
    </form>
@endsection
