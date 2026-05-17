@php
    $country = $country ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-2">
        <label class="form-label">{{ __('messages.shipping_ui.code') }}</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $country->code ?? '') }}" maxlength="2" required>
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label class="form-label">{{ __('messages.shipping_ui.name_en') }}</label>
        <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
               value="{{ old('name_en', $country->name['en'] ?? '') }}" required>
        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-5">
        <label class="form-label">{{ __('messages.shipping_ui.name_ar') }}</label>
        <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
               value="{{ old('name_ar', $country->name['ar'] ?? '') }}" required>
        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.shipping_ui.dial_code') }}</label>
        <input type="text" name="dial_code" class="form-control @error('dial_code') is-invalid @enderror"
               value="{{ old('dial_code', $country->dial_code ?? '') }}">
        @error('dial_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Flag') }}</label>
        <input type="file" name="flag" class="form-control @error('flag') is-invalid @enderror" accept="image/*">
        @error('flag')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @if($country && $country->flag)
            <div class="mt-2">
                <img src="{{ $country->flag }}" alt="flag" style="width: 42px; height: 28px; object-fit: cover; border-radius: 4px;">
            </div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.shipping_ui.verification_channel') }}</label>
        @php
            $selectedChannels = old('verification_channels', $country?->getVerificationChannels() ?? ['sms']);
        @endphp
        <select name="verification_channels[]" class="form-select @error('verification_channels') is-invalid @enderror"
                data-channel-multiselect multiple required>
            <option value="sms" @selected(in_array('sms', $selectedChannels, true))>SMS</option>
            <option value="whatsapp" @selected(in_array('whatsapp', $selectedChannels, true))>WHATSAPP</option>
            <option value="email" @selected(in_array('email', $selectedChannels, true))>EMAIL</option>
        </select>
        @error('verification_channels')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @error('verification_channels.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('messages.shipping_ui.sort_order') }}</label>
        <input type="number" min="0" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $country->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label d-block">{{ __('messages.shipping_ui.status') }}</label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $country->is_active ?? true))>
            <label class="form-check-label">{{ __('messages.shipping_ui.status_active') }}</label>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">{{ __('messages.shipping_ui.save') }}</button>
    <a href="{{ route('admin.shipping-countries.index') }}" class="btn btn-outline-secondary">{{ __('messages.shipping_ui.cancel') }}</a>
</div>

