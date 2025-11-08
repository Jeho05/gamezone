@echo off
echo.
echo ================================
echo Simple Git Push Script - Backend
echo ================================
echo.

cd /d "c:\xampp\htdocs\EXAMEN\projet ismo"

echo Adding all changes...
git add .

echo.
echo Committing changes...
git commit -m "Update fixes" --allow-empty

echo.
echo Pushing to remote repository...
git push origin main

echo.
echo ================================
echo Push completed successfully!
echo ================================
echo.
pause
