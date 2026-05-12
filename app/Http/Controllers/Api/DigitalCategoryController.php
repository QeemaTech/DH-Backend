<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DigitalCategoryResource;
use App\Services\DigitalCategoryService;
use Illuminate\Http\Request;

class DigitalCategoryController extends Controller
{
    public function __construct(protected DigitalCategoryService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $digitalCategories = $this->service->getPaginatedDigitalCategories($perPage);

        return DigitalCategoryResource::collection($digitalCategories);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $digitalCategory = $this->service->getDigitalCategoryById((int) $id);
        if (! $digitalCategory) {
            abort(404);
        }
        return new DigitalCategoryResource($digitalCategory);
    }
}
