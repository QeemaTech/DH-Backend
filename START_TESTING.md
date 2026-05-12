# 🚀 Start Testing Your System

## Your System URL
**Base URL**: `http://multi-vendor-e-commerce.test/api`

---

## ✅ Quick Test Steps

### 1. Test API is Working

Open your browser and visit:
```
http://multi-vendor-e-commerce.test/api/products
```

You should see JSON response with products (or empty array if no products).

### 2. Test with Postman (Recommended)

#### Step 1: Import Collection
1. Open **Postman**
2. Click **Import** button
3. Select file: `Multi-Vendor E-Commerce API.postman_collection.json`
4. Click **Import**

#### Step 2: Create Environment
1. Click **Environments** (left sidebar)
2. Click **+** to create new environment
3. Name: `Multi-Vendor E-Commerce`
4. Add variables:
   - **Variable**: `base_url`
   - **Initial Value**: `http://multi-vendor-e-commerce.test`
   - **Variable**: `token`
   - **Initial Value**: (leave empty)
5. Click **Save**
6. Select this environment from dropdown (top right)

#### Step 3: Test Authentication

**Register User:**
1. Open collection: **Multi-Vendor E-Commerce API**
2. Go to: **Authentication** → **Register**
3. Click **Send**
4. Check response - should be 201 Created

**Login:**
1. Go to: **Authentication** → **Login**
2. Update body with your registered email/password
3. Click **Send**
4. Copy the `token` from response
5. Go to Environment → Edit → Set `token` variable
6. Save

**Test Protected Route:**
1. Go to: **User** → **Get User Profile**
2. Click **Send**
3. Should return user data (200 OK)

### 3. Test Products API

**List Products:**
```
GET http://multi-vendor-e-commerce.test/api/products
```

**Search Products:**
```
GET http://multi-vendor-e-commerce.test/api/products?search=laptop
```

**Filter Products:**
```
GET http://multi-vendor-e-commerce.test/api/products?min_price=100&max_price=1000
```

### 4. Test Cart API

**Add to Cart:**
```
POST http://multi-vendor-e-commerce.test/api/cart/1
Authorization: Bearer {your_token}
```

**Get Cart:**
```
GET http://multi-vendor-e-commerce.test/api/cart
Authorization: Bearer {your_token}
```

### 5. Test Orders API

**Calculate Shipping:**
```
POST http://multi-vendor-e-commerce.test/api/orders/calculate-shipping
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "address_id": 1
}
```

---

## 📋 Testing Checklist

### Authentication ✅
- [ ] Register new user
- [ ] Verify email/phone
- [ ] Login successfully
- [ ] Get user profile
- [ ] Logout

### Products ✅
- [ ] List products
- [ ] Search products
- [ ] Filter by category
- [ ] Filter by price
- [ ] View product details

### Cart ✅
- [ ] Add product to cart
- [ ] Update quantity
- [ ] Remove item
- [ ] Apply coupon

### Orders ✅
- [ ] Calculate shipping
- [ ] Create order
- [ ] View orders

---

## 🔧 If You Get Errors

### 401 Unauthorized
- Make sure you're logged in
- Check token is valid
- Token should be in format: `Bearer {token}`

### 422 Validation Error
- Check required fields
- Email must be valid
- Password minimum 8 characters

### 429 Too Many Requests
- Wait 60 seconds
- Rate limit exceeded

### 500 Internal Server Error
- Check `storage/logs/laravel.log`
- Make sure database is migrated
- Check if roles exist (may need to seed)

---

## 📚 More Information

- **Full Testing Guide**: See `TESTING_GUIDE.md`
- **API Documentation**: See `API_DOCUMENTATION.md`
- **Postman Collection**: `Multi-Vendor E-Commerce API.postman_collection.json`

---

**Happy Testing! 🎉**
