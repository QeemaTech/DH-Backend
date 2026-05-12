<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\VendorUsers\CreateRequest;
use App\Http\Requests\Vendor\VendorUsers\UpdateRequest;
use App\Models\Vendor;
use App\Models\VendorUser;
use App\Services\BranchService;
use App\Services\VendorUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class VendorUserController extends Controller
{
    protected VendorUserService $vendorUserService;

    protected BranchService $branchService;

    public function __construct(VendorUserService $vendorUserService, BranchService $branchService)
    {
        $this->vendorUserService = $vendorUserService;
        $this->branchService = $branchService;
    }

    /**
     * Get the vendor for the authenticated user
     */
    protected function getVendor(): ?Vendor
    {
        $user = Auth::user();

        return Vendor::where('owner_id', $user->id)->first();
    }

    /**
     * Display a listing of the vendor's users.
     */
    public function index(): View
    {
        $vendor = $this->getVendor();

        if (! $vendor) {
            abort(404, __('Vendor account not found.'));
        }

        $vendorUsers = $this->vendorUserService->getVendorUsersByVendor($vendor->id);

        return view('vendor.vendor-users.index', compact('vendorUsers', 'vendor'));
    }

    /**
     * Show the form for creating a new vendor user.
     */
    public function create(): View
    {
        $vendor = $this->getVendor();

        if (! $vendor) {
            abort(404, __('Vendor account not found.'));
        }

        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                // Group permissions by category
                $name = $permission->name;
                if (str_starts_with($name, 'manage-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'view-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'create-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'edit-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'delete-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'subscribe-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'cancel-')) {
                    return explode('-', $name)[1] ?? 'other';
                }

                return 'other';
            });

        // Get branches for this vendor
        $branches = $this->branchService->getBranchesByVendor($vendor->id);

        return view('vendor.vendor-users.create', compact('vendor', 'permissions', 'branches'));
    }

    /**
     * Store a newly created vendor user in storage.
     */
    public function store(CreateRequest $request): RedirectResponse
    {
        try {
            $vendor = $this->getVendor();

            if (! $vendor) {
                return redirect()->back()
                    ->with('error', __('Vendor account not found. Please contact administrator.'));
            }

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password,
                'is_active' => $request->boolean('is_active', true),
                'permissions' => $request->input('permissions', []),
                'user_type' => $request->input('user_type', 'owner'),
                'branch_id' => $request->input('user_type') === 'branch' ? $request->input('branch_id') : null,
            ];

            $this->vendorUserService->createVendorUser($vendor->id, $data);

            return redirect()->route('vendor.vendor-users.index')
                ->with('success', __('Vendor user created successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Failed to create vendor user: :error', ['error' => $e->getMessage()]))
                ->withInput();
        }
    }

    /**
     * Display the specified vendor user.
     */
    public function show(VendorUser $vendorUser): View
    {
        $vendor = $this->getVendor();

        if (! $vendor) {
            abort(404, __('Vendor account not found.'));
        }

        // Ensure the vendor user belongs to this vendor
        if ($vendorUser->vendor_id !== $vendor->id) {
            abort(403, __('You do not have permission to view this user.'));
        }

        return view('vendor.vendor-users.show', compact('vendorUser', 'vendor'));
    }

    /**
     * Show the form for editing the specified vendor user.
     */
    public function edit(VendorUser $vendorUser): View
    {
        $vendor = $this->getVendor();

        if (! $vendor) {
            abort(404, __('Vendor account not found.'));
        }

        // Ensure vendor user exists
        if (! $vendorUser) {
            abort(404, __('Vendor user not found.'));
        }

        // Ensure the vendor user belongs to this vendor
        if ($vendorUser->vendor_id !== $vendor->id) {
            abort(403, __('You do not have permission to edit this user.'));
        }

        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
                // Group permissions by category
                $name = $permission->name;
                if (str_starts_with($name, 'manage-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'view-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'create-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'edit-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'delete-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'subscribe-')) {
                    return explode('-', $name)[1] ?? 'other';
                }
                if (str_starts_with($name, 'cancel-')) {
                    return explode('-', $name)[1] ?? 'other';
                }

                return 'other';
            });

        $userPermissions = $vendorUser->user->permissions->pluck('name')->toArray();

        // Get branches for this vendor
        $branches = $this->branchService->getBranchesByVendor($vendor->id);

        return view('vendor.vendor-users.edit', compact('vendorUser', 'vendor', 'permissions', 'userPermissions', 'branches'));
    }

    /**
     * Update the specified vendor user in storage.
     */
    public function update(UpdateRequest $request, VendorUser $vendorUser): RedirectResponse
    {
        try {
            $vendor = $this->getVendor();

            if (! $vendor) {
                return redirect()->back()
                    ->with('error', __('Vendor account not found. Please contact administrator.'));
            }

            // Ensure the vendor user belongs to this vendor
            if ($vendorUser->vendor_id !== $vendor->id) {
                abort(403, __('You do not have permission to update this user.'));
            }

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password,
                'is_active' => $request->boolean('is_active'),
                'permissions' => $request->input('permissions', []),
                'user_type' => $request->input('user_type', 'owner'),
                'branch_id' => $request->input('user_type') === 'branch' ? $request->input('branch_id') : null,
            ];

            $this->vendorUserService->updateVendorUser($vendorUser, $data);

            return redirect()->route('vendor.vendor-users.index')
                ->with('success', __('Vendor user updated successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Failed to update vendor user: :error', ['error' => $e->getMessage()]))
                ->withInput();
        }
    }

    /**
     * Remove the specified vendor user from storage.
     */
    public function destroy(VendorUser $vendorUser): RedirectResponse
    {
        try {
            $vendor = $this->getVendor();

            if (! $vendor) {
                return redirect()->back()
                    ->with('error', __('Vendor account not found. Please contact administrator.'));
            }

            // Ensure the vendor user belongs to this vendor
            if ($vendorUser->vendor_id !== $vendor->id) {
                abort(403, __('You do not have permission to delete this user.'));
            }

            $this->vendorUserService->deleteVendorUser($vendorUser);

            return redirect()->route('vendor.vendor-users.index')
                ->with('success', __('Vendor user deleted successfully.'));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', __('Failed to delete vendor user: :error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Toggle vendor user active status.
     */
    public function toggleActive(VendorUser $vendorUser): JsonResponse
    {
        try {
            $vendor = $this->getVendor();

            if (! $vendor) {
                return response()->json([
                    'success' => false,
                    'message' => __('Vendor account not found.'),
                ], 404);
            }

            // Ensure the vendor user belongs to this vendor
            if ($vendorUser->vendor_id !== $vendor->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('You do not have permission to update this user.'),
                ], 403);
            }

            $vendorUser = $this->vendorUserService->toggleActive($vendorUser);

            return response()->json([
                'success' => true,
                'message' => __('User status updated successfully.'),
                'is_active' => $vendorUser->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Failed to update user status: :error', ['error' => $e->getMessage()]),
            ], 500);
        }
    }
}
