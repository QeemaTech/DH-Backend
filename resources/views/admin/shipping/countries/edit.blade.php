@extends('layouts.app')

@php
    $page = 'shipping-countries';
@endphp

@section('title', __('messages.shipping_ui.edit_country'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <h1 class="h3 mb-4">{{ __('messages.shipping_ui.edit_country') }}</h1>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.shipping-countries.update', $country) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    @include('admin.shipping.countries.partials.form', ['country' => $country])
                </form>
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
