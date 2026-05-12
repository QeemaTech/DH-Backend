# Rate Limiting Configuration
## Multi-Vendor E-Commerce API

This document describes the rate limiting configuration for the API endpoints.

## Global API Rate Limiting

All API routes have a **global rate limit of 60 requests per minute** per IP address. This is configured in `bootstrap/app.php` using Laravel's throttle middleware.

## Endpoint-Specific Rate Limiting

In addition to the global limit, sensitive endpoints have stricter rate limits:

### Authentication Endpoints

| Endpoint | Rate Limit | Description |
|----------|------------|-------------|
| `POST /api/auth/register` | 5 requests/minute | Prevents spam registrations |
| `POST /api/auth/login` | 5 requests/minute | Handled in LoginRequest (prevents brute force) |
| `POST /api/auth/verify-email` | 10 requests/minute | Email verification attempts |
| `POST /api/auth/verify-phone` | 10 requests/minute | Phone verification attempts |
| `POST /api/auth/resend-verification-code` | 3 requests/minute | Prevents spam verification code requests |

### Password Reset Endpoints

| Endpoint | Rate Limit | Description |
|----------|------------|-------------|
| `POST /api/auth/reset-password/send-code` | 5 requests/minute | Prevents spam password reset requests |
| `POST /api/auth/reset-password/verify-code` | 10 requests/minute | Password reset code verification |
| `POST /api/auth/reset-password/set-new-password` | 5 requests/minute | Setting new password |

### User Profile Endpoints

| Endpoint | Rate Limit | Description |
|----------|------------|-------------|
| `PUT /api/profile` | 10 requests/minute | Profile updates |
| `PUT /api/password` | 5 requests/minute | Password changes |

### Address Endpoints

| Endpoint | Rate Limit | Description |
|----------|------------|-------------|
| `POST /api/addresses` | 10 requests/minute | Creating addresses |
| `DELETE /api/addresses/{id}` | 10 requests/minute | Deleting addresses |

### Cart Endpoints

| Endpoint | Rate Limit | Description |
|----------|------------|-------------|
| `POST /api/cart/{product}` | 30 requests/minute | Adding items to cart |
| `PUT /api/cart/{product}` | 30 requests/minute | Updating cart quantities |
| `DELETE /api/cart/{product}` | 30 requests/minute | Removing items from cart |
| `DELETE /api/cart` | 10 requests/minute | Clearing entire cart |
| `POST /api/cart/apply-coupon` | 10 requests/minute | Applying coupon codes |

### Order Endpoints

| Endpoint | Rate Limit | Description |
|----------|------------|-------------|
| `POST /api/orders` | 10 requests/minute | Creating orders (prevents spam orders) |
| `POST /api/orders/calculate-shipping` | 30 requests/minute | Calculating shipping costs |
| `POST /api/orders/{order}/cancel` | 5 requests/minute | Cancelling orders |
| `POST /api/orders/{order}/reorder` | 10 requests/minute | Reordering items |
| `POST /api/orders/{order}/pay` | 10 requests/minute | Payment processing |
| `POST /api/orders/{order}/refund-request` | 5 requests/minute | Refund requests |

### Rating Endpoints

| Endpoint | Rate Limit | Description |
|----------|------------|-------------|
| `POST /api/products/{product}/rate` | 10 requests/minute | Rating products |
| `POST /api/vendors/{vendor}/rate` | 10 requests/minute | Rating vendors |

### Report Endpoints

| Endpoint | Rate Limit | Description |
|----------|------------|-------------|
| `POST /api/products/{product}/report` | 5 requests/minute | Reporting products (prevents spam) |
| `POST /api/vendors/{vendor}/report` | 5 requests/minute | Reporting vendors (prevents spam) |

### Ticket Endpoints

| Endpoint | Rate Limit | Description |
|----------|------------|-------------|
| `POST /api/tickets` | 10 requests/minute | Creating support tickets |
| `PUT /api/tickets/{ticket}` | 10 requests/minute | Updating tickets |
| `DELETE /api/tickets/{ticket}` | 5 requests/minute | Deleting tickets |
| `POST /api/tickets/{ticket}/add-message` | 20 requests/minute | Adding messages to tickets |
| `POST /api/tickets/{ticket}/update-status` | 10 requests/minute | Updating ticket status |

## Rate Limit Response

When a rate limit is exceeded, the API returns:

```json
{
  "message": "Too Many Attempts.",
  "errors": {
    "login": ["Too many login attempts. Please try again in 60 seconds."]
  }
}
```

With HTTP status code **429 Too Many Requests**.

## Rate Limit Headers

Laravel automatically includes rate limit headers in responses:

- `X-RateLimit-Limit`: Maximum number of requests allowed
- `X-RateLimit-Remaining`: Number of requests remaining
- `Retry-After`: Seconds until the rate limit resets

## Configuration

### Global Rate Limiting

Configured in `bootstrap/app.php`:

```php
$middleware->api(prepend: [
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
]);
```

This applies 60 requests per minute to all API routes.

### Per-Route Rate Limiting

Applied using the `throttle` middleware:

```php
Route::post('/endpoint', [Controller::class, 'method'])
    ->middleware('throttle:10,1'); // 10 requests per minute
```

## Best Practices

1. **Authentication endpoints**: Use strict limits (5-10 requests/minute) to prevent brute force attacks
2. **Payment endpoints**: Use strict limits (5-10 requests/minute) to prevent abuse
3. **Read endpoints**: Can have higher limits (30-60 requests/minute)
4. **Write endpoints**: Should have moderate limits (10-20 requests/minute)
5. **Spam-prone endpoints**: Use very strict limits (3-5 requests/minute)

## Production Considerations

- Consider using Redis for rate limiting in production for better performance
- Monitor rate limit hits to identify potential attacks
- Adjust limits based on actual usage patterns
- Consider implementing different limits for authenticated vs. unauthenticated users

---

**Last Updated**: January 2026
