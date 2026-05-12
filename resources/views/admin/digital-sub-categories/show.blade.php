@extends('layouts.app')

@php
    $page = 'digital-sub-categories';
@endphp

@section('title', 'Digital Sub Category Details')

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">{{ __('Digital Sub Category Details') }}</h1>
                <p class="text-muted mb-0">{{ __('View digital sub category information') }}</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.digital-sub-categories.edit', $digitalSubCategory) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>{{ __('Edit') }}
                </a>
                <a href="{{ route('admin.digital-sub-categories.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>{{ __('Back') }}
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Information') }}</h5>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ __('Digital Category') }}</dt>
                            <dd class="col-sm-8">{{ $digitalSubCategory->digitalCategory?->getTranslation('name', app()->getLocale()) ?? '-' }}</dd>

                            <dt class="col-sm-4">{{ __('Name (English)') }}</dt>
                            <dd class="col-sm-8">{{ $digitalSubCategory->getTranslation('name', 'en') }}</dd>

                            <dt class="col-sm-4">{{ __('Name (Arabic)') }}</dt>
                            <dd class="col-sm-8">{{ $digitalSubCategory->getTranslation('name', 'ar') }}</dd>

                            <dt class="col-sm-4">{{ __('Slug') }}</dt>
                            <dd class="col-sm-8">{{ $digitalSubCategory->slug }}</dd>

                            <dt class="col-sm-4">{{ __('Visits') }}</dt>
                            <dd class="col-sm-8">{{ $digitalSubCategory->visits }}</dd>

                            <dt class="col-sm-4">{{ __('Status') }}</dt>
                            <dd class="col-sm-8">
                                @if($digitalSubCategory->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4">{{ __('Last Updated By') }}</dt>
                            <dd class="col-sm-8">
                                @if($digitalSubCategory->lastUpdatedBy)
                                    {{ $digitalSubCategory->lastUpdatedBy->name }}
                                    @if($digitalSubCategory->lastUpdatedBy->email)
                                        <small class="text-muted d-block">{{ $digitalSubCategory->lastUpdatedBy->email }}</small>
                                    @endif
                                @else
                                    -
                                @endif
                            </dd>

                            <dt class="col-sm-4">{{ __('Created At') }}</dt>
                            <dd class="col-sm-8">{{ $digitalSubCategory->created_at?->format('Y-m-d h:i A') }}</dd>

                            <dt class="col-sm-4">{{ __('Updated At') }}</dt>
                            <dd class="col-sm-8">{{ $digitalSubCategory->updated_at?->format('Y-m-d h:i A') }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Image') }}</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ $digitalSubCategory->image }}" alt="image" class="img-fluid rounded">
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Thumbnail') }}</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="{{ $digitalSubCategory->thumbnail ? asset('storage/'.$digitalSubCategory->getOriginal('thumbnail')) : $digitalSubCategory->image }}" alt="thumbnail" class="img-fluid rounded">
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('Actions') }}</h5>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <a href="{{ route('admin.digital-sub-categories.edit', $digitalSubCategory) }}" class="btn btn-primary">{{ __('Edit') }}</a>
                        <form action="{{ route('admin.digital-sub-categories.destroy', $digitalSubCategory) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure?') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">{{ __('Delete') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
