<?php

if (! function_exists('setting')) {
    /**
     * Get a setting value by key
     *
     * This function retrieves settings from Redis cache first,
     * and falls back to database if not found in cache.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  Default value if setting doesn't exist
     * @return mixed The setting value or default
     */
    function setting(string $key, $default = null)
    {
        $cacheKey = "setting:{$key}";

        try {
            // Try to get from cache first
            $value = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($key, $default) {
                $setting = \App\Models\Setting::where('key', $key)->first();

                return $setting ? $setting->value : $default;
            });
        } catch (\Throwable) {
            return $default;
        }

        // If value is null and default is provided, return default
        return $value ?? $default;
    }
}

if (! function_exists('settings')) {
    /**
     * Get all settings as an associative array
     */
    function settings(): array
    {
        try {
            return \Illuminate\Support\Facades\Cache::remember('settings:all', 3600, function () {
                return \App\Models\Setting::pluck('value', 'key')->toArray();
            });
        } catch (\Throwable) {
            return [];
        }
    }
}

if (! function_exists('vendorCan')) {
    /**
     * Check if vendor user has permission
     * Vendor role has all permissions automatically
     *
     * @param  string  $permission  The permission name
     */
    function vendorCan(string $permission): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Admin has all permissions
        if ($user->hasRole('admin')) {
            return true;
        }

        // Vendor role has all permissions
        if ($user->hasRole('vendor')) {
            return true;
        }

        // Vendor employee needs specific permission
        if ($user->hasRole('vendor_employee')) {
            return $user->hasPermissionTo($permission);
        }

        return false;
    }
}

if (! function_exists('currentVendorUser')) {
    /**
     * Get the current vendor user record
     * Cached per request to avoid multiple queries
     * Uses View Composer cache if available to avoid duplicate queries
     */
    function currentVendorUser(): ?\App\Models\VendorUser
    {
        // Always use View Composer's getVendorData() which handles caching
        // This ensures we only query once per request, regardless of who calls first
        $composerData = \App\View\Composers\SidebarComposer::getVendorData();

        return $composerData['currentVendorUser'] ?? null;
    }
}

if (! function_exists('currentBranch')) {
    /**
     * Get the current branch for branch users
     * Uses cached vendor user to avoid duplicate queries
     */
    function currentBranch(): ?\App\Models\Branch
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $vendorUser = currentVendorUser();

        if ($vendorUser && $vendorUser->user_type === 'branch') {
            return $cached = $vendorUser->branch;
        }

        return $cached = null;
    }
}

if (! function_exists('canCreateProducts')) {
    /**
     * Check if user can create products
     * Only vendor owners or vendor users with user_type 'owner' can create products
     * Cached per request to avoid multiple queries
     */
    function canCreateProducts(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $user = auth()->user();

        if (! $user) {
            return $cached = false;
        }

        // Admin can create products
        if ($user->hasRole('admin')) {
            return $cached = true;
        }

        // Vendor owner (vendor->owner_id matches user id) can create products
        if ($user->hasRole('vendor') && $user->ownedVendor) {
            return $cached = true;
        }

        // Vendor user with user_type 'owner' can create products
        // Use cached vendor user to avoid duplicate queries
        $vendorUser = currentVendorUser();
        if ($vendorUser && $vendorUser->user_type === 'owner') {
            return $cached = true;
        }

        // Branch users cannot create products
        return $cached = false;
    }
}

if (! function_exists('vendorSetting')) {
    /**
     * Get a vendor setting value by key
     * Cached per request to avoid multiple queries
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  Default value if setting doesn't exist
     * @return mixed The setting value or default
     */
    function vendorSetting(string $key, $default = null)
    {
        static $cachedSettings = [];

        $vendor = auth()->user()?->vendor();

        if (! $vendor) {
            return $default;
        }

        // Check cache first
        if (isset($cachedSettings[$vendor->id][$key])) {
            return $cachedSettings[$vendor->id][$key];
        }

        // Initialize cache for this vendor if not exists
        if (! isset($cachedSettings[$vendor->id])) {
            $cachedSettings[$vendor->id] = [];
        }

        // Try to get from cache
        $cacheKey = "vendor_settings:{$vendor->id}";
        $settings = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($vendor) {
            return \App\Models\VendorSetting::where('vendor_id', $vendor->id)
                ->get()
                ->keyBy('key')
                ->map(function ($setting) {
                    return $setting->value;
                })
                ->toArray();
        });

        // Store in static cache
        $cachedSettings[$vendor->id] = $settings;

        return $cachedSettings[$vendor->id][$key] ?? $default;
    }
}
