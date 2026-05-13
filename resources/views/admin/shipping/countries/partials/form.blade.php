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
        <label class="form-label">{{ __('messages.shipping_ui.verification_channel') }}</label>
        <select name="verification_channel" class="form-select @error('verification_channel') is-invalid @enderror" required>
            @foreach (\App\Enums\VerificationChannel::cases() as $channel)
                @php
                    $selected = old('verification_channel', (string) ($country->verification_channel->value ?? $country->verification_channel ?? \App\Enums\VerificationChannel::Sms->value));
                @endphp
                <option value="{{ $channel->value }}" @selected($selected === $channel->value)>{{ strtoupper($channel->value) }}</option>
            @endforeach
        </select>
        @error('verification_channel')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
