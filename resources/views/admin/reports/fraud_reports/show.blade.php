@extends('layouts.app')

@php
    $page = 'fraud_reports';
@endphp

@section('title', __('Fraud Report Details'))

@section('content')
<div class="container-fluid p-4 p-lg-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.fraud-reports.index') }}">{{ __('Fraud Reports') }}</a></li>
                    <li class="breadcrumb-item active">#{{ $fraudReport->id }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ __('Fraud Report') }} #{{ $fraudReport->id }}</h1>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>{{ __('Full Name') }}:</strong> {{ $fraudReport->full_name }}</div>
                <div class="col-md-6"><strong>{{ __('Company Name') }}:</strong> {{ $fraudReport->company_name ?: '-' }}</div>
                <div class="col-md-6"><strong>{{ __('Email') }}:</strong> {{ $fraudReport->email }}</div>
                <div class="col-md-6"><strong>{{ __('Phone Number') }}:</strong> {{ $fraudReport->phone_number }}</div>
                <div class="col-md-6"><strong>{{ __('Card Type') }}:</strong> {{ strtoupper($fraudReport->card_type) }}</div>
                <div class="col-md-6"><strong>{{ __('Last 4 Digits') }}:</strong> {{ $fraudReport->card_last4 }}</div>
                <div class="col-md-6"><strong>{{ __('IP-Address') }}:</strong> {{ $fraudReport->ip_address }}</div>
                <div class="col-md-6"><strong>{{ __('User Agent') }}:</strong> {{ $fraudReport->user_agent }}</div>
                <div class="col-md-6"><strong>{{ __('Assigned To') }}:</strong> {{ $fraudReport->assignee?->name ?? '-' }}</div>
                <div class="col-md-6"><strong>{{ __('Submitted At') }}:</strong> {{ optional($fraudReport->created_at)->format('Y-m-d H:i') }}</div>
                <div class="col-12"><strong>{{ __('Fraud Description') }}:</strong><br>{{ $fraudReport->fraud_description }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ __('Manage Report') }}</h5></div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.fraud-reports.update', $fraudReport) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">{{ __('Assign To Admin') }}</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">{{ __('Unassigned') }}</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" @selected((int) $fraudReport->assigned_to === (int) $admin->id)>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 text-end">
                    <button type="submit" name="status" value="in_review" class="btn btn-primary" @disabled($fraudReport->status === 'in_review')>{{ __('Mark In Review') }}</button>
                    <button type="submit" name="status" value="resolved" class="btn btn-success" @disabled($fraudReport->status === 'resolved')>{{ __('Resolved') }}</button>
                    <button type="submit" name="status" value="rejected" class="btn btn-outline-danger" @disabled($fraudReport->status === 'rejected')>{{ __('Rejected') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('Event Log') }}</h5></div>
        

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('Action By') }}</th>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('Before') }}</th>
                            <th>{{ __('After') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fraudReport->events->sortByDesc('id') as $event)
                            @php
                                $actionLabel = match ($event->event_type) {
                                    'submitted' => __('Submitted'),
                                    'status_changed' => __('Status Changed'),
                                    'assigned_changed' => __('Assignment Changed'),
                                    default => ucfirst(str_replace('_', ' ', $event->event_type)),
                                };

                                $beforeValue = '-';
                                $afterValue = '-';

                                if ($event->event_type === 'status_changed') {
                                    $beforeValue = isset($event->meta['from']) ? ucwords(str_replace('_', ' ', (string) $event->meta['from'])) : '-';
                                    $afterValue = isset($event->meta['to']) ? ucwords(str_replace('_', ' ', (string) $event->meta['to'])) : '-';
                                } elseif ($event->event_type === 'assigned_changed') {
                                    $beforeValue = $event->meta['from_name'] ?? (($event->meta['from'] ?? null) ? '#'.$event->meta['from'] : __('Unassigned'));
                                    $afterValue = $event->meta['to_name'] ?? (($event->meta['to'] ?? null) ? '#'.$event->meta['to'] : __('Unassigned'));
                                }
                            @endphp
                            <tr>
                                <td>{{ $event->id }}</td>
                                <td>{{ $event->actor?->name ?? ($event->actor_type ?: 'system') }}</td>
                                <td>{{ $actionLabel }}</td>
                                <td>{{ $beforeValue }}</td>
                                <td>{{ $afterValue }}</td>
                                
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">{{ __('No events logged yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection
