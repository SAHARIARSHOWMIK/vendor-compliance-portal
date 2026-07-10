@echo off
setlocal
cd /d "%~dp0"
php artisan test
if errorlevel 1 (
  echo Tests failed.
  pause
  exit /b 1
)
npm run build
pause
