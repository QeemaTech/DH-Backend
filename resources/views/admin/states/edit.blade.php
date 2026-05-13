@extends('layouts.app')

@php
    $page = 'shipping-states';
@endphp

@section('title', __('messages.shipping_ui.edit_state'))

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <h1 class="h3 mb-4">{{ __('messages.shipping_ui.edit_state') }}</h1>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.states.update', $state) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.states.partials.form', ['state' => $state])
                </form>
            </div>
        </div>
    </div>
@endsection
