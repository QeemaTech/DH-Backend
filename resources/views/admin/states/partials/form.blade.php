@php
    $state = $state ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.shipping_ui.country') }}</label>
        <select name="country_id" class="form-select @error('country_id') is-invalid @enderror" required>
            <option value="">{{ __('messages.shipping_ui.select_country') }}</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected((int) old('country_id', $state->country_id ?? 0) === (int) $country->id)>
                    {{ $country->name['en'] ?? $country->code }}
                </option>
            @endforeach
        </select>
        @error('country_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('messages.shipping_ui.code') }}</label>
        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
               value="{{ old('code', $state->code ?? '') }}">
        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('messages.shipping_ui.name_en') }}</label>
        <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
               value="{{ old('name_en', $state->name['en'] ?? '') }}" required>
        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('messages.shipping_ui.name_ar') }}</label>
        <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
               value="{{ old('name_ar', $state->name['ar'] ?? '') }}" required>
        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('messages.shipping_ui.sort_order') }}</label>
        <input type="number" min="0" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $state->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label d-block">{{ __('messages.shipping_ui.status') }}</label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $state->is_active ?? true))>
            <label class="form-check-label">{{ __('messages.shipping_ui.status_active') }}</label>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">{{ __('messages.shipping_ui.save') }}</button>
    <a href="{{ route('admin.states.index') }}" class="btn btn-outline-secondary">{{ __('messages.shipping_ui.cancel') }}</a>
</div>
