@extends('layouts.app')

@php
    $page = 'contact_messages';
@endphp

@section('title', __('Contact Messages'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Contact Messages') }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">{{ __('Contact Messages') }}</h1>
                <p class="text-muted mb-0">{{ __('Messages submitted from Contact Us API') }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Email') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Subject') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($messages as $message)
                                <tr>
                                    <td>
                                        @if($message->viewed_at)
                                            <span class="badge bg-success">{{ __('Viewed') }}</span>
                                        @else
                                            <span class="badge bg-warning text-dark">{{ __('New') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $message->name }}</td>
                                    <td>{{ $message->email }}</td>
                                    <td>{{ $message->phone ?: '—' }}</td>
                                    <td class="text-truncate" style="max-width: 260px;">{{ $message->subject }}</td>
                                    <td>{{ $message->created_at?->diffForHumans() }}</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ route('admin.contact-messages.show', $message) }}">
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">{{ __('No messages yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end">
                    {{ $messages->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

