@extends('layouts.app')

@php
    $page = 'shipping-countries';
@endphp

@section('title', __('Add Country'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <h1 class="h3 mb-4">{{ __('Add Country') }}</h1>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.countries.store') }}">
                    @csrf
                    @include('admin.countries.partials.form')
                </form>
            </div>
        </div>
    </div>
@endsection
