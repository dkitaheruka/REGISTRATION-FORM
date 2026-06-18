@echo off
setlocal enabledelayedexpansion

:: Configuration du titre de la fenetre
title CBCA Katoyi - Lanceur Automatique
color 0B

echo ==========================================================
echo       LANCEMENT AUTOMATIQUE DU SITE CBCA KATOYI
echo ==========================================================
echo.

:: 1. Detection de PHP
:: On verifie d'abord si "php" est dans le PATH de Windows
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    set PHP_PATH=php
    echo [INFO] PHP detecte dans le PATH de Windows.
) else (
    :: Sinon on cherche le chemin XAMPP par defaut
    set PHP_PATH=C:\xampp\php\php.exe
    if not exist "!PHP_PATH!" (
        echo [ERREUR] PHP est introuvable sur votre ordinateur.
        echo.
        echo Pour faire tourner le site sans XAMPP, vous devez :
        echo 1. Telecharger PHP (Zip) sur windows.php.net/download
        echo 2. L'extraire dans un dossier (ex: C:\php)
        echo 3. Ajouter ce dossier a votre variable d'environnement PATH.
        echo.
        set /p PHP_MANUAL="Ou alors, entrez le chemin complet de php.exe ici : "
        if "!PHP_MANUAL!"=="" exit
        set PHP_PATH=!PHP_MANUAL!
    )
)

:: 2. Demarrage du serveur PHP en arriere-plan
echo [INFO] Demarrage du serveur PHP sur http://localhost:8000 ...
start /min "PHP Server - CBCA Katoyi" !PHP_PATH! -S localhost:8000
if %ERRORLEVEL% NEQ 0 (
    echo [ERREUR] Impossible de lancer le serveur PHP.
    pause
    exit
)

:: 3. Attendre 2 secondes pour s'assurer que le serveur est pret
timeout /t 2 /nobreak > nul

:: 4. Ouverture du site dans le navigateur par defaut
echo [INFO] Ouverture de votre navigateur...
start http://localhost:8000

echo.
echo ==========================================================
echo [SUCCES] Votre site est maintenant en ligne localement !
echo ==========================================================
echo.
echo Voulez-vous activer l'acces public via ngrok ?
echo (Vous devez avoir ngrok installe sur votre ordinateur)
echo.
set /p NGROK_CHOICE="Activer ngrok (O/N) ? "

if /i "!NGROK_CHOICE!"=="O" (
    where ngrok >nul 2>nul
    if %ERRORLEVEL% EQU 0 (
        echo [INFO] Lancement de ngrok sur le port 8000...
        start "ngrok - CBCA Katiyi" ngrok http 8000
    ) else (
        echo [ERREUR] ngrok n'est pas installe ou pas dans le PATH.
        echo Telechargez-le sur ngrok.com et ajoutez-le au PATH.
    )
)

echo.
echo ==========================================================
echo Appuyez sur une touche pour quitter ce lanceur.
echo (Le serveur PHP et ngrok resteront actifs en arriere-plan)
echo ==========================================================
pause > nul
