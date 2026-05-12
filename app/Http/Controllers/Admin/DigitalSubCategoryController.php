<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DigitalSubCategories\CreateRequest;
use App\Http\Requests\Admin\DigitalSubCategories\UpdateRequest;
use App\Models\DigitalSubCategory;
use App\Services\DigitalCategoryService;
use App\Services\DigitalSubCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DigitalSubCategoryController extends Controller
{
    public function __construct(
        protected DigitalSubCategoryService $service,
        protected DigitalCategoryService $digitalCategoryService
    ) {}

    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 15);
        $filters = [
            'search' => (string) $request->get('search', ''),
            'status' => (string) $request->get('status', ''),
            'sort' => (string) $request->get('sort', 'latest'),
            'digital_category_id' => (string) $request->get('digital_category_id', ''),
        ];

        $digitalSubCategories = $this->service->getPaginatedDigitalSubCategories($perPage, $filters);
        $digitalCategories = $this->digitalCategoryService->getAllDigitalCategories();

        return view('admin.digital-sub-categories.index', compact('digitalSubCategories', 'digitalCategories'));
    }

    public function create(): View
    {
        $digitalCategories = $this->digitalCategoryService->getAllDigitalCategories();

        return view('admin.digital-sub-categories.create', compact('digitalCategories'));
    }

    public function store(CreateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['last_update_by'] = Auth::id();

        $this->service->createDigitalSubCategory($data);

        return redirect()->route('admin.digital-sub-categories.index')
            ->with('success', __('Digital sub category created successfully.'));
    }

    public function show(DigitalSubCategory $digitalSubCategory): View
    {
        $digitalSubCategory->load(['digitalCategory', 'lastUpdatedBy']);

        return view('admin.digital-sub-categories.show', compact('digitalSubCategory'));
    }

    public function edit(DigitalSubCategory $digitalSubCategory): View
    {
        $digitalSubCategory->load(['digitalCategory', 'lastUpdatedBy']);
        $digitalCategories = $this->digitalCategoryService->getAllDigitalCategories();

        return view('admin.digital-sub-categories.edit', compact('digitalSubCategory', 'digitalCategories'));
    }

    public function update(UpdateRequest $request, DigitalSubCategory $digitalSubCategory): RedirectResponse
    {
        $data = $request->validated();
        $data['last_update_by'] = Auth::id();

        $this->service->updateDigitalSubCategory($digitalSubCategory, $data);

        return redirect()->route('admin.digital-sub-categories.index')
            ->with('success', __('Digital sub category updated successfully.'));
    }

    public function destroy(DigitalSubCategory $digitalSubCategory): RedirectResponse
    {
        $this->service->deleteDigitalSubCategory($digitalSubCategory);

        return redirect()->route('admin.digital-sub-categories.index')
            ->with('success', __('Digital sub category deleted successfully.'));
    }
}
