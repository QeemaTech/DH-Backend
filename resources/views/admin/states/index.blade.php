@extends('layouts.app')

@php
    $page = 'shipping-states';
@endphp

@section('title', __('messages.shipping_ui.states_title'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">{{ __('messages.shipping_ui.states_title') }}</h1>
                <p class="text-muted mb-0">{{ __('messages.shipping_ui.states_subtitle') }}</p>
            </div>
            <a href="{{ route('admin.states.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>{{ __('messages.shipping_ui.add_state') }}
            </a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.states.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select name="country_id" class="form-select">
                                <option value="">{{ __('messages.shipping_ui.all_countries') }}</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" @selected((int) request('country_id') === (int) $country->id)>
                                        {{ $country->name['en'] ?? $country->code }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                   placeholder="{{ __('messages.shipping_ui.search_state_placeholder') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">{{ __('messages.shipping_ui.all_statuses') }}</option>
                                <option value="active" @selected(request('status') === 'active')>{{ __('messages.shipping_ui.status_active') }}</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('messages.shipping_ui.status_inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">{{ __('messages.shipping_ui.filter') }}</button>
                        </div>
                        <div class="col-md-2 d-grid">
                            <a href="{{ route('admin.states.index') }}" class="btn btn-outline-secondary">{{ __('messages.shipping_ui.reset') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>{{ __('messages.shipping_ui.country') }}</th>
                        <th>{{ __('messages.shipping_ui.code') }}</th>
                        <th>{{ __('messages.shipping_ui.name') }}</th>
                        <th>{{ __('messages.shipping_ui.status') }}</th>
                        <th>{{ __('messages.shipping_ui.sort') }}</th>
                        <th class="text-end">{{ __('messages.shipping_ui.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($states as $state)
                        <tr>
                            <td>{{ $state->country?->name[app()->getLocale()] ?? $state->country?->name['en'] ?? '-' }}</td>
                            <td>{{ $state->code ?: '-' }}</td>
                            <td>{{ $state->name[app()->getLocale()] ?? $state->name['en'] ?? '-' }}</td>
                            <td>
                                @if($state->is_active)
                                    <span class="badge bg-success">{{ __('messages.shipping_ui.status_active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('messages.shipping_ui.status_inactive') }}</span>
                                @endif
                            </td>
                            <td>{{ $state->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.states.edit', $state) }}" class="btn btn-sm btn-outline-primary">{{ __('messages.shipping_ui.edit') }}</a>
                                <form action="{{ route('admin.states.destroy', $state) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ __('messages.shipping_ui.confirm_delete_state') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('messages.shipping_ui.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">{{ __('messages.shipping_ui.no_states_found') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">{{ $states->links() }}</div>
        </div>
    </div>
@endsection
