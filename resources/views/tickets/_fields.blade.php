@php
    $validity = $ticket?->validity();
    $ruleValue = old('special_validity', $validity['type'] ?? '');
@endphp

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="inputName">Name</label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="inputName"
               name="name" value="{{ old('name', $ticket->name ?? '') }}">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="inputCost">Cost</label>
        <input type="number" step="0.01" min="0" class="form-control @error('cost') is-invalid @enderror"
               id="inputCost" name="cost" value="{{ old('cost', $ticket->cost ?? '0') }}">
        @error('cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="selectSpecialValidity">Special Validity</label>
        <select class="form-control" id="selectSpecialValidity" name="special_validity">
            <option value="" @selected($ruleValue === '')>None</option>
            <option value="amount" @selected($ruleValue === 'amount')>Limited amount</option>
            <option value="date" @selected($ruleValue === 'date')>Purchaseable till date</option>
        </select>
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="inputAmount">Maximum amount of tickets to be sold</label>
        <input type="number" min="1" class="form-control @error('amount') is-invalid @enderror"
               id="inputAmount" name="amount"
               value="{{ old('amount', $validity['amount'] ?? '') }}">
        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-12 col-lg-4 mb-3">
        <label for="inputValidTill">Tickets can be sold until</label>
        <input type="text" class="form-control @error('valid_until') is-invalid @enderror"
               id="inputValidTill" name="valid_until" placeholder="yyyy-mm-dd"
               value="{{ old('valid_until', $validity['date'] ?? '') }}">
        @error('valid_until')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var rule = document.getElementById('selectSpecialValidity');
        var amount = document.getElementById('inputAmount').closest('.row');
        var date = document.getElementById('inputValidTill').closest('.row');

        function toggle() {
            amount.style.display = rule.value === 'amount' ? '' : 'none';
            date.style.display = rule.value === 'date' ? '' : 'none';
        }

        rule.addEventListener('change', toggle);
        toggle();
    });
</script>
