# Cron Jobs Setup for Subscription Management

## Overview

This document describes the cron jobs needed for proper subscription management:

1. **`subscriptions:expire`** - Marks expired subscriptions
2. **`subscriptions:activate-scheduled`** - Activates scheduled subscriptions

Both commands should be run daily via cron jobs.

## Commands Details

### 1. Expire Subscriptions

**Command**: `php artisan subscriptions:expire`

**Description**: Checks all active subscriptions and marks them as expired if their `end_date` has passed.

**What it does**:
- Finds all subscriptions with status `active` where `end_date < today`
- Updates their status to `expired`
- Provides progress feedback and summary

### 2. Activate Scheduled Subscriptions

**Command**: `php artisan subscriptions:activate-scheduled`

**Description**: Activates scheduled subscriptions that have reached their start date.

**What it does**:
- Finds all subscriptions with status `inactive` where `start_date <= today`
- Activates them (sets status to `active`)
- Deactivates any other active subscriptions for the same vendor
- Applies downgrade restrictions if applicable
- Updates vendor plan information

**Use case**: When a vendor downgrades, the new subscription is scheduled to start after the current one ends. This command activates it automatically.

## Setting Up Cron Job

### Option 1: Using Laravel Scheduler (Recommended)

Laravel provides a task scheduler that can be used instead of adding multiple cron entries. Add this to your `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Expire subscriptions at midnight
Schedule::command('subscriptions:expire')
    ->daily()
    ->at('00:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground();

// Activate scheduled subscriptions at 00:01 (after expiration check)
Schedule::command('subscriptions:activate-scheduled')
    ->daily()
    ->at('00:01')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->runInBackground();
```

Then add this single cron entry to your server (run once per minute):

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Option 2: Direct Cron Entry

Add this line to your crontab (`crontab -e`):

```bash
# Run subscription expiration check daily at midnight
0 0 * * * cd /path-to-your-project && php artisan subscriptions:expire >> /dev/null 2>&1

# Activate scheduled subscriptions daily at 00:01
1 0 * * * cd /path-to-your-project && php artisan subscriptions:activate-scheduled >> /dev/null 2>&1
```

**For Windows Task Scheduler**:
1. Open Task Scheduler
2. Create Basic Task
3. Set trigger to "Daily" at midnight
4. Set action to "Start a program"
5. Program: `php`
6. Arguments: `artisan subscriptions:expire`
7. Start in: `C:\laragon\www\multi-vendor-e-commerce`

### Option 3: Using Laravel Forge / Similar Services

If you're using Laravel Forge or similar services:
1. Navigate to your server settings
2. Add a new scheduled task
3. Command: `php artisan subscriptions:expire`
4. Frequency: Daily at 00:00 UTC

## Testing the Command

You can test the command manually:

```bash
php artisan subscriptions:expire
```

Expected output:
```
Checking for expired subscriptions...
Found X subscription(s) to expire.
[Progress bar]
Successfully expired X subscription(s).
```

Or if no expired subscriptions:
```
Checking for expired subscriptions...
No expired subscriptions found.
```

## Command Features

- ✅ Only processes active subscriptions
- ✅ Uses date comparison (end_date < today)
- ✅ Provides progress feedback
- ✅ Error handling for individual subscription updates
- ✅ Summary of results
- ✅ Safe to run multiple times (idempotent)

## Model Scopes Added

The following scopes have been added to `VendorSubscription` model for easier querying:

```php
// Get active subscriptions
VendorSubscription::active()->get();

// Get expired subscriptions
VendorSubscription::expired()->get();

// Get subscriptions that should be expired
VendorSubscription::shouldExpire()->get();
```

## Notes

- The command compares dates using `startOfDay()` to ensure accurate comparison
- Only subscriptions with status `active` are checked
- The command is idempotent - safe to run multiple times
- Failed updates are logged but don't stop the process
- Consider running during low-traffic hours (e.g., midnight)

## Troubleshooting

### Command not found
- Ensure you're in the project root directory
- Verify the command file exists at `app/Console/Commands/ExpireSubscriptions.php`
- Run `php artisan list` to see all available commands

### Permissions issue
- Ensure the cron user has write permissions to the database
- Check Laravel logs at `storage/logs/laravel.log`

### No subscriptions expired
- Verify there are active subscriptions in the database
- Check that `end_date` values are in the past
- Verify the `status` field is set to `active`

## Related Files

### Commands
- `app/Console/Commands/ExpireSubscriptions.php` - Expires subscriptions
- `app/Console/Commands/ActivateScheduledSubscriptions.php` - Activates scheduled subscriptions

### Models & Migrations
- `app/Models/VendorSubscription.php`
- `database/migrations/2026_01_12_162533_create_vendor_subscriptions_table.php`

### Services
- `app/Services/PlanService.php` - Handles subscription logic
