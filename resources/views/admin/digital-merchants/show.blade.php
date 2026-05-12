@extends('layouts.app')

@php
    $page = 'digital-merchants';
@endphp

@section('title', 'Digital Merchant Details')

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">{{ __('Digital Merchant Details') }}</h1>
                <p class="text-muted mb-0">{{ __('View merchant information') }}</p>
            </div>
            <a href="{{ route('admin.digital-merchants.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>{{ __('Back') }}
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">{{ __('Merchant ID') }}</dt>
                    <dd class="col-sm-9">{{ $digitalMerchant->merchant_id }}</dd>

                    <dt class="col-sm-3">{{ __('Company') }}</dt>
                    <dd class="col-sm-9">{{ $digitalMerchant->company_name }}</dd>

                    <dt class="col-sm-3">{{ __('Name (English)') }}</dt>
                    <dd class="col-sm-9">{{ $digitalMerchant->getTranslation('name', 'en') }}</dd>

                    <dt class="col-sm-3">{{ __('Name (Arabic)') }}</dt>
                    <dd class="col-sm-9">{{ $digitalMerchant->getTranslation('name', 'ar') }}</dd>

                    <dt class="col-sm-3">{{ __('Description') }}</dt>
                    <dd class="col-sm-9">{{ $digitalMerchant->getTranslation('description', app()->getLocale()) ?? '-' }}</dd>

                    <dt class="col-sm-3">{{ __('Redeem Steps') }}</dt>
                    <dd class="col-sm-9">{{ $digitalMerchant->getTranslation('redeem_steps', app()->getLocale()) ?? '-' }}</dd>

                    <dt class="col-sm-3">{{ __('Terms') }}</dt>
                    <dd class="col-sm-9">{{ $digitalMerchant->getTranslation('terms', app()->getLocale()) ?? '-' }}</dd>

                    <dt class="col-sm-3">{{ __('Parent Merchant') }}</dt>
                    <dd class="col-sm-9">
                        @if($digitalMerchant->parent)
                            <a href="{{ route('admin.digital-merchants.show', $digitalMerchant->parent) }}">
                                {{ $digitalMerchant->parent->getTranslation('name', app()->getLocale()) }}
                            </a>
                        @else
                            -
                        @endif
                    </dd>

                    <dt class="col-sm-3">{{ __('Children Count') }}</dt>
                    <dd class="col-sm-9">{{ $digitalMerchant->children->count() }}</dd>

                    <dt class="col-sm-3">{{ __('Last Synced At') }}</dt>
                    <dd class="col-sm-9">{{ $digitalMerchant->last_synced_at?->format('Y-m-d h:i A') ?? '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>
@endsection
