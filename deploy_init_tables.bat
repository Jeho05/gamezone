@echo off
cd /d "C:\xampp\htdocs\projet ismo"
git checkout backend-railway
git add backend_infinityfree\api\init_all_tables.php
git commit -m "Add-complete-table-initialization-script"
git push origin backend-railway
echo.
echo Done! Railway va rebuild...
echo Ensuite allez sur: https://overflowing-fulfillment-production-36c6.up.railway.app/init_all_tables.php
pause
