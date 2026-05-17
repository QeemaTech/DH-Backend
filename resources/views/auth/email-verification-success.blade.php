@extends('layouts.auth')

@section('title', __('Email Verification'))

@section('content')
    <div class="mx-auto py-3" style="max-width: 560px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-4 p-md-5">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width:64px;height:64px;">
                        <i class="bi bi-check-lg fs-3"></i>
                    </span>
                </div>
                <h1 class="h4 fw-bold mb-2">{{ __('Email verified successfully') }}</h1>
                <p class="text-muted mb-0 lh-lg">{{ __('Your account email is verified. You can now return to the app and continue.') }}</p>
            </div>
        </div>
    </div>
@endsection
