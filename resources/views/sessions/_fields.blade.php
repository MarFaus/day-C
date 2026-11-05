@php
    $isEdit = $session !== null;
    $typeValue = old('type', $session->type ?? 'talk');
    $roomValue = old('room', $session->room_id ?? null);
@endphp

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="selectType">Type</label>
        <select class="form-control @error('type') is-invalid @enderror" id="selectType" name="type">
            <option value="talk" @selected($typeValue === 'talk')>Talk</option>
            <option value="workshop" @selected($typeValue === 'workshop')>Workshop</option>
        </select>
        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="inputTitle">Title</label>
        <input type="text" class="form-control @error('title') is-invalid @enderror" id="inputTitle"
               name="title" value="{{ old('title', $session->title ?? '') }}">
        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="inputSpeaker">Speaker</label>
        <input type="text" class="form-control @error('speaker') is-invalid @enderror" id="inputSpeaker"
               name="speaker" value="{{ old('speaker', $session->speaker ?? '') }}">
        @error('speaker')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="selectRoom">Room</label>
        <select class="form-control @error('room') is-invalid @enderror" id="selectRoom" name="room">
            <option value="">Select a room</option>
            @foreach ($rooms as $room)
                <option value="{{ $room['id'] }}" @selected((string) $roomValue === (string) $room['id'])>
                    {{ $room['label'] }}
                </option>
            @endforeach
        </select>
        @error('room')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="inputCost">Cost</label>
        <input type="number" step="0.01" min="0" class="form-control @error('cost') is-invalid @enderror"
               id="inputCost" name="cost" value="{{ old('cost', $session->cost ?? '0') }}">
        @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-6 mb-3">
        <label for="inputStart">Start</label>
        <input type="text" class="form-control @error('start') is-invalid @enderror" id="inputStart"
               name="start" placeholder="yyyy-mm-dd HH:MM"
               value="{{ old('start', isset($session) && $session ? $session->start->format('Y-m-d H:i') : '') }}">
        @error('start')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-lg-6 mb-3">
        <label for="inputEnd">End</label>
        <input type="text" class="form-control @error('end') is-invalid @enderror" id="inputEnd"
               name="end" placeholder="yyyy-mm-dd HH:MM"
               value="{{ old('end', isset($session) && $session ? $session->end->format('Y-m-d H:i') : '') }}">
        @error('end')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 mb-3">
        <label for="textareaDescription">Description</label>
        <textarea class="form-control @error('description') is-invalid @enderror" id="textareaDescription"
                  name="description" rows="5">{{ old('description', $session->description ?? '') }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
