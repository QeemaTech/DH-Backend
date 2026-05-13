@extends('layouts.app')

@php
    $page = 'shipping-cities';
@endphp

@section('title', __('messages.shipping_ui.edit_city'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <h1 class="h3 mb-4">{{ __('messages.shipping_ui.edit_city') }}</h1>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.cities.update', $city) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.cities.partials.form', ['city' => $city])
                </form>
            </div>
        </div>
    </div>
@endsection
