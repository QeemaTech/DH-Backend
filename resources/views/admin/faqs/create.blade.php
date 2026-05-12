@extends('layouts.app')

@php
    $page = 'faqs';
@endphp

@section('title', __('Create FAQ'))

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

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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
                        <li class="breadcrumb-item active">{{ __('Create FAQ') }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">{{ __('Create FAQ') }}</h1>
                <p class="text-muted mb-0">{{ __('Add a new frequently asked question') }}</p>
            </div>
            <div>
                <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>{{ __('Back') }}
                </a>
            </div>
        </div>

        <!-- FAQ Form -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('FAQ Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.faqs.store') }}" method="POST" id="faqForm">
                            @csrf

                            <ul class="nav nav-tabs mb-3" id="localeTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="en-tab" data-bs-toggle="tab" data-bs-target="#en-pane" type="button" role="tab">English</button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="ar-tab" data-bs-toggle="tab" data-bs-target="#ar-pane" type="button" role="tab">العربية</button>
                                </li>
                            </ul>

                            <div class="tab-content" id="localeTabContent">
                                <div class="tab-pane fade show active" id="en-pane" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="question_en" class="form-label">{{ __('Question (English)') }} *</label>
                                        <input type="text" class="form-control @error('question.en') is-invalid @enderror"
                                            id="question_en" name="question[en]"
                                            value="{{ old('question.en') }}" required>
                                        @error('question.en')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="answer_en" class="form-label">{{ __('Answer (English)') }} *</label>
                                        <textarea class="form-control @error('answer.en') is-invalid @enderror" id="answer_en"
                                            name="answer[en]" rows="4" required>{{ old('answer.en') }}</textarea>
                                        @error('answer.en')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="ar-pane" role="tabpanel">
                                    <div class="mb-3">
                                        <label for="question_ar" class="form-label">{{ __('Question (Arabic)') }} *</label>
                                        <input type="text" class="form-control @error('question.ar') is-invalid @enderror"
                                            id="question_ar" name="question[ar]"
                                            value="{{ old('question.ar') }}" required dir="rtl">
                                        @error('question.ar')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="mb-3">
                                        <label for="answer_ar" class="form-label">{{ __('Answer (Arabic)') }} *</label>
                                        <textarea class="form-control @error('answer.ar') is-invalid @enderror" id="answer_ar"
                                            name="answer[ar]" rows="4" required dir="rtl">{{ old('answer.ar') }}</textarea>
                                        @error('answer.ar')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="order" class="form-label">{{ __('Order') }}</label>
                                <input type="number" class="form-control @error('order') is-invalid @enderror"
                                    id="order" name="order" value="{{ old('order', 0) }}" min="0">
                                @error('order')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">{{ __('Lower numbers appear first') }}</div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                        {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>{{ __('Cancel') }}
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>{{ __('Create FAQ') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
