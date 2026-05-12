@extends('layouts.app')

@php
    $page = 'faqs';
@endphp

@section('title', __('FAQ Details'))

@section('content')

    <div class="container-fluid p-4 p-lg-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.faqs.index') }}">{{ __('FAQs') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('FAQ Details') }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">{{ __('FAQ Details') }}</h1>
                <p class="text-muted mb-0">{{ __('View FAQ information') }}</p>
            </div>
            <div>
                <a href="{{ route('admin.faqs.edit', $faq->id) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>{{ __('Edit') }}
                </a>
                <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>{{ __('Back') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-3">{{ __('Question') }}</dt>
                            <dd class="col-sm-9">{{ $faq->question }}</dd>

                            <dt class="col-sm-3">{{ __('Answer') }}</dt>
                            <dd class="col-sm-9">{{ $faq->answer }}</dd>

                            <dt class="col-sm-3">{{ __('Order') }}</dt>
                            <dd class="col-sm-9">{{ $faq->order }}</dd>

                            <dt class="col-sm-3">{{ __('Status') }}</dt>
                            <dd class="col-sm-9">
                                @if($faq->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </dd>

                            <dt class="col-sm-3">{{ __('Created At') }}</dt>
                            <dd class="col-sm-9">{{ $faq->created_at->format('M d, Y H:i') }}</dd>

                            <dt class="col-sm-3">{{ __('Updated At') }}</dt>
                            <dd class="col-sm-9">{{ $faq->updated_at->format('M d, Y H:i') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
