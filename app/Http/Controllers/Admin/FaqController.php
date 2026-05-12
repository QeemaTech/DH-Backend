<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faqs\CreateRequest;
use App\Http\Requests\Admin\Faqs\UpdateRequest;
use App\Services\FaqService;

class FaqController extends Controller
{
    protected FaqService $service;

    public function __construct(FaqService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $faqs = $this->service->getAllFaqs();

        return view('admin.faqs.index', compact('faqs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.faqs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request)
    {
        $this->service->createFaq($request->validated());

        return redirect()->route('admin.faqs.index')->with('success', __('FAQ created successfully.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $faq = $this->service->getFaqById($id);

        if (! $faq) {
            return redirect()->route('admin.faqs.index')->with('error', __('FAQ not found.'));
        }

        return view('admin.faqs.show', compact('faq'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $faq = $this->service->getFaqById($id);

        if (! $faq) {
            return redirect()->route('admin.faqs.index')->with('error', __('FAQ not found.'));
        }

        return view('admin.faqs.edit', compact('faq'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, string $id)
    {
        $faq = $this->service->getFaqById($id);
        if (! $faq) {
            return redirect()->route('admin.faqs.index')->with('error', __('FAQ not found.'));
        }

        $this->service->updateFaq($id, $request->validated());

        return redirect()->route('admin.faqs.index')->with('success', __('FAQ updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->service->deleteFaq($id);

        return redirect()->route('admin.faqs.index')->with('success', __('FAQ deleted successfully.'));
    }
}
