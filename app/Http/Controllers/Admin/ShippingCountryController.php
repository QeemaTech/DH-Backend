<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Countries\CreateRequest;
use App\Http\Requests\Admin\Countries\UpdateRequest;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingCountryController extends Controller
{
    public function index(Request $request): View
    {
        $query = Country::query();

        if ($request->filled('search')) {
            $search = (string) $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('dial_code', 'like', "%{$search}%")
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) like ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) like ?", ["%{$search}%"]);
            });
        }

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $countries = $query->ordered()->paginate(25)->withQueryString();

        return view('admin.shipping.countries.index', compact('countries'));
    }

    public function create(): View
    {
        return view('admin.shipping.countries.create');
    }

    public function store(CreateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        Country::query()->create([
            'code' => strtoupper((string) $data['code']),
            'name' => [
                'en' => (string) $data['name_en'],
                'ar' => (string) $data['name_ar'],
            ],
            'dial_code' => $data['dial_code'] ?? null,
            'verification_channel' => $data['verification_channel'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.shipping-countries.index')
            ->with('success', __('Country created successfully.'));
    }

    public function edit(Country $shipping_country): View
    {
        $country = $shipping_country;

        return view('admin.shipping.countries.edit', compact('country'));
    }

    public function update(UpdateRequest $request, Country $shipping_country): RedirectResponse
    {
        $data = $request->validated();
        $shipping_country->update([
            'code' => strtoupper((string) $data['code']),
            'name' => [
                'en' => (string) $data['name_en'],
                'ar' => (string) $data['name_ar'],
            ],
            'dial_code' => $data['dial_code'] ?? null,
            'verification_channel' => $data['verification_channel'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.shipping-countries.index')
            ->with('success', __('Country updated successfully.'));
    }

    public function destroy(Country $shipping_country): RedirectResponse
    {
        $shipping_country->delete();

        return redirect()
            ->route('admin.shipping-countries.index')
            ->with('success', __('Country deleted successfully.'));
    }
}

