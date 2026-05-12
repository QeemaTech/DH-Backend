<?php

namespace App\Services;

use App\Models\User;
use App\Models\VendorUser;
use App\Repositories\VendorUserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class VendorUserService
{
    protected VendorUserRepository $vendorUserRepository;

    public function __construct(VendorUserRepository $vendorUserRepository)
    {
        $this->vendorUserRepository = $vendorUserRepository;
    }

    /**
     * Get all vendor users for a vendor
     */
    public function getVendorUsersByVendor(int $vendorId): Collection
    {
        return $this->vendorUserRepository->getVendorUsersByVendor($vendorId);
    }

    /**
     * Get paginated vendor users for a vendor
     */
    public function getPaginatedVendorUsersByVendor(int $vendorId, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        return $this->vendorUserRepository->getPaginatedVendorUsersByVendor($vendorId, $perPage, $filters);
    }

    /**
     * Get vendor user by ID
     */
    public function getVendorUserById(int $id): ?VendorUser
    {
        return $this->vendorUserRepository->getVendorUserById($id);
    }

    /**
     * Create a new vendor user
     */
    public function createVendorUser(int $vendorId, array $data): VendorUser
    {
        DB::beginTransaction();
        try {
            // Check if user already exists for this vendor
            if (isset($data['user_id'])) {
                if ($this->vendorUserRepository->userExistsForVendor($vendorId, $data['user_id'])) {
                    throw new \Exception(__('User is already associated with this vendor.'));
                }
            } else {
                // Create new user if email/phone provided
                $userData = [
                    'name' => $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'password' => Hash::make($data['password']),
                    'is_active' => true,
                    'is_verified' => false,
                    'role' => 'vendor',
                ];

                $user = User::create($userData);

                // Assign vendor_employee role to vendor user (not vendor owner)
                $user->assignRole('vendor_employee');

                $data['user_id'] = $user->id;
            }

            $vendorUserData = [
                'vendor_id' => $vendorId,
                'user_id' => $data['user_id'],
                'is_active' => $data['is_active'] ?? true,
                'user_type' => $data['user_type'] ?? 'owner',
                'branch_id' => $data['branch_id'] ?? null,
            ];

            $vendorUser = $this->vendorUserRepository->create($vendorUserData);

            // Assign permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $user = $vendorUser->user;
                $permissions = Permission::whereIn('name', $data['permissions'])
                    ->where('guard_name', 'web')
                    ->get();
                $user->syncPermissions($permissions);
            }

            DB::commit();

            return $vendorUser->load(['user', 'vendor']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a vendor user
     */
    public function updateVendorUser(VendorUser $vendorUser, array $data): VendorUser
    {
        DB::beginTransaction();
        try {
            // Update user information if provided
            if (isset($data['name']) || isset($data['email']) || isset($data['phone'])) {
                $userData = [];
                if (isset($data['name'])) {
                    $userData['name'] = $data['name'];
                }
                if (isset($data['email'])) {
                    $userData['email'] = $data['email'];
                }
                if (isset($data['phone'])) {
                    $userData['phone'] = $data['phone'];
                }
                if (isset($data['password']) && ! empty($data['password'])) {
                    $userData['password'] = Hash::make($data['password']);
                }

                $vendorUser->user->update($userData);
            }

            // Update vendor user status
            $vendorUserData = [];
            if (isset($data['is_active'])) {
                $vendorUserData['is_active'] = $data['is_active'];
            }
            if (isset($data['user_type'])) {
                $vendorUserData['user_type'] = $data['user_type'];
            }
            if (isset($data['branch_id'])) {
                $vendorUserData['branch_id'] = $data['branch_id'];
            }

            if (! empty($vendorUserData)) {
                $this->vendorUserRepository->update($vendorUser, $vendorUserData);
            }

            // Update permissions if provided
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                $permissions = Permission::whereIn('name', $data['permissions'])
                    ->where('guard_name', 'web')
                    ->get();
                $vendorUser->user->syncPermissions($permissions);
            }

            DB::commit();

            return $vendorUser->fresh(['user', 'vendor']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a vendor user
     */
    public function deleteVendorUser(VendorUser $vendorUser): bool
    {
        return $this->vendorUserRepository->delete($vendorUser);
    }

    /**
     * Toggle active status
     */
    public function toggleActive(VendorUser $vendorUser): VendorUser
    {
        return $this->vendorUserRepository->toggleActive($vendorUser);
    }
}
