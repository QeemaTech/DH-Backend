@extends('layouts.app')

@php
    $page = 'shipping-cities';
@endphp

@section('title', __('messages.shipping_ui.cities_title'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">{{ __('messages.shipping_ui.cities_title') }}</h1>
                <p class="text-muted mb-0">{{ __('messages.shipping_ui.cities_subtitle') }}</p>
            </div>
            <a href="{{ route('admin.cities.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>{{ __('messages.shipping_ui.add_city') }}
            </a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.cities.index') }}">
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
                            <select name="state_id" class="form-select">
                                <option value="">{{ __('messages.shipping_ui.all_states') }}</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}" @selected((int) request('state_id') === (int) $state->id)>
                                        {{ $state->name['en'] ?? $state->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                   placeholder="{{ __('messages.shipping_ui.search_city_placeholder') }}">
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">{{ __('messages.shipping_ui.all_statuses') }}</option>
                                <option value="active" @selected(request('status') === 'active')>{{ __('messages.shipping_ui.status_active') }}</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('messages.shipping_ui.status_inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-grid">
                            <button type="submit" class="btn btn-outline-primary">{{ __('messages.shipping_ui.go') }}</button>
                        </div>
                        <div class="col-md-1 d-grid">
                            <a href="{{ route('admin.cities.index') }}" class="btn btn-outline-secondary">{{ __('messages.shipping_ui.clear') }}</a>
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
                        <th>{{ __('messages.shipping_ui.state') }}</th>
                        <th>{{ __('messages.shipping_ui.city') }}</th>
                        <th>{{ __('messages.shipping_ui.shipping_cost') }}</th>
                        <th>{{ __('messages.shipping_ui.status') }}</th>
                        <th>{{ __('messages.shipping_ui.sort') }}</th>
                        <th class="text-end">{{ __('messages.shipping_ui.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($cities as $city)
                        <tr>
                            <td>{{ $city->country?->name[app()->getLocale()] ?? $city->state?->country?->name['en'] ?? '-' }}</td>
                            <td>{{ $city->state?->name[app()->getLocale()] ?? $city->state?->name['en'] ?? '-' }}</td>
                            <td>{{ $city->name[app()->getLocale()] ?? $city->name['en'] ?? '-' }}</td>
                            <td>{{ number_format((float) $city->shipping_cost, 3) }}</td>
                            <td>
                                @if($city->is_active)
                                    <span class="badge bg-success">{{ __('messages.shipping_ui.status_active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('messages.shipping_ui.status_inactive') }}</span>
                                @endif
                            </td>
                            <td>{{ $city->sort_order }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.cities.edit', $city) }}" class="btn btn-sm btn-outline-primary">{{ __('messages.shipping_ui.edit') }}</a>
                                <form action="{{ route('admin.cities.destroy', $city) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('{{ __('messages.shipping_ui.confirm_delete_city') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('messages.shipping_ui.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">{{ __('messages.shipping_ui.no_cities_found') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">{{ $cities->links() }}</div>
        </div>
    </div>
@endsection
