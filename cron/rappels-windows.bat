@echo off
rem ======================================================
rem Lanceur Windows des rappels BookPro (Planificateur de taches)
rem Journal : cron\rappels.log
rem
rem Installation (une fois, dans une invite de commandes) :
rem   schtasks /Create /TN "BookPro - rappels" /TR "C:\wamp64\www\BookPro_web\BooKPro_web\cron\rappels-windows.bat" /SC HOURLY /ST 00:05 /F
rem Suppression :
rem   schtasks /Delete /TN "BookPro - rappels" /F
rem ======================================================
cd /d "%~dp0.."
"C:\wamp64\bin\php\php8.3.6\php.exe" cron\rappels.php >> cron\rappels.log 2>&1
