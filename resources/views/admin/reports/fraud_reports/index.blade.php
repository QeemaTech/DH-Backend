@extends('layouts.app')

@php
    $page = 'fraud_reports';
@endphp

@section('title', __('Fraud Reports'))

@section('content')
<div class="container-fluid p-4 p-lg-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Fraud Reports') }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">{{ __('Fraud Reports') }}</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.fraud-reports.index') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('Assigned To') }}</label>
                    <select name="assigned_to" class="form-select">
                        <option value="">{{ __('All') }}</option>
                        <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>{{ __('Unassigned') }}</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->id }}" @selected((string) request('assigned_to') === (string) $admin->id)>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary w-100">{{ __('Filter') }}</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('Full Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Phone') }}</th>
                            <th>{{ __('Assigned To') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            @php
                                $status = $report->status ?? 'new';
                                $badge = $status === 'resolved' ? 'bg-success' : ($status === 'rejected' ? 'bg-danger' : ($status === 'in_review' ? 'bg-primary' : 'bg-warning text-dark'));
                            @endphp
                            <tr>
                                <td>{{ $report->id }}</td>
                                <td>{{ $report->full_name }}</td>
                                <td>{{ $report->email }}</td>
                                <td>{{ $report->phone_number }}</td>
                                <td>{{ $report->assignee?->name ?? __('Unassigned') }}</td>
                                <td><span class="badge {{ $badge }}">{{ ucwords(str_replace('_', ' ', $status)) }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.fraud-reports.show', $report) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">{{ __('No fraud reports found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $reports->links() }}</div>
        </div>
    </div>
</div>
@endsection
