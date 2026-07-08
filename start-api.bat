@echo off
title WashEase - PHP Backend
echo Starting WashEase PHP REST API Server on http://localhost:8000 ...
echo.
cd /d C:\xampp\htdocs\washease
echo Backend folder: %CD%
echo Running: php -S localhost:8000 -t api
echo.
C:\xampp\php\php.exe -S localhost:8000 -t %CD%\api
pause
