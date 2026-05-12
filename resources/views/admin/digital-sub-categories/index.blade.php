@extends('layouts.app')

@php
    $page = 'digital-sub-categories';
@endphp

@section('title', 'Digital Sub Categories')

@section('content')
    <div class="container-fluid p-4 p-lg-4">
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">{{ __('Digital Sub Categories') }}</h1>
                <p class="text-muted mb-0">{{ __('Manage digital sub categories') }}</p>
            </div>
            <a href="{{ route('admin.digital-sub-categories.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-2"></i>{{ __('Add Digital Sub Category') }}
            </a>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.digital-sub-categories.index') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="{{ __('Search by name...') }}" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="digital_category_id" class="form-select">
                                <option value="">{{ __('All Digital Categories') }}</option>
                                @foreach($digitalCategories as $digitalCategory)
                                    <option value="{{ $digitalCategory->id }}" {{ (string) request('digital_category_id') === (string) $digitalCategory->id ? 'selected' : '' }}>
                                        {{ $digitalCategory->getTranslation('name', app()->getLocale()) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">{{ __('All Statuses') }}</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="sort" class="form-select">
                                <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>{{ __('Latest') }}</option>
                                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>{{ __('Oldest') }}</option>
                                <option value="name_asc" {{ request('sort') === 'name_asc' ? 'selected' : '' }}>{{ __('Name A-Z') }}</option>
                                <option value="name_desc" {{ request('sort') === 'name_desc' ? 'selected' : '' }}>{{ __('Name Z-A') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">{{ __('Filter') }}</button>
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
                            <th>{{ __('Image') }}</th>
                            <th>{{ __('Name (EN)') }}</th>
                            <th>{{ __('Digital Category') }}</th>
                            <th>{{ __('Visits') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Last Updated By') }}</th>
                            <th>{{ __('Updated At') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($digitalSubCategories as $digitalSubCategory)
                            <tr>
                                <td>
                                    <img src="{{ $digitalSubCategory->image }}" alt="image" class="rounded" style="width: 44px; height: 44px; object-fit: cover;">
                                </td>
                                <td>{{ $digitalSubCategory->getTranslation('name', 'en') }}</td>
                                <td>{{ $digitalSubCategory->digitalCategory?->getTranslation('name', app()->getLocale()) ?? '-' }}</td>
                                <td>{{ $digitalSubCategory->visits }}</td>
                                <td>
                                    @if($digitalSubCategory->is_active)
                                        <span class="badge bg-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td>{{ $digitalSubCategory->lastUpdatedBy?->name ?? '-' }}</td>
                                <td>{{ $digitalSubCategory->updated_at?->format('Y-m-d h:i A') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.digital-sub-categories.show', $digitalSubCategory) }}" class="btn btn-sm btn-outline-info">{{ __('View') }}</a>
                                    <a href="{{ route('admin.digital-sub-categories.edit', $digitalSubCategory) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                    <form action="{{ route('admin.digital-sub-categories.destroy', $digitalSubCategory) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">{{ __('No digital sub categories found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body">
                {{ $digitalSubCategories->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
