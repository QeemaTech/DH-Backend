# Multi-Vendor E-Commerce Platform

## Project Overview

A comprehensive multi-vendor e-commerce platform built with Laravel 12, designed to enable multiple vendors to manage their products, branches, and subscriptions through a unified marketplace system. The platform supports role-based access control with separate dashboards for administrators and vendors.

## Technology Stack

### Backend
- **Framework**: Laravel 12
- **PHP Version**: 8.3.19
- **Database**: MySQL/MariaDB
- **Authentication**: Laravel Sanctum v4
- **Authorization**: Spatie Laravel Permission
- **Internationalization**: Spatie Laravel Translatable (English/Arabic support)
- **Slug Generation**: Cviebrock Eloquent Sluggable

### Frontend
- **CSS Framework**: Tailwind CSS v4
- **JavaScript Framework**: Vanilla JavaScript (Alpine.js removed from product edit pages)
- **UI Components**: Bootstrap 5.3.8
- **Icons**: Bootstrap Icons 1.13.1
- **Build Tool**: Vite 7.0.7
- **Styling**: Sass

### Development Tools
- **Code Formatter**: Laravel Pint v1
- **Testing**: PHPUnit v11
- **Development Server**: Laravel Sail v1
- **Debugging**: Laravel Pail v1.2.2

## Project Architecture

### Design Patterns
- **Repository Pattern**: Data access layer abstraction
- **Service Layer**: Business logic separation
- **Factory Pattern**: Product type handling (SimpleProduct, VariableProduct)
- **Form Request Validation**: Request validation classes

### Directory Structure
```
app/
├── Contracts/          # Interface definitions
├── Factories/          # Factory classes
├── Helpers/            # Helper functions
├── Http/
│   ├── Controllers/
│   │   ├── Admin/     # Admin-specific controllers
│   │   ├── Auth/      # Authentication controllers
│   │   └── Vendor/    # Vendor-specific controllers
│   ├── Middleware/    # Custom middleware
│   └── Requests/      # Form request validation
├── Models/             # Eloquent models
├── Repositories/      # Data access layer
└── Services/          # Business logic layer
    └── ProductTypes/  # Product type implementations
```

## Core Features

### 1. User Management & Authentication
- ✅ User registration and authentication
- ✅ Vendor registration with verification
- ✅ Email verification system
- ✅ Password reset functionality
- ✅ Profile management
- ✅ Role-based access control (Admin, Vendor)

### 2. Vendor Management
- ✅ Vendor registration and approval system
- ✅ Vendor profile management
- ✅ Vendor status control (Active/Inactive)
- ✅ Vendor featuring system
- ✅ Vendor balance tracking
- ✅ Commission rate management
- ✅ Vendor subscription management
- ✅ Vendor employee management with granular permissions
- ✅ Role-based access control for vendor employees

### 3. Subscription & Plans System
- ✅ Subscription plans creation and management
- ✅ Plan features:
  - Duration (days)
  - Price
  - Maximum products count
  - Product featuring capability
  - Active/Featured status
- ✅ Vendor subscription tracking
- ✅ Subscription status (Active/Expired/Inactive)
- ✅ Subscription history
- ✅ Days remaining calculation
- ✅ Admin subscription overview
- ✅ Vendor subscription details view

### 4. Category Management
- ✅ Hierarchical category system
- ✅ Multi-language support (English/Arabic)
- ✅ Category slug generation
- ✅ Category requests system (vendors can request new categories)
- ✅ Admin approval/rejection workflow
- ✅ Category soft deletion

### 5. Variant Management
- ✅ Product variant system (Size, Color, etc.)
- ✅ Variant options management
- ✅ Variant requests system (vendors can request new variants)
- ✅ Admin approval/rejection workflow
- ✅ Variant active/required toggle
- ✅ Variant soft deletion

### 6. Product Management
- ✅ Product CRUD operations
- ✅ Product types:
  - **Simple Products**: Single SKU products
  - **Variable Products**: Products with variants
- ✅ Product features:
  - Multi-language support (English/Arabic)
  - SKU generation
  - Slug generation
  - Thumbnail upload
  - Multiple images
  - Pricing with discount (percentage/fixed)
  - Stock management per branch
  - Product status (Active/Inactive)
  - Featured products
  - New products flag
  - Approval workflow
  - Bookable products
- ✅ Product approval system (Admin approval required for vendor products)
- ✅ Product categorization (many-to-many)
- ✅ Product variants management
- ✅ Product images gallery

### 7. Branch Management
- ✅ Multi-branch support per vendor
- ✅ Branch CRUD operations
- ✅ Branch status control (Active/Inactive)
- ✅ Branch-specific stock management
- ✅ Branch product stock tracking
- ✅ Branch variant stock tracking

### 8. Stock Management
- ✅ Stock tracking per branch
- ✅ Simple product stock management
- ✅ Variable product variant stock management
- ✅ Stock availability checking
- ✅ Stock quantity tracking

### 9. Settings Management
- ✅ Application settings
- ✅ Currency configuration
- ✅ System-wide settings management

### 10. Dashboard & Analytics
- ✅ Admin dashboard
- ✅ Vendor dashboard
- ✅ Role-based dashboard access

### 11. Vendor Employee Management
- ✅ Vendor employee creation and management
- ✅ Granular permission system for vendor employees
- ✅ Permission assignment during user creation/update
- ✅ Role-based UI element visibility
- ✅ Custom middleware for vendor area access control
- ✅ Helper function `vendorCan()` for permission checks
- ✅ Comprehensive permission seeder

## Database Schema

### Core Tables
- `users` - User accounts
- `vendors` - Vendor information
- `vendor_users` - Vendor-user relationships
- `plans` - Subscription plans
- `vendor_subscriptions` - Vendor subscription records
- `categories` - Product categories
- `variants` - Product variants
- `variant_options` - Variant option values
- `products` - Products
- `product_variants` - Product variant instances
- `product_variant_values` - Variant value assignments
- `product_images` - Product images
- `product_categories` - Product-category relationships
- `branches` - Vendor branches
- `branch_product_stocks` - Simple product stock per branch
- `branch_product_variant_stocks` - Variant product stock per branch
- `category_requests` - Category request submissions
- `variant_requests` - Variant request submissions
- `settings` - Application settings
- `verifications` - Email verification codes

## Current Implementation Status

### ✅ Completed Features

#### Admin Panel
- [x] Admin dashboard
- [x] Settings management
- [x] Category management (CRUD)
- [x] Plan management (CRUD)
- [x] Vendor management (CRUD)
- [x] Variant management (CRUD)
- [x] Product management (CRUD)
- [x] Branch management (CRUD)
- [x] Category request approval/rejection
- [x] Variant request approval/rejection
- [x] Product approval workflow
- [x] Subscription management (View, List)
- [x] Subscription details view

#### Vendor Panel
- [x] Vendor dashboard
- [x] Vendor profile management
- [x] Category browsing (read-only)
- [x] Variant browsing (read-only)
- [x] Product management (CRUD for own products)
- [x] Branch management (CRUD for own branches)
- [x] Category request submission
- [x] Variant request submission
- [x] Plan browsing
- [x] Plan subscription
- [x] Subscription management (View, List)
- [x] Subscription details view
- [x] Vendor employee management (CRUD)
- [x] Permission assignment for vendor employees
- [x] Role-based UI element visibility

#### Core Functionality
- [x] Authentication system
- [x] Role-based access control
- [x] Multi-language support (EN/AR)
- [x] File upload handling
- [x] Image management
- [x] Stock management system
- [x] Product variant system
- [x] Slug generation
- [x] SKU generation
- [x] Soft deletes
- [x] Form validation
- [x] AJAX filtering and pagination
- [x] Permission-based UI rendering
- [x] Custom middleware for vendor access
- [x] Helper functions for permission checks

### 🚧 In Progress / Pending Features

#### Frontend
- [ ] Customer-facing storefront
- [ ] Shopping cart functionality
- [ ] Checkout process
- [ ] Order management
- [ ] Payment integration
- [ ] Customer reviews and ratings
- [ ] Product search and filtering
- [ ] Wishlist functionality

#### Backend
- [x] Order management system (admin & vendor dashboards)
- [x] Payment gateway integration scaffolding (payment_status + wallet/points handling)
- [x] Invoice generation (PDF invoices for vendor orders and full admin orders)
- [x] Advanced order status workflow (Pending → Processing → Shipped → Delivered → Cancelled/Refunded)
- [x] Vendor order status sync with main order (auto-processing/delivered when appropriate)
- [x] Order cancel & refund flows:
  - Cancel (pending/processing): refund wallet_used + points/cashback, restore stock for processing vendor orders, cancel vendor orders, mark main order cancelled.
  - Refund (delivered): refund full order total to wallet (gateway + wallet), keep points, restore stock for delivered vendor orders, set payment_status/refund_status.
- [x] Email notifications for order & vendor order status changes
- [ ] SMS notifications
- [x] Email notifications
- [x] Reporting and analytics
- [ ] Export functionality (CSV, PDF)
- [x] Advanced search functionality
- [x] Product recommendations
- [x] Inventory alerts

#### Vendor Features
- [x] Order fulfillment
- [x] Sales reports
- [x] Earnings dashboard
- [x] Product performance analytics
- [x] Customer management

#### Admin Features
- [x] Advanced analytics dashboard
- [x] Revenue reports
- [x] Vendor performance metrics
- [ ] System logs and audit trail
- [ ] Backup and restore functionality

## API Structure

### Admin Routes
```
/admin/dashboard
/admin/settings
/admin/categories (CRUD)
/admin/plans (CRUD)
/admin/vendors (CRUD)
/admin/variants (CRUD)
/admin/products (CRUD)
/admin/branches (CRUD)
/admin/variant-requests (List, Approve, Reject)
/admin/category-requests (List, Approve, Reject)
/admin/subscriptions (List, Show)
```

### Vendor Routes
```
/vendor/dashboard
/vendor/profile
/vendor/categories (Read-only)
/vendor/variants (Read-only)
/vendor/products (CRUD for own products)
/vendor/branches (CRUD for own branches)
/vendor/variant-requests (List, Create)
/vendor/category-requests (List, Create)
/vendor/plans (List, Subscribe)
/vendor/subscriptions (List, Show, Cancel)
/vendor/vendor-users (CRUD for vendor employees)
```

## Security Features

- ✅ CSRF protection
- ✅ XSS protection
- ✅ SQL injection prevention (Eloquent ORM)
- ✅ Role-based authorization
- ✅ Permission-based access control
- ✅ Granular permissions for vendor employees
- ✅ File upload validation
- ✅ Input validation and sanitization
- ✅ Password hashing
- ✅ Email verification
- ✅ Custom middleware for vendor area access

## Internationalization

- ✅ Multi-language support (English/Arabic)
- ✅ Locale switching
- ✅ Translatable models (Categories, Products, Plans, Vendors)
- ✅ RTL support ready

## Code Quality

- ✅ Laravel Pint code formatting
- ✅ Repository pattern implementation
- ✅ Service layer separation
- ✅ Form request validation
- ✅ Type hints and return types
- ✅ PHPDoc comments
- ✅ Consistent naming conventions

## Testing

- ⚠️ Test suite setup (PHPUnit configured)
- ⚠️ Unit tests (Pending)
- ⚠️ Feature tests (Pending)
- ⚠️ Integration tests (Pending)

## Deployment Considerations

- ✅ Environment configuration
- ✅ Database migrations
- ✅ Asset compilation (Vite)
- ⚠️ Queue workers configuration
- ⚠️ Cron jobs setup
- ⚠️ Caching strategy
- ⚠️ CDN integration
- ⚠️ SSL configuration

## Recent Updates

### Latest Changes (January 2026)

#### Product Edit Pages Refactoring
- ✅ Removed Alpine.js dependency from vendor product edit page
- ✅ Removed Alpine.js dependency from admin product edit page
- ✅ Replaced Alpine.js with vanilla JavaScript for better compatibility
- ✅ Fixed branch loading issue in both vendor and admin edit pages
- ✅ Added `renderBranchStockTable()` function to dynamically render branch stock table
- ✅ Fixed variant selection section visibility (hidden for simple products)
- ✅ Improved form initialization and step management
- ✅ Added proper event handlers for vendor and type changes
- ✅ Enhanced branch stock table rendering with existing stock values

#### Permission System Improvements
- ✅ Replaced all `hasRole('admin')` checks with `hasRole('vendor')` in vendor blade views
- ✅ Updated sidebar to use `vendorCan()` helper function consistently
- ✅ Added subscription menu items to admin and vendor sidebars
- ✅ Improved permission checks across all vendor-related views

#### Code Quality
- ✅ Standardized permission checks using `vendorCan()` helper
- ✅ Improved code consistency across vendor views
- ✅ Enhanced error handling in branch loading functionality
- ✅ Added console logging for debugging branch loading issues

#### Reporting & Analytics Enhancements
- ✅ Added dedicated Earnings dashboards for admin and vendors (net revenue, commissions, withdrawals, balances)
- ✅ Implemented Product Performance dashboards (admin & vendor) with filters (product, category, vendor, status, date range)
- ✅ Implemented Vendor Performance dashboards:
  - Admin: KPIs for vendor gross sales, commission, net earnings, withdrawals, balances
  - Vendor: self-view of gross/commission/net, withdrawals, balances
- ✅ Added vendor share-of-platform metrics:
  - Percentage of total platform sales per vendor for the selected period
  - Percentage of total refunded orders per vendor for the selected period
- ✅ Added offcanvas-based advanced filters (date range, payment status, order status, vendor/product/category) using GET so parameters appear in URL

### Previous Updates
- ✅ Created admin subscription show view
- ✅ Created vendor subscription show view
- ✅ Fixed vendor subscription controller view path
- ✅ Updated repository to eager load relationships
- ✅ Improved subscription status calculation
- ✅ Enhanced UI with status badges and statistics

## Next Steps

### Immediate Priorities
1. Complete customer-facing storefront
2. Implement shopping cart functionality
3. Develop customer-facing order history and tracking pages
4. Integrate payment gateway
5. Enhance notification system (email/database templates, preferences, coverage)

### Short-term Goals
1. Refine customer reviews and ratings UX (filters, pagination, moderation)
2. Extend advanced search and filtering to remaining areas and mobile UX
3. Extend reporting and analytics dashboards with more KPIs and charts
4. Develop mobile-responsive design improvements
5. Add export functionality (CSV/PDF) for key reports

### Long-term Goals
1. Mobile app development
2. Advanced analytics and AI recommendations
3. Multi-currency support
4. Advanced shipping options
5. Marketplace commission system

## Notes

- The project follows Laravel 12 best practices
- Uses modern PHP 8.3 features
- Implements clean architecture principles
- Supports both simple and variable products
- Multi-branch inventory management
- Subscription-based vendor access control
- Request-based category and variant system for flexibility

---

**Last Updated**: January 2026
**Version**: 1.1.0 (Development)
**Status**: Active Development

## Technical Notes

### JavaScript Framework Migration
- Product edit pages (both admin and vendor) have been migrated from Alpine.js to vanilla JavaScript
- This change improves compatibility and reduces dependency on external frameworks
- All interactive features remain fully functional with vanilla JavaScript implementation

### Permission System Architecture
- Vendor role has inherent access to all vendor-related actions
- Vendor employees require explicit permission assignment
- Permissions are checked at multiple layers:
  - Route middleware (`role_or_permission`)
  - Form Request authorization
  - UI element visibility (`vendorCan()` helper)
- Custom middleware ensures only authorized users access vendor area
