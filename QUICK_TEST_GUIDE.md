# Quick Test Guide
## Multi-Vendor E-Commerce System

**Base URL**: `http://multi-vendor-e-commerce.test/api`

---

## 🚀 Quick Start Testing

### Step 1: Verify System is Running

Open your browser and check:
- **Homepage**: `http://multi-vendor-e-commerce.test/`
- **API Health**: `http://multi-vendor-e-commerce.test/api/products` (should return JSON)

### Step 2: Test API with Postman

#### A. Import Postman Collection

1. Open Postman
2. Click **Import**
3. Select file: `Multi-Vendor E-Commerce API.postman_collection.json`
4. Create/Update Environment:
   - Click **Environments** → **Create Environment**
   - Name: `Multi-Vendor E-Commerce`
   - Add variable:
     - **Variable**: `base_url`
     - **Initial Value**: `http://multi-vendor-e-commerce.test`
   - Add variable:
     - **Variable**: `token`
     - **Initial Value**: (leave empty, will be set after login)
   - Click **Save**

#### B. Test Authentication Flow

**1. Register New User**
```
POST http://multi-vendor-e-commerce.test/api/auth/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "phone": "+1234567890",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Expected Response (201)**:
```json
{
  "success": true,
  "message": "Registration successful. Please verify your account before logging in.",
  "data": {
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com"
    }
  }
}
```

**2. Verify Email** (Check your email or database for verification code)
```
POST http://multi-vendor-e-commerce.test/api/auth/verify-email
Content-Type: application/json

{
  "email": "test@example.com",
  "code": "123456"
}
```

**3. Login**
```
POST http://multi-vendor-e-commerce.test/api/auth/login
Content-Type: application/json

{
  "login": "test@example.com",
  "password": "password123"
}
```

**Expected Response (200)**:
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "user": {
      "id": 1,
      "name": "Test User",
      "email": "test@example.com"
    }
  }
}
```

**Copy the token** and use it in subsequent requests!

**4. Get User Profile** (Protected Route)
```
GET http://multi-vendor-e-commerce.test/api/user
Authorization: Bearer {your_token}
```

---

### Step 3: Test Products API

**1. List Products**
```
GET http://multi-vendor-e-commerce.test/api/products?per_page=10
```

**2. Search Products**
```
GET http://multi-vendor-e-commerce.test/api/products?search=laptop&per_page=10
```

**3. Filter Products**
```
GET http://multi-vendor-e-commerce.test/api/products?min_price=100&max_price=1000&stock=in_stock
```

**4. Get Single Product**
```
GET http://multi-vendor-e-commerce.test/api/products/1
```

---

### Step 4: Test Cart API

**1. Add Product to Cart**
```
POST http://multi-vendor-e-commerce.test/api/cart/1
Authorization: Bearer {your_token}
```

**2. Get Cart**
```
GET http://multi-vendor-e-commerce.test/api/cart
Authorization: Bearer {your_token}
```

**3. Update Cart Quantity**
```
PUT http://multi-vendor-e-commerce.test/api/cart/1
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "quantity": 2
}
```

**4. Apply Coupon**
```
POST http://multi-vendor-e-commerce.test/api/cart/apply-coupon
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "coupon_code": "DISCOUNT10"
}
```

---

### Step 5: Test Orders API

**1. Calculate Shipping**
```
POST http://multi-vendor-e-commerce.test/api/orders/calculate-shipping
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "address_id": 1
}
```

**2. Create Order**
```
POST http://multi-vendor-e-commerce.test/api/orders
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "address_id": 1,
  "payment_method": "cash_on_delivery"
}
```

**3. Get Orders**
```
GET http://multi-vendor-e-commerce.test/api/orders
Authorization: Bearer {your_token}
```

**4. Get Single Order**
```
GET http://multi-vendor-e-commerce.test/api/orders/1
Authorization: Bearer {your_token}
```

---

### Step 6: Test Categories API

**1. List Categories**
```
GET http://multi-vendor-e-commerce.test/api/categories
```

**2. Get Single Category**
```
GET http://multi-vendor-e-commerce.test/api/categories/1
```

---

### Step 7: Test Vendors API

**1. List Vendors**
```
GET http://multi-vendor-e-commerce.test/api/vendors
```

**2. Get Single Vendor**
```
GET http://multi-vendor-e-commerce.test/api/vendors/1
```

---

## 🧪 Testing with cURL (Command Line)

### Register User
```bash
curl -X POST "http://multi-vendor-e-commerce.test/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "phone": "+1234567890",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### Login
```bash
curl -X POST "http://multi-vendor-e-commerce.test/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "login": "test@example.com",
    "password": "password123"
  }'
```

### Get Products (with token)
```bash
curl -X GET "http://multi-vendor-e-commerce.test/api/products" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## ✅ Testing Checklist

### Authentication
- [ ] Register new user
- [ ] Verify email/phone
- [ ] Login successfully
- [ ] Get user profile
- [ ] Logout

### Products
- [ ] List products (with pagination)
- [ ] Search products
- [ ] Filter by category
- [ ] Filter by vendor
- [ ] Filter by price range
- [ ] Filter by stock status
- [ ] Sort products (latest, price, name)
- [ ] View product details
- [ ] Add to favorites
- [ ] Remove from favorites

### Cart
- [ ] Add product to cart
- [ ] Update cart quantity
- [ ] Remove item from cart
- [ ] Clear cart
- [ ] Apply coupon
- [ ] View cart

### Orders
- [ ] Calculate shipping cost
- [ ] Create order
- [ ] View orders list
- [ ] View single order
- [ ] Filter orders by status
- [ ] Cancel order

### Categories
- [ ] List categories
- [ ] View category details
- [ ] Filter categories

### Vendors
- [ ] List vendors
- [ ] View vendor details
- [ ] Filter vendors

---

## 🔍 Common Issues & Solutions

### Issue: 401 Unauthorized
**Solution**: Make sure you're including the Bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

### Issue: 422 Validation Error
**Solution**: Check the error message in the response. Common issues:
- Missing required fields
- Invalid email format
- Password too short (minimum 8 characters)
- Password confirmation doesn't match

### Issue: 429 Too Many Requests
**Solution**: You've exceeded the rate limit. Wait 60 seconds and try again.

### Issue: 500 Internal Server Error
**Solution**: 
1. Check Laravel logs: `storage/logs/laravel.log`
2. Make sure database migrations are run: `php artisan migrate`
3. Check if roles are seeded: `php artisan db:seed --class=RoleSeeder` (if exists)

---

## 📊 Performance Testing

### Test Response Time
```bash
# Using curl with time
time curl -X GET "http://multi-vendor-e-commerce.test/api/products"
```

### Test Multiple Requests
```bash
# Test 100 requests
for i in {1..100}; do
  curl -X GET "http://multi-vendor-e-commerce.test/api/products" \
    -w "%{time_total}\n" -o /dev/null -s
done | awk '{sum+=$1; count++} END {print "Average:", sum/count "s"}'
```

---

## 🎯 Next Steps

1. **Test all endpoints** using Postman collection
2. **Verify database indexes** are applied: `php artisan migrate`
3. **Check rate limiting** is working
4. **Test error handling** with invalid data
5. **Monitor performance** using browser DevTools or Postman

---

**Happy Testing! 🚀**
