@extends('layouts.auth')

@section('title', __('Email Verification'))

@section('content')
    <div class="mx-auto py-3" style="max-width: 560px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center p-4 p-md-5">
                <div class="mb-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger" style="width:64px;height:64px;">
                        <i class="bi bi-x-lg fs-4"></i>
                    </span>
                </div>
                <h1 class="h4 fw-bold mb-2">{{ __('Email verification failed') }}</h1>
                <p class="text-muted mb-0 lh-lg">{{ __('The verification link is invalid or expired. Please request a new verification email.') }}</p>
            </div>
        </div>
    </div>
@endsection
