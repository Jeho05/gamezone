@echo off
echo Committing auto-install database fix...
git commit -m "Add auto-install for Railway database initialization"
echo.
echo Pushing to backend-railway branch...
git push origin backend-railway
echo.
echo Done! Railway will rebuild and auto-install database.
echo.
echo This will fix all 500 errors by creating missing tables.
pause
