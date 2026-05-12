@extends('layouts.app')

@php
    $page = 'digital-merchants';
@endphp

@section('title', 'Digital Merchants')

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
                <h1 class="h3 mb-0">{{ __('Digital Merchants') }}</h1>
                <p class="text-muted mb-0">{{ __('View synced merchants from providers') }}</p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.digital-merchants.index') }}">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <input type="text" name="search" class="form-control"
                                   placeholder="{{ __('Search by merchant id or name...') }}"
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
                        <div class="col-md-1 d-grid">
                            <button type="submit" class="btn btn-outline-primary">{{ __('Go') }}</button>
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
                            <th>{{ __('Merchant ID') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Name (EN)') }}</th>
                            <th>{{ __('Name (AR)') }}</th>
                            <th>{{ __('Parent') }}</th>
                            <th>{{ __('Last Synced') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($digitalMerchants as $merchant)
                            <tr>
                                <td>{{ $merchant->merchant_id }}</td>
                                <td>{{ $merchant->company_name }}</td>
                                <td>{{ $merchant->getTranslation('name', 'en') }}</td>
                                <td>{{ $merchant->getTranslation('name', 'ar') }}</td>
                                <td>{{ $merchant->parent?->getTranslation('name', app()->getLocale()) ?? '-' }}</td>
                                <td>{{ $merchant->last_synced_at?->format('Y-m-d h:i A') ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.digital-merchants.show', $merchant) }}" class="btn btn-sm btn-outline-info">
                                        {{ __('View') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">{{ __('No digital merchants found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                {{ $digitalMerchants->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
