@echo off
title WashEase - All Servers
echo =========================================
echo WashEase Backend + Frontend Startup
echo =========================================
echo.

REM Set Node.js PATH
set PATH=%PATH%;C:\Program Files\nodejs

REM Start PHP Backend in new window
echo [1/2] Starting PHP Backend on http://localhost:8000 ...
start "WashEase Backend" cmd /k "cd /d C:\xampp\htdocs\washease && C:\xampp\php\php.exe -S localhost:8000 -t api"

REM Wait 2 seconds for backend to start
timeout /t 2 /nobreak

REM Start Frontend
echo [2/2] Starting Frontend Dev Server on http://localhost:5173 ...
cd /d C:\xampp\htdocs\washease\frontend
call npm run dev

pause
