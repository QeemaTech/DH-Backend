<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DigitalProductPurchaseLimits\StoreRequest;
use App\Http\Requests\Admin\DigitalProductPurchaseLimits\UpdateRequest;
use App\Models\DigitalProductPurchaseLimit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DigitalProductPurchaseLimitController extends Controller
{
    public function index(Request $request): View
    {
        $query = DigitalProductPurchaseLimit::query();

        if ($request->filled('verification_level')) {
            $query->where('verification_level', (string) $request->get('verification_level'));
        }

        if ($request->filled('period_type')) {
            $query->where('period_type', (string) $request->get('period_type'));
        }

        $purchaseLimits = $query->latest('id')->paginate(15);

        return view('admin.digital-product-purchase-limits.index', compact('purchaseLimits'));
    }

    public function create(): View
    {
        return view('admin.digital-product-purchase-limits.create');
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $this->ensureNoDuplicateActiveLimit($data['verification_level'], $data['period_type']);

        DigitalProductPurchaseLimit::create($data);

        return redirect()->route('admin.digital-product-purchase-limits.index')
            ->with('success', __('Digital product purchase limit created successfully.'));
    }

    public function edit(DigitalProductPurchaseLimit $digitalProductPurchaseLimit): View
    {
        return view('admin.digital-product-purchase-limits.edit', compact('digitalProductPurchaseLimit'));
    }

    public function update(UpdateRequest $request, DigitalProductPurchaseLimit $digitalProductPurchaseLimit): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $this->ensureNoDuplicateActiveLimit(
            $data['verification_level'],
            $data['period_type'],
            $digitalProductPurchaseLimit->id
        );

        $digitalProductPurchaseLimit->update($data);

        return redirect()->route('admin.digital-product-purchase-limits.index')
            ->with('success', __('Digital product purchase limit updated successfully.'));
    }

    public function destroy(DigitalProductPurchaseLimit $digitalProductPurchaseLimit): RedirectResponse
    {
        $digitalProductPurchaseLimit->delete();

        return redirect()->route('admin.digital-product-purchase-limits.index')
            ->with('success', __('Digital product purchase limit deleted successfully.'));
    }

    public function toggleActive(DigitalProductPurchaseLimit $digitalProductPurchaseLimit): RedirectResponse
    {
        $newStatus = ! (bool) $digitalProductPurchaseLimit->is_active;

        if ($newStatus) {
            $this->ensureNoDuplicateActiveLimit(
                $digitalProductPurchaseLimit->verification_level,
                $digitalProductPurchaseLimit->period_type,
                $digitalProductPurchaseLimit->id
            );
        }

        $digitalProductPurchaseLimit->update(['is_active' => $newStatus]);

        return redirect()->route('admin.digital-product-purchase-limits.index')
            ->with('success', __('Digital product purchase limit status updated successfully.'));
    }

    protected function ensureNoDuplicateActiveLimit(string $verificationLevel, string $periodType, ?int $ignoreId = null): void
    {
        $exists = DigitalProductPurchaseLimit::query()
            ->where('verification_level', $verificationLevel)
            ->where('period_type', $periodType)
            ->where('is_active', true)
            ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'period_type' => [__('An active limit already exists for this verification level and period.')],
            ]);
        }
    }
}
