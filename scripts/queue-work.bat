@echo off
setlocal enabledelayedexpansion
set tries=3

echo Starting Laravel queue worker (database)
:loop
php artisan queue:work database --tries=%tries% --sleep=3 --timeout=60
echo Queue worker exited, restarting in 2s...
timeout /t 2 /nobreak >nul
goto loop
