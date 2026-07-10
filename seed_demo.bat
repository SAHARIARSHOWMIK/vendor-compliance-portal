@echo off
setlocal
cd /d "%~dp0"
echo Resetting database and loading controlled showcase data...
php artisan migrate:fresh --seed --seeder=DemoSeeder
if errorlevel 1 (
  echo Demo seeding failed.
  pause
  exit /b 1
)
echo Demo data loaded.
echo Login: super.admin@demo.test / password
pause
