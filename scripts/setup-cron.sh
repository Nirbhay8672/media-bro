#!/bin/bash

# Media Bro - Cron Job Setup Script
# This script sets up daily cron jobs for user management

echo "Setting up Media Bro cron jobs..."

# Get the current directory (project root)
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_PATH=$(which php)

echo "Project directory: $PROJECT_DIR"
echo "PHP path: $PHP_PATH"

# Create cron job entries
CRON_JOBS="
# Media Bro - Daily user management tasks
# Deactivate expired users (runs daily at 2:00 AM)
0 2 * * * cd $PROJECT_DIR && $PHP_PATH artisan users:deactivate-expired --quiet >> /dev/null 2>&1

# Update user account statuses (runs daily at 2:30 AM)
30 2 * * * cd $PROJECT_DIR && $PHP_PATH artisan users:update-account-statuses >> /dev/null 2>&1

# Generate daily user status report (runs daily at 3:00 AM)
0 3 * * * cd $PROJECT_DIR && $PHP_PATH artisan users:status-report --format=csv > $PROJECT_DIR/storage/logs/daily-user-report-\$(date +\%Y\%m\%d).csv 2>/dev/null
"

echo "Cron jobs to be added:"
echo "$CRON_JOBS"

# Add to crontab
echo "$CRON_JOBS" | crontab -

echo "Cron jobs have been added successfully!"
echo ""
echo "Current crontab:"
crontab -l
echo ""
echo "To view logs, check: $PROJECT_DIR/storage/logs/laravel.log"
echo "Daily reports will be saved to: $PROJECT_DIR/storage/logs/daily-user-report-YYYYMMDD.csv"
