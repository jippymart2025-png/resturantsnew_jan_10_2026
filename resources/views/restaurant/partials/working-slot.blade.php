@php
    $slotId = $day . '-' . $index . '-' . uniqid();
@endphp
<div class="row align-items-end working-slot" data-index="{{ $index }}">
    <div class="col-md-5 mb-2">
        <label class="form-label">From</label>
        <input type="time"
               class="form-control"
               name="working_hours[{{ $day }}][{{ $index }}][from]"
               value="{{ old("working_hours.$day.$index.from", $slot['from'] ?? '') }}">
    </div>
    <div class="col-md-5 mb-2">
        <label class="form-label">To</label>
        <input type="time"
               class="form-control"
               name="working_hours[{{ $day }}][{{ $index }}][to]"
               value="{{ old("working_hours.$day.$index.to", $slot['to'] ?? '') }}">
    </div>
    <div class="col-md-2 mb-2 d-flex align-items-end">
        <button type="button" class="btn btn-outline-danger remove-slot w-100" data-day="{{ $day }}">Remove</button>
    </div>
</div>

