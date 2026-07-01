@echo off
setlocal
cd /d "%~dp0"

echo Starting Vite frontend dev server
npm run dev
pause
