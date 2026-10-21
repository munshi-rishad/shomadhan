@echo off
chcp 65001 >nul
REM ================================================================
REM  update.bat - database update, DATA MUCHE NA (setup.bat er moto notun kore import kore na)
REM  1) MySQL bondho thakle chalu kore
REM  2) Age pura database er backup rakhe: backups\shomadhan-<date>.sql
REM  3) database\migrations\ er notun .sql file gulo chalay (php tools\update.php)
REM  XAMPP C:\xampp e dhore neya (na hole niche XAMPP= bodlan)
REM ================================================================
setlocal
set "XAMPP=C:\xampp"
set "ROOT=%~dp0"
if "%ROOT:~-1%"=="\" set "ROOT=%ROOT:~0,-1%"
set "PHP=%XAMPP%\php\php.exe"
set "MYSQLADMIN=%XAMPP%\mysql\bin\mysqladmin.exe"
set "DUMP=%XAMPP%\mysql\bin\mysqldump.exe"

if not exist "%PHP%" (
    echo  [X] PHP pawa jayni: %PHP%
    goto :fail
)

REM ---- 1) MySQL
"%MYSQLADMIN%" -u root ping >nul 2>&1
if not errorlevel 1 goto :ready
echo  MySQL bondho. Chalu korchi...
start "MySQL (Shomadhan)" /min "%XAMPP%\mysql_start.bat"
set /a TRIES=0
:wait
timeout /t 1 /nobreak >nul
"%MYSQLADMIN%" -u root ping >nul 2>&1
if not errorlevel 1 goto :ready
set /a TRIES+=1
if %TRIES% lss 30 goto :wait
echo  [X] MySQL chalu holo na. fix-mysql.bat chalan, tarpor abar eta.
goto :fail

:ready
echo  [OK] MySQL cholche.

REM ---- 2) Backup (tarikh + somoy diye nam)
if not exist "%ROOT%\backups" mkdir "%ROOT%\backups"
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HHmm"') do set "STAMP=%%i"
set "BACKUP=%ROOT%\backups\shomadhan-%STAMP%.sql"
"%DUMP%" -u root --default-character-set=utf8mb4 --single-transaction --routines shomadhan > "%BACKUP%"
if errorlevel 1 (
    echo  [X] Backup nite parlam na, tai update o korlam na.
    goto :fail
)
echo  [OK] Backup: %BACKUP%

REM ---- 3) Update
"%PHP%" "%ROOT%\tools\update.php"
if errorlevel 1 goto :fail
echo.
echo  Sob thik ache. 10 second por nije bondho hobe.
timeout /t 10 >nul
exit /b 0

:fail
echo.
pause
exit /b 1
