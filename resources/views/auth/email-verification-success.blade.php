@extends('layouts.auth')

@section('title', __('Email Verification'))

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-body text-center p-4">
                        <h1 class="h4 mb-3">{{ __('Email verified successfully') }}</h1>
                        <p class="text-muted mb-0">{{ __('Your email has been verified. You can return to the app now.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
