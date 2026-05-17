<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalProductPurchaseLimit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DigitalProductPurchaseLimitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'verification_level' => ['nullable', Rule::in([
                DigitalProductPurchaseLimit::VERIFICATION_CONTACT,
                DigitalProductPurchaseLimit::VERIFICATION_FULLY,
            ])],
            'period_type' => ['nullable', Rule::in([
                DigitalProductPurchaseLimit::PERIOD_DAILY,
                DigitalProductPurchaseLimit::PERIOD_WEEKLY,
                DigitalProductPurchaseLimit::PERIOD_MONTHLY,
            ])],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $limits = DigitalProductPurchaseLimit::query()
            ->when(isset($validated['verification_level']), function ($query) use ($validated) {
                $query->where('verification_level', $validated['verification_level']);
            })
            ->when(isset($validated['period_type']), function ($query) use ($validated) {
                $query->where('period_type', $validated['period_type']);
            })
            ->when(isset($validated['is_active']), function ($query) use ($validated) {
                $query->where('is_active', (bool) $validated['is_active']);
            }, function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('verification_level')
            ->orderByRaw("FIELD(period_type, 'daily', 'weekly', 'monthly')")
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $limits->items(),
            'meta' => [
                'current_page' => $limits->currentPage(),
                'last_page' => $limits->lastPage(),
                'per_page' => $limits->perPage(),
                'total' => $limits->total(),
            ],
        ]);
    }
}
