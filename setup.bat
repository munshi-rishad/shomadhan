@echo off
REM ================================================================
REM  Shomadhan - setup (ek click e sob)
REM  Zip khule je kono jayga theke double-click korun, kichu chapte hobe na.
REM  (XAMPP: C:\xampp, MySQL user root, password khali)
REM  0) XAMPP bondho thakle SOBAR AGE chalu kore: MySQL + Apache (lukiye), XAMPP Control Panel o khule (choto kore).
REM     Kono prosno / "Yes" chaoa hoy na.
REM     MySQL "shutdown unexpectedly" hole nijei thik kore (fix-mysql.bat er kaj, database gulo rekhe).
REM  1) Ei folder ta (jekhane setup.bat ache) C:\xampp\htdocs\shomadhan e CUT-PASTE kore:
REM     purono copy bodle dey, shesh e ei folder ta muche dey (zip file ta thake). .git folder ta chhoy na (GitHub e push er jonno).
REM     config\database.php / config\mail.php ei folder e na thakle htdocs er ta rakhe.
REM     Ei folder ta jodi already htdocs\shomadhan hoy (git clone), copy kore na.
REM  2) "shomadhan" database DROP kore database\schema.sql abar import kore.
REM     SAVDHAN: ager sob data muche jabe.
REM  3) Startup folder e choto ekta file rakhe,
REM     jate porer bar Windows chalu holei ora nije chalu hoy.
REM  4) Chrome er "Najiat Islam" profile e bookmarks bar e 2 ta bookmark dey
REM     (age thakle notun dey na), tarpor 2 ta tab khule:
REM       Welcome page   http://localhost/shomadhan/public/
REM       phpMyAdmin     shomadhan database er table gulo
REM     Bookmark dite hole ar Chrome khola thakle Chrome ekbar bondho kore abar khule.
REM  CHROMENAME line bodle onno profile deya jay.
REM ================================================================
setlocal
set "XAMPP=C:\xampp"
set "MOVED=0"
set "CLEAN="
set "DBUSER=root"
set "DBPASS="
set "MYSQL=%XAMPP%\mysql\bin\mysql.exe"
set "MYSQLADMIN=%XAMPP%\mysql\bin\mysqladmin.exe"
set "SITE=http://localhost/shomadhan/public/"
set "PMA=http://localhost/phpmyadmin/index.php?route=/database/structure&db=shomadhan"
set "PMASQL=%XAMPP%\phpMyAdmin\sql\create_tables.sql"
REM Chrome er kon profile e khulbe: profile er NAAM likhun (Chrome er "Who's using Chrome?" page e jemon dekhay)
set "CHROMENAME=Najiat Islam"
set "CHROMEPROFILE=Default"

if "%DBPASS%"=="" (set "PASSARG=") else (set "PASSARG=-p%DBPASS%")

REM ---- SRC = ei setup.bat er folder, DEST = htdocs\shomadhan (dujonar shesh e "\" nai)
for %%I in ("%~dp0.") do set "SRC=%%~fI"
for %%I in ("%XAMPP%\htdocs\shomadhan") do set "DEST=%%~fI"

echo.
echo  ===== Shomadhan setup =====
echo.

if not exist "%MYSQL%" goto :no_xampp
if not exist "%XAMPP%\htdocs\" goto :no_xampp

REM ================================================================
REM  0) XAMPP chalu (MySQL + Apache + Control Panel), bondho thakle
REM ================================================================
if not exist "%SRC%\tools\start-xampp.ps1" goto :xampp_old
powershell -NoProfile -ExecutionPolicy Bypass -File "%SRC%\tools\start-xampp.ps1" -XamppDir "%XAMPP%"
if errorlevel 1 goto :fail
:xampp_old
echo.

REM ================================================================
REM  1) htdocs e copy
REM ================================================================
if /i "%SRC%"=="%DEST%" goto :copy_skip

REM ---- ek folder arek tar bhitore hole copy kora bipod (nijeke muche felbe), tai thami
set "CHK=%SRC%\"
call set "CHK2=%%CHK:%DEST%\=%%"
if /i not "%CHK2%"=="%CHK%" goto :copy_nested
set "CHK=%DEST%\"
call set "CHK2=%%CHK:%SRC%\=%%"
if /i not "%CHK2%"=="%CHK%" goto :copy_nested

echo  Copy korchi: %SRC%
echo         -^> %DEST%

REM ---- gopon config file: ei folder e na thakle htdocs er ta age rekhe di, pore ferot di
set "KEEP=%TEMP%\shomadhan-keep"
if not exist "%KEEP%\" mkdir "%KEEP%" >nul 2>&1
call :keep database.php
call :keep mail.php

REM ---- /MIR: purono copy hubohu ei folder er moto hoy (muche fela file o jay)
REM      /XD .git: .git folder copy o hoy na, muche o jay na
robocopy "%SRC%" "%DEST%" /MIR /XD .git /R:2 /W:2 /NP /NFL /NDL /NJH /NJS
set "RC=%ERRORLEVEL%"

REM ---- rakha config file ferot di (thik moto ferot gele tobei backup muchi)
call :restore database.php
call :restore mail.php
rmdir "%KEEP%" >nul 2>&1

REM ---- robocopy: 0-7 = thik, 8 ba beshi = error
if %RC% geq 8 goto :copy_fail
echo  [OK] Project copy hoyeche: %DEST%
REM ---- cut-paste: shesh e ei folder ta muchbo, tai ekhon theke ei folder e "boshe" thakbo na
set "MOVED=1"
cd /d "%DEST%"
goto :copy_done

:copy_skip
echo  [OK] Project already htdocs e ache, copy lage nai: %DEST%

:copy_done
REM ---- ekhon theke sob kaj htdocs er copy theke
set "ROOT=%DEST%"
set "SQL=%ROOT%\database\schema.sql"
if not exist "%SQL%" set "SQL=%ROOT%\shomadhan.sql"
if not exist "%SQL%" goto :no_sql
echo.

REM ================================================================
REM  2) Database
REM ================================================================
REM ---- MySQL cholche? na cholle chalu kori
"%MYSQLADMIN%" -u %DBUSER% %PASSARG% ping >nul 2>&1
if not errorlevel 1 goto :ready
echo  MySQL bondho. Chalu korchi...
start "MySQL (Shomadhan)" /min "%XAMPP%\mysql_start.bat"
set /a TRIES=0
:wait
timeout /t 1 /nobreak >nul
"%MYSQLADMIN%" -u %DBUSER% %PASSARG% ping >nul 2>&1
if not errorlevel 1 goto :ready
set /a TRIES+=1
if %TRIES% lss 30 goto :wait
echo  [X] MySQL chalu holo na.
echo      "MySQL shutdown unexpectedly" dekhale age fix-mysql.bat chalan, tarpor abar eta.
goto :fail

:ready
echo  [OK] MySQL cholche.
echo.
echo  Import korchi: %SQL%
"%MYSQL%" -u %DBUSER% %PASSARG% --default-character-set=utf8mb4 < "%SQL%"
if errorlevel 1 goto :import_fail

echo  [OK] Database notun kore import hoyeche.
REM ---- schema.sql e sob update already ache: database\migrations er file gulo "done" likhe rakhi (update.bat jate abar na chalay)
if exist "%XAMPP%\php\php.exe" "%XAMPP%\php\php.exe" "%ROOT%\tools\update.php" --mark-all

REM ---- phpMyAdmin er lal lekha "configuration storage is not completely configured" bondho korte
REM      phpMyAdmin er nijer table gulo banai (age thakle kichu bodlay na)
if not exist "%PMASQL%" goto :pma_done
"%MYSQL%" -u %DBUSER% %PASSARG% < "%PMASQL%" >nul 2>&1
if not errorlevel 1 echo  [OK] phpMyAdmin er setup table thik kora holo.
:pma_done
echo       Admin: 01700000001 / Admin@123
echo       Site : %SITE%
echo.

REM ================================================================
REM  3) Apache + porer bar nije chalu howa
REM ================================================================
REM ---- Apache cholche? na cholle chalu kori (site + phpMyAdmin er jonno lage)
tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if not errorlevel 1 goto :apache_ok
echo  Apache bondho. Chalu korchi...
start "Apache (Shomadhan)" /min "%XAMPP%\apache_start.bat"
timeout /t 3 /nobreak >nul
:apache_ok
echo  [OK] Apache cholche.

REM ---- Windows e login korlei MySQL + Apache lukiye chalu hobe (Startup folder e .vbs)
if exist "%ROOT%\tools\setup-autostart.ps1" powershell -NoProfile -ExecutionPolicy Bypass -File "%ROOT%\tools\setup-autostart.ps1" -XamppDir "%XAMPP%"
if not exist "%ROOT%\tools\setup-autostart.ps1" echo  [!] tools\setup-autostart.ps1 pawa jayni, auto-start deya holo na.
echo.

REM ================================================================
REM  4) Chrome: profile khuji, bookmark dei, 2 ta tab khuli
REM ================================================================
set "PROFFILE=%TEMP%\shomadhan-chrome-profile.txt"
del "%PROFFILE%" >nul 2>&1
if exist "%ROOT%\tools\setup-chrome.ps1" powershell -NoProfile -ExecutionPolicy Bypass -File "%ROOT%\tools\setup-chrome.ps1" -ProfileName "%CHROMENAME%" -SiteUrl "%SITE%" -PmaUrl "%PMA%" -OutFile "%PROFFILE%"
if not exist "%ROOT%\tools\setup-chrome.ps1" echo  [!] tools\setup-chrome.ps1 pawa jayni, bookmark deya holo na.
if exist "%PROFFILE%" set /p "CHROMEPROFILE=" < "%PROFFILE%"
del "%PROFFILE%" >nul 2>&1

set "CHROME="
if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" set "CHROME=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
if not defined CHROME if exist "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe" set "CHROME=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
if not defined CHROME if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" set "CHROME=%LocalAppData%\Google\Chrome\Application\chrome.exe"
if defined CHROME (
    start "" "%CHROME%" --profile-directory="%CHROMEPROFILE%" "%SITE%" "%PMA%"
) else (
    echo  Chrome pawa jayni, default browser e khulchi.
    start "" "%SITE%"
    start "" "%PMA%"
)
echo  [OK] Browser e welcome page ar phpMyAdmin khula hoyeche.
echo.
echo  Setup shes. Porer bar shudhu Chrome khule bookmark e click korlei hobe.
if "%MOVED%"=="1" call :remove_src
echo  Ei window 10 second por nije bondho hobe.
timeout /t 10 >nul
if defined CLEAN start "" /min "%CLEAN%" & endlocal & exit /b 0
endlocal
exit /b 0

REM ================================================================
REM  Error gulo
REM ================================================================
:no_xampp
echo  [X] XAMPP pawa jayni: %XAMPP%
echo      XAMPP onno folder e thakle ei file er "set XAMPP=" line ta bodlan.
goto :fail

:copy_nested
echo  [X] Ei folder ta htdocs\shomadhan er bhitore (ba upore), tai copy kora jabe na:
echo      %SRC%
echo      Zip ta Downloads e khule sekhan theke setup.bat chalan.
goto :fail

:copy_fail
echo  [X] Copy hoyni (robocopy error %RC%).
echo      htdocs\shomadhan er kono file onno program e khola thakle bondho kore abar chalan.
goto :fail

:no_sql
echo  [X] SQL file pawa jayni: %ROOT%\database\schema.sql
goto :fail

:import_fail
echo  [X] Import e error hoyeche. Upore lal lekha ta dekhun.
goto :fail

:fail
echo.
echo  Kaj hoyni.
echo.
pause
endlocal
exit /b 1

REM ================================================================
REM  Choto kaj (call kore chalai)
REM ================================================================
REM ---- :remove_src = cut-paste er "cut": htdocs e copy thik moto hole, zip theke khola ei folder ta muche dei.
REM      Ekhon muchle ei setup.bat nijer folder muche felbe, tai TEMP e choto ekta file banai,
REM      setup shes hole sei file ta 2 second por por (sorbochcho 1 minute) folder ta muchte chesta kore.
REM      Folder e .git thakle (GitHub er kaj er folder) ba eta drive er root hole kokhono muchi na.
:remove_src
set "CLEAN="
if not exist "%SRC%\setup.bat" goto :eof
if not exist "%SRC%\database\schema.sql" goto :eof
if not exist "%DEST%\database\schema.sql" goto :eof
if exist "%SRC%\.git\" (
    echo  [i] Ei folder e .git ache, tai muchlam na: %SRC%
    goto :eof
)
for %%I in ("%SRC%") do if "%%~pnxI"=="\" goto :eof
REM ---- zip khulle Windows ekta bairer folder o banay (jemon Downloads\shomadhan (1)\shomadhan).
REM      Sei bairer folder er naam "shomadhan..." hole ar khali hoye gele seta o muchi (khali na hole thake).
set "PARENT="
set "PARENTNAME="
for %%I in ("%SRC%\..") do (
    set "PARENTNAME=%%~nxI"
    set "PARENT=%%~fI"
)
if /i not "%PARENTNAME:~0,9%"=="shomadhan" set "PARENT="
set "CLEAN=%TEMP%\shomadhan-cleanup.bat"
> "%CLEAN%" echo @echo off
>>"%CLEAN%" echo cd /d "%TEMP%"
>>"%CLEAN%" echo set N=0
>>"%CLEAN%" echo :again
>>"%CLEAN%" echo timeout /t 2 /nobreak ^>nul
>>"%CLEAN%" echo rmdir /s /q "%SRC%" 2^>nul
>>"%CLEAN%" echo set /a N+=1
>>"%CLEAN%" echo if exist "%SRC%\" if %%N%% lss 30 goto again
if defined PARENT >>"%CLEAN%" echo rmdir "%PARENT%" 2^>nul
>>"%CLEAN%" echo (goto) 2^>nul ^& del "%%~f0"
echo  [OK] Cut-paste: zip theke khola folder ta ektu por muche jabe:
echo       %SRC%
if defined PARENT echo       (bairer "%PARENTNAME%" folder o, khali hole)
echo       Ekhon theke project ache shudhu: %DEST%
goto :eof

REM ---- :keep FILE = ei folder e config\FILE na thakle htdocs er ta KEEP e rakhi
:keep
if exist "%SRC%\config\%~1" goto :eof
if not exist "%DEST%\config\%~1" goto :eof
copy /Y "%DEST%\config\%~1" "%KEEP%\%~1" >nul
goto :eof

REM ---- :restore FILE = KEEP er file ta htdocs e ferot di; thik moto gele tobei KEEP theke muchi
:restore
if exist "%SRC%\config\%~1" goto :eof
if not exist "%KEEP%\%~1" goto :eof
if not exist "%DEST%\config\" mkdir "%DEST%\config" >nul 2>&1
copy /Y "%KEEP%\%~1" "%DEST%\config\%~1" >nul
if errorlevel 1 goto :eof
del "%KEEP%\%~1" >nul 2>&1
goto :eof
