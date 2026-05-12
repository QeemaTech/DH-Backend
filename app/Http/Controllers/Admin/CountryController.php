<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Countries\UpdateCountryRequest;
use App\Models\Country;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(): View
    {
        $countries = Country::query()->ordered()->paginate(50);

        return view('admin.countries.index', compact('countries'));
    }

    public function update(UpdateCountryRequest $request, Country $country): RedirectResponse
    {
        $country->verification_channel = $request->validated('verification_channel');
        if ($request->has('is_active')) {
            $country->is_active = $request->boolean('is_active');
        }
        $country->save();

        return redirect()
            ->route('admin.countries.index')
            ->with('success', __('Country updated successfully.'));
    }
}
