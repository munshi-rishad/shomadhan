@echo off
REM ================================================================
REM  Shomadhan - fix "Error: MySQL shutdown unexpectedly" (XAMPP)
REM  XAMPP er nijer backup theke MySQL er system file thik kore.
REM  Apnar database folder gulo (shomadhan etc.) rakha hoy.
REM  Puro ager data folder ta "data_old_<tarikh>" name e rekhe dey (kichu hariye jay na).
REM  XAMPP Control Panel e MySQL STOP kore tarpor double-click korun.
REM ================================================================
setlocal
set "XAMPP=C:\xampp"
set "DATA=%XAMPP%\mysql\data"
set "BACKUP=%XAMPP%\mysql\backup"

echo.
echo  ===== MySQL fix (XAMPP) =====
echo.
if not exist "%BACKUP%\" (
    echo  [X] XAMPP backup folder pawa jayni: %BACKUP%
    goto :fail
)
if not exist "%DATA%\" (
    echo  [X] MySQL data folder pawa jayni: %DATA%
    goto :fail
)

set /p "OK=  MySQL bondho kore data folder thik korbo. Sure? (Y/N): "
if /i not "%OK%"=="Y" (
    echo  Bati hoyeche, kichu bodlay ni.
    goto :end
)

REM ---- MySQL cholle bondho kori
taskkill /F /IM mysqld.exe >nul 2>&1
timeout /t 2 /nobreak >nul

REM ---- tarikh-shomoy diye nam (jemon 20261020_142530)
for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "TS=%%i"
set "OLD=%XAMPP%\mysql\data_old_%TS%"

echo  1/4 Ager data folder rakhchi: %OLD%
move "%DATA%" "%OLD%" >nul
if errorlevel 1 (
    echo  [X] data folder sorano gelo na. XAMPP Control Panel ar MySQL bondho kore abar chalan.
    goto :fail
)

echo  2/4 XAMPP backup theke notun data folder banachchi
xcopy "%BACKUP%" "%DATA%\" /E /I /H /Y /Q >nul
if errorlevel 1 (
    echo  [X] Copy hoyni. Ager folder ferot dichchi.
    rmdir /S /Q "%DATA%" >nul 2>&1
    move "%OLD%" "%DATA%" >nul
    goto :fail
)

echo  3/4 Apnar database folder gulo ferot anchi
for /d %%D in ("%OLD%\*") do (
    if /i not "%%~nxD"=="mysql" if /i not "%%~nxD"=="performance_schema" if /i not "%%~nxD"=="phpmyadmin" if /i not "%%~nxD"=="test" (
        xcopy "%%D" "%DATA%\%%~nxD\" /E /I /H /Y /Q >nul
    )
)

echo  4/4 ibdata1 ferot anchi
if exist "%OLD%\ibdata1" copy /Y "%OLD%\ibdata1" "%DATA%\ibdata1" >nul

echo.
echo  [OK] Hoye geche. Ekhon XAMPP Control Panel e MySQL Start korun.
echo       Tarpor shomadhan database notun kore chaile setup.bat chalan.
echo       Shob thik thakle pore "%OLD%" folder ta muche dite paren.
goto :end

:fail
echo.
echo  Kaj hoyni.
:end
echo.
pause
endlocal
