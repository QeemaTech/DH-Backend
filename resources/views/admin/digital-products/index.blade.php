@extends('layouts.app')

@php
    $page = 'digital-products';
@endphp

@section('title', 'Digital Products')

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">{{ __('Digital Products') }}</h1>
                <p class="text-muted mb-0">{{ __('View synced digital products from providers') }}</p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.digital-products.index') }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control"
                                   placeholder="{{ __('Search by product id or name...') }}"
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="company_name" class="form-select">
                                <option value="">{{ __('All Companies') }}</option>
                                @foreach($companies as $company)
                                    <option value="{{ $company }}" {{ request('company_name') === $company ? 'selected' : '' }}>
                                        {{ $company }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="merchant_id" class="form-select">
                                <option value="">{{ __('All Merchants') }}</option>
                                @foreach($merchants as $merchant)
                                    <option value="{{ $merchant->id }}" {{ (string) request('merchant_id') === (string) $merchant->id ? 'selected' : '' }}>
                                        {{ $merchant->company_name }} - {{ $merchant->getTranslation('name', app()->getLocale()) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="available" class="form-select">
                                <option value="">{{ __('All Availability') }}</option>
                                <option value="1" {{ request('available') === '1' ? 'selected' : '' }}>{{ __('Available') }}</option>
                                <option value="0" {{ request('available') === '0' ? 'selected' : '' }}>{{ __('Unavailable') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <div class="col-md-2 ms-auto d-grid">
                            <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Product ID') }}</th>
                            <th>{{ __('Image') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Merchant') }}</th>
                            <th>{{ __('Name (EN)') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Currency') }}</th>
                            <th>{{ __('Available') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($digitalProducts as $product)
                            <tr>
                                <td>{{ $product->product_id }}</td>
                                <td><img src="{{ $product->image }}" alt="image" class="rounded" style="width: 44px; height: 44px; object-fit: cover;"></td>
                                <td>{{ $product->company_name }}</td>
                                <td>{{ $product->merchant?->getTranslation('name', app()->getLocale()) ?? '-' }}</td>
                                <td>{{ $product->getTranslation('name', 'en') }}</td>
                                <td>{{ number_format($product->price, 2) }}</td>
                                <td>{{ $product->currency ?? '-' }}</td>
                                <td>
                                    @if($product->is_available)
                                        <span class="badge bg-success">{{ __('Available') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Unavailable') }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input toggle-active-digital-product"
                                               type="checkbox"
                                               data-toggle-url="{{ route('admin.digital-products.toggle-active', $product) }}"
                                               {{ $product->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label">
                                            @if($product->is_active)
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </label>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.digital-products.show', $product) }}" class="btn btn-sm btn-outline-info">
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">{{ __('No digital products found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                {{ $digitalProducts->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
@push('scripts')
<script>
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('toggle-active-digital-product')) {
        const checkbox = e.target;
        const toggleUrl = checkbox.dataset.toggleUrl;
        const originalChecked = checkbox.checked;

        checkbox.disabled = true;

        fetch(toggleUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            checkbox.disabled = false;
            if (data.success) {
                const label = checkbox.nextElementSibling;
                if (label) {
                    if (checkbox.checked) {
                        label.innerHTML = '<span class="badge bg-success">{{ __('Active') }}</span>';
                    } else {
                        label.innerHTML = '<span class="badge bg-secondary">{{ __('Inactive') }}</span>';
                    }
                }
            } else {
                checkbox.checked = !originalChecked;
                Swal.fire('{{ __('Error!') }}', data.message, 'error');
            }
        })
        .catch(() => {
            checkbox.disabled = false;
            checkbox.checked = !originalChecked;
            Swal.fire('{{ __('Error!') }}', '{{ __('Something went wrong') }}', 'error');
        });
    }
});
</script>
@endpush

