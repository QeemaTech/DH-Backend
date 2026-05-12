<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DigitalProductResource;
use App\Services\DigitalProductService;
use Illuminate\Http\Request;

class DigitalProductController extends Controller
{
    public function __construct(protected DigitalProductService $service) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);

        $countryId = $request->get('country_id');
        if (($countryId === null || $countryId === '') && $request->user()) {
            $countryId = $request->user()->country_id;
        }

        $filters = [
            'search' => $request->get('search', ''),
            'company_name' => $request->get('company_name', ''),
            'merchant_id' => $request->get('merchant_id', ''),
            'category_id' => $request->get('category_id', ''),
            'sub_category_id' => $request->get('sub_category_id', ''),
            'sort' => $request->get('sort', ''),
            'status' => 'active',
            'available' => 1,
            'country_id' => $countryId,
        ];

        $digitalProducts = $this->service->getPaginatedDigitalProducts($perPage, $filters);

        return DigitalProductResource::collection($digitalProducts);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $digitalProduct = $this->service->getDigitalProductById((int) $id);
        if (! $digitalProduct) {
            abort(404);
        }

        if (! $digitalProduct->is_active || ! $digitalProduct->is_available) {
            abort(404);
        }

        $countryId = $request->get('country_id');
        if (($countryId === null || $countryId === '') && $request->user()) {
            $countryId = $request->user()->country_id;
        }

        if (! $digitalProduct->isVisibleInCountry($countryId !== null && $countryId !== '' ? (int) $countryId : null)) {
            abort(404);
        }

        return new DigitalProductResource($digitalProduct);
    }
}
