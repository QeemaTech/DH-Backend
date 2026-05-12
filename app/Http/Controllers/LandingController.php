<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\VendorUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user && $user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            }

            if ($user && ($user->hasRole('vendor') || $user->hasRole('vendor_employee'))) {
                $vendorUser = VendorUser::query()
                    ->where('user_id', '=', $user->id, 'and')
                    ->where('is_active', '=', true, 'and')
                    ->first();

                if ($vendorUser && $vendorUser->user_type === 'branch') {
                    return redirect()->route('vendor.branch.dashboard');
                }

                if ($user->hasPermissionTo('view-dashboard')) {
                    return redirect()->route('vendor.dashboard');
                }
            }
        }

        return view('landing.index');
    }

    public function features(): View
    {
        return view('landing.features');
    }

    public function pricing(): View
    {
        $plans = Plan::query()
            ->active()
            ->orderByDesc('is_featured')
            ->orderBy('price')
            ->get();

        return view('landing.pricing', [
            'plans' => $plans,
        ]);
    }
}
