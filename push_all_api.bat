@echo off
cd /d "C:\xampp\htdocs\projet ismo"
echo Adding all backend_infinityfree/api files...
git add backend_infinityfree/api/ --all
git add backend_infinityfree/api/admin/ --all
git add backend_infinityfree/api/auth/ --all
git add backend_infinityfree/api/content/ --all
git add backend_infinityfree/api/gamification/ --all
git add backend_infinityfree/api/shop/ --all
echo.
echo Status:
git status --short | findstr "backend_infinityfree"
echo.
echo Committing...
git commit -m "Add-all-missing-API-files"
echo.
echo Pushing...
git push origin backend-railway
echo.
echo Done!
pause
