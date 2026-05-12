@extends('layouts.app')

@php
    $page = 'faqs';
@endphp

@section('title', __('FAQs'))

@section('content')

    <div class="container-fluid p-4 p-lg-4">

        <!-- Success/Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb" class="mb-2">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('FAQs') }}</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-0">{{ __('FAQs') }}</h1>
                <p class="text-muted mb-0">{{ __('Manage frequently asked questions') }}</p>
            </div>
            <div>
                <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>{{ __('Add FAQ') }}
                </a>
            </div>
        </div>

        <!-- FAQs Table -->
        <div class="card">
            <div class="card-body">
                @include('admin.faqs.partials.table', ['faqs' => $faqs])
            </div>
        </div>

    </div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-faq-btn');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const faqId = this.getAttribute('data-id');

                Swal.fire({
                    title: '{{ __('Are you sure?') }}',
                    text: '{{ __('You are about to delete this FAQ. This action cannot be undone!') }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '{{ __('Yes, delete it!') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route('admin.faqs.destroy', ':id') }}'.replace(':id', faqId);
                        form.innerHTML = '@csrf @method('DELETE')';
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
