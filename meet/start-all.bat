@echo off
echo Starting Video Conference Platform for 150+ Attendees
echo.
echo Opening 3 terminals...
echo.

start cmd /k "echo Laravel Server && php artisan serve"
timeout /t 2 /nobreak >nul

start cmd /k "echo Reverb WebSocket && php artisan reverb:start"
timeout /t 2 /nobreak >nul

start cmd /k "echo Mediasoup SFU (150+ users) && npm run sfu"

echo.
echo All services starting...
echo.
echo Access: http://127.0.0.1:8000
echo.
pause
