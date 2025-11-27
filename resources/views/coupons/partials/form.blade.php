@php
    $editing = isset($coupon) && $coupon->exists;
    $expiresOld = old('expires_at');
    if ($expiresOld !== null) {
        $expiresValue = $expiresOld;
    } else {
        try {
            $expiresValue = isset($coupon->expiresAt) ? \Carbon\Carbon::parse(trim($coupon->expiresAt ?? '', "\"\\\\"))->format('Y-m-d') : '';
        } catch (\Throwable $e) {
            $expiresValue = '';
        }
    }
@endphp

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-group row width-50">
    <label class="col-3 control-label">{{ trans('lang.coupon_code') }}</label>
    <div class="col-7">
        <input type="text" name="code" class="form-control" value="{{ old('code', $coupon->code ?? '') }}" required>
        <div class="form-text text-muted">{{ trans('lang.coupon_code_help') }}</div>
    </div>
</div>

<div class="form-group row width-50">
    <label class="col-3 control-label">{{ trans('lang.coupon_discount_type') }}</label>
    <div class="col-7">
        <select name="discount_type" class="form-control" required>
            @foreach (['Percentage' => trans('lang.coupon_percent'), 'Fix Price' => trans('lang.coupon_fixed')] as $value => $label)
                <option value="{{ $value }}" {{ old('discount_type', $coupon->discountType ?? '') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <div class="form-text text-muted">{{ trans('lang.coupon_discount_type_help') }}</div>
    </div>
</div>

<div class="form-group row width-50">
    <label class="col-3 control-label">{{ trans('lang.coupon_discount') }}</label>
    <div class="col-7">
        <input type="number" step="0.01" name="discount" class="form-control" value="{{ old('discount', $coupon->discount ?? '') }}" required>
        <div class="form-text text-muted">{{ trans('lang.coupon_discount_help') }}</div>
    </div>
</div>

<div class="form-group row width-50">
    <label class="col-3 control-label">{{ trans('lang.coupon_expires_at') }}</label>
    <div class="col-7">
        <input type="date" name="expires_at" class="form-control" value="{{ $expiresValue }}" required>
        <div class="form-text text-muted">{{ trans('lang.coupon_expires_at_help') }}</div>
    </div>
</div>

<div class="form-group row width-50">
    <label class="col-3 control-label">Coupon Type</label>
    <div class="col-7">
        <select name="cType" class="form-control" required>
            @foreach (['restaurant' => 'Restaurant', 'mart' => 'Mart'] as $value => $label)
                <option value="{{ $value }}" {{ old('cType', $coupon->cType ?? 'restaurant') === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <div class="form-text text-muted">Select where this coupon applies.</div>
    </div>
</div>

<div class="form-group row width-50">
    <label class="col-3 control-label">{{ __('Minimum Order Amount') }}</label>
    <div class="col-7">
        <input type="number" name="minimum_order" class="form-control" value="{{ old('minimum_order', $coupon->item_value ?? '') }}">
        <div class="form-text text-muted">{{ __('Set a minimum order amount (optional)') }}</div>
    </div>
</div>

<div class="form-group row width-50">
    <label class="col-3 control-label">{{ __('Usage Limit') }}</label>
    <div class="col-7">
        <input type="number" name="usage_limit" class="form-control" value="{{ old('usage_limit', $coupon->usageLimit ?? '') }}">
        <div class="form-text text-muted">{{ __('Leave empty for unlimited uses') }}</div>
    </div>
</div>

<div class="form-group row width-100">
    <label class="col-3 control-label">{{ trans('lang.coupon_description') }}</label>
    <div class="col-7">
        <textarea rows="6" name="description" class="form-control">{{ old('description', $coupon->description ?? '') }}</textarea>
        <div class="form-text text-muted">{{ trans('lang.coupon_description_help') }}</div>
    </div>
</div>

<div class="form-group row width-100">
    <label class="col-3 control-label">{{ trans('lang.category_image') }}</label>
    <div class="col-7">
        <input type="file" name="image" class="form-control-file">
        @if ($editing && $coupon->image)
            <div class="mt-2">
                <img src="{{ Str::startsWith($coupon->image, ['http://', 'https://']) ? $coupon->image : asset('storage/' . $coupon->image) }}" alt="coupon image" class="rounded" style="width:80px;height:80px;object-fit:cover;">
            </div>
        @endif
        <div class="form-text text-muted">{{ trans('lang.category_image_help') }}</div>
    </div>
</div>

<div class="form-group row width-100">
    <div class="form-check ml-3">
        <input type="hidden" name="isEnabled" value="0">
        <input type="checkbox" class="form-check-input" id="coupon_enabled" name="isEnabled" value="1" {{ old('isEnabled', $coupon->isEnabled ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="coupon_enabled">{{ trans('lang.coupon_enabled') }}</label>
    </div>
</div>

<div class="form-group row width-100">
    <div class="form-check ml-3">
        <input type="hidden" name="isPublic" value="0">
        <input type="checkbox" class="form-check-input" id="coupon_public" name="isPublic" value="1" {{ old('isPublic', $coupon->isPublic ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="coupon_public">{{ trans('lang.coupon_public') }}</label>
    </div>
</div>

