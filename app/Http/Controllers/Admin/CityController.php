<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Cities\CreateRequest;
use App\Http\Requests\Admin\Cities\UpdateRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CityController extends Controller
{
    public function index(Request $request): View
    {
        $query = City::query()->with(['country', 'state.country']);

        if ($request->filled('country_id')) {
            $query->where('country_id', (int) $request->get('country_id'));
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', (int) $request->get('state_id'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) like ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) like ?", ["%{$search}%"]);
            });
        }

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $cities = $query->ordered()->paginate(25)->withQueryString();
        $countries = Country::query()->ordered()->get();
        $states = State::query()
            ->when($request->filled('country_id'), fn ($q) => $q->where('country_id', (int) $request->get('country_id')))
            ->ordered()
            ->get();

        return view('admin.cities.index', compact('cities', 'countries', 'states'));
    }

    public function create(): View
    {
        $countries = Country::query()->ordered()->get();
        $states = State::query()->with('country')->ordered()->get();

        return view('admin.cities.create', compact('countries', 'states'));
    }

    public function store(CreateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        City::query()->create([
            'country_id' => (int) $data['country_id'],
            'state_id' => (int) $data['state_id'],
            'name' => [
                'en' => (string) $data['name_en'],
                'ar' => (string) $data['name_ar'],
            ],
            'shipping_cost' => (float) $data['shipping_cost'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.cities.index')
            ->with('success', __('City created successfully.'));
    }

    public function edit(City $city): View
    {
        $countries = Country::query()->ordered()->get();
        $states = State::query()->with('country')->ordered()->get();

        return view('admin.cities.edit', compact('city', 'countries', 'states'));
    }

    public function update(UpdateRequest $request, City $city): RedirectResponse
    {
        $data = $request->validated();

        $city->update([
            'country_id' => (int) $data['country_id'],
            'state_id' => (int) $data['state_id'],
            'name' => [
                'en' => (string) $data['name_en'],
                'ar' => (string) $data['name_ar'],
            ],
            'shipping_cost' => (float) $data['shipping_cost'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.cities.index')
            ->with('success', __('City updated successfully.'));
    }

    public function destroy(City $city): RedirectResponse
    {
        $city->delete();

        return redirect()->route('admin.cities.index')
            ->with('success', __('City deleted successfully.'));
    }
}
