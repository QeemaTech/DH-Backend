<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirect based on user role
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        } elseif ($user->hasRole('vendor') || $user->hasRole('vendor_employee')) {
            // Check if user is a branch user
            $vendorUser = \App\Models\VendorUser::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if ($vendorUser && $vendorUser->user_type === 'branch') {
                // Redirect branch users to branch dashboard
                return redirect()->intended(route('vendor.branch.dashboard', absolute: false));
            }

            // Check if user has view-dashboard permission (vendor has all permissions, vendor_employee needs specific permission)
            if (vendorCan('view-dashboard')) {
                return redirect()->intended(route('vendor.dashboard', absolute: false));
            }
        }

        // Default fallback
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
