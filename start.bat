@echo off
title Spliqo — Dev Server
echo Starting Spliqo development environment...
echo.

:: Storage link (idempotent)
php artisan storage:link 2>nul

:: Start Laravel server
start "Laravel" cmd /k "cd /d %~dp0 && php artisan serve"

:: Start Reverb (WebSocket)
start "Reverb" cmd /k "cd /d %~dp0 && php artisan reverb:start"

:: Start queue worker
start "Queue" cmd /k "cd /d %~dp0 && php artisan queue:work mongodb --tries=3 --sleep=3"

:: Start Vite (hot reload)
start "Vite" cmd /k "cd /d %~dp0 && npm run dev"

echo.
echo All processes started in separate windows.
echo.
echo   App:    http://localhost:8000
echo   Admin:  http://localhost:8000/admin
echo.
echo Press any key to stop all windows...
pause >nul
taskkill /FI "WindowTitle eq Laravel*"  /F >nul 2>&1
taskkill /FI "WindowTitle eq Reverb*"   /F >nul 2>&1
taskkill /FI "WindowTitle eq Queue*"    /F >nul 2>&1
taskkill /FI "WindowTitle eq Vite*"     /F >nul 2>&1
