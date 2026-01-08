@echo off
echo ========================================
echo    Interface SQLite en ligne de commande
echo ========================================
echo.

:menu
echo Choisissez une base de donnees:
echo 1. Base de configuration (siem_config.db)
echo 2. Base SUSDR 360 (susdr360.db)
echo 3. Quitter
echo.
set /p choice="Votre choix (1-3): "

if "%choice%"=="1" goto config_db
if "%choice%"=="2" goto susdr360_db
if "%choice%"=="3" goto end
goto menu

:config_db
echo.
echo Connexion a la base de configuration...
if exist "config\siem_config.db" (
    sqlite3 "config\siem_config.db"
) else (
    echo Erreur: Base de donnees non trouvee: config\siem_config.db
    pause
)
goto menu

:susdr360_db
echo.
echo Connexion a la base SUSDR 360...
if exist "susdr360\data\susdr360.db" (
    sqlite3 "susdr360\data\susdr360.db"
) else (
    echo Erreur: Base de donnees non trouvee: susdr360\data\susdr360.db
    pause
)
goto menu

:end
echo Au revoir!
pause
