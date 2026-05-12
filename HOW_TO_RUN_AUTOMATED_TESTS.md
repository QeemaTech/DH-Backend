# 🤖 How to Run Automated Tests

## Quick Start

### Run All Automated Tests

**Windows (PowerShell):**
```powershell
.\run-automated-tests.ps1
```

**Linux/Mac:**
```bash
chmod +x run-automated-tests.sh
./run-automated-tests.sh
```

**Or directly:**
```bash
php artisan test --filter=ApiAutomatedTest
php artisan test --filter=ApiEndpointsTest
```

---

## 📋 Available Automated Tests

### 1. Complete API Flow Test
**File**: `tests/Feature/ApiAutomatedTest.php`

**Tests:**
- ✅ Complete user journey (register → login → products → cart → order)
- ✅ Products API with all filters
- ✅ Cart operations
- ✅ Categories API
- ✅ Vendors API
- ✅ Authentication flow
- ✅ Rate limiting

**Run:**
```bash
php artisan test --filter=ApiAutomatedTest
```

### 2. API Endpoints Test
**File**: `tests/Feature/ApiEndpointsTest.php`

**Tests:**
- ✅ Public endpoints accessibility
- ✅ Protected endpoints require auth
- ✅ Query parameters work
- ✅ Complete order flow

**Run:**
```bash
php artisan test --filter=ApiEndpointsTest
```

---

## 🎯 What Gets Tested Automatically

### ✅ Covered by Automated Tests:
- Authentication (Register, Login, Logout)
- Products (List, Search, Filter, Details)
- Cart (Add, Update, Remove, Clear)
- Orders (Create, View, List)
- Categories & Vendors
- Rate Limiting
- Protected Routes
- Query Parameters

### ⚠️ Still Need Manual Testing:
- UI/UX (Dashboard appearance)
- Visual elements
- Browser compatibility
- User experience flow

---

## 🚀 Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Class
```bash
php artisan test --filter=ApiAutomatedTest
php artisan test --filter=ApiEndpointsTest
```

### Run Specific Test Method
```bash
php artisan test --filter=test_complete_api_flow_automated
php artisan test --filter=test_products_api_filters_automated
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Run with Verbose Output
```bash
php artisan test --filter=ApiAutomatedTest -v
```

---

## 📊 Test Results

After running tests, you'll see:
- ✅ Passed tests (green)
- ❌ Failed tests (red) with error details
- Total assertions
- Duration

---

## 🔧 Troubleshooting

### Tests Fail with "Role does not exist"
**Solution**: Tests automatically create roles, but if it fails:
```bash
php artisan db:seed --class=RoleSeeder
```

### Tests Fail with "Table doesn't exist"
**Solution**: Run migrations:
```bash
php artisan migrate
```

### Tests Fail with "Factory not found"
**Solution**: Some factories may be missing. The tests create data manually, so this shouldn't be an issue.

---

## 📚 More Information

- **Full Guide**: See `AUTOMATED_TESTING_GUIDE.md`
- **Testing Plan**: See `TESTING_PLAN.md`
- **Quick Start**: See `START_TESTING.md`

---

**Run Automated Tests Now!**

```bash
php artisan test --filter=ApiAutomatedTest
```
