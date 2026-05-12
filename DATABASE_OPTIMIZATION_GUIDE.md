# Database Optimization Guide
## Multi-Vendor E-Commerce API

This document describes the database optimization strategies implemented in the system, including indexes, query optimizations, and recommendations for read replicas.

## Table of Contents

1. [Database Indexes](#database-indexes)
2. [Query Optimizations](#query-optimizations)
3. [N+1 Query Prevention](#n1-query-prevention)
4. [Read Replicas Configuration](#read-replicas-configuration)
5. [Performance Monitoring](#performance-monitoring)

---

## Database Indexes

### Overview

Database indexes have been added to frequently queried columns to improve query performance. The indexes are created in the migration file: `2026_01_22_112608_add_performance_indexes_to_tables.php`.

### Indexes by Table

#### Products Table

| Index Name | Columns | Purpose |
|------------|---------|---------|
| `products_status_index` | `is_active`, `is_approved`, `is_featured` | Composite index for common filter combinations |
| `products_price_index` | `price` | Index for price range queries and sorting |
| `products_vendor_id_index` | `vendor_id` | Index for vendor filtering |
| `products_type_index` | `type` | Index for product type filtering |
| `products_created_at_index` | `created_at` | Index for date sorting |
| `products_is_new_index` | `is_new` | Index for new products filter |
| `products_is_bookable_index` | `is_bookable` | Index for bookable products filter |

**Usage Examples:**
- Filtering active, approved, featured products
- Price range queries (`WHERE price BETWEEN min AND max`)
- Sorting by price or date
- Filtering by vendor

#### Orders Table

| Index Name | Columns | Purpose |
|------------|---------|---------|
| `orders_user_status_index` | `user_id`, `status` | Composite index for user order queries |
| `orders_status_payment_index` | `status`, `payment_status` | Composite index for order status filtering |
| `orders_created_at_index` | `created_at` | Index for date range queries |
| `orders_total_index` | `total` | Index for total sorting |
| `orders_payment_method_index` | `payment_method` | Index for payment method filtering |
| `orders_refund_status_index` | `refund_status` | Index for refund status filtering |

**Usage Examples:**
- Fetching orders by user and status
- Filtering orders by payment status
- Date range queries
- Sorting by total amount

#### Vendor Orders Table

| Index Name | Columns | Purpose |
|------------|---------|---------|
| `vendor_orders_vendor_status_index` | `vendor_id`, `status` | Composite index for vendor order queries |
| `vendor_orders_order_vendor_index` | `order_id`, `vendor_id` | Composite index for order-vendor lookups |
| `vendor_orders_branch_id_index` | `branch_id` | Index for branch filtering |
| `vendor_orders_status_index` | `status` | Index for status filtering |

**Usage Examples:**
- Fetching orders for a specific vendor
- Filtering by branch
- Status-based queries

#### Cart Items Table

| Index Name | Columns | Purpose |
|------------|---------|---------|
| `cart_items_user_product_index` | `user_id`, `product_id` | Composite index for user cart queries |
| `cart_items_user_product_variant_index` | `user_id`, `product_id`, `variant_id` | Composite index for variant queries |

**Usage Examples:**
- Fetching user cart items
- Checking if product exists in cart
- Variant-specific cart operations

#### Vendors Table

| Index Name | Columns | Purpose |
|------------|---------|---------|
| `vendors_status_index` | `is_active`, `is_featured` | Composite index for active/featured filtering |
| `vendors_owner_id_index` | `owner_id` | Index for owner queries |
| `vendors_plan_id_index` | `plan_id` | Index for plan filtering |
| `vendors_balance_index` | `balance` | Index for balance sorting |
| `vendors_commission_rate_index` | `commission_rate` | Index for commission rate filtering |
| `vendors_created_at_index` | `created_at` | Index for date filtering |

**Usage Examples:**
- Filtering active/featured vendors
- Finding vendors by owner
- Sorting by balance or commission rate

#### Categories Table

| Index Name | Columns | Purpose |
|------------|---------|---------|
| `categories_status_index` | `is_active`, `is_featured` | Composite index for active/featured filtering |
| `categories_parent_id_index` | `parent_id` | Index for parent category queries |
| `categories_created_at_index` | `created_at` | Index for date sorting |

**Usage Examples:**
- Filtering active/featured categories
- Building category trees (parent-child relationships)
- Sorting by creation date

#### Users Table

| Index Name | Columns | Purpose |
|------------|---------|---------|
| `users_role_status_index` | `role`, `is_active` | Composite index for role and status filtering |
| `users_created_at_index` | `created_at` | Index for date filtering |

**Usage Examples:**
- Filtering users by role and status
- Date range queries

#### Branch Stock Tables

**Branch Product Stocks:**
- `branch_product_stocks_branch_product_index`: `branch_id`, `product_id`
- `branch_product_stocks_branch_quantity_index`: `branch_id`, `quantity`
- `branch_product_stocks_product_quantity_index`: `product_id`, `quantity`

**Branch Product Variant Stocks:**
- `branch_variant_stocks_branch_variant_index`: `branch_id`, `product_variant_id`
- `branch_variant_stocks_branch_quantity_index`: `branch_id`, `quantity`
- `branch_variant_stocks_variant_quantity_index`: `product_variant_id`, `quantity`

**Usage Examples:**
- Checking stock availability by branch
- Filtering products with stock > 0
- Variant stock queries

---

## Query Optimizations

### Eager Loading

All repositories use eager loading to prevent N+1 query problems:

#### ProductRepository

```php
// Eager loads all necessary relationships
$with = [
    'vendor',
    'categories',
    'images',
    'variants',
    'branchProductStocks',
    'variants.branchVariantStocks',
    'ratings'
];
```

#### OrderRepository

```php
// Eager loads order relationships
$with = [
    'user',
    'coupon',
    'address',
    'vendorOrders.vendor',
    'vendorOrders.branch',
    'vendorOrders.items.product',
    'vendorOrders.items.variant',
];
```

### Conditional Eager Loading

When filtering by branch, only load relevant branch stocks:

```php
if ($branchId) {
    $with = [
        'branchProductStocks' => function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        },
        'variants.branchVariantStocks' => function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        },
    ];
}
```

### Query Scopes

Use Eloquent scopes for common filters:

```php
Product::active()->approved()->featured()->get();
```

---

## N+1 Query Prevention

### Problem

N+1 queries occur when you loop through a collection and make additional queries for each item. For example:

```php
// BAD: N+1 problem
$products = Product::all();
foreach ($products as $product) {
    echo $product->vendor->name; // Query for each product
}
```

### Solution

Use eager loading to load relationships upfront:

```php
// GOOD: Eager loading
$products = Product::with('vendor')->get();
foreach ($products as $product) {
    echo $product->vendor->name; // No additional queries
}
```

### Fixed Issues

1. **ProductResource - Ratings**: Fixed N+1 by using eager loaded ratings collection instead of querying twice
2. **ProductResource - FavoritedBy**: Optimized to use `exists()` query instead of loading full relationship

### Best Practices

1. **Always eager load relationships** that will be accessed in loops
2. **Use `with()` for relationships** that are always needed
3. **Use `whenLoaded()` in resources** to check if relationship is loaded
4. **Use `exists()` queries** for boolean checks instead of loading full relationships
5. **Use `withCount()`** for counting related records

---

## Read Replicas Configuration

### Overview

Read replicas allow you to distribute read queries across multiple database servers, improving performance for read-heavy applications.

### When to Use Read Replicas

- High read-to-write ratio (80%+ reads)
- Large number of concurrent users
- Complex queries that take time to execute
- Geographic distribution requirements

### Laravel Configuration

#### 1. Database Configuration

Update `config/database.php`:

```php
'mysql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1', '192.168.1.1'),
            env('DB_READ_HOST_2', '192.168.1.2'),
        ],
    ],
    'write' => [
        'host' => [
            env('DB_WRITE_HOST', '192.168.1.3'),
        ],
    ],
    'sticky' => true, // Ensures write operations use write connection
    'driver' => 'mysql',
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    // ... other config
],
```

#### 2. Environment Variables

Add to `.env`:

```env
DB_READ_HOST_1=read-replica-1.example.com
DB_READ_HOST_2=read-replica-2.example.com
DB_WRITE_HOST=master-db.example.com
```

#### 3. Force Read Connection

For specific queries that should always use read replica:

```php
// Use read connection
$products = Product::on('mysql')->get();

// Use write connection
Product::onWriteConnection()->create([...]);
```

#### 4. Transaction Handling

Laravel automatically uses write connection for transactions:

```php
DB::transaction(function () {
    // Automatically uses write connection
    $order = Order::create([...]);
    $order->items()->create([...]);
});
```

### Read Replica Setup (MySQL)

#### Master Database Configuration

```ini
[mysqld]
server-id = 1
log-bin = mysql-bin
binlog-format = ROW
```

#### Replica Database Configuration

```ini
[mysqld]
server-id = 2
relay-log = mysql-relay-bin
read-only = 1
```

#### Replication Setup

```sql
-- On replica
CHANGE MASTER TO
  MASTER_HOST='master-host',
  MASTER_USER='replication-user',
  MASTER_PASSWORD='replication-password',
  MASTER_LOG_FILE='mysql-bin.000001',
  MASTER_LOG_POS=0;

START SLAVE;
```

### Monitoring Read Replicas

1. **Check replication lag**:
   ```sql
   SHOW SLAVE STATUS\G
   ```

2. **Monitor read/write distribution**:
   - Use Laravel Debugbar or Telescope
   - Monitor database connection usage

3. **Set up alerts** for:
   - Replication lag > threshold
   - Replica server down
   - Connection errors

### Best Practices

1. **Use read replicas for**:
   - Product listings
   - Search queries
   - Reports and analytics
   - Read-only API endpoints

2. **Use write connection for**:
   - Order creation
   - Payment processing
   - User registration
   - Any write operations

3. **Consider sticky connections**:
   - Set `'sticky' => true` to ensure write operations use write connection
   - Prevents reading stale data immediately after writes

4. **Monitor replication lag**:
   - Set up alerts for lag > 1 second
   - Consider using write connection for time-sensitive reads

---

## Performance Monitoring

### Tools

1. **Laravel Debugbar**: Shows query count and execution time
2. **Laravel Telescope**: Comprehensive application monitoring
3. **MySQL Slow Query Log**: Identifies slow queries
4. **New Relic / Datadog**: Production monitoring

### Key Metrics to Monitor

1. **Query Count**: Should be minimal (use eager loading)
2. **Query Execution Time**: Should be < 100ms for most queries
3. **N+1 Queries**: Should be zero
4. **Index Usage**: Monitor index hit rate
5. **Connection Pool Usage**: Monitor database connections

### Slow Query Analysis

Enable slow query log in MySQL:

```ini
[mysqld]
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
```

Analyze slow queries:

```sql
-- Find slow queries
SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;

-- Check index usage
EXPLAIN SELECT * FROM products WHERE vendor_id = 1 AND is_active = 1;
```

### Optimization Checklist

- [x] Add indexes for frequently queried columns
- [x] Use eager loading to prevent N+1 queries
- [x] Optimize resource transformations
- [x] Use query scopes for common filters
- [ ] Set up read replicas (production)
- [ ] Monitor query performance
- [ ] Set up slow query logging
- [ ] Configure connection pooling
- [ ] Set up database backups
- [ ] Test query performance under load

---

## Migration

To apply the indexes, run:

```bash
php artisan migrate
```

To rollback:

```bash
php artisan migrate:rollback --step=1
```

---

**Last Updated**: January 2026
