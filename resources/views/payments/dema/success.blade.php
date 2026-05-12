@extends('layouts.payment-redirect')

@section('title', __('Payment successful'))

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success mb-3"
                            style="width: 56px; height: 56px;">
                            <i class="bi bi-check2-circle fs-2"></i>
                        </div>

                        <h1 class="h4 fw-bold mb-2">{{ __('Payment successful') }}</h1>
                        <p class="text-muted mb-4">
                            {{ __('Thanks! Your Deema payment was completed. You can safely close this page.') }}
                        </p>

                        @if (! empty($chargeId))
                            <div class="border rounded-3 p-3 text-start mb-4 bg-body-tertiary">
                                <div class="small text-muted mb-1">{{ __('Charge ID') }}</div>
                                <div class="font-monospace text-break">{{ $chargeId }}</div>
                            </div>
                        @endif

                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                <i class="bi bi-house me-1"></i>{{ __('Go home') }}
                            </a>
                            <a href="{{ url('/profile') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-person me-1"></i>{{ __('Profile') }}
                            </a>
                        </div>
                    </div>
                </div>
                <p class="text-center text-muted small mt-3 mb-0">
                    {{ __('If you do not see the order updated yet, please wait a moment while we confirm it.') }}
                </p>
            </div>
        </div>
    </div>
@endsection
