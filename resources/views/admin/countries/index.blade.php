@extends('layouts.app')

@php
    $page = 'countries';
@endphp

@section('title', __('messages.shipping_ui.system_countries_verification'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item active">{{ __('messages.shipping_ui.system_countries_verification') }}</li>
            </ol>
        </nav>
        <h1 class="h3 mb-1">{{ __('messages.shipping_ui.system_countries_verification') }}</h1>
        <p class="text-muted mb-4">{{ __('messages.shipping_ui.system_countries_subtitle') }}</p>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Code') }}</th>
                                <th>{{ __('Flag') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Dial code') }}</th>
                                <th>{{ __('Verification & status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($countries as $country)
                                <tr>
                                    <td class="fw-semibold">{{ $country->code }}</td>
                                    <td>
                                        @if($country->flag)
                                            <img src="{{ $country->flag }}" alt="flag" style="width: 42px; height: 28px; object-fit: cover; border-radius: 4px;">
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $country->name[app()->getLocale()] ?? $country->name['en'] ?? $country->code }}</td>
                                    <td>{{ $country->dial_code }}</td>
                                    <td>
                                        <form action="{{ route('admin.countries.update', $country) }}" method="POST" class="row g-2 align-items-end">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-md-4">
                                                <label class="form-label small text-muted mb-0">{{ __('Channel') }}</label>
                                                @php
                                                    $selectedChannels = old('verification_channels', $country->getVerificationChannels());
                                                @endphp
                                                <select name="verification_channels[]" class="form-select form-select-sm" data-channel-multiselect multiple required>
                                                    <option value="sms" @selected(in_array('sms', $selectedChannels, true))>SMS</option>
                                                    <option value="whatsapp" @selected(in_array('whatsapp', $selectedChannels, true))>WHATSAPP</option>
                                                    <option value="email" @selected(in_array('email', $selectedChannels, true))>EMAIL</option>
                                                </select>
                                                @error('verification_channels')
                                                    <div class="text-danger small">{{ $message }}</div>
                                                @enderror
                                                @error('verification_channels.*')
                                                    <div class="text-danger small">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-check form-switch mt-3">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $country->id }}"
                                                        @checked($country->is_active)>
                                                    <label class="form-check-label" for="active_{{ $country->id }}">{{ __('Country active') }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-3 text-md-end">
                                                <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $countries->links() }}
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-channel-multiselect]').forEach(function (element) {
                if (!element.tomselect) {
                    new TomSelect(element, {
                        plugins: ['remove_button'],
                        create: false,
                        maxItems: null,
                        hideSelected: true,
                        closeAfterSelect: false
                    });
                }
            });
        });
    </script>
@endpush

