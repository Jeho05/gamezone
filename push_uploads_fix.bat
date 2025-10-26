@echo off
echo Committing uploads directory fix...
git commit -m "Fix uploads directory permissions for Railway"
echo.
echo Pushing to backend-railway branch...
git push origin backend-railway
echo.
echo Done! Railway will redeploy automatically.
echo.
echo This will fix the uploads directory issue.
pause
