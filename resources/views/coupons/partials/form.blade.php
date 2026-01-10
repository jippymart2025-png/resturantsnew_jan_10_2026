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
        <select name="discount_type" id="discount_type" class="form-control" required>
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
        <input type="number" step="0.01" name="discount" id="discount" class="form-control" value="{{ old('discount', $coupon->discount ?? '') }}" required>
        <div class="form-text text-muted">{{ trans('lang.coupon_discount_help') }}</div>
        <small class="text-danger" id="discount-error" style="display: none;"></small>
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
        <input type="number" name="minimum_order" id="minimum_order" class="form-control" value="{{ old('minimum_order', $coupon->item_value ?? '') }}">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const discountInput = document.getElementById('discount');
    const discountTypeSelect = document.getElementById('discount_type');
    const minimumOrderInput = document.getElementById('minimum_order');
    const discountErrorElement = document.getElementById('discount-error');
    const couponForm = document.querySelector('form[method="POST"]');

    function validateDiscount() {
        if (!discountInput || !discountTypeSelect) return true;

        const discount = parseFloat(discountInput.value) || 0;
        const discountType = discountTypeSelect.value;
        const minimumOrder = minimumOrderInput ? (parseFloat(minimumOrderInput.value) || null) : null;

        // Clear previous error
        if (discountErrorElement) {
            discountErrorElement.style.display = 'none';
            discountErrorElement.textContent = '';
        }
        if (discountInput) {
            discountInput.style.borderColor = '';
        }

        // Validate Fix Price discount
        if (discountType === 'Fix Price') {
            if (minimumOrder !== null && discount > minimumOrder) {
                if (discountErrorElement) {
                    discountErrorElement.textContent = 'Discount amount cannot be greater than minimum order amount.';
                    discountErrorElement.style.display = 'block';
                }
                if (discountInput) {
                    discountInput.style.borderColor = '#dc3545';
                }
                return false;
            }
        }

        // Validate Percentage discount
        if (discountType === 'Percentage') {
            if (discount > 100) {
                if (discountErrorElement) {
                    discountErrorElement.textContent = 'Percentage discount cannot exceed 100%.';
                    discountErrorElement.style.display = 'block';
                }
                if (discountInput) {
                    discountInput.style.borderColor = '#dc3545';
                }
                return false;
            }
        }

        return true;
    }

    // Validate when discount changes
    if (discountInput) {
        discountInput.addEventListener('input', validateDiscount);
        discountInput.addEventListener('blur', validateDiscount);
    }

    // Validate when discount type changes
    if (discountTypeSelect) {
        discountTypeSelect.addEventListener('change', validateDiscount);
    }

    // Validate when minimum order changes
    if (minimumOrderInput) {
        minimumOrderInput.addEventListener('input', validateDiscount);
        minimumOrderInput.addEventListener('blur', validateDiscount);
    }

    // Validate before form submission
    if (couponForm) {
        couponForm.addEventListener('submit', function(e) {
            if (!validateDiscount()) {
                e.preventDefault();
                const errorMsg = discountErrorElement ? discountErrorElement.textContent : 'Please fix the discount validation error.';
                alert(errorMsg);
                return false;
            }
        });
    }
});
</script>