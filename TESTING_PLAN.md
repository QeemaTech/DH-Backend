# 🎯 Testing Plan
## Multi-Vendor E-Commerce System

**System URL**: `http://multi-vendor-e-commerce.test`

---

## 📋 Recommended Testing Order

### Phase 1: APIs First (Foundation) ⭐ **START HERE**

**Why APIs First?**
- APIs are the foundation - Dashboard depends on them
- Easier to test and debug
- No UI dependencies
- Can test all functionality programmatically

**Time Estimate**: 2-4 hours

#### Step 1: Basic API Health Check ✅
```
✓ Test API is accessible
✓ Test public endpoints (products, categories, vendors)
✓ Verify JSON responses
```

#### Step 2: Authentication Flow ✅
```
✓ Register new user
✓ Verify email/phone
✓ Login and get token
✓ Test protected routes
```

#### Step 3: Core Features ✅
```
✓ Products (list, search, filter, details)
✓ Cart (add, update, remove, apply coupon)
✓ Orders (calculate shipping, create, view)
✓ Categories & Vendors
```

#### Step 4: Advanced Features ✅
```
✓ Addresses management
✓ Favorites
✓ Ratings & Reviews
✓ Tickets/Support
```

---

### Phase 2: Dashboard Testing (After APIs Work)

**Why Dashboard Second?**
- Dashboard uses APIs internally
- Needs data from APIs to display
- Requires authentication setup
- UI testing is more visual

**Time Estimate**: 3-5 hours

#### Step 1: Admin Dashboard
```
URL: http://multi-vendor-e-commerce.test/admin/dashboard
Required: Admin user with role 'admin'

Test:
- Login as admin
- View dashboard statistics
- Navigate to all admin sections
- Test CRUD operations
```

#### Step 2: Vendor Dashboard
```
URL: http://multi-vendor-e-commerce.test/vendor/dashboard
Required: Vendor user with role 'vendor'

Test:
- Login as vendor
- View vendor dashboard
- Manage products
- Manage branches
- View orders
```

#### Step 3: Branch Dashboard
```
URL: http://multi-vendor-e-commerce.test/vendor/branch/dashboard
Required: Branch user (vendor employee with branch type)

Test:
- Login as branch user
- View branch-specific dashboard
- Manage branch products
- View branch orders
```

---

## 🚀 Quick Start: Test APIs Now

### Option 1: Using Postman (Recommended)

1. **Import Collection**
   ```
   File: Multi-Vendor E-Commerce API.postman_collection.json
   ```

2. **Create Environment**
   - Name: `Multi-Vendor E-Commerce`
   - Variables:
     - `base_url` = `http://multi-vendor-e-commerce.test`
     - `token` = (empty, will be set after login)

3. **Test Flow**
   ```
   1. Register → 2. Login → 3. Get Products → 4. Add to Cart → 5. Create Order
   ```

### Option 2: Using Browser

1. **Test Public Endpoints**
   ```
   http://multi-vendor-e-commerce.test/api/products
   http://multi-vendor-e-commerce.test/api/categories
   http://multi-vendor-e-commerce.test/api/vendors
   ```

2. **Should see JSON responses**

### Option 3: Using cURL (Command Line)

```bash
# Test Products
curl http://multi-vendor-e-commerce.test/api/products

# Test Categories
curl http://multi-vendor-e-commerce.test/api/categories
```

---

## 📝 API Testing Checklist

### ✅ Authentication
- [ ] Register user
- [ ] Verify email/phone
- [ ] Login
- [ ] Get user profile
- [ ] Logout
- [ ] Password reset flow

### ✅ Products
- [ ] List products (with pagination)
- [ ] Search products
- [ ] Filter by category
- [ ] Filter by vendor
- [ ] Filter by price range
- [ ] Filter by stock status
- [ ] Sort products
- [ ] Get product details
- [ ] Add to favorites
- [ ] Remove from favorites

### ✅ Cart
- [ ] Add product to cart
- [ ] Update quantity
- [ ] Remove item
- [ ] Clear cart
- [ ] Apply coupon
- [ ] View cart

### ✅ Orders
- [ ] Calculate shipping cost
- [ ] Create order
- [ ] View orders list
- [ ] View single order
- [ ] Filter orders
- [ ] Cancel order
- [ ] Reorder

### ✅ Other Features
- [ ] Manage addresses
- [ ] Rate products/vendors
- [ ] Create support tickets
- [ ] View notifications

---

## 🖥️ Dashboard Testing Checklist

### ✅ Admin Dashboard
**URL**: `http://multi-vendor-e-commerce.test/admin/dashboard`

**Prerequisites:**
- Create admin user (or use existing)
- Login as admin

**Test Sections:**
- [ ] Dashboard statistics load correctly
- [ ] Categories management (CRUD)
- [ ] Plans management (CRUD)
- [ ] Vendors management (CRUD)
- [ ] Customers management
- [ ] Products management
- [ ] Orders management
- [ ] Settings
- [ ] Reports

### ✅ Vendor Dashboard
**URL**: `http://multi-vendor-e-commerce.test/vendor/dashboard`

**Prerequisites:**
- Create vendor account
- Login as vendor

**Test Sections:**
- [ ] Dashboard statistics
- [ ] Products management (own products only)
- [ ] Branches management
- [ ] Orders (vendor orders only)
- [ ] Subscriptions
- [ ] Profile

### ✅ Branch Dashboard
**URL**: `http://multi-vendor-e-commerce.test/vendor/branch/dashboard`

**Prerequisites:**
- Create branch user (vendor employee)
- Login as branch user

**Test Sections:**
- [ ] Branch dashboard loads
- [ ] Branch-specific data
- [ ] Limited permissions (as configured)

---

## 🎯 Recommended Testing Sequence

### Day 1: APIs (Foundation)
1. ✅ Test API accessibility
2. ✅ Test authentication flow
3. ✅ Test products endpoints
4. ✅ Test cart functionality
5. ✅ Test orders flow
6. ✅ Test other features

### Day 2: Dashboard (UI)
1. ✅ Setup admin/vendor accounts
2. ✅ Test admin dashboard
3. ✅ Test vendor dashboard
4. ✅ Test branch dashboard
5. ✅ Test all CRUD operations
6. ✅ Test permissions

---

## 🔧 Setup for Dashboard Testing

### Create Admin User

**Option 1: Using Tinker**
```bash
php artisan tinker
```

```php
$admin = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password123'),
    'role' => 'admin',
    'is_active' => true,
    'is_verified' => true,
]);

$admin->assignRole('admin');
```

**Option 2: Using Database**
- Insert directly into database
- Or use existing admin account

### Create Vendor User

```php
// In tinker
$vendor = \App\Models\User::create([
    'name' => 'Vendor Owner',
    'email' => 'vendor@example.com',
    'password' => bcrypt('password123'),
    'role' => 'vendor',
    'is_active' => true,
    'is_verified' => true,
]);

$vendor->assignRole('vendor');

// Create vendor record
$vendorRecord = \App\Models\Vendor::create([
    'owner_id' => $vendor->id,
    'name' => ['en' => 'Test Vendor', 'ar' => 'بائع تجريبي'],
    'slug' => 'test-vendor',
    'is_active' => true,
]);
```

---

## 📊 Testing Priority

### High Priority (Must Test)
1. ✅ **APIs** - All endpoints
2. ✅ **Authentication** - Login, Register, Password Reset
3. ✅ **Orders** - Complete order flow
4. ✅ **Products** - CRUD operations
5. ✅ **Cart** - Add, update, remove

### Medium Priority
1. ✅ **Dashboard** - Admin & Vendor
2. ✅ **Permissions** - Role-based access
3. ✅ **Reports** - Various reports

### Low Priority (Nice to Have)
1. ✅ **Notifications** - Real-time updates
2. ✅ **Analytics** - Dashboard charts
3. ✅ **Export** - Data export features

---

## 🎬 Next Steps

### Right Now: Start with APIs

1. **Open Postman**
2. **Import**: `Multi-Vendor E-Commerce API.postman_collection.json`
3. **Set Environment**: `base_url = http://multi-vendor-e-commerce.test`
4. **Test**: Register → Login → Get Products

### After APIs Work: Test Dashboard

1. **Create Admin User** (using tinker or database)
2. **Login**: `http://multi-vendor-e-commerce.test/login`
3. **Access**: `http://multi-vendor-e-commerce.test/admin/dashboard`
4. **Test**: All admin features

---

## 📚 Reference Files

- **API Testing**: `START_TESTING.md`, `QUICK_TEST_GUIDE.md`
- **Full Guide**: `TESTING_GUIDE.md`
- **Postman Collection**: `Multi-Vendor E-Commerce API.postman_collection.json`

---

**Recommendation: Start with APIs first, then move to Dashboard! 🚀**
