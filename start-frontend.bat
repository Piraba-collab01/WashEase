@echo off
title WashEase - Frontend Dev Server
echo Starting WashEase React Frontend Dev Server...
echo.
set PATH=%PATH%;C:\Program Files\nodejs
cd /d C:\xampp\htdocs\washease\frontend
echo Frontend folder: %CD%
echo Running: npm run dev
echo.
call npm run dev
pause
