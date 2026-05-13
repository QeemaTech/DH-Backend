@extends('layouts.app')

@php
    $page = 'shipping-countries';
@endphp

@section('title', __('Edit Country'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <h1 class="h3 mb-4">{{ __('Edit Country') }}</h1>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.countries.update', $country) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.countries.partials.form', ['country' => $country])
                </form>
            </div>
        </div>
    </div>
@endsection
