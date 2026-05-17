<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Countries\CreateRequest;
use App\Http\Requests\Admin\Countries\UpdateRequest;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        $flagPath = null;
        if ($request->hasFile('flag')) {
            $flagPath = $request->file('flag')->store('countries/flags', 'public');
        }

        Country::query()->create([
            'code' => strtoupper((string) $data['code']),
            'name' => [
                'en' => (string) $data['name_en'],
                'ar' => (string) $data['name_ar'],
            ],
            'dial_code' => $data['dial_code'] ?? null,
            'flag' => $flagPath,
            'verification_channels' => $data['verification_channels'],
            'verification_channel' => (string) ($data['verification_channels'][0] ?? 'sms'),
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
        $flagPath = $shipping_country->getRawOriginal('flag');
        if ($request->hasFile('flag')) {
            if ($flagPath && Storage::disk('public')->exists($flagPath)) {
                Storage::disk('public')->delete($flagPath);
            }
            $flagPath = $request->file('flag')->store('countries/flags', 'public');
        }

        $shipping_country->update([
            'code' => strtoupper((string) $data['code']),
            'name' => [
                'en' => (string) $data['name_en'],
                'ar' => (string) $data['name_ar'],
            ],
            'dial_code' => $data['dial_code'] ?? null,
            'flag' => $flagPath,
            'verification_channels' => $data['verification_channels'],
            'verification_channel' => (string) ($data['verification_channels'][0] ?? 'sms'),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()
            ->route('admin.shipping-countries.index')
            ->with('success', __('Country updated successfully.'));
    }

    public function destroy(Country $shipping_country): RedirectResponse
    {
        $flagPath = $shipping_country->getRawOriginal('flag');
        if ($flagPath && Storage::disk('public')->exists($flagPath)) {
            Storage::disk('public')->delete($flagPath);
        }

        $shipping_country->delete();

        return redirect()
            ->route('admin.shipping-countries.index')
            ->with('success', __('Country deleted successfully.'));
    }
}
