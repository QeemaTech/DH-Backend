@extends('layouts.app')

@php
    $page = 'digital-product-purchase-limits';
@endphp

@section('title', 'Digital Product Purchase Limits')

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">{{ __('Digital Product Purchase Limits') }}</h1>
                <p class="text-muted mb-0">{{ __('Manage purchase limits by verification level and period') }}</p>
            </div>
            <a href="{{ route('admin.digital-product-purchase-limits.create') }}" class="btn btn-primary">
                {{ __('Add Purchase Limit') }}
            </a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.digital-product-purchase-limits.index') }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <select name="verification_level" class="form-select">
                                <option value="">{{ __('All Verification Levels') }}</option>
                                <option value="contact_verified" {{ request('verification_level') === 'contact_verified' ? 'selected' : '' }}>contact_verified</option>
                                <option value="fully_verified" {{ request('verification_level') === 'fully_verified' ? 'selected' : '' }}>fully_verified</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="period_type" class="form-select">
                                <option value="">{{ __('All Periods') }}</option>
                                <option value="daily" {{ request('period_type') === 'daily' ? 'selected' : '' }}>daily</option>
                                <option value="weekly" {{ request('period_type') === 'weekly' ? 'selected' : '' }}>weekly</option>
                                <option value="monthly" {{ request('period_type') === 'monthly' ? 'selected' : '' }}>monthly</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-grid">
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
                            <th>{{ __('Verification Level') }}</th>
                            <th>{{ __('Period Type') }}</th>
                            <th>{{ __('Limit Amount') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Updated At') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseLimits as $limit)
                            <tr>
                                <td>{{ $limit->verification_level }}</td>
                                <td>{{ $limit->period_type }}</td>
                                <td>{{ number_format((float) $limit->limit_amount, 2, '.', '') }}</td>
                                <td>
                                    <span class="badge {{ $limit->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $limit->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                </td>
                                <td>{{ $limit->updated_at?->format('Y-m-d h:i A') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.digital-product-purchase-limits.edit', $limit) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    <form method="POST" action="{{ route('admin.digital-product-purchase-limits.toggle-active', $limit) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                            {{ $limit->is_active ? __('Disable') : __('Enable') }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.digital-product-purchase-limits.destroy', $limit) }}" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">{{ __('No purchase limits found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                {{ $purchaseLimits->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
