@extends('layouts.app')

@php
    $page = 'contact_messages';
@endphp

@section('title', __('Contact Message'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.contact-messages.index') }}">{{ __('Contact Messages') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('View') }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">{{ __('Contact Message') }}</h1>
                <p class="text-muted mb-0">
                    {{ __('Created') }}: {{ $message->created_at?->toDayDateTimeString() }}
                    @if($message->viewed_at)
                        · {{ __('Viewed') }}: {{ $message->viewed_at?->toDayDateTimeString() }}
                    @endif
                </p>
            </div>
            <div>
                <a href="{{ route('admin.contact-messages.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>{{ __('Back') }}
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted small mb-1">{{ __('Name') }}</div>
                        <div class="fw-semibold">{{ $message->name }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small mb-1">{{ __('Email') }}</div>
                        <div class="fw-semibold">{{ $message->email }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small mb-1">{{ __('Phone') }}</div>
                        <div class="fw-semibold">{{ $message->phone ?: '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted small mb-1">{{ __('Subject') }}</div>
                        <div class="fw-semibold">{{ $message->subject }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small mb-1">{{ __('Message') }}</div>
                        <div class="border rounded p-3 bg-light-subtle" style="white-space: pre-wrap;">{{ $message->message }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

