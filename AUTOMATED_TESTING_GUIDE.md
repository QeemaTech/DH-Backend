# 🤖 Automated Testing Guide
## Multi-Vendor E-Commerce System

This guide shows you how to run automated tests instead of manual testing.

---

## 🚀 Quick Start

### Option 1: Run All Automated Tests (Recommended)

**Windows (PowerShell):**
```powershell
.\run-automated-tests.ps1
```

**Linux/Mac:**
```bash
chmod +x run-automated-tests.sh
./run-automated-tests.sh
```

### Option 2: Run Specific Test Classes

```bash
# Run complete API flow test
php artisan test --filter=ApiAutomatedTest

# Run API endpoints test
php artisan test --filter=ApiEndpointsTest

# Run all API tests
php artisan test tests/Feature/ApiAutomatedTest.php tests/Feature/ApiEndpointsTest.php
```

---

## 📋 What Gets Tested Automatically

### ✅ Complete API Flow Test (`ApiAutomatedTest`)

Automatically tests:
1. **Authentication** - Login and token generation
2. **Products** - List, search, filter, get details
3. **Cart** - Add, update, remove items
4. **Addresses** - Create address
5. **Shipping** - Calculate shipping cost
6. **Orders** - Create order, view order, list orders

### ✅ API Endpoints Test (`ApiEndpointsTest`)

Automatically tests:
1. **Public Endpoints** - All public endpoints return 200
2. **Protected Endpoints** - Require authentication (401)
3. **Query Parameters** - All filter/sort options work
4. **Complete Order Flow** - End-to-end order creation

---

## 🎯 Automated Test Files

### 1. `tests/Feature/ApiAutomatedTest.php`
- Tests complete user journey
- Tests all major features
- Creates test data automatically
- Verifies responses

### 2. `tests/Feature/ApiEndpointsTest.php`
- Tests all endpoints systematically
- Tests query parameters
- Tests authentication requirements
- Tests error handling

---

## 🔧 Running Tests

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

## 📊 Test Coverage

### What's Covered:
- ✅ Authentication (Register, Login, Logout)
- ✅ Products (List, Search, Filter, Details)
- ✅ Cart (Add, Update, Remove, Clear)
- ✅ Orders (Create, View, List)
- ✅ Categories (List, Details)
- ✅ Vendors (List, Details)
- ✅ Rate Limiting
- ✅ Protected Routes
- ✅ Query Parameters

### What's NOT Covered (Manual Testing Needed):
- ⚠️ UI/UX Testing (Dashboard)
- ⚠️ Visual Testing
- ⚠️ Browser-specific issues
- ⚠️ Performance under load (use load testing tools)

---

## 🎬 Postman Collection Runner (Alternative)

### Automated Postman Tests

1. **Open Postman**
2. **Select Collection**: Multi-Vendor E-Commerce API
3. **Click**: "Run" button (top right)
4. **Configure**:
   - Iterations: 1
   - Delay: 0ms
   - Data: None
5. **Click**: "Run Multi-Vendor E-Commerce API"

### Postman Test Scripts

Add this to your Postman collection's test script:

```javascript
// Auto-save token after login
if (pm.response.code === 200) {
    const jsonData = pm.response.json();
    if (jsonData.data && jsonData.data.token) {
        pm.environment.set("token", jsonData.data.token);
    }
}

// Test response time
pm.test("Response time is less than 500ms", function () {
    pm.expect(pm.response.responseTime).to.be.below(500);
});

// Test status code
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

// Test response structure
pm.test("Response has data", function () {
    const jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('data');
});
```

---

## 🔄 Continuous Testing

### Run Tests on File Change (Watch Mode)

**Install Pest (if using Pest):**
```bash
composer require pestphp/pest --dev
```

**Or use PHPUnit with watch:**
```bash
# Install fswatch (Mac/Linux)
brew install fswatch

# Watch and run tests
fswatch -o tests/ | xargs -n1 php artisan test
```

### GitHub Actions / CI/CD

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          
      - name: Install Dependencies
        run: composer install
        
      - name: Run Tests
        run: php artisan test
```

---

## 📈 Test Reports

### Generate HTML Report

```bash
php artisan test --coverage-html coverage/
```

Open `coverage/index.html` in browser.

### Generate Text Report

```bash
php artisan test --coverage-text
```

---

## 🎯 Testing Strategy

### Automated Tests (Run These)
1. ✅ **API Endpoints** - All endpoints work
2. ✅ **Business Logic** - Order flow, cart logic
3. ✅ **Authentication** - Login, register, permissions
4. ✅ **Data Validation** - Input validation

### Manual Tests (Still Needed)
1. ⚠️ **UI/UX** - Dashboard appearance
2. ⚠️ **Visual** - Layout, colors, responsive
3. ⚠️ **User Experience** - Flow, navigation
4. ⚠️ **Browser Compatibility** - Different browsers

---

## 🚀 Quick Commands

```bash
# Run all automated tests
php artisan test

# Run API tests only
php artisan test --filter=Api

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=test_complete_api_flow_automated

# Run with verbose output
php artisan test -v

# Run and stop on first failure
php artisan test --stop-on-failure
```

---

## 📝 Adding More Automated Tests

### Example: Add New Test

```php
public function test_new_feature_automated(): void
{
    $response = $this->withToken($this->token)
        ->getJson('/api/new-endpoint');
    
    $response->assertStatus(200);
    $this->assertArrayHasKey('data', $response->json());
}
```

---

## 🎉 Benefits of Automated Testing

1. ✅ **Fast** - Run all tests in seconds
2. ✅ **Reliable** - Same tests every time
3. ✅ **Comprehensive** - Test all endpoints
4. ✅ **Repeatable** - Run anytime
5. ✅ **CI/CD Ready** - Integrate with deployment

---

**Start Automated Testing Now!**

```bash
php artisan test --filter=ApiAutomatedTest
```
