# 📥 Queue Import Setup Guide

## Overview

All imports are now processed in the background using Laravel Queue system. This improves user experience by:
- ✅ Non-blocking imports (user doesn't wait)
- ✅ Better performance for large files
- ✅ Ability to handle multiple imports simultaneously
- ✅ Better error handling and retry mechanism

---

## Setup Instructions

### 1. Database Queue Setup

The system uses `database` queue driver by default. Make sure the jobs table exists:

```bash
# If jobs table doesn't exist, create it
php artisan queue:table
php artisan migrate
```

### 2. Start Queue Worker

You need to run a queue worker to process the queued imports:

#### For Development (Laragon/Windows):

```powershell
# In a separate terminal/command prompt
php artisan queue:work
```

#### For Production:

```bash
# Using Supervisor (recommended)
# Or use Laravel Horizon if installed
php artisan queue:work --daemon
```

#### For Testing:

```bash
# Process queue synchronously (for testing)
php artisan queue:work --once
```

---

## Configuration

### Queue Connection

The default queue connection is set in `.env`:

```env
QUEUE_CONNECTION=database
```

### Available Queue Drivers:

- **database** - Uses database table (default, recommended for small-medium apps)
- **redis** - Faster, recommended for production
- **sync** - Synchronous (not queued, for testing only)

### Change Queue Driver:

```env
# In .env file
QUEUE_CONNECTION=redis  # or database, sqs, etc.
```

---

## How It Works

### 1. User Uploads File

When user uploads an Excel file:
1. File is stored temporarily in `storage/app/imports/`
2. Import job is queued
3. User receives immediate response
4. Import processes in background

### 2. Queue Processing

Queue worker processes the import:
1. Reads Excel file
2. Validates each row
3. Imports categories in batches (250 per batch)
4. Handles errors gracefully
5. Logs results

### 3. Completion

After import completes:
- Results are logged
- User can check categories list
- Errors are logged for review

---

## Monitoring Queue

### Check Queue Status

```bash
# See pending jobs
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed
```

### View Jobs Table

```sql
SELECT * FROM jobs ORDER BY created_at DESC;
```

### Clear Failed Jobs

```bash
php artisan queue:flush
```

---

## Troubleshooting

### Import Not Processing

**Problem:** Import is queued but not processing

**Solutions:**
1. Make sure queue worker is running:
   ```bash
   php artisan queue:work
   ```

2. Check queue connection in `.env`:
   ```env
   QUEUE_CONNECTION=database
   ```

3. Check jobs table:
   ```sql
   SELECT * FROM jobs;
   ```

### Jobs Failing

**Problem:** Jobs are failing

**Solutions:**
1. Check failed jobs:
   ```bash
   php artisan queue:failed
   ```

2. Retry failed jobs:
   ```bash
   php artisan queue:retry all
   ```

3. Check logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Memory Issues

**Problem:** Queue worker running out of memory

**Solutions:**
1. Restart worker periodically:
   ```bash
   php artisan queue:work --max-jobs=1000 --max-time=3600
   ```

2. Increase PHP memory limit:
   ```ini
   memory_limit = 256M
   ```

---

## Production Setup

### Using Supervisor (Recommended)

Create `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Using Laravel Horizon (Advanced)

If you have Laravel Horizon installed:

```bash
php artisan horizon
```

Horizon provides:
- Web dashboard for monitoring
- Better job management
- Automatic scaling

---

## Performance Tips

### 1. Adjust Batch Size

In `CategoriesImport.php`:

```php
public function batchSize(): int
{
    return 250; // Adjust based on server capacity
}
```

### 2. Adjust Chunk Size

```php
public function chunkSize(): int
{
    return 250; // Adjust based on server capacity
}
```

### 3. Multiple Workers

For high-volume imports, run multiple workers:

```bash
# Terminal 1
php artisan queue:work

# Terminal 2
php artisan queue:work

# Terminal 3
php artisan queue:work
```

Or use Supervisor with `numprocs=4`

---

## Testing Queue Imports

### Test Synchronously

For testing, you can process queue synchronously:

```php
// In test
Queue::fake();
// or
Queue::assertPushed(CategoriesImport::class);
```

### Test Queue Processing

```bash
# Process one job
php artisan queue:work --once

# Process all pending jobs
php artisan queue:work --stop-when-empty
```

---

## Future Enhancements

### 1. Progress Tracking

Add progress tracking using cache:

```php
Cache::put("import_progress_{$importId}", [
    'total' => $totalRows,
    'processed' => $processedRows,
    'percentage' => ($processedRows / $totalRows) * 100
]);
```

### 2. User Notifications

Send notification when import completes:

```php
// In AfterImport event
auth()->user()->notify(new ImportCompletedNotification($results));
```

### 3. Email Reports

Send email report with import results:

```php
Mail::to(auth()->user())->send(new ImportReportMail($results));
```

---

## Summary

✅ **Queue imports are now enabled**

**To use:**
1. Make sure queue worker is running: `php artisan queue:work`
2. Upload Excel file through admin panel
3. Import processes in background
4. Check categories list for results

**Benefits:**
- Non-blocking user experience
- Better performance
- Scalable for large imports
- Better error handling

---

**Ready for production use!** 🚀
