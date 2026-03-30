@echo off
echo ========================================
echo Subiendo archivo corregido al servidor
echo ========================================
echo.

git add api/get_nearby_content.php
git commit -m "Fix: Corregir parametros SQL en get_nearby_content.php para Google Rich Results"
git push origin main

echo.
echo ========================================
echo Archivo subido correctamente
echo ========================================
echo.
echo IMPORTANTE: Espera 1-2 minutos para que el servidor actualice el archivo
echo Luego prueba: https://rutasrurales.io/api/get_nearby_content.php?accommodation_id=50
echo.
pause
