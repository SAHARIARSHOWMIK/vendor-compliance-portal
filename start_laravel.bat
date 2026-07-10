@echo off
setlocal
cd /d "%~dp0"
echo Starting VendorGuard backend on http://127.0.0.1:8000
php artisan serve --host=127.0.0.1 --port=8000
pause
