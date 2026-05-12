<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DigitalCategories\CreateRequest;
use App\Http\Requests\Admin\DigitalCategories\UpdateRequest;
use App\Models\DigitalCategory;
use App\Services\DigitalCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DigitalCategoryController extends Controller
{
    public function __construct(protected DigitalCategoryService $service) {}

    /**
     * Display a listing of digital categories.
     */
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 15);
        $filters = [
            'search' => (string) $request->get('search', ''),
            'status' => (string) $request->get('status', ''),
            'sort' => (string) $request->get('sort', 'latest'),
        ];

        $digitalCategories = $this->service->getPaginatedDigitalCategories($perPage, $filters);

        return view('admin.digital-categories.index', compact('digitalCategories'));
    }

    /**
     * Show the form for creating a new digital category.
     */
    public function create(): View
    {
        return view('admin.digital-categories.create');
    }

    /**
     * Store a newly created digital category.
     */
    public function store(CreateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['last_update_by'] = Auth::id();

        $this->service->createDigitalCategory($data);

        return redirect()->route('admin.digital-categories.index')
            ->with('success', __('Digital category created successfully.'));
    }

    /**
     * Display the specified digital category.
     */
    public function show(DigitalCategory $digitalCategory): View
    {
        $digitalCategory->load('lastUpdatedBy');

        return view('admin.digital-categories.show', compact('digitalCategory'));
    }

    /**
     * Show the form for editing the specified digital category.
     */
    public function edit(DigitalCategory $digitalCategory): View
    {
        return view('admin.digital-categories.edit', compact('digitalCategory'));
    }

    /**
     * Update the specified digital category.
     */
    public function update(UpdateRequest $request, DigitalCategory $digitalCategory): RedirectResponse
    {
        $data = $request->validated();
        $data['last_update_by'] = Auth::id();

        $this->service->updateDigitalCategory($digitalCategory, $data);

        return redirect()->route('admin.digital-categories.index')
            ->with('success', __('Digital category updated successfully.'));
    }

    /**
     * Remove the specified digital category.
     */
    public function destroy(DigitalCategory $digitalCategory): RedirectResponse
    {
        $this->service->deleteDigitalCategory($digitalCategory);

        return redirect()->route('admin.digital-categories.index')
            ->with('success', __('Digital category deleted successfully.'));
    }
}
