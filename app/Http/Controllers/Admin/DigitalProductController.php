<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DigitalCategory;
use App\Models\DigitalMerchant;
use App\Models\DigitalProduct;
use App\Models\DigitalSubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DigitalProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = DigitalProduct::query()->with('merchant');

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('product_id', 'like', "%{$search}%")
                    ->orWhereRaw("JSON_EXTRACT(name, '$.en') LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_EXTRACT(name, '$.ar') LIKE ?", ["%{$search}%"]);
            });
        }

        if ($request->filled('company_name')) {
            $query->where('company_name', (string) $request->get('company_name'));
        }

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', (int) $request->get('merchant_id'));
        }

        if ($request->filled('available')) {
            $available = $request->get('available') === '1';
            $query->where('is_available', $available);
        }

        $digitalProducts = $query->latest('id')->paginate(20);
        $companies = DigitalProduct::query()->select('company_name')->distinct()->pluck('company_name');
        $merchants = DigitalMerchant::query()->orderBy('company_name')->orderBy('id')->get(['id', 'company_name', 'name']);

        return view('admin.digital-products.index', compact('digitalProducts', 'companies', 'merchants'));
    }

    public function show(DigitalProduct $digitalProduct): View
    {
        $digitalProduct->load(['merchant', 'category', 'subCategory', 'lastUpdatedBy', 'countries']);

        $digitalCategories = DigitalCategory::active()->orderBy('id')->get();
        $digitalSubCategories = DigitalSubCategory::active()->orderBy('id')->get();
        $countries = Country::active()->ordered()->get(['id', 'code', 'name']);
        $subCategoriesByCategory = $digitalSubCategories
            ->groupBy('digital_category_id')
            ->map(fn ($group) => $group->map(fn (DigitalSubCategory $sub) => [
                'id' => $sub->id,
                'name' => $sub->getTranslation('name', app()->getLocale()),
            ])->values())
            ->toArray();

        return view('admin.digital-products.show', compact('digitalProduct', 'digitalCategories', 'digitalSubCategories', 'subCategoriesByCategory', 'countries'));
    }

    public function assignCategory(Request $request, DigitalProduct $digitalProduct): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:digital_categories,id'],
            'sub_category_id' => ['nullable', 'integer', 'exists:digital_sub_categories,id'],
        ]);

        $digitalProduct->update([
            'category_id' => $data['category_id'] ?? null,
            'sub_category_id' => $data['sub_category_id'] ?? null,
        ]);

        return redirect()
            ->route('admin.digital-products.show', $digitalProduct)
            ->with('success', __('Digital product category updated successfully.'));
    }

    public function syncCountries(Request $request, DigitalProduct $digitalProduct): RedirectResponse
    {
        $data = $request->validate([
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['integer', 'exists:countries,id'],
        ]);

        $digitalProduct->countries()->sync($data['country_ids'] ?? []);

        return redirect()
            ->route('admin.digital-products.show', $digitalProduct)
            ->with('success', __('Digital product countries updated successfully.'));
    }

    /**
     * Toggle digital product active status (controls visibility to users).
     */
    public function toggleActive(DigitalProduct $digitalProduct): JsonResponse
    {
        try {
            $digitalProduct->update([
                'is_active' => ! (bool) $digitalProduct->is_active,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Digital product status updated successfully.'),
                'is_active' => (bool) $digitalProduct->is_active,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to update digital product status: :error', ['error' => $e->getMessage()]),
            ], 422);
        }
    }
}
