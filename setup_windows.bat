@echo off
setlocal
cd /d "%~dp0"

echo ==================================================
echo VendorGuard - Windows Setup
echo ==================================================

where php >nul 2>nul || (echo ERROR: Install PHP 8.2 or newer and add it to PATH.& pause & exit /b 1)
where composer >nul 2>nul || (echo ERROR: Install Composer and add it to PATH.& pause & exit /b 1)
where npm >nul 2>nul || (echo ERROR: Install Node.js 20 or newer and add npm to PATH.& pause & exit /b 1)

if not exist .env copy .env.sqlite.example .env
if not exist database mkdir database
if not exist database\database.sqlite type nul > database\database.sqlite
if not exist storage\app\private\vendor-documents mkdir storage\app\private\vendor-documents
if not exist storage\framework\cache\data mkdir storage\framework\cache\data
if not exist storage\framework\sessions mkdir storage\framework\sessions
if not exist storage\framework\views mkdir storage\framework\views
if not exist storage\logs mkdir storage\logs

composer install --no-interaction --prefer-dist
if errorlevel 1 (echo ERROR: Composer installation failed.& pause & exit /b 1)

npm install
if errorlevel 1 (echo ERROR: npm installation failed.& pause & exit /b 1)

php artisan key:generate --force
if errorlevel 1 (echo ERROR: Application key generation failed.& pause & exit /b 1)

php artisan migrate --force
if errorlevel 1 (echo ERROR: Database migration failed.& pause & exit /b 1)

npm run build
if errorlevel 1 (echo ERROR: Frontend build failed.& pause & exit /b 1)

echo.
echo Setup completed successfully.
echo Next: run seed_demo.bat, then start_laravel.bat and start_vite.bat.
pause
