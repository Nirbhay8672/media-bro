# Media Bro - Daily User Management Cron Setup

This document explains how to set up daily automated tasks for user account management.

## Overview

The system includes several commands that should be run daily to:
- Deactivate users with expired subscriptions
- Update user account statuses based on subscription dates
- Generate daily reports for monitoring

## Commands Available

### 1. Deactivate Expired Users
```bash
php artisan users:deactivate-expired --quiet
```
- Runs daily at 2:00 AM
- Deactivates users whose subscriptions have expired
- Uses `--quiet` flag to suppress output in cron logs

### 2. Update Account Statuses
```bash
php artisan users:update-account-statuses
```
- Runs daily at 2:30 AM
- Updates all user account statuses based on current subscription dates
- Ensures consistency between subscription dates and account status

### 3. Generate Daily Report
```bash
php artisan users:status-report --format=csv
```
- Runs daily at 3:00 AM
- Creates a CSV report of all user statuses
- Saves to `storage/logs/daily-user-report-YYYY-MM-DD.csv`

## Setup Methods

### Method 1: Laravel Task Scheduler (Recommended)

Laravel includes a built-in task scheduler. To use it:

1. **Add to crontab** (only one entry needed):
```bash
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

2. **The Laravel scheduler will handle all tasks** defined in `app/Console/Kernel.php`

### Method 2: Direct Cron Jobs (Linux/Mac)

1. **Open crontab**:
```bash
crontab -e
```

2. **Add these entries**:
```bash
# Media Bro - Daily user management tasks
0 2 * * * cd /path/to/your/project && php artisan users:deactivate-expired --quiet >> /dev/null 2>&1
30 2 * * * cd /path/to/your/project && php artisan users:update-account-statuses >> /dev/null 2>&1
0 3 * * * cd /path/to/your/project && php artisan users:status-report --format=csv > /path/to/your/project/storage/logs/daily-user-report-$(date +\%Y\%m\%d).csv 2>/dev/null
```

3. **Replace `/path/to/your/project`** with your actual project path

### Method 3: Windows Task Scheduler

1. **Run the setup script**:
```cmd
scripts\setup-cron-windows.bat
```

2. **Follow the instructions** in the script output

3. **Or manually create tasks** in Task Scheduler:
   - Name: "Media Bro Daily User Management"
   - Trigger: Daily at 2:00 AM
   - Action: Start a program
   - Program: `php.exe`
   - Arguments: `artisan users:deactivate-expired --quiet`
   - Start in: `C:\path\to\your\project`

### Method 4: Using Setup Scripts

#### Linux/Mac:
```bash
chmod +x scripts/setup-cron.sh
./scripts/setup-cron.sh
```

#### Windows:
```cmd
scripts\setup-cron-windows.bat
```

## Monitoring and Logs

### Log Files
- **Main Laravel Log**: `storage/logs/laravel.log`
- **Daily Reports**: `storage/logs/daily-user-report-YYYY-MM-DD.csv`
- **Cron Logs**: Check system cron logs (`/var/log/cron` on Linux)

### Monitoring Commands
```bash
# Check if scheduler is working
php artisan schedule:list

# Run scheduler manually (for testing)
php artisan schedule:run

# Check last run status
php artisan schedule:work
```

### Manual Testing
```bash
# Test deactivating expired users
php artisan users:deactivate-expired

# Test updating account statuses
php artisan users:update-account-statuses

# Generate a report
php artisan users:status-report
```

## Troubleshooting

### Common Issues

1. **Permission Denied**
   - Ensure the web server user has write permissions to `storage/logs/`
   - Check file ownership: `chown -R www-data:www-data storage/`

2. **PHP Not Found**
   - Use full path to PHP: `/usr/bin/php` instead of `php`
   - Check PHP path: `which php`

3. **Cron Not Running**
   - Check if cron service is running: `systemctl status cron`
   - Verify crontab entries: `crontab -l`
   - Check cron logs: `tail -f /var/log/cron`

4. **Laravel Scheduler Not Working**
   - Ensure only one cron entry: `* * * * * php artisan schedule:run`
   - Check Laravel logs for errors
   - Test manually: `php artisan schedule:run`

### Verification Steps

1. **Check if tasks are scheduled**:
```bash
php artisan schedule:list
```

2. **Test individual commands**:
```bash
php artisan users:deactivate-expired
php artisan users:update-account-statuses
php artisan users:status-report
```

3. **Check log files**:
```bash
tail -f storage/logs/laravel.log
ls -la storage/logs/daily-user-report-*.csv
```

## Customization

### Changing Schedule Times
Edit `app/Console/Kernel.php` to modify when tasks run:

```php
// Change to run at 1:00 AM instead of 2:00 AM
$schedule->command('users:deactivate-expired --quiet')
    ->dailyAt('01:00');
```

### Adding New Tasks
Add new scheduled tasks in `app/Console/Kernel.php`:

```php
$schedule->command('your:custom-command')
    ->dailyAt('04:00')
    ->withoutOverlapping();
```

### Email Notifications
Add email notifications for task completion:

```php
$schedule->command('users:deactivate-expired --quiet')
    ->dailyAt('02:00')
    ->emailOutputTo('admin@example.com');
```

## Security Notes

- Cron jobs run with limited permissions
- Ensure database credentials are properly configured
- Use `--quiet` flag to prevent sensitive data in logs
- Regularly rotate log files to prevent disk space issues
- Monitor cron job execution to ensure they're running properly

## Support

If you encounter issues:
1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify cron job syntax: `crontab -l`
3. Test commands manually first
4. Check file permissions and paths
