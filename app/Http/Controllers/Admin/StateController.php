<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\States\CreateRequest;
use App\Http\Requests\Admin\States\UpdateRequest;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StateController extends Controller
{
    public function index(Request $request): View
    {
        $query = State::query()->with('country');

        if ($request->filled('country_id')) {
            $query->where('country_id', (int) $request->get('country_id'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) like ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) like ?", ["%{$search}%"]);
            });
        }

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $states = $query->ordered()->paginate(25)->withQueryString();
        $countries = Country::query()->ordered()->get();

        return view('admin.states.index', compact('states', 'countries'));
    }

    public function create(): View
    {
        $countries = Country::query()->ordered()->get();

        return view('admin.states.create', compact('countries'));
    }

    public function store(CreateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        State::query()->create([
            'country_id' => (int) $data['country_id'],
            'name' => [
                'en' => (string) $data['name_en'],
                'ar' => (string) $data['name_ar'],
            ],
            'code' => isset($data['code']) ? strtoupper((string) $data['code']) : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.states.index')
            ->with('success', __('State created successfully.'));
    }

    public function edit(State $state): View
    {
        $countries = Country::query()->ordered()->get();

        return view('admin.states.edit', compact('state', 'countries'));
    }

    public function update(UpdateRequest $request, State $state): RedirectResponse
    {
        $data = $request->validated();

        $state->update([
            'country_id' => (int) $data['country_id'],
            'name' => [
                'en' => (string) $data['name_en'],
                'ar' => (string) $data['name_ar'],
            ],
            'code' => isset($data['code']) ? strtoupper((string) $data['code']) : null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);

        return redirect()->route('admin.states.index')
            ->with('success', __('State updated successfully.'));
    }

    public function destroy(State $state): RedirectResponse
    {
        $state->delete();

        return redirect()->route('admin.states.index')
            ->with('success', __('State deleted successfully.'));
    }
}

