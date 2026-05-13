@php
    $city = $city ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.shipping_ui.country') }}</label>
        <select name="country_id" id="city_country_id" class="form-select @error('country_id') is-invalid @enderror" required>
            <option value="">{{ __('messages.shipping_ui.select_country') }}</option>
            @foreach($countries as $country)
                <option value="{{ $country->id }}" @selected((int) old('country_id', $city->country_id ?? $city->state?->country_id ?? 0) === (int) $country->id)>
                    {{ $country->name['en'] ?? $country->code }}
                </option>
            @endforeach
        </select>
        @error('country_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('messages.shipping_ui.state') }}</label>
        <select name="state_id" id="city_state_id" class="form-select @error('state_id') is-invalid @enderror" required>
            <option value="">{{ __('messages.shipping_ui.select_state') }}</option>
            @foreach($states as $state)
                <option value="{{ $state->id }}" data-country-id="{{ $state->country_id }}" @selected((int) old('state_id', $city->state_id ?? 0) === (int) $state->id)>
                    {{ ($state->country?->name['en'] ?? $state->country?->code ?? '-') . ' - ' . ($state->name['en'] ?? '-') }}
                </option>
            @endforeach
        </select>
        @error('state_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('messages.shipping_ui.name_en') }}</label>
        <input type="text" name="name_en" class="form-control @error('name_en') is-invalid @enderror"
               value="{{ old('name_en', $city->name['en'] ?? '') }}" required>
        @error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('messages.shipping_ui.name_ar') }}</label>
        <input type="text" name="name_ar" class="form-control @error('name_ar') is-invalid @enderror"
               value="{{ old('name_ar', $city->name['ar'] ?? '') }}" required>
        @error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('messages.shipping_ui.shipping_cost') }}</label>
        <input type="number" step="0.001" min="0" name="shipping_cost" class="form-control @error('shipping_cost') is-invalid @enderror"
               value="{{ old('shipping_cost', isset($city) ? (float) $city->shipping_cost : '0') }}" required>
        @error('shipping_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label">{{ __('messages.shipping_ui.sort_order') }}</label>
        <input type="number" min="0" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $city->sort_order ?? 0) }}">
        @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-2">
        <label class="form-label d-block">{{ __('messages.shipping_ui.status') }}</label>
        <div class="form-check form-switch mt-2">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $city->is_active ?? true))>
            <label class="form-check-label">{{ __('messages.shipping_ui.status_active') }}</label>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">{{ __('messages.shipping_ui.save') }}</button>
    <a href="{{ route('admin.cities.index') }}" class="btn btn-outline-secondary">{{ __('messages.shipping_ui.cancel') }}</a>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const countrySelect = document.getElementById('city_country_id');
        const stateSelect = document.getElementById('city_state_id');
        if (!countrySelect || !stateSelect) return;

        const allStateOptions = Array.from(stateSelect.querySelectorAll('option'));

        function filterStatesByCountry() {
            const selectedCountryId = countrySelect.value;
            const currentStateId = stateSelect.value;

            allStateOptions.forEach((option) => {
                if (option.value === '') {
                    option.hidden = false;
                    return;
                }
                if (!selectedCountryId) {
                    option.hidden = true;
                    return;
                }
                option.hidden = option.dataset.countryId !== selectedCountryId;
            });

            const selectedOption = stateSelect.querySelector(`option[value="${currentStateId}"]`);
            if (selectedOption && selectedOption.hidden) {
                stateSelect.value = '';
            }

            stateSelect.disabled = !selectedCountryId;
        }

        countrySelect.addEventListener('change', filterStatesByCountry);
        filterStatesByCountry();
    });
</script>
@endpush
