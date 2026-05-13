<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingLocationController extends Controller
{
    public function states(Request $request): JsonResponse
    {
        /** @var Country $country */
        $country = $request->attributes->get('resolved_country');

        $states = State::query()
            ->where('country_id', (int) $country->id)
            ->active()
            ->ordered()
            ->get(['id', 'country_id', 'name', 'code']);

        return response()->json([
            'success' => true,
            'data' => $states,
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        /** @var Country $country */
        $country = $request->attributes->get('resolved_country');

        $validated = $request->validate([
            'state_id' => ['required', 'integer'],
        ]);

        $stateId = (int) $validated['state_id'];

        $state = State::query()
            ->where('id', $stateId)
            ->where('country_id', (int) $country->id)
            ->first();

        if (! $state) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid state for selected country.'),
                'errors' => [
                    'state_id' => [__('Invalid state for selected country.')],
                ],
            ], 422);
        }

        $cities = City::query()
            ->where('country_id', (int) $country->id)
            ->where('state_id', $stateId)
            ->active()
            ->ordered()
            ->get(['id', 'country_id', 'state_id', 'name', 'shipping_cost']);

        return response()->json([
            'success' => true,
            'data' => $cities,
        ]);
    }

    public function city(Request $request, int $city): JsonResponse
    {
        /** @var Country $country */
        $country = $request->attributes->get('resolved_country');

        $record = City::query()
            ->where('id', $city)
            ->where('country_id', (int) $country->id)
            ->active()
            ->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => __('City not found for selected country.'),
                'errors' => [
                    'city_id' => [__('City not found for selected country.')],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $record->id,
                'country_id' => $record->country_id,
                'state_id' => $record->state_id,
                'name' => $record->name,
                'shipping_cost' => (float) $record->shipping_cost,
            ],
        ]);
    }
}
