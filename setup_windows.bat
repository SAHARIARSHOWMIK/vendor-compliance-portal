@echo off
setlocal
cd /d "%~dp0"

echo ===============================================
echo Vendor Compliance Portal - Windows Setup
echo ===============================================

where php >nul 2>nul
if errorlevel 1 (
  echo ERROR: PHP is not installed or not in PATH.
  echo Install PHP 8.2+ or use Docker Desktop.
  pause
  exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
  echo ERROR: Composer is not installed or not in PATH.
  echo Install Composer first: https://getcomposer.org/download/
  pause
  exit /b 1
)

where npm >nul 2>nul
if errorlevel 1 (
  echo ERROR: Node.js/npm is not installed or not in PATH.
  echo Install Node.js LTS first.
  pause
  exit /b 1
)

if not exist .env copy .env.example .env
composer install
npm install
php artisan key:generate

echo.
echo Setup finished.
echo Next terminal 1: start_laravel.bat
echo Next terminal 2: start_vite.bat
pause
