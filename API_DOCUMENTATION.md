# Multi-Vendor E-Commerce API Documentation

## 📋 Table of Contents

1. [Overview](#overview)
2. [Base URL](#base-url)
3. [Authentication](#authentication)
4. [Rate Limiting](#rate-limiting)
5. [Response Format](#response-format)
6. [Endpoints](#endpoints)
   - [Authentication](#authentication-endpoints)
   - [Profile Management](#profile-management)
   - [Password Management](#password-management)
   - [Address Management](#address-management)
   - [Products](#products)
   - [Categories](#categories)
   - [Vendors](#vendors)
   - [Cart Management](#cart-management)
   - [Orders](#orders)
   - [Tickets](#tickets)
   - [Ratings](#ratings)
   - [Reports](#reports)
   - [Transactions](#transactions)
   - [Sliders](#sliders)
   - [Password Reset](#password-reset)
7. [Data Models](#data-models)
8. [Error Codes](#error-codes)
9. [Postman Collection](#postman-collection)

---

## Overview

This is the comprehensive REST API documentation for the Multi-Vendor E-Commerce Platform. The API uses Laravel Sanctum for authentication and returns JSON responses. All endpoints support localization and follow RESTful conventions.

### Key Features

- 🔐 **Secure Authentication**: Laravel Sanctum token-based authentication
- 🌍 **Multi-language Support**: All endpoints support localization via `Accept-Language` header
- 📊 **Pagination**: List endpoints support pagination with customizable page size
- 🔍 **Advanced Filtering**: Most list endpoints support filtering, searching, and sorting
- ⚡ **Rate Limiting**: Built-in rate limiting for security and performance
- 📱 **Mobile Ready**: Optimized for mobile applications

---

## Base URL

```
http://localhost:8000/api
```

For production, replace `localhost:8000` with your production domain.

**Example:**
```
https://api.example.com/api
```

---

## Authentication

Most endpoints require Bearer token authentication. To authenticate:

1. **Register** using `/api/auth/register` endpoint (or **Login** using `/api/auth/login`)
2. Receive a **token** in the response
3. Include the token in subsequent requests:
   ```
   Authorization: Bearer {your_token}
   ```

### Token Format

Tokens are returned as plain text strings. Include them in the `Authorization` header:

```
Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Token Expiration

Tokens do not expire by default. To invalidate a token, use the `/api/logout` endpoint.

---

## Rate Limiting

Rate limiting is applied to various endpoints to prevent abuse and ensure fair usage:

| Endpoint Type | Limit | Window |
|--------------|-------|--------|
| Registration | 5 requests | 1 minute |
| Login | 5 requests | 1 minute |
| Email/Phone Verification | 10 requests | 1 minute |
| Resend Verification Code | 3 requests | 1 minute |
| Password Reset (Send Code) | 5 requests | 1 minute |
| Password Reset (Verify Code) | 10 requests | 1 minute |
| Password Reset (Set Password) | 5 requests | 1 minute |
| Profile Update | 10 requests | 1 minute |
| Password Update | 5 requests | 1 minute |
| Address Operations | 10 requests | 1 minute |
| Cart Operations | 30 requests | 1 minute |
| Order Creation | 10 requests | 1 minute |
| Shipping Calculation | 30 requests | 1 minute |
| Ticket Operations | 10-20 requests | 1 minute |
| Rating Operations | 10 requests | 1 minute |
| Report Operations | 5 requests | 1 minute |

When the limit is exceeded, you'll receive a `429 Too Many Requests` response with a `Retry-After` header indicating when you can retry.

---

## Response Format

All API responses follow a consistent structure:

### Success Response

```json
{
  "success": true,
  "message": "Operation successful message",
  "data": {
    // Response data
  }
}
```

For paginated responses:

```json
{
  "success": true,
  "data": [
    // Array of items
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

### Error Response

**Validation Error (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message for this field"]
  }
}
```

**General Error (400, 401, 403, 404, 500):**
```json
{
  "success": false,
  "message": "Error message"
}
```

**Rate Limit Error (429):**
```json
{
  "message": "Too Many Attempts."
}
```

## Endpoints

---

## Authentication Endpoints

### Register User

**POST** `/api/auth/register`

Register a new user account. After registration, the user must verify their account before logging in.

**Rate Limit:** 5 requests per minute

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "phone": "+1234567890",
  "password": "password123",
  "password_confirmation": "password123",
  "referred_by_code": "REF123"
}
```

**Validation Rules:**
- `name` (required, string, max:255): User's full name
- `email` (required, email): Valid email address, must be unique, automatically lowercased
- `phone` (optional, string|null, max:255): Phone number, must be unique if provided
- `password` (required, string, min:8): Password, must match confirmation
- `password_confirmation` (required, string): Must match password
- `referred_by_code` (optional, string): Referral code from another user

**Success Response (201):**
```json
{
  "success": true,
  "message": "Registration successful. Please verify your account before logging in.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "phone": "+1234567890",
      "image": "http://example.com/storage/users/default.png",
      "email_verified_at": null,
      "phone_verified_at": null,
      "is_active": true,
      "is_verified": false,
      "roles": ["user"],
      "permissions": [],
      "created_at": "2026-01-15T12:00:00.000000Z",
      "updated_at": "2026-01-15T12:00:00.000000Z"
    }
  }
}
```

**Error Responses:**
- `422`: Validation errors (email taken, password mismatch, etc.)
- `429`: Too many registration attempts

**Important Notes:**
- After registration, user account is created with `is_verified: false`
- Users **cannot log in** until their account is verified
- Verification email/code is automatically sent after registration
- If `referred_by_code` is provided and valid, the referrer receives points

---

### Login

**POST** `/api/auth/login`

Authenticate a user and receive an access token. Users must be verified to log in.

**Rate Limit:** 5 requests per minute (handled in LoginRequest)

**Request Body:**
```json
{
  "login": "user@example.com",
  "password": "password123"
}
```

**Validation Rules:**
- `login` (required, string): Email address or phone number
- `password` (required, string): User's password

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "phone": "+1234567890",
      "image": "http://example.com/storage/users/image.jpg",
      "email_verified_at": "2026-01-15T10:30:00.000000Z",
      "phone_verified_at": null,
      "is_active": true,
      "is_verified": true,
      "roles": ["user"],
      "permissions": [],
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-15T10:30:00.000000Z"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**Error Responses:**
- `401`: Invalid credentials, account deactivated, or account not verified
- `422`: Validation errors
- `429`: Too many login attempts

**Important Notes:**
- Users must have `is_verified: true` to log in
- Unverified users will receive a 401 error
- The `login` field accepts either email or phone number
- Token does not expire by default

---

### Logout

**POST** `/api/logout`

Log out the authenticated user and invalidate the current token.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

**Error Responses:**
- `401`: Unauthenticated (missing or invalid token)

---

### Get Current User

**GET** `/api/user`

Get the currently authenticated user's information.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "phone": "+1234567890",
      "image": "http://example.com/storage/users/image.jpg",
      "email_verified_at": "2026-01-15T10:30:00.000000Z",
      "phone_verified_at": null,
      "is_active": true,
      "is_verified": true,
      "roles": ["user"],
      "permissions": [],
      "created_at": "2026-01-01T00:00:00.000000Z",
      "updated_at": "2026-01-15T10:30:00.000000Z"
    }
  }
}
```

**Error Responses:**
- `401`: Unauthenticated (missing or invalid token)

---

### Verify Email

**POST** `/api/auth/verify-email`

Verify user's email address using the verification code sent to their email.

**Rate Limit:** 10 requests per minute

**Request Body:**
```json
{
  "email": "user@example.com",
  "code": "123456"
}
```

**Validation Rules:**
- `email` (required, email): User's email address
- `code` (required, string): Verification code received via email

**Success Response (200):**
```json
{
  "success": true,
  "message": "Account verified successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com",
      "is_verified": true,
      "email_verified_at": "2026-01-15T12:00:00.000000Z"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**Error Responses:**
- `400`: Invalid verification code, account already verified, or user not found
- `404`: User not found
- `422`: Validation errors
- `429`: Too many verification attempts

**Important Notes:**
- Verification code expires after a certain period (check system settings)
- After successful verification, a token is automatically generated for immediate login

---

### Verify Phone

**POST** `/api/auth/verify-phone`

Verify user's phone number using the verification code sent via SMS.

**Rate Limit:** 10 requests per minute

**Request Body:**
```json
{
  "phone": "+1234567890",
  "code": "123456"
}
```

**Validation Rules:**
- `phone` (required, string): User's phone number
- `code` (required, string): Verification code received via SMS

**Success Response (200):**
```json
{
  "success": true,
  "message": "Account verified successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "phone": "+1234567890",
      "is_verified": true,
      "phone_verified_at": "2026-01-15T12:00:00.000000Z"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "token_type": "Bearer"
  }
}
```

**Error Responses:**
- `400`: Invalid verification code, account already verified, or user not found
- `404`: User not found
- `422`: Validation errors
- `429`: Too many verification attempts

---

### Resend Verification Code

**POST** `/api/auth/resend-verification-code`

Resend verification code to user's email or phone.

**Rate Limit:** 3 requests per minute

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

OR

```json
{
  "phone": "+1234567890"
}
```

**Validation Rules:**
- `email` (sometimes, nullable, email, exists:users,email): User's email address
- `phone` (sometimes, nullable, string, exists:users,phone): User's phone number
- At least one of `email` or `phone` must be provided

**Success Response (200):**
```json
{
  "success": true,
  "message": "Verification code resent successfully."
}
```

**Error Responses:**
- `404`: User not found
- `422`: Validation errors (email/phone not found)
- `429`: Too many resend attempts

---

## Profile Management

### Get Profile

**GET** `/api/profile`

Get the authenticated user's profile information.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
Same structure as Get Current User endpoint.

**Error Responses:**
- `401`: Unauthenticated

---

### Update Profile

**PUT** `/api/profile`

Update the authenticated user's profile information.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data (if uploading image)
```

**Request Body (all fields optional):**
```json
{
  "name": "John Doe Updated",
  "email": "newemail@example.com",
  "phone": "+9876543210",
  "image": "<file>"
}
```

**Validation Rules:**
- `name` (optional, string, max:255): User's full name
- `email` (optional, email): Valid email address, must be unique, automatically lowercased
- `phone` (optional, string|null, max:255): Phone number, must be unique if provided
- `image` (optional, file): Image file (jpeg, png, jpg, gif, svg, webp), max 5MB

**Important Notes:**
- When updating email or phone, verification status is reset (`is_verified` becomes `false`)
- Old profile images are automatically deleted when a new one is uploaded
- Use `multipart/form-data` content type when uploading images

**Success Response (200):**
```json
{
  "success": true,
  "message": "Profile updated successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe Updated",
      "email": "newemail@example.com",
      "phone": "+9876543210",
      "image": "http://example.com/storage/users/new-image.jpg",
      "is_verified": false,
      "email_verified_at": null,
      "phone_verified_at": null
    }
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `422`: Validation errors (email taken, invalid format, file too large, etc.)
- `429`: Too many update attempts

---

## Password Management

### Update Password

**PUT** `/api/password`

Update the authenticated user's password.

**Authentication:** Required  
**Rate Limit:** 5 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "current_password": "oldpassword123",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Validation Rules:**
- `current_password` (required, string): Must match user's current password
- `password` (required, string, min:8): New password
- `password_confirmation` (required, string): Must match `password`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password updated successfully."
}
```

**Error Responses:**
- `401`: Unauthenticated
- `422`: Current password incorrect, password confirmation mismatch, or validation errors
- `429`: Too many update attempts

---

---

## Address Management

### List Addresses

**GET** `/api/addresses`

Get all addresses for the authenticated user.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "phone": "+1234567890",
      "address": "123 Main Street",
      "city": "New York",
      "state": "NY",
      "latitude": 40.7128,
      "longitude": -74.0060,
      "is_default": true
    }
  ]
}
```

**Error Responses:**
- `401`: Unauthenticated

---

### Create Address

**POST** `/api/addresses`

Create a new address for the authenticated user.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "name": "John Doe",
  "phone": "+1234567890",
  "address": "123 Main Street",
  "city": "New York",
  "state": "NY",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "is_default": true
}
```

**Validation Rules:**
- `name` (required, string, max:255): Contact name
- `phone` (required, string, max:20): Contact phone number
- `address` (required, string, max:500): Street address
- `city` (nullable, string, max:100): City name
- `state` (nullable, string, max:100): State/Province name
- `latitude` (nullable, numeric): GPS latitude coordinate
- `longitude` (nullable, numeric): GPS longitude coordinate
- `is_default` (nullable, boolean): Set as default address

**Success Response (201):**
```json
{
  "id": 1,
  "name": "John Doe",
  "phone": "+1234567890",
  "address": "123 Main Street",
  "city": "New York",
  "state": "NY",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "is_default": true
}
```

**Error Responses:**
- `401`: Unauthenticated
- `422`: Validation errors
- `429`: Too many requests

**Important Notes:**
- If `is_default` is set to `true`, all other addresses for the user will be set to `is_default: false`
- At least one address should be marked as default for order placement

---

### Delete Address

**DELETE** `/api/addresses/{address}`

Delete an address for the authenticated user.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `address` (integer): Address ID

**Success Response (200):**
```json
{
  "message": "Address deleted successfully"
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Address does not belong to the authenticated user
- `404`: Address not found
- `429`: Too many requests

---

## Products

### List Products

**GET** `/api/products`

Get a paginated list of active and approved products.

**Authentication:** Optional (public endpoint)

**Query Parameters:**
- `per_page` (integer, default: 15): Number of items per page
- `search` (string): Search in product name, description, or SKU
- `featured` (string): Filter by featured status (`1` or `0`)
- `vendor_id` (integer): Filter by vendor ID
- `category_id` (integer): Filter by category ID
- `min_price` (numeric): Minimum price filter
- `max_price` (numeric): Maximum price filter
- `stock` (string): Filter by stock availability (`in_stock`, `out_of_stock`, `low_stock`)
- `sort` (string): Sort order (`price_asc`, `price_desc`, `name_asc`, `name_desc`, `newest`, `oldest`)

**Example Request:**
```
GET /api/products?per_page=20&category_id=5&min_price=10&max_price=100&sort=price_asc
```

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": {
        "en": "Product Name",
        "ar": "اسم المنتج"
      },
      "slug": "product-name",
      "thumb_image": "http://example.com/storage/products/image.jpg",
      "description": {
        "en": "Product description",
        "ar": "وصف المنتج"
      },
      "discount": 10,
      "discount_type": "percentage",
      "is_active": true,
      "is_favorite": false,
      "is_featured": true,
      "is_approved": true,
      "is_bookable": false,
      "is_new": true,
      "vendor": {
        "id": 1,
        "name": "Vendor Name",
        "image": "http://example.com/storage/vendors/image.jpg",
        "rating": {
          "average": 4.5,
          "count": 120
        }
      },
      "categories": [
        {
          "id": 1,
          "name": {
            "en": "Category Name",
            "ar": "اسم الفئة"
          }
        }
      ],
      "images": [
        {
          "id": 1,
          "image": "http://example.com/storage/products/image1.jpg"
        }
      ],
      "price": 99.99,
      "stock": 50,
      "type": "simple",
      "rating": {
        "average": 4.5,
        "count": 25
      },
      "variants": [],
      "variant_options": [],
      "variants_summary": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

**Important Notes:**
- Products are filtered to show only active and approved products
- `is_favorite` is only included if the user is authenticated
- For variable products, `variants`, `variant_options`, and `variants_summary` are included

---

### Get Product

**GET** `/api/products/{product}`

Get detailed information about a specific product.

**Authentication:** Optional (public endpoint)

**URL Parameters:**
- `product` (integer): Product ID

**Success Response (200):**
```json
{
  "id": 1,
  "name": {
    "en": "Product Name",
    "ar": "اسم المنتج"
  },
  "slug": "product-name",
  "thumb_image": "http://example.com/storage/products/image.jpg",
  "description": {
    "en": "Product description",
    "ar": "وصف المنتج"
  },
  "discount": 10,
  "discount_type": "percentage",
  "is_active": true,
  "is_favorite": false,
  "is_featured": true,
  "is_approved": true,
  "is_bookable": false,
  "is_new": true,
  "vendor": {
    "id": 1,
    "name": "Vendor Name",
    "image": "http://example.com/storage/vendors/image.jpg",
    "rating": {
      "average": 4.5,
      "count": 120
    }
  },
  "categories": [
    {
      "id": 1,
      "name": {
        "en": "Category Name",
        "ar": "اسم الفئة"
      }
    }
  ],
  "images": [
    {
      "id": 1,
      "image": "http://example.com/storage/products/image1.jpg"
    }
  ],
  "related_products": [
    {
      "id": 2,
      "name": {
        "en": "Related Product",
        "ar": "منتج ذو صلة"
      }
    }
  ],
  "price": 99.99,
  "stock": 50,
  "type": "simple",
  "rating": {
    "average": 4.5,
    "count": 25
  },
  "ratings": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "name": "User Name"
      },
      "rating": 5,
      "comment": "Great product!",
      "created_at": "2026-01-15T12:00:00.000000Z"
    }
  ],
  "variants": [],
  "variant_options": [],
  "variants_summary": null
}
```

**Error Responses:**
- `404`: Product not found or not approved/active

**Important Notes:**
- Includes related products (manual or auto-generated)
- For variable products, includes all variants and variant options
- Ratings are filtered to show only visible ratings

---

### Toggle Favorite Product

**POST** `/api/products/{product}/toggle-favorite`

Add or remove a product from the user's favorite list.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `product` (integer): Product ID

**Success Response (200):**
```json
{
  "message": "Added to favorites"
}
```

OR

```json
{
  "message": "Removed from favorites"
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Product not found

---

### Get Favorite List

**GET** `/api/favorite-list`

Get all products in the authenticated user's favorite list.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": {
        "en": "Product Name",
        "ar": "اسم المنتج"
      },
      "is_favorite": true
    }
  ]
}
```

**Error Responses:**
- `401`: Unauthenticated

---

## Categories

### List Categories

**GET** `/api/categories`

Get a paginated list of categories.

**Authentication:** Optional (public endpoint)

**Query Parameters:**
- `per_page` (integer, default: 15): Number of items per page
- `search` (string): Search in category name
- `status` (string): Filter by status (`active`, `inactive`)
- `featured` (string): Filter by featured status (`1` or `0`)
- `parent_id` (integer): Filter by parent category ID (use `0` for root categories)
- `sort` (string): Sort order (`name_asc`, `name_desc`, `newest`, `oldest`)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": {
        "en": "Category Name",
        "ar": "اسم الفئة"
      },
      "image": "http://example.com/storage/categories/image.jpg",
      "is_active": true,
      "is_featured": true,
      "parent": null,
      "children": [
        {
          "id": 2,
          "name": {
            "en": "Subcategory",
            "ar": "فئة فرعية"
          }
        }
      ],
      "products": []
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

**Important Notes:**
- Use `parent_id=0` to get only root categories
- Use `parent_id={id}` to get subcategories of a specific category
- `children` and `products` are only included if explicitly loaded

---

### Get Category

**GET** `/api/categories/{category}`

Get detailed information about a specific category.

**Authentication:** Optional (public endpoint)

**URL Parameters:**
- `category` (integer): Category ID

**Success Response (200):**
```json
{
  "id": 1,
  "name": {
    "en": "Category Name",
    "ar": "اسم الفئة"
  },
  "image": "http://example.com/storage/categories/image.jpg",
  "is_active": true,
  "is_featured": true,
  "parent": {
    "id": 0,
    "name": {
      "en": "Parent Category",
      "ar": "الفئة الرئيسية"
    }
  },
  "children": [
    {
      "id": 2,
      "name": {
        "en": "Subcategory",
        "ar": "فئة فرعية"
      }
    }
  ],
  "products": [
    {
      "id": 1,
      "name": {
        "en": "Product Name",
        "ar": "اسم المنتج"
      }
    }
  ]
}
```

**Error Responses:**
- `404`: Category not found

---

## Vendors

### List Vendors

**GET** `/api/vendors`

Get a paginated list of active vendors.

**Authentication:** Optional (public endpoint)

**Query Parameters:**
- `per_page` (integer, default: 15): Number of items per page
- `search` (string): Search in vendor name
- `featured` (string): Filter by featured status (`1` or `0`)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Vendor Name",
      "image": "http://example.com/storage/vendors/image.jpg",
      "phone": "+1234567890",
      "address": "123 Vendor Street",
      "rating": {
        "average": 4.5,
        "count": 120
      },
      "products": [],
      "branches": []
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

**Important Notes:**
- Only active vendors are returned
- `products` and `branches` are only included if explicitly loaded

---

### Get Vendor

**GET** `/api/vendors/{vendor}`

Get detailed information about a specific vendor.

**Authentication:** Optional (public endpoint)

**URL Parameters:**
- `vendor` (integer): Vendor ID

**Success Response (200):**
```json
{
  "id": 1,
  "name": "Vendor Name",
  "image": "http://example.com/storage/vendors/image.jpg",
  "phone": "+1234567890",
  "address": "123 Vendor Street",
  "rating": {
    "average": 4.5,
    "count": 120
  },
  "products": [
    {
      "id": 1,
      "name": {
        "en": "Product Name",
        "ar": "اسم المنتج"
      }
    }
  ],
  "branches": [
    {
      "id": 1,
      "name": "Branch Name",
      "address": "123 Branch Street"
    }
  ]
}
```

**Error Responses:**
- `404`: Vendor not found or not active

---

## Cart Management

### Get Cart

**GET** `/api/cart`

Get all items in the authenticated user's cart.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "product": {
        "id": 1,
        "name": {
          "en": "Product Name",
          "ar": "اسم المنتج"
        },
        "price": 99.99,
        "discount": 10,
        "discount_type": "percentage"
      },
      "variant": null,
      "quantity": 2,
      "subtotal": 179.98
    },
    {
      "id": 2,
      "product": {
        "id": 2,
        "name": {
          "en": "Variable Product",
          "ar": "منتج متغير"
        }
      },
      "variant": {
        "id": 1,
        "name": {
          "en": "Red - Large",
          "ar": "أحمر - كبير"
        },
        "price": 149.99,
        "stock": 10
      },
      "quantity": 1,
      "subtotal": 149.99
    }
  ]
}
```

**Error Responses:**
- `401`: Unauthenticated

**Important Notes:**
- `subtotal` is calculated after applying product discounts
- For variable products, `variant` contains the selected variant details

---

### Add Product to Cart

**POST** `/api/cart/{product}`

Add a product to the authenticated user's cart.

**Authentication:** Required  
**Rate Limit:** 30 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `product` (integer): Product ID

**Request Body (for variable products):**
```json
{
  "variant_id": 1
}
```

**Request Body (for simple products):**
```json
{}
```

**Validation Rules:**
- `variant_id` (required for variable products, integer, exists:product_variants,id): Variant ID for variable products

**Success Response (200):**
```json
{
  "id": 1,
  "product": {
    "id": 1,
    "name": {
      "en": "Product Name",
      "ar": "اسم المنتج"
    }
  },
  "variant": null,
  "quantity": 1,
  "subtotal": 99.99
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Product not found
- `422`: Validation errors (variant_id required for variable products, variant not found, out of stock)
- `429`: Too many requests

**Important Notes:**
- If the product already exists in the cart, the quantity is incremented
- For variable products, `variant_id` is required
- Stock availability is checked before adding to cart

---

### Update Cart Item Quantity

**PUT** `/api/cart/{product}`

Update the quantity of a product in the cart.

**Authentication:** Required  
**Rate Limit:** 30 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `product` (integer): Product ID

**Request Body:**
```json
{
  "quantity": 3,
  "variant_id": 1
}
```

**Validation Rules:**
- `quantity` (required, integer, min:1): New quantity
- `variant_id` (required for variable products, integer, exists:product_variants,id): Variant ID for variable products

**Success Response (200):**
```json
{
  "id": 1,
  "product": {
    "id": 1,
    "name": {
      "en": "Product Name",
      "ar": "اسم المنتج"
    }
  },
  "variant": null,
  "quantity": 3,
  "subtotal": 299.97
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Product not found in cart
- `422`: Validation errors (invalid quantity, insufficient stock)
- `429`: Too many requests

---

### Remove Product from Cart

**DELETE** `/api/cart/{product}`

Remove a product from the cart.

**Authentication:** Required  
**Rate Limit:** 30 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `product` (integer): Product ID

**Request Body (for variable products):**
```json
{
  "variant_id": 1
}
```

**Validation Rules:**
- `variant_id` (sometimes, required for variable products, integer, exists:product_variants,id): Variant ID for variable products

**Success Response (200):**
```json
{
  "message": "Product removed from cart"
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Product not found in cart
- `422`: Validation errors
- `429`: Too many requests

---

### Clear Cart

**DELETE** `/api/cart`

Clear all items from the authenticated user's cart.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "message": "Cart cleared"
}
```

**Error Responses:**
- `401`: Unauthenticated
- `429`: Too many requests

---

### Apply Coupon

**POST** `/api/cart/apply-coupon`

Apply a coupon code to the cart.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "code": "SAVE10"
}
```

**Validation Rules:**
- `code` (required, string): Coupon code

**Success Response (200):**
```json
{
  "success": true,
  "message": "Coupon applied successfully.",
  "data": {
    "coupon": {
      "id": 1,
      "code": "SAVE10",
      "type": "percentage",
      "discount_value": 10
    },
    "cart_subtotal": 299.97,
    "discount": 29.997,
    "total": 269.973
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `422`: Validation errors (invalid coupon code, coupon expired, minimum amount not met, etc.)
- `429`: Too many requests

**Important Notes:**
- Coupon validation includes checking expiry date, usage limits, minimum order amount, etc.
- The discount is calculated based on the coupon type (percentage or fixed amount)

---

## Orders

### List Orders

**GET** `/api/orders`

Get a paginated list of orders for the authenticated user.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page` (integer, default: 15): Number of items per page
- `search` (string): Search in order ID or notes
- `status` (string): Filter by order status (`pending`, `processing`, `shipped`, `delivered`, `cancelled`)
- `payment_status` (string): Filter by payment status (`pending`, `paid`, `refunded`)
- `payment_method` (string): Filter by payment method
- `refund_status` (string): Filter by refund status (`pending`, `approved`, `rejected`)
- `vendor_id` (integer): Filter by vendor ID
- `branch_id` (integer): Filter by branch ID
- `from_date` (date): Filter orders from this date (YYYY-MM-DD)
- `to_date` (date): Filter orders to this date (YYYY-MM-DD)
- `min_total` (numeric): Minimum order total
- `max_total` (numeric): Maximum order total
- `sort` (string): Sort order (`newest`, `oldest`, `total_asc`, `total_desc`)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "coupon_id": 1,
      "address_id": 1,
      "sub_total": 299.97,
      "order_discount": 29.997,
      "coupon_discount": 10.00,
      "total_shipping": 15.00,
      "points_discount": 5.00,
      "wallet_used": 10.00,
      "total": 260.973,
      "total_commission": 26.097,
      "status": "pending",
      "payment_status": "pending",
      "notes": "Please deliver in the morning",
      "user": {
        "id": 1,
        "name": "John Doe"
      },
      "coupon": {
        "id": 1,
        "code": "SAVE10"
      },
      "address": {
        "id": 1,
        "name": "John Doe",
        "address": "123 Main Street"
      },
      "vendor_orders": [
        {
          "id": 1,
          "vendor_id": 1,
          "status": "pending",
          "total": 260.973
        }
      ],
      "vendor_orders_count": 1
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

**Error Responses:**
- `401`: Unauthenticated

---

### Get Order

**GET** `/api/orders/{order}`

Get detailed information about a specific order.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `order` (integer): Order ID

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "coupon_id": 1,
    "address_id": 1,
    "sub_total": 299.97,
    "order_discount": 29.997,
    "coupon_discount": 10.00,
    "total_shipping": 15.00,
    "points_discount": 5.00,
    "wallet_used": 10.00,
    "total": 260.973,
    "total_commission": 26.097,
    "status": "pending",
    "payment_status": "pending",
    "notes": "Please deliver in the morning",
    "user": {
      "id": 1,
      "name": "John Doe"
    },
    "coupon": {
      "id": 1,
      "code": "SAVE10"
    },
    "address": {
      "id": 1,
      "name": "John Doe",
      "address": "123 Main Street",
      "city": "New York",
      "state": "NY"
    },
    "vendor_orders": [
      {
        "id": 1,
        "vendor_id": 1,
        "status": "pending",
        "total": 260.973,
        "items": [
          {
            "id": 1,
            "product_id": 1,
            "variant_id": null,
            "quantity": 2,
            "price": 99.99
          }
        ]
      }
    ],
    "vendor_orders_count": 1
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Order does not belong to the authenticated user
- `404`: Order not found

---

### Create Order

**POST** `/api/orders`

Create a new order from the authenticated user's cart.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "address_id": 1,
  "coupon_code": "SAVE10",
  "use_wallet": true,
  "use_points": true,
  "notes": "Please deliver in the morning"
}
```

**Validation Rules:**
- `address_id` (required, integer, exists:addresses,id): Delivery address ID
- `coupon_code` (nullable, string, exists:coupons,code): Coupon code to apply
- `use_wallet` (nullable, boolean): Whether to use available wallet balance
- `use_points` (nullable, boolean): Whether to use available points
- `notes` (nullable, string): Order notes

**Success Response (201):**
```json
{
  "success": true,
  "message": "Order created successfully.",
  "data": {
    "id": 1,
    "user_id": 1,
    "address_id": 1,
    "sub_total": 299.97,
    "order_discount": 29.997,
    "coupon_discount": 10.00,
    "total_shipping": 15.00,
    "points_discount": 5.00,
    "wallet_used": 10.00,
    "total": 260.973,
    "status": "pending",
    "payment_status": "pending",
    "notes": "Please deliver in the morning"
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `422`: Validation errors (empty cart, invalid address, invalid coupon, insufficient stock, etc.)
- `429`: Too many requests

**Important Notes:**
- The cart is automatically cleared after successful order creation
- Order discount is calculated automatically from product discounts
- Shipping cost is calculated based on the delivery address
- If `use_wallet` or `use_points` is true, all available balance/points are used

---

### Calculate Shipping

**POST** `/api/orders/calculate-shipping`

Calculate shipping costs for the cart without creating an order.

**Authentication:** Required  
**Rate Limit:** 30 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "address_id": 1
}
```

**Validation Rules:**
- `address_id` (required, integer, exists:addresses,id): Delivery address ID

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "shipping_per_vendor": [
      {
        "vendor_id": 1,
        "vendor_name": "Vendor Name",
        "shipping_cost": 15.00
      }
    ],
    "total_shipping": 15.00
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `422`: Validation errors (empty cart, invalid address, etc.)
- `429`: Too many requests

**Important Notes:**
- This endpoint does not create an order, it only calculates shipping costs
- Useful for displaying shipping costs before order placement

---

### Cancel Order

**POST** `/api/orders/{order}/cancel`

Cancel an order (if possible).

**Authentication:** Required  
**Rate Limit:** 5 requests per minute

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `order` (integer): Order ID

**Success Response (200):**
```json
{
  "success": true,
  "message": "Order cancelled successfully.",
  "data": {
    "id": 1,
    "status": "cancelled",
    "payment_status": "pending"
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Order does not belong to the authenticated user
- `404`: Order not found
- `422`: Order cannot be cancelled at this stage
- `429`: Too many requests

**Important Notes:**
- Orders can only be cancelled if they are in `pending` or `processing` status
- Cancelled orders may be eligible for refund (use refund request endpoint)

---

### Reorder

**POST** `/api/orders/{order}/reorder`

Add all items from a previous order to the cart.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `order` (integer): Order ID

**Success Response (200):**
```json
{
  "success": true,
  "message": "Items added to cart successfully.",
  "data": {
    "added": 3,
    "skipped": 1
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Order does not belong to the authenticated user
- `404`: Order not found
- `422`: Validation errors (products no longer available, out of stock, etc.)
- `429`: Too many requests

**Important Notes:**
- Items that are no longer available or out of stock are skipped
- The response indicates how many items were added and how many were skipped

---

### Pay Order

**POST** `/api/orders/{order}/pay`

Mark an order as paid (used after successful payment gateway integration).

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `order` (integer): Order ID

**Request Body:**
```json
{
  "payment_method": "credit_card"
}
```

**Validation Rules:**
- `payment_method` (required, string, max:50): Payment method used

**Success Response (200):**
```json
{
  "success": true,
  "message": "Order paid successfully.",
  "data": {
    "id": 1,
    "payment_status": "paid",
    "payment_method": "credit_card"
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Order does not belong to the authenticated user
- `404`: Order not found
- `422`: Validation errors
- `429`: Too many requests

---

### Request Refund

**POST** `/api/orders/{order}/refund-request`

Submit a refund request for an order.

**Authentication:** Required  
**Rate Limit:** 5 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `order` (integer): Order ID

**Request Body:**
```json
{
  "reason": "Product damaged",
  "details": "The product arrived damaged and unusable."
}
```

**Validation Rules:**
- `reason` (nullable, string, max:255): Reason for refund request
- `details` (nullable, string, max:2000): Additional details

**Success Response (201):**
```json
{
  "success": true,
  "message": "Refund request submitted successfully.",
  "data": {
    "id": 1,
    "status": "pending"
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Order does not belong to the authenticated user
- `404`: Order not found
- `422`: Validation errors (order not eligible for refund, refund already requested, etc.)
- `429`: Too many requests

**Important Notes:**
- Refund requests are subject to approval by administrators
- Not all orders are eligible for refund (check order status and payment status)

---

## Tickets

### List Tickets

**GET** `/api/tickets`

Get a paginated list of tickets for the authenticated user.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page` (integer, default: 15): Number of items per page
- `search` (string): Search in ticket subject or description
- `status` (string): Filter by status (`pending`, `resolved`, `closed`)
- `from_date` (date): Filter tickets from this date (YYYY-MM-DD)
- `to_date` (date): Filter tickets to this date (YYYY-MM-DD)
- `sort` (string): Sort order (`newest`, `oldest`)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "vendor_id": null,
      "subject": "Product Issue",
      "description": "I have an issue with my order",
      "status": "pending",
      "ticket_from": "user",
      "attachments": [
        "http://example.com/storage/tickets/attachment1.jpg"
      ],
      "user": {
        "id": 1,
        "name": "John Doe"
      },
      "vendor": null,
      "messages": [],
      "messages_count": 0,
      "created_at": "2026-01-15T12:00:00.000000Z",
      "updated_at": "2026-01-15T12:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

**Error Responses:**
- `401`: Unauthenticated

---

### Get Ticket

**GET** `/api/tickets/{ticket}`

Get detailed information about a specific ticket including all messages.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `ticket` (integer): Ticket ID

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "vendor_id": null,
    "subject": "Product Issue",
    "description": "I have an issue with my order",
    "status": "pending",
    "ticket_from": "user",
    "attachments": [
      "http://example.com/storage/tickets/attachment1.jpg"
    ],
    "user": {
      "id": 1,
      "name": "John Doe"
    },
    "vendor": null,
    "messages": [
      {
        "id": 1,
        "ticket_id": 1,
        "message": "We will look into this issue.",
        "sender_type": "admin",
        "sender_id": 1,
        "sender": {
          "id": 1,
          "name": "Admin User"
        },
        "attachments": [],
        "created_at": "2026-01-15T13:00:00.000000Z"
      }
    ],
    "messages_count": 1,
    "created_at": "2026-01-15T12:00:00.000000Z",
    "updated_at": "2026-01-15T13:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Ticket does not belong to the authenticated user
- `404`: Ticket not found

---

### Create Ticket

**POST** `/api/tickets`

Create a new support ticket.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body:**
```json
{
  "vendor_id": 1,
  "subject": "Product Issue",
  "description": "I have an issue with my order",
  "attachments": ["<file1>", "<file2>"]
}
```

**Validation Rules:**
- `vendor_id` (nullable, integer, exists:vendors,id): Vendor ID if ticket is related to a vendor
- `subject` (required, string, max:255): Ticket subject
- `description` (required, string, max:2000): Ticket description
- `attachments` (nullable, array of files): Attachment files (images, documents)

**Success Response (201):**
```json
{
  "success": true,
  "message": "Ticket created successfully.",
  "data": {
    "id": 1,
    "user_id": 1,
    "vendor_id": 1,
    "subject": "Product Issue",
    "description": "I have an issue with my order",
    "status": "pending",
    "ticket_from": "user",
    "attachments": [
      "http://example.com/storage/tickets/attachment1.jpg"
    ],
    "messages_count": 0
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `422`: Validation errors
- `429`: Too many requests

**Important Notes:**
- Use `multipart/form-data` content type when uploading attachments
- `ticket_from` is automatically set to `"user"` for user-created tickets

---

### Update Ticket

**PUT** `/api/tickets/{ticket}`

Update a ticket's subject, description, or attachments.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**URL Parameters:**
- `ticket` (integer): Ticket ID

**Request Body (all fields optional):**
```json
{
  "subject": "Updated Subject",
  "description": "Updated description",
  "attachments": ["<file1>"]
}
```

**Validation Rules:**
- `subject` (sometimes, string, max:255): Ticket subject
- `description` (sometimes, string, max:2000): Ticket description
- `attachments` (sometimes, array of files): Attachment files

**Success Response (200):**
```json
{
  "success": true,
  "message": "Ticket updated successfully.",
  "data": {
    "id": 1,
    "subject": "Updated Subject",
    "description": "Updated description"
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Ticket does not belong to the authenticated user
- `404`: Ticket not found
- `422`: Validation errors
- `429`: Too many requests

---

### Add Message to Ticket

**POST** `/api/tickets/{ticket}/add-message`

Add a message to an existing ticket.

**Authentication:** Required  
**Rate Limit:** 20 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**URL Parameters:**
- `ticket` (integer): Ticket ID

**Request Body:**
```json
{
  "message": "This is my response",
  "attachments": ["<file1>"]
}
```

**Validation Rules:**
- `message` (required, string, max:2000): Message content
- `attachments` (nullable, array of files): Attachment files

**Success Response (201):**
```json
{
  "success": true,
  "message": "Message added successfully.",
  "data": {
    "id": 1,
    "ticket_id": 1,
    "message": "This is my response",
    "sender_type": "user",
    "sender_id": 1,
    "sender": {
      "id": 1,
      "name": "John Doe"
    },
    "attachments": [],
    "created_at": "2026-01-15T14:00:00.000000Z"
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Ticket does not belong to the authenticated user
- `404`: Ticket not found
- `422`: Validation errors
- `429`: Too many requests

---

### Update Ticket Status

**POST** `/api/tickets/{ticket}/update-status`

Update the status of a ticket.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `ticket` (integer): Ticket ID

**Request Body:**
```json
{
  "status": "resolved"
}
```

**Validation Rules:**
- `status` (required, string, in:pending,resolved,closed): New ticket status

**Success Response (200):**
```json
{
  "success": true,
  "message": "Ticket status updated successfully.",
  "data": {
    "id": 1,
    "status": "resolved"
  }
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Ticket does not belong to the authenticated user
- `404`: Ticket not found
- `422`: Validation errors (invalid status)
- `429`: Too many requests

---

### Delete Ticket

**DELETE** `/api/tickets/{ticket}`

Delete a ticket.

**Authentication:** Required  
**Rate Limit:** 5 requests per minute

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `ticket` (integer): Ticket ID

**Success Response (200):**
```json
{
  "success": true,
  "message": "Ticket deleted successfully."
}
```

**Error Responses:**
- `401`: Unauthenticated
- `403`: Ticket does not belong to the authenticated user
- `404`: Ticket not found
- `429`: Too many requests

---

## Ratings

### Rate Product

**POST** `/api/products/{product}/rate`

Rate a product (1-5 stars) with an optional comment.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `product` (integer): Product ID

**Request Body:**
```json
{
  "rating": 5,
  "comment": "Excellent product! Highly recommended."
}
```

**Validation Rules:**
- `rating` (required, integer, min:1, max:5): Rating value (1-5 stars)
- `comment` (nullable, string, max:1000): Optional review comment

**Success Response (200):**
```json
{
  "success": true,
  "message": "Product rated successfully."
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Product not found
- `422`: Validation errors
- `429`: Too many requests

**Important Notes:**
- If the user has already rated the product, the rating is updated
- Ratings may be moderated before being visible to other users

---

### Rate Vendor

**POST** `/api/vendors/{vendor}/rate`

Rate a vendor (1-5 stars) with an optional comment.

**Authentication:** Required  
**Rate Limit:** 10 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `vendor` (integer): Vendor ID

**Request Body:**
```json
{
  "rating": 4,
  "comment": "Great service and fast delivery."
}
```

**Validation Rules:**
- `rating` (required, integer, min:1, max:5): Rating value (1-5 stars)
- `comment` (nullable, string, max:1000): Optional review comment

**Success Response (200):**
```json
{
  "success": true,
  "message": "Vendor rated successfully."
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Vendor not found
- `422`: Validation errors
- `429`: Too many requests

**Important Notes:**
- If the user has already rated the vendor, the rating is updated
- Ratings may be moderated before being visible to other users

---

## Reports

### Report Product

**POST** `/api/products/{product}/report`

Report a product for inappropriate content or policy violations.

**Authentication:** Required  
**Rate Limit:** 5 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `product` (integer): Product ID

**Request Body:**
```json
{
  "reason": "Inappropriate content",
  "description": "This product contains inappropriate images."
}
```

**Validation Rules:**
- `reason` (nullable, string, max:255): Reason for reporting
- `description` (nullable, string, max:2000): Detailed description

**Success Response (200):**
```json
{
  "success": true,
  "message": "Product reported successfully."
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Product not found
- `422`: Validation errors
- `429`: Too many requests

**Important Notes:**
- Reports are reviewed by administrators
- The reporting user may remain anonymous

---

### Report Vendor

**POST** `/api/vendors/{vendor}/report`

Report a vendor for inappropriate behavior or policy violations.

**Authentication:** Required  
**Rate Limit:** 5 requests per minute

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `vendor` (integer): Vendor ID

**Request Body:**
```json
{
  "reason": "Fraudulent behavior",
  "description": "The vendor is not delivering products as described."
}
```

**Validation Rules:**
- `reason` (nullable, string, max:255): Reason for reporting
- `description` (nullable, string, max:2000): Detailed description

**Success Response (200):**
```json
{
  "success": true,
  "message": "Vendor reported successfully."
}
```

**Error Responses:**
- `401`: Unauthenticated
- `404`: Vendor not found
- `422`: Validation errors
- `429`: Too many requests

**Important Notes:**
- Reports are reviewed by administrators
- The reporting user may remain anonymous

---

## Transactions

### Get Wallet History

**GET** `/api/wallet/history`

Get transaction history for the authenticated user's wallet.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page` (integer, default: 15): Number of items per page
- `type` (string): Filter by transaction type (`credit`, `debit`)
- `from_date` (date): Filter transactions from this date (YYYY-MM-DD)
- `to_date` (date): Filter transactions to this date (YYYY-MM-DD)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "type": "credit",
      "amount": 100.00,
      "balance_after": 100.00,
      "description": "Order refund",
      "created_at": "2026-01-15T12:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 1,
      "type": "debit",
      "amount": 50.00,
      "balance_after": 50.00,
      "description": "Order payment",
      "created_at": "2026-01-14T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

**Error Responses:**
- `401`: Unauthenticated

---

### Get Points History

**GET** `/api/points/history`

Get transaction history for the authenticated user's points.

**Authentication:** Required

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `per_page` (integer, default: 15): Number of items per page
- `from_date` (date): Filter transactions from this date (YYYY-MM-DD)
- `to_date` (date): Filter transactions to this date (YYYY-MM-DD)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "type": "credit",
      "points": 100,
      "balance_after": 100,
      "description": "Referral bonus",
      "created_at": "2026-01-15T12:00:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 1,
      "type": "debit",
      "points": 50,
      "balance_after": 50,
      "description": "Order discount",
      "created_at": "2026-01-14T10:00:00.000000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 75
  }
}
```

**Error Responses:**
- `401`: Unauthenticated

---

## Sliders

### List Sliders

**GET** `/api/sliders`

Get all active sliders for display on the homepage or app.

**Authentication:** Optional (public endpoint)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "title": {
        "en": "Summer Sale",
        "ar": "تخفيضات الصيف"
      },
      "description": {
        "en": "Up to 50% off",
        "ar": "خصم يصل إلى 50%"
      },
      "image": "http://example.com/storage/sliders/summer-sale.jpg",
      "link": "http://example.com/products?category=summer",
      "is_active": true,
      "order": 1
    }
  ]
}
```

**Important Notes:**
- Only active sliders are returned
- Sliders are ordered by the `order` field
- `link` is optional and can be used for navigation

---

## Password Reset

### Send Reset Code

**POST** `/api/auth/reset-password/send-code`

Send a password reset code to the user's email or phone.

**Rate Limit:** 5 requests per minute

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

OR

```json
{
  "phone": "+1234567890"
}
```

**Validation Rules:**
- `email` (nullable, email, exists:users,email): User's email address
- `phone` (nullable, string, exists:users,phone): User's phone number
- At least one of `email` or `phone` must be provided

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password reset code sent successfully."
}
```

**Error Responses:**
- `404`: User not found
- `422`: Validation errors (email/phone not found)
- `429`: Too many requests

**Important Notes:**
- A 6-digit OTP code is generated and sent via email or SMS
- The code expires after 10 minutes

---

### Verify Reset Code

**POST** `/api/auth/reset-password/verify-code`

Verify the password reset code and receive a reset token.

**Rate Limit:** 10 requests per minute

**Request Body:**
```json
{
  "email": "user@example.com",
  "code": "123456"
}
```

OR

```json
{
  "phone": "+1234567890",
  "code": "123456"
}
```

**Validation Rules:**
- `email` (nullable, email, exists:users,email): User's email address
- `phone` (nullable, string, exists:users,phone): User's phone number
- `code` (required, digits:6): 6-digit verification code
- At least one of `email` or `phone` must be provided

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password reset code verified successfully.",
  "data": {
    "reset_token": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

**Error Responses:**
- `400`: Invalid or expired verification code
- `404`: User not found
- `422`: Validation errors
- `429`: Too many requests

**Important Notes:**
- The reset token expires after 30 minutes
- Use this token in the next step to set a new password

---

### Set New Password

**POST** `/api/auth/reset-password/set-new-password`

Set a new password using the reset token.

**Rate Limit:** 5 requests per minute

**Request Body:**
```json
{
  "reset_token": "550e8400-e29b-41d4-a716-446655440000",
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

**Validation Rules:**
- `reset_token` (required, string): Reset token from verify-code step
- `password` (required, string, confirmed, min:8): New password
- `password_confirmation` (required, string): Must match password

**Success Response (200):**
```json
{
  "success": true,
  "message": "Password reset successfully. Please login with your new password.",
  "data": {
    "user": {
      "id": 1,
      "email": "user@example.com"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
  }
}
```

**Error Responses:**
- `400`: Invalid or expired reset token
- `404`: User not found
- `422`: Validation errors (password mismatch, etc.)
- `429`: Too many requests

**Important Notes:**
- After successful password reset, a token is automatically generated for immediate login
- The reset token is invalidated after use

---

## Postman Collection

A complete Postman collection is available in the project root:

- **Collection**: `postman_collection.json`
- **Environment**: `postman_environment.json`

### Import Instructions

1. Open Postman
2. Click **Import** button
3. Select `postman_collection.json` file
4. Import the environment file (`postman_environment.json`)
5. Set the `base_url` variable in the environment (default: `http://localhost:8000`)

### Using the Collection

1. **Set Environment Variables:**
   - `base_url`: Your API base URL (e.g., `http://localhost:8000`)
   - `auth_token`: Will be automatically set after login (or set manually)

2. **Login:**
   - Use the **Login** request in the Authentication folder
   - Copy the `token` from the response
   - Set it in the `auth_token` environment variable (or use Postman's automatic token extraction)

3. **Use Authenticated Endpoints:**
   - All protected endpoints automatically use the `auth_token` variable
   - Make sure you're logged in before using protected endpoints

---

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created Successfully |
| 400 | Bad Request (invalid data, expired token, etc.) |
| 401 | Unauthenticated (missing or invalid token) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found (resource does not exist) |
| 422 | Validation Error (invalid input data) |
| 429 | Too Many Requests (rate limit exceeded) |
| 500 | Server Error |

---

## Data Models

### User Model

| Field             | Type | Description |
|-------------------|------|-------------|
| id                | integer | User ID |
| name              | string | User's full name |
| email             | string | Email address |
| phone             | string\|null | Phone number |
| image             | string | Profile image URL |
| email_verified_at | datetime\|null | Email verification timestamp |
| phone_verified_at | datetime\|null | Phone verification timestamp |
| is_active         | boolean | Account active status |
| is_verified       | boolean | Account verification status |
| roles             | array | User roles |
| permissions       | array | User permissions |
| created_at        | datetime | Account creation timestamp |
| updated_at        | datetime | Last update timestamp |

### Product Model

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Product ID |
| name | object | Product name (multilingual: en, ar) |
| slug | string | URL-friendly product identifier |
| thumb_image | string | Main product image URL |
| description | object | Product description (multilingual) |
| discount | numeric\|null | Discount value |
| discount_type | string | Discount type (`percentage` or `fixed`) |
| is_active | boolean | Product active status |
| is_favorite | boolean | Whether product is in user's favorites (authenticated users only) |
| is_featured | boolean | Featured product flag |
| is_approved | boolean | Product approval status |
| is_bookable | boolean | Whether product can be booked |
| is_new | boolean | New product flag |
| price | numeric | Product price (calculated) |
| stock | integer | Product stock (calculated) |
| type | string | Product type (`simple` or `variable`) |
| rating | object | Product rating summary (`average`, `count`) |
| variants | array | Product variants (for variable products) |
| variant_options | array | Available variant options (for variable products) |

### Order Model

| Field | Type | Description |
|-------|------|-------------|
| id | integer | Order ID |
| user_id | integer | User ID |
| coupon_id | integer\|null | Applied coupon ID |
| address_id | integer | Delivery address ID |
| sub_total | numeric | Subtotal before discounts |
| order_discount | numeric | Discount from product discounts |
| coupon_discount | numeric | Discount from coupon |
| total_shipping | numeric | Total shipping cost |
| points_discount | numeric | Discount from points |
| wallet_used | numeric | Amount used from wallet |
| total | numeric | Final order total |
| total_commission | numeric | Total commission for vendors |
| status | string | Order status (`pending`, `processing`, `shipped`, `delivered`, `cancelled`) |
| payment_status | string | Payment status (`pending`, `paid`, `refunded`) |
| notes | string\|null | Order notes |

---

## Notes

- All datetime fields are returned in ISO 8601 format
- Image URLs are absolute URLs
- When email or phone is updated, verification status is reset
- Old profile images are automatically deleted when a new one is uploaded
- Password must meet Laravel's default requirements (minimum 8 characters)
- All multilingual fields support English (`en`) and Arabic (`ar`)
- Use `Accept-Language` header for localized responses (e.g., `Accept-Language: ar`)
- Pagination metadata is included in list endpoints
- Stock calculations consider branch-level inventory for multi-branch vendors

---

## Support

For issues or questions, please contact the development team.

---

**Last Updated**: January 2026  
**API Version**: 1.0.0
