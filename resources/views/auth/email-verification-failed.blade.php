@extends('layouts.auth')

@section('title', __('Email Verification'))

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow-lg">
                    <div class="card-body text-center p-5">
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger-subtle text-danger" style="width:64px;height:64px;">
                                <i class="bi bi-x-lg fs-4"></i>
                            </span>
                        </div>
                        <h1 class="h4 fw-bold mb-2">{{ __('Email verification failed') }}</h1>
                        <p class="text-muted mb-0">{{ __('The verification link is invalid or expired. Please request a new verification email.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
