@php
    $channelValue = old('channel', $room->channel_id ?? null);
@endphp

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="inputName">Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror"
               id="inputName" name="name" value="{{ old('name', $room->name ?? '') }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="selectChannel">Channel</label>
        <select class="form-control @error('channel') is-invalid @enderror" id="selectChannel" name="channel">
            <option value="">Select a channel</option>
            @foreach ($channels as $channel)
                <option value="{{ $channel->id }}" @selected((string) $channelValue === (string) $channel->id)>
                    {{ $channel->name }}
                </option>
            @endforeach
        </select>
        @error('channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="inputCapacity">Capacity</label>
        <input type="number" min="1" class="form-control @error('capacity') is-invalid @enderror"
               id="inputCapacity" name="capacity" value="{{ old('capacity', $room->capacity ?? '') }}">
        @error('capacity')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
