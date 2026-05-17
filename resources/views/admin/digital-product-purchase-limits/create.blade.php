@extends('layouts.app')

@php
    $page = 'digital-product-purchase-limits';
@endphp

@section('title', 'Create Digital Product Purchase Limit')

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">{{ __('Create Digital Product Purchase Limit') }}</h1>
            <a href="{{ route('admin.digital-product-purchase-limits.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </div>

        <div class="card"><div class="card-body">
            <form method="POST" action="{{ route('admin.digital-product-purchase-limits.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">{{ __('Verification Level') }}</label>
                    <select name="verification_level" class="form-select @error('verification_level') is-invalid @enderror" required>
                        <option value="contact_verified" {{ old('verification_level') === 'contact_verified' ? 'selected' : '' }}>contact_verified</option>
                        <option value="fully_verified" {{ old('verification_level') === 'fully_verified' ? 'selected' : '' }}>fully_verified</option>
                    </select>
                    @error('verification_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('Period Type') }}</label>
                    <select name="period_type" class="form-select @error('period_type') is-invalid @enderror" required>
                        <option value="daily" {{ old('period_type') === 'daily' ? 'selected' : '' }}>daily</option>
                        <option value="weekly" {{ old('period_type') === 'weekly' ? 'selected' : '' }}>weekly</option>
                        <option value="monthly" {{ old('period_type') === 'monthly' ? 'selected' : '' }}>monthly</option>
                    </select>
                    @error('period_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('Limit Amount') }}</label>
                    <input type="number" step="0.01" min="0.01" name="limit_amount" value="{{ old('limit_amount') }}" class="form-control @error('limit_amount') is-invalid @enderror" required>
                    @error('limit_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
            </form>
        </div></div>
    </div>
@endsection
