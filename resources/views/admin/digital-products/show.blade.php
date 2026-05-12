@extends('layouts.app')

@php
    $page = 'digital-products';
@endphp

@section('title', 'Digital Product Details')

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">{{ __('Digital Product Details') }}</h1>
                <p class="text-muted mb-0">{{ __('View digital product information') }}</p>
            </div>
            <a href="{{ route('admin.digital-products.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>{{ __('Back') }}
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Product Info') }}</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ __('Product ID') }}</dt>
                            <dd class="col-sm-8">{{ $digitalProduct->product_id }}</dd>

                            <dt class="col-sm-4">{{ __('Company') }}</dt>
                            <dd class="col-sm-8">{{ $digitalProduct->company_name }}</dd>

                            <dt class="col-sm-4">{{ __('Digital Category') }}</dt>
                            <dd class="col-sm-8">
                                {{ $digitalProduct->category?->getTranslation('name', app()->getLocale()) ?? '-' }}
                            </dd>

                            <dt class="col-sm-4">{{ __('Digital Sub Category') }}</dt>
                            <dd class="col-sm-8">
                                {{ $digitalProduct->subCategory?->getTranslation('name', app()->getLocale()) ?? '-' }}
                            </dd>

                            <dt class="col-sm-4">{{ __('Merchant') }}</dt>
                            <dd class="col-sm-8">
                                @if($digitalProduct->merchant)
                                    {{ $digitalProduct->merchant->getTranslation('name', app()->getLocale()) }}
                                @else
                                    -
                                @endif
                            </dd>

                            <dt class="col-sm-4">{{ __('Name (English)') }}</dt>
                            <dd class="col-sm-8">{{ $digitalProduct->getTranslation('name', 'en') }}</dd>

                            <dt class="col-sm-4">{{ __('Name (Arabic)') }}</dt>
                            <dd class="col-sm-8">{{ $digitalProduct->getTranslation('name', 'ar') }}</dd>

                            <dt class="col-sm-4">{{ __('How To Use') }}</dt>
                            <dd class="col-sm-8">{{ $digitalProduct->getTranslation('how_to_use', app()->getLocale()) ?? '-' }}</dd>

                            <dt class="col-sm-4">{{ __('Description') }}</dt>
                            <dd class="col-sm-8">{{ $digitalProduct->getTranslation('description', app()->getLocale()) ?? '-' }}</dd>

                            <dt class="col-sm-4">{{ __('Price') }}</dt>
                            <dd class="col-sm-8">{{ number_format($digitalProduct->price, 2) }} {{ $digitalProduct->currency }}</dd>

                            <dt class="col-sm-4">{{ __('Cost After VAT') }}</dt>
                            <dd class="col-sm-8">{{ number_format($digitalProduct->cost_after_vat, 2) }} {{ $digitalProduct->currency }}</dd>

                            <dt class="col-sm-4">{{ __('Availability') }}</dt>
                            <dd class="col-sm-8">
                                @if($digitalProduct->is_available)
                                    <span class="badge bg-success">{{ __('Available') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Unavailable') }}</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">{{ __('Optional Fields') }}</dt>
                            <dd class="col-sm-8">
                                @if($digitalProduct->optional_fields_exists)
                                    <span class="badge bg-info">{{ __('Has optional fields') }}</span>
                                @else
                                    <span class="text-muted">{{ __('No optional fields') }}</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">{{ __('Last Updated By') }}</dt>
                            <dd class="col-sm-8">
                                @if($digitalProduct->lastUpdatedBy)
                                    {{ $digitalProduct->lastUpdatedBy->name }}
                                    @if($digitalProduct->lastUpdatedBy->email)
                                        <small class="d-block text-muted">{{ $digitalProduct->lastUpdatedBy->email }}</small>
                                    @endif
                                @else
                                    -
                                @endif
                            </dd>

                            <dt class="col-sm-4">{{ __('Created At') }}</dt>
                            <dd class="col-sm-8">{{ $digitalProduct->created_at?->format('Y-m-d h:i A') }}</dd>

                            <dt class="col-sm-4">{{ __('Updated At') }}</dt>
                            <dd class="col-sm-8">{{ $digitalProduct->updated_at?->format('Y-m-d h:i A') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Image') }}</h5>
                    </div>
                    <div class="card-body text-center">
                        @if($digitalProduct->image)
                            <img src="{{ $digitalProduct->image }}" alt="Product image" class="img-fluid rounded">
                        @else
                            <span class="text-muted">{{ __('No image') }}</span>
                        @endif
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Assign Category') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.digital-products.assign-category', $digitalProduct) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('Digital Category') }}</label>
                                <select name="category_id" id="digitalCategorySelect" class="form-select @error('category_id') is-invalid @enderror">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach($digitalCategories as $category)
                                        <option value="{{ $category->id }}" {{ (int) old('category_id', $digitalProduct->category_id) === $category->id ? 'selected' : '' }}>
                                            {{ $category->getTranslation('name', app()->getLocale()) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">{{ __('Digital Sub Category') }}</label>
                                <select name="sub_category_id" id="digitalSubCategorySelect" class="form-select @error('sub_category_id') is-invalid @enderror">
                                    <option value="">{{ __('None') }}</option>
                                </select>
                                @error('sub_category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> {{ __('Save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Allowed Countries') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.digital-products.sync-countries', $digitalProduct) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">{{ __('Countries') }}</label>
                                <select name="country_ids[]" class="form-select @error('country_ids') is-invalid @enderror" multiple size="8">
                                    @php
                                        $selectedCountryIds = collect(old('country_ids', $digitalProduct->countries->pluck('id')->all()))->map(fn ($id) => (int) $id)->all();
                                    @endphp
                                    @foreach($countries as $country)
                                        <option value="{{ $country->id }}" {{ in_array($country->id, $selectedCountryIds, true) ? 'selected' : '' }}>
                                            {{ $country->code }} - {{ $country->name[app()->getLocale()] ?? $country->name['en'] ?? $country->code }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('country_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    {{ __('If you select no countries, the digital product will be visible in all countries.') }}
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> {{ __('Save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const subCategoriesByCategory = @json($subCategoriesByCategory);
        const categorySelect = document.getElementById('digitalCategorySelect');
        const subCategorySelect = document.getElementById('digitalSubCategorySelect');
        const initialSubCategoryId = '{{ (int) old('sub_category_id', $digitalProduct->sub_category_id) }}';

        function populateSubCategories(categoryId) {
            subCategorySelect.innerHTML = '';
            const noneOption = document.createElement('option');
            noneOption.value = '';
            noneOption.textContent = '{{ __('None') }}';
            subCategorySelect.appendChild(noneOption);

            const list = subCategoriesByCategory[categoryId] || [];
            list.forEach(function (sub) {
                const opt = document.createElement('option');
                opt.value = sub.id;
                opt.textContent = sub.name;
                if (String(sub.id) === String(initialSubCategoryId)) {
                    opt.selected = true;
                }
                subCategorySelect.appendChild(opt);
            });
        }

        if (categorySelect) {
            // initial population
            if (categorySelect.value) {
                populateSubCategories(categorySelect.value);
            }

            categorySelect.addEventListener('change', function (e) {
                populateSubCategories(e.target.value);
            });
        }
    });
</script>
@endpush
