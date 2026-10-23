@echo off
setlocal
set "PATH=%~dp0.local\php;%PATH%"
"%~dp0.local\php\php.exe" "%~dp0.local\composer.phar" %*
