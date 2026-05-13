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
                <form method="POST" action="{{ route('admin.shipping-countries.update', $country) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.shipping.countries.partials.form', ['country' => $country])
                </form>
            </div>
        </div>
    </div>
@endsection
