@echo off
cd /d "C:\xampp\htdocs\projet ismo"
git add setup_complete.php
git commit -m "Add-debug-env-loading"
git push origin backend-railway:main
echo Done! Attendre 2 min puis rafraichir
pause
