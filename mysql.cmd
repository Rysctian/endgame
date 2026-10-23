@echo off
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\mysql.ps1" %*
