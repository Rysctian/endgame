@echo off
setlocal
cd /d "%~dp0"
set "PATH=%~dp0.local\php;%PATH%"
call "%~dp0mysql.cmd" start
if errorlevel 1 exit /b %errorlevel%
call "%~dp0composer.cmd" run dev
