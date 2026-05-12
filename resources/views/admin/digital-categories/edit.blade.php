@extends('layouts.app')

@php
    $page = 'digital-categories';
@endphp

@section('title', 'Edit Digital Category')

@section('content')
    <div class="container-fluid p-4 p-lg-4">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">{{ __('Edit Digital Category') }}</h1>
                <p class="text-muted mb-0">{{ __('Update digital category details') }}</p>
            </div>
            <a href="{{ route('admin.digital-categories.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>{{ __('Back') }}
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.digital-categories.update', $digitalCategory) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">{{ __('Name (English)') }} *</label>
                        <input type="text" name="name[en]" class="form-control @error('name.en') is-invalid @enderror"
                               value="{{ old('name.en', $digitalCategory->getTranslation('name', 'en')) }}" required>
                        @error('name.en')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">{{ __('Name (Arabic)') }} *</label>
                        <input type="text" name="name[ar]" class="form-control @error('name.ar') is-invalid @enderror"
                               value="{{ old('name.ar', $digitalCategory->getTranslation('name', 'ar')) }}" required>
                        @error('name.ar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Image') }}</label>
                            <input type="file" name="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
                            @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">{{ __('Leave empty to keep current image') }}</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Thumbnail') }}</label>
                            <input type="file" name="thumbnail" class="form-control @error('thumbnail') is-invalid @enderror" accept="image/*">
                            @error('thumbnail')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">{{ __('Leave empty to keep current thumbnail') }}</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Current Image') }}</label>
                            <div>
                                <img src="{{ $digitalCategory->image }}" alt="image" class="img-thumbnail" style="max-width: 160px;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Current Thumbnail') }}</label>
                            <div>
                                <img src="{{ $digitalCategory->thumbnail ? asset('storage/'.$digitalCategory->getOriginal('thumbnail')) : $digitalCategory->image }}"
                                     alt="thumbnail" class="img-thumbnail" style="max-width: 160px;">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Visits') }}</label>
                            <input type="number" name="visits" class="form-control @error('visits') is-invalid @enderror" min="0"
                                   value="{{ old('visits', $digitalCategory->visits) }}">
                            @error('visits')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $digitalCategory->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
                        <a href="{{ route('admin.digital-categories.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
