@echo off
title Easy!Appointments Server
echo ===================================================
echo   Dang khoi dong may chu Easy!Appointments...
echo ===================================================
echo.
echo   - Trang dat lich (Khach hang) : http://localhost:8000
echo   - Trang dang nhap quan tri     : http://localhost:8000/login
echo   - Tai khoan mac dinh          : administrator / administrator
echo.
echo ===================================================
echo Nhan Ctrl + C de dung may chu.
echo ===================================================
"C:\xampp\php\php.exe" -S 127.0.0.1:8000 router.php
pause
