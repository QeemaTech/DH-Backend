@extends('layouts.payment-redirect')

@section('title', __('Payment cancelled'))

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning mb-3"
                            style="width: 56px; height: 56px;">
                            <i class="bi bi-x-circle fs-2"></i>
                        </div>

                        <h1 class="h4 fw-bold mb-2">{{ __('Payment cancelled') }}</h1>
                        <p class="text-muted mb-4">
                            {{ __('You cancelled the payment. You can try again or choose another payment method.') }}
                        </p>

                        @if (! empty($paymentId))
                            <div class="border rounded-3 p-3 text-start mb-4 bg-body-tertiary">
                                <div class="small text-muted mb-1">{{ __('Payment ID') }}</div>
                                <div class="font-monospace text-break">{{ $paymentId }}</div>
                            </div>
                        @endif

                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                <i class="bi bi-cart me-1"></i>{{ __('Back to shop') }}
                            </a>
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i>{{ __('Go back') }}
                            </a>
                        </div>
                    </div>
                </div>
                <p class="text-center text-muted small mt-3 mb-0">
                    {{ __('If you were charged, please contact support and share the Payment ID.') }}
                </p>
            </div>
        </div>
    </div>
@endsection

