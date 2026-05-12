# Testing Guide
## Multi-Vendor E-Commerce System

This comprehensive guide will help you test all aspects of your system, from API endpoints to performance and security.

## Table of Contents

1. [Quick Start Testing](#quick-start-testing)
2. [API Testing with Postman](#api-testing-with-postman)
3. [Manual Testing Checklist](#manual-testing-checklist)
4. [Performance Testing](#performance-testing)
5. [Database Testing](#database-testing)
6. [Security Testing](#security-testing)
7. [Integration Testing](#integration-testing)
8. [Load Testing](#load-testing)

---

## Quick Start Testing

### 1. Start the Application

```bash
# Start Laravel development server
php artisan serve

# Or if using Laragon, it should already be running
# Access: http://localhost:8000
```

### 2. Run Database Migrations

```bash
# Apply all migrations including the new indexes
php artisan migrate

# Check migration status
php artisan migrate:status
```

### 3. Run Existing Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/VendorWithdrawalServiceTest.php

# Run with coverage (if configured)
php artisan test --coverage
```

### 4. Check Application Health

```bash
# Check routes
php artisan route:list

# Check configuration
php artisan config:show

# Clear and cache config
php artisan config:clear
php artisan config:cache
```

---

## API Testing with Postman

### Setup Postman Collection

1. **Import Collection**
   - Open Postman
   - Click "Import"
   - Select `Multi-Vendor E-Commerce API.postman_collection.json`
   - The collection includes all endpoints with examples

2. **Configure Environment Variables**
   - Create a new environment in Postman
   - Add variables:
     ```
     base_url: http://localhost:8000/api
     token: (will be set after login)
     user_id: (will be set after registration)
     ```

3. **Authentication Flow**
   - Start with `POST /api/auth/register`
   - Then `POST /api/auth/verify-email` or `POST /api/auth/verify-phone`
   - Finally `POST /api/auth/login`
   - Copy the token and set it in environment variable
   - Use `{{token}}` in Authorization header for protected routes

### Test Scenarios

#### Authentication Flow
```
1. Register new user
   POST /api/auth/register
   - Test with valid data
   - Test with invalid email
   - Test with weak password
   - Test with duplicate email

2. Verify email/phone
   POST /api/auth/verify-email
   - Test with valid code
   - Test with invalid code
   - Test with expired code

3. Login
   POST /api/auth/login
   - Test with correct credentials
   - Test with wrong password
   - Test rate limiting (try 6 times quickly)
```

#### Product Endpoints
```
1. List products
   GET /api/products
   - Test pagination (per_page parameter)
   - Test search (search parameter)
   - Test filters (vendor_id, category_id, min_price, max_price)
   - Test sorting (sort parameter)
   - Test stock filter (stock=in_stock)

2. Get single product
   GET /api/products/{id}
   - Test with valid ID
   - Test with invalid ID
   - Verify all relationships are loaded

3. Toggle favorite
   POST /api/products/{id}/toggle-favorite
   - Test adding to favorites
   - Test removing from favorites
   - Test without authentication
```

#### Cart Endpoints
```
1. Add to cart
   POST /api/cart/{product}
   - Test with simple product
   - Test with variant product
   - Test with invalid product
   - Test quantity limits

2. Update cart
   PUT /api/cart/{product}
   - Test quantity update
   - Test with variant

3. Apply coupon
   POST /api/cart/apply-coupon
   - Test with valid coupon
   - Test with invalid coupon
   - Test with expired coupon
   - Test minimum order value
```

#### Order Endpoints
```
1. Calculate shipping
   POST /api/orders/calculate-shipping
   - Test with valid address
   - Test with multiple vendors
   - Test free shipping threshold
   - Test distance calculation

2. Create order
   POST /api/orders
   - Test complete order flow
   - Test with coupon
   - Test with points
   - Test with wallet
   - Test insufficient stock

3. Get orders
   GET /api/orders
   - Test pagination
   - Test filters (status, payment_status)
   - Test sorting
```

### Postman Test Scripts

Add this to your Postman collection tests:

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
```

---

## Manual Testing Checklist

### Authentication & Authorization

- [ ] User registration with email
- [ ] User registration with phone
- [ ] Email verification
- [ ] Phone verification
- [ ] Resend verification code
- [ ] Login with email
- [ ] Login with phone
- [ ] Password reset flow
- [ ] Rate limiting on login (5 attempts)
- [ ] Rate limiting on registration (5 attempts)
- [ ] Token expiration
- [ ] Refresh token (if implemented)

### Product Management

- [ ] List products with pagination
- [ ] Search products by name/SKU
- [ ] Filter products by category
- [ ] Filter products by vendor
- [ ] Filter products by price range
- [ ] Filter products by stock status
- [ ] Sort products (latest, price, name)
- [ ] View product details
- [ ] Add product to favorites
- [ ] Remove product from favorites
- [ ] View favorite products list

### Cart & Checkout

- [ ] Add simple product to cart
- [ ] Add variant product to cart
- [ ] Update cart quantity
- [ ] Remove item from cart
- [ ] Clear entire cart
- [ ] Apply coupon code
- [ ] Calculate shipping cost
- [ ] View cart with all details
- [ ] Check stock availability

### Order Management

- [ ] Create order with simple products
- [ ] Create order with variant products
- [ ] Create order with multiple vendors
- [ ] Create order with coupon
- [ ] Create order with points discount
- [ ] Create order with wallet payment
- [ ] View order details
- [ ] List user orders
- [ ] Filter orders by status
- [ ] Cancel order
- [ ] Reorder items

### Vendor Features

- [ ] Vendor registration
- [ ] Vendor login
- [ ] View vendor products
- [ ] View vendor orders
- [ ] Update vendor profile
- [ ] Manage vendor branches
- [ ] View vendor dashboard

### Admin Features

- [ ] Admin login
- [ ] Manage products
- [ ] Manage vendors
- [ ] Manage categories
- [ ] Manage orders
- [ ] View reports
- [ ] Manage settings

---

## Performance Testing

### 1. Database Query Performance

#### Check Index Usage

```bash
# Enable query logging
# In .env, set: DB_LOG_QUERIES=true

# Or use Laravel Debugbar/Telescope
```

#### Test Query Performance

```php
// Create a test script: tests/Performance/QueryPerformanceTest.php
use Illuminate\Support\Facades\DB;

DB::enableQueryLog();

// Run your queries
$products = Product::with(['vendor', 'categories'])->paginate(15);

$queries = DB::getQueryLog();
dd($queries);
```

#### Check N+1 Problems

```bash
# Install Laravel Debugbar
composer require barryvdh/laravel-debugbar --dev

# Or use Laravel Telescope
composer require laravel/telescope --dev
php artisan telescope:install
```

### 2. API Response Time Testing

#### Using Postman

1. Open Postman Collection Runner
2. Select your collection
3. Set iterations (e.g., 100)
4. Check "Save responses"
5. Run and check average response time

#### Using cURL

```bash
# Test single endpoint
time curl -X GET "http://localhost:8000/api/products" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Test multiple requests
for i in {1..100}; do
  curl -X GET "http://localhost:8000/api/products" \
    -H "Authorization: Bearer YOUR_TOKEN" \
    -w "%{time_total}\n" -o /dev/null -s
done | awk '{sum+=$1; count++} END {print "Average:", sum/count}'
```

#### Using Apache Bench (ab)

```bash
# Install Apache Bench
# Windows: Download from Apache website
# Linux: sudo apt-get install apache2-utils

# Test products endpoint
ab -n 1000 -c 10 -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/products
```

### 3. Database Index Verification

```sql
-- Check if indexes are being used
EXPLAIN SELECT * FROM products 
WHERE vendor_id = 1 AND is_active = 1 AND is_approved = 1;

-- Check index usage statistics
SHOW INDEX FROM products;

-- Analyze table
ANALYZE TABLE products;
```

### 4. Memory Usage Testing

```php
// Test memory usage
$startMemory = memory_get_usage();

$products = Product::with(['vendor', 'categories', 'images'])->get();

$endMemory = memory_get_usage();
$memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // MB

echo "Memory used: {$memoryUsed} MB\n";
```

---

## Database Testing

### 1. Verify Indexes

```bash
# Run migration to add indexes
php artisan migrate

# Check if indexes exist
php artisan tinker
```

```php
// In tinker
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Check products table indexes
DB::select("SHOW INDEX FROM products");

// Check index usage
DB::select("EXPLAIN SELECT * FROM products WHERE vendor_id = 1");
```

### 2. Test Query Performance

```sql
-- Test products query with index
EXPLAIN SELECT * FROM products 
WHERE is_active = 1 
  AND is_approved = 1 
  AND is_featured = 1
ORDER BY created_at DESC
LIMIT 15;

-- Should show "Using index" in Extra column

-- Test orders query
EXPLAIN SELECT * FROM orders 
WHERE user_id = 1 AND status = 'pending';

-- Should use orders_user_status_index
```

### 3. Test N+1 Prevention

```php
// Test in tinker
use App\Models\Product;

// BAD: N+1 problem
$products = Product::all();
foreach ($products as $product) {
    echo $product->vendor->name; // Multiple queries
}

// GOOD: Eager loading
$products = Product::with('vendor')->get();
foreach ($products as $product) {
    echo $product->vendor->name; // Single query
}
```

### 4. Test Database Transactions

```php
// Test order creation with transaction
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    $order = OrderService::createOrder(...);
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    throw $e;
}
```

---

## Security Testing

### 1. Rate Limiting Tests

```bash
# Test login rate limiting
for i in {1..6}; do
  curl -X POST "http://localhost:8000/api/auth/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"test@example.com","password":"wrong"}'
  echo "Attempt $i"
done

# Should get 429 after 5 attempts
```

### 2. Authentication Tests

```bash
# Test protected endpoint without token
curl -X GET "http://localhost:8000/api/orders"

# Should return 401 Unauthorized

# Test with invalid token
curl -X GET "http://localhost:8000/api/orders" \
  -H "Authorization: Bearer invalid_token"

# Should return 401
```

### 3. Input Validation Tests

```bash
# Test SQL injection attempt
curl -X POST "http://localhost:8000/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"test","email":"test@example.com","password":"password123","password_confirmation":"password123"}'

# Test XSS attempt
curl -X POST "http://localhost:8000/api/products/1/rate" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"rating":5,"comment":"<script>alert(\"XSS\")</script>"}'
```

### 4. Authorization Tests

```bash
# Test vendor accessing admin endpoint
# Test user accessing vendor endpoint
# Test accessing other user's orders
```

---

## Integration Testing

### 1. Complete Order Flow

```bash
# 1. Register user
POST /api/auth/register

# 2. Verify email
POST /api/auth/verify-email

# 3. Login
POST /api/auth/login

# 4. Add product to cart
POST /api/cart/{product}

# 5. Apply coupon
POST /api/cart/apply-coupon

# 6. Calculate shipping
POST /api/orders/calculate-shipping

# 7. Create order
POST /api/orders

# 8. View order
GET /api/orders/{id}
```

### 2. Payment Flow

```bash
# 1. Create order
POST /api/orders

# 2. Process payment
POST /api/orders/{id}/pay

# 3. Verify payment status
GET /api/orders/{id}
```

### 3. Vendor Subscription Flow

```bash
# 1. Vendor login
POST /api/auth/login (vendor credentials)

# 2. View plans
GET /api/plans

# 3. Subscribe to plan
POST /api/subscriptions

# 4. Verify subscription
GET /api/subscriptions/{id}
```

---

## Load Testing

### 1. Using Apache Bench

```bash
# Test products endpoint with 1000 requests, 10 concurrent
ab -n 1000 -c 10 -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/products

# Test order creation (POST)
ab -n 100 -c 5 -p order.json -T application/json \
  -H "Authorization: Bearer YOUR_TOKEN" \
  http://localhost:8000/api/orders
```

### 2. Using JMeter

1. Download JMeter
2. Create test plan
3. Add thread group (users, ramp-up, loops)
4. Add HTTP request sampler
5. Add listeners (View Results Tree, Summary Report)
6. Run test

### 3. Monitor During Load Test

```bash
# Monitor database connections
mysql> SHOW PROCESSLIST;

# Monitor server resources
# Windows: Task Manager
# Linux: htop, top

# Monitor Laravel logs
tail -f storage/logs/laravel.log
```

---

## Automated Testing Scripts

### Create Test Script: `test-api.sh`

```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api"
TOKEN=""

echo "Testing API Endpoints..."

# Register
echo "1. Testing registration..."
RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d '{"name":"Test User","email":"test@example.com","password":"password123","password_confirmation":"password123"}')
echo $RESPONSE

# Login
echo "2. Testing login..."
RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}')
TOKEN=$(echo $RESPONSE | jq -r '.data.token')
echo "Token: $TOKEN"

# Get products
echo "3. Testing products endpoint..."
curl -s -X GET "$BASE_URL/products" \
  -H "Authorization: Bearer $TOKEN" | jq '.data | length'

echo "Testing complete!"
```

### Create PHP Test Script: `tests/Manual/ApiTest.php`

```php
<?php

namespace Tests\Manual;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_order_flow(): void
    {
        // Register user
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $response->assertStatus(201);

        // Login
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $token = $response->json('data.token');

        // Get products
        $response = $this->withToken($token)
            ->getJson('/api/products');
        $response->assertStatus(200);

        // Add to cart
        $productId = $response->json('data.data.0.id');
        $response = $this->withToken($token)
            ->postJson("/api/cart/{$productId}");
        $response->assertStatus(200);

        // Create order
        $response = $this->withToken($token)
            ->postJson('/api/orders', [
                'address_id' => 1,
            ]);
        $response->assertStatus(201);
    }
}
```

---

## Testing Checklist Summary

### Pre-Production Testing

- [ ] All API endpoints tested
- [ ] Authentication flow tested
- [ ] Order creation flow tested
- [ ] Payment processing tested
- [ ] Rate limiting verified
- [ ] Database indexes applied and verified
- [ ] N+1 queries fixed
- [ ] Performance benchmarks met
- [ ] Security vulnerabilities checked
- [ ] Error handling tested
- [ ] Edge cases handled

### Performance Benchmarks

- [ ] API response time < 500ms (average)
- [ ] Database queries < 50ms (average)
- [ ] No N+1 query problems
- [ ] Indexes being used
- [ ] Memory usage acceptable
- [ ] Can handle 100+ concurrent users

### Security Checklist

- [ ] Rate limiting working
- [ ] Authentication required for protected routes
- [ ] SQL injection prevented
- [ ] XSS protection enabled
- [ ] CSRF protection enabled
- [ ] Input validation working
- [ ] File upload validation working

---

## Next Steps

1. **Run all tests**: `php artisan test`
2. **Test with Postman**: Import collection and test all endpoints
3. **Performance testing**: Use Apache Bench or JMeter
4. **Security audit**: Check for vulnerabilities
5. **Load testing**: Test under high load
6. **Monitor**: Set up monitoring tools

---

**Last Updated**: January 2026
