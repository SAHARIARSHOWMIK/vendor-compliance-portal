@echo off
setlocal
cd /d "%~dp0"
echo Starting Vite development server on http://127.0.0.1:5173
npm run dev -- --host 127.0.0.1
pause
