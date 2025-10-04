@echo off
REM Media Bro - Windows Task Scheduler Setup Script
REM This script helps set up Windows Task Scheduler for daily user management

echo Setting up Media Bro Windows Task Scheduler...

REM Get the current directory (project root)
set PROJECT_DIR=%~dp0..
set PROJECT_DIR=%PROJECT_DIR:~0,-1%

echo Project directory: %PROJECT_DIR%

REM Create a PowerShell script for the task
echo Creating PowerShell script for task execution...

(
echo # Media Bro - Daily User Management Task
echo # This script runs daily user management commands
echo.
echo Set-Location "%PROJECT_DIR%"
echo.
echo # Deactivate expired users
echo Write-Host "Deactivating expired users..."
echo php artisan users:deactivate-expired --quiet
echo.
echo # Update user account statuses
echo Write-Host "Updating user account statuses..."
echo php artisan users:update-account-statuses
echo.
echo # Generate daily report
echo Write-Host "Generating daily user report..."
echo $date = Get-Date -Format "yyyyMMdd"
echo php artisan users:status-report --format=csv ^> "storage/logs/daily-user-report-$date.csv"
echo.
echo Write-Host "Daily user management completed successfully!"
) > "%PROJECT_DIR%\scripts\daily-user-management.ps1"

echo PowerShell script created: %PROJECT_DIR%\scripts\daily-user-management.ps1
echo.
echo To set up Windows Task Scheduler:
echo 1. Open Task Scheduler (taskschd.msc)
echo 2. Create a new task
echo 3. Set the following properties:
echo    - Name: Media Bro Daily User Management
echo    - Trigger: Daily at 2:00 AM
echo    - Action: Start a program
echo    - Program: powershell.exe
echo    - Arguments: -ExecutionPolicy Bypass -File "%PROJECT_DIR%\scripts\daily-user-management.ps1"
echo    - Start in: %PROJECT_DIR%
echo.
echo 4. Save the task
echo.
echo The task will run daily and:
echo - Deactivate users with expired subscriptions
echo - Update all user account statuses
echo - Generate a daily CSV report
echo.
echo Logs will be available in: %PROJECT_DIR%\storage\logs\
echo Daily reports will be saved as: daily-user-report-YYYYMMDD.csv
echo.
pause
    