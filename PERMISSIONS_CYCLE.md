# Vendor & Vendor Employee Permissions Cycle

## Overview / نظرة عامة

This document describes the complete permissions cycle for Vendor and Vendor Employee roles in the multi-vendor e-commerce system.

## Roles / الأدوار

### 1. Vendor Role (المالك)
- **Has all permissions automatically** - لا يحتاج صلاحيات محددة
- Created when vendor registers or admin creates vendor
- Can perform all actions in vendor area
- Can manage vendor employees and their permissions

### 2. Vendor Employee Role (موظف البائع)
- **Needs specific permissions** - يحتاج صلاحيات محددة
- Created when vendor owner adds a new user
- Permissions are assigned by vendor owner
- Can only perform actions based on assigned permissions

## Permission Flow / تدفق الصلاحيات

### Step 1: Role Creation / إنشاء الأدوار
```
RolesSeeder creates:
- admin
- vendor
- vendor_employee ← NEW
- user
```

### Step 2: Vendor Owner Registration / تسجيل مالك البائع
```
1. User registers as vendor
2. User gets 'vendor' role assigned
3. Vendor account created with owner_id = user.id
4. Vendor has ALL permissions automatically
```

### Step 3: Vendor Employee Creation / إنشاء موظف البائع
```
1. Vendor owner goes to Vendor Users → Add User
2. Fills form with user details
3. Selects permissions for the employee
4. VendorUserService creates user with 'vendor_employee' role
5. Selected permissions are assigned to the user
```

### Step 4: Permission Checking / التحقق من الصلاحيات

#### In Routes (web.php)
```php
// Vendor role OR specific permission
Route::middleware('role_or_permission:vendor|create-products')
```

#### In Views (Blade Templates)
```blade
@if(vendorCan('create-products'))
    <button>Create Product</button>
@endif
```

#### In Form Requests
```php
public function authorize(): bool
{
    return $this->user()->hasRole('vendor') || 
           ($this->user()->hasRole('vendor_employee') && 
            $this->user()->hasPermissionTo('create-products'));
}
```

#### Helper Function (vendorCan)
```php
function vendorCan(string $permission): bool
{
    $user = auth()->user();
    
    // Admin has all permissions
    if ($user->hasRole('admin')) return true;
    
    // Vendor role has all permissions
    if ($user->hasRole('vendor')) return true;
    
    // Vendor employee needs specific permission
    if ($user->hasRole('vendor_employee')) {
        return $user->hasPermissionTo($permission);
    }
    
    return false;
}
```

## Available Permissions / الصلاحيات المتاحة

### Products / المنتجات
- `manage-products` - Full access (create, edit, delete, view)
- `view-products` - View only
- `create-products` - Create only
- `edit-products` - Edit only
- `delete-products` - Delete only

### Branches / الفروع
- `manage-branches` - Full access
- `view-branches` - View only
- `create-branches` - Create only
- `edit-branches` - Edit only
- `delete-branches` - Delete only

### Categories / الفئات
- `view-categories` - View only

### Variants / المتغيرات
- `view-variants` - View only

### Variant Requests / طلبات المتغيرات
- `view-variant-requests` - View requests
- `create-variant-requests` - Create requests

### Category Requests / طلبات الفئات
- `view-category-requests` - View requests
- `create-category-requests` - Create requests

### Plans / الخطط
- `view-plans` - View plans
- `subscribe-plans` - Subscribe to plans

### Subscriptions / الاشتراكات
- `view-subscriptions` - View subscriptions
- `cancel-subscriptions` - Cancel subscriptions

### Vendor Users / مستخدمي البائع
- `manage-vendor-users` - Full access
- `view-vendor-users` - View only
- `create-vendor-users` - Create only
- `edit-vendor-users` - Edit only
- `delete-vendor-users` - Delete only

### Profile / الملف الشخصي
- `edit-profile` - Edit profile

### Dashboard / لوحة التحكم
- `view-dashboard` - View dashboard

## Middleware Flow / تدفق الـ Middleware

### VendorUserMiddleware
1. Checks if user is authenticated
2. Admin → Allow all
3. Vendor owner (has ownedVendor) → Allow all
4. Vendor employee → Check VendorUser record and vendor status
5. If vendor is inactive → Deny access

### Route Middleware
- `vendor.user` - Checks vendor access (owner or employee)
- `role_or_permission:vendor|permission-name` - Checks role OR permission

## Key Files / الملفات الرئيسية

### Services
- `app/Services/VendorUserService.php` - Creates vendor employees with `vendor_employee` role
- `app/Services/VendorService.php` - Creates vendor owners with `vendor` role

### Middleware
- `app/Http/Middleware/VendorUserMiddleware.php` - Validates vendor access

### Helpers
- `app/Helpers/helpers.php` - Contains `vendorCan()` helper function

### Requests
- `app/Http/Requests/Vendor/*` - Form requests with permission checks

### Seeders
- `database/seeders/RolesSeeder.php` - Creates roles
- `database/seeders/VendorPermissionsSeeder.php` - Creates permissions

## Testing Checklist / قائمة التحقق

### Vendor Owner
- [ ] Can access all vendor routes
- [ ] Can see all menu items in sidebar
- [ ] Can perform all actions (create, edit, delete)
- [ ] Can manage vendor employees
- [ ] Can assign permissions to employees

### Vendor Employee
- [ ] Can only access routes based on permissions
- [ ] Can only see menu items based on permissions
- [ ] Can only perform actions based on permissions
- [ ] Cannot manage vendor users (unless has permission)
- [ ] Cannot access routes without required permissions

## Notes / ملاحظات

1. **Vendor role** = Full access, no permission checks needed
2. **Vendor employee role** = Limited access based on assigned permissions
3. **Admin role** = Full access to everything
4. All permission checks use `vendorCan()` helper for consistency
5. Routes use `role_or_permission:vendor|permission` to allow both roles
