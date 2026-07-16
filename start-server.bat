@echo off
cd /d "%~dp0"
where php >nul 2>nul
if %errorlevel% neq 0 (
  echo PHP was not found. Install XAMPP or PHP and add php.exe to PATH.
  pause
  exit /b 1
)
start "" http://localhost:8000
php -S localhost:8000 router.php
pause
