@echo off
cd /d %~dp0
echo === Archivos modificados ===
git diff --name-only
echo.
echo === Archivos sin rastrear ===
git ls-files --others --exclude-standard
echo.
pause
