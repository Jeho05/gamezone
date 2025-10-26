@echo off
echo Copie complete du backend local vers backend_infinityfree...

REM Copier tous les dossiers et fichiers
xcopy /E /I /Y api\admin backend_infinityfree\api\admin
xcopy /E /I /Y api\auth backend_infinityfree\api\auth
xcopy /E /I /Y api\content backend_infinityfree\api\content
xcopy /E /I /Y api\cron backend_infinityfree\api\cron
xcopy /E /I /Y api\events backend_infinityfree\api\events
xcopy /E /I /Y api\examples backend_infinityfree\api\examples
xcopy /E /I /Y api\gallery backend_infinityfree\api\gallery
xcopy /E /I /Y api\gamification backend_infinityfree\api\gamification
xcopy /E /I /Y api\helpers backend_infinityfree\api\helpers
xcopy /E /I /Y api\invoices backend_infinityfree\api\invoices
xcopy /E /I /Y api\leaderboard backend_infinityfree\api\leaderboard
xcopy /E /I /Y api\middleware backend_infinityfree\api\middleware
xcopy /E /I /Y api\migrations backend_infinityfree\api\migrations
xcopy /E /I /Y api\player backend_infinityfree\api\player
xcopy /E /I /Y api\points backend_infinityfree\api\points
xcopy /E /I /Y api\reservations backend_infinityfree\api\reservations
xcopy /E /I /Y api\rewards backend_infinityfree\api\rewards
xcopy /E /I /Y api\sessions backend_infinityfree\api\sessions
xcopy /E /I /Y api\shop backend_infinityfree\api\shop
xcopy /E /I /Y api\test backend_infinityfree\api\test
xcopy /E /I /Y api\tournaments backend_infinityfree\api\tournaments
xcopy /E /I /Y api\transactions backend_infinityfree\api\transactions
xcopy /E /I /Y api\upload backend_infinityfree\api\upload
xcopy /E /I /Y api\users backend_infinityfree\api\users
xcopy /E /I /Y api\utils backend_infinityfree\api\utils

echo.
echo Copie terminee!
echo.
pause
