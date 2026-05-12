<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;

class NotificationService
{
    public function notifyUser(?User $user, Notification $notification): void
    {
        if (! $user) {
            return;
        }

        $user->notify($notification);
    }

    /**
     * @return Collection<int, User>
     */
    public function adminUsers(): Collection
    {
        try {
            return User::role('admin')->get();
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            // Role doesn't exist, return empty collection
            return new Collection;
        }
    }

    public function notifyAdmins(Notification $notification): void
    {
        $this->adminUsers()->each(fn (User $admin) => $admin->notify($notification));
    }

    /**
     * @return Collection<int, User>
     */
    public function vendorUsers(int $vendorId): Collection
    {
        $vendor = Vendor::query()->with(['owner', 'users'])->find($vendorId);

        if (! $vendor) {
            return new Collection;
        }

        $users = new Collection;

        if ($vendor->owner) {
            $users->push($vendor->owner);
        }

        $vendorActiveUsers = $vendor->users()->wherePivot('is_active', true)->get();
        $users = $users->merge($vendorActiveUsers);

        return User::whereIn('id', $users->unique('id')->pluck('id'))->get();
    }

    public function notifyVendorUsers(int $vendorId, Notification $notification): void
    {
        $this->vendorUsers($vendorId)->each(fn (User $user) => $user->notify($notification));
    }
}
