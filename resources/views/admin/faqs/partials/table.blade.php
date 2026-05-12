@if($faqs->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>{{ __('Question') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end" style="width: 180px;">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($faqs as $faq)
                    <tr>
                        <td class="text-muted">{{ $faq->order }}</td>
                        <td>
                            <span class="text-truncate d-inline-block" style="max-width: 300px;">
                                {{ $faq->question ?? '' }}
                            </span>
                        </td>
                        <td>
                            @if($faq->is_active)
                                <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                                <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group" role="group">
                                <a href="{{ route('admin.faqs.show', $faq->id) }}"
                                   class="btn btn-sm btn-outline-info"
                                   data-bs-toggle="tooltip"
                                   title="{{ __('View') }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.faqs.edit', $faq->id) }}"
                                   class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="tooltip"
                                   title="{{ __('Edit') }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger delete-faq-btn"
                                        data-id="{{ $faq->id }}"
                                        data-bs-toggle="tooltip"
                                        title="{{ __('Delete') }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-center py-5">
        <i class="bi bi-question-circle fs-1 text-muted"></i>
        <p class="text-muted mt-3">{{ __('No FAQs found.') }}</p>
        <a href="{{ route('admin.faqs.create') }}" class="btn btn-primary mt-2">
            <i class="bi bi-plus-lg me-2"></i>{{ __('Add Your First FAQ') }}
        </a>
    </div>
@endif
