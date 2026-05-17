@extends('layouts.auth')

@section('title', __('Email Verification'))

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow-lg">
                    <div class="card-body text-center p-5">
                        <div class="mb-3">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width:64px;height:64px;">
                                <i class="bi bi-check-lg fs-3"></i>
                            </span>
                        </div>
                        <h1 class="h4 fw-bold mb-2">{{ __('Email verified successfully') }}</h1>
                        <p class="text-muted mb-0">{{ __('Your account email is verified. You can now return to the app and continue.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
