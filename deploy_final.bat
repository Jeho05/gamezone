@echo off
echo Deploying final fix to Railway...
git commit -m "Fix-uploads-directory-path"
git push origin backend-railway
echo Done!
pause
