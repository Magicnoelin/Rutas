@echo off
echo ========================================
echo  SUBIR PROYECTO RUTAS A GITHUB
echo ========================================
echo.

REM Verificar si Git está instalado
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Git no está instalado.
    echo.
    echo Por favor, instala Git desde: https://git-scm.com/download/win
    echo Después de instalar, reinicia esta ventana y ejecuta este script de nuevo.
    echo.
    pause
    exit /b 1
)

echo [OK] Git está instalado
echo.

REM Navegar a la carpeta del proyecto
cd /d "%~dp0"

echo Inicializando repositorio Git...
git init

echo.
echo Configurando usuario de Git...
git config user.name "Magicnoelin"
git config user.email "olgamarin@rutasrurales.io"

echo.
echo Añadiendo archivos al repositorio...
git add .

echo.
echo Haciendo commit inicial...
git commit -m "Initial commit: Rutas - Red Unificada de Turistas, Alojamientos y Servicios"

echo.
echo Conectando con GitHub...
git remote add origin https://github.com/Magicnoelin/rutasrurales.git

echo.
echo Cambiando a rama main...
git branch -M main

echo.
echo ========================================
echo  IMPORTANTE: AUTENTICACIÓN REQUERIDA
echo ========================================
echo.
echo GitHub te pedirá que te autentiques.
echo Opciones:
echo   1. Usar GitHub Desktop (recomendado para principiantes)
echo   2. Usar Personal Access Token
echo   3. Usar SSH keys
echo.
echo Si no tienes configurada la autenticación, visita:
echo https://docs.github.com/es/authentication
echo.
pause

echo.
echo Subiendo archivos a GitHub...
git push -u origin main

echo.
echo ========================================
echo  PROCESO COMPLETADO
echo ========================================
echo.
echo Tu repositorio debería estar disponible en:
echo https://github.com/Magicnoelin/rutasrurales
echo.
echo Para activar GitHub Pages:
echo 1. Ve a tu repositorio en GitHub
echo 2. Settings ^> Pages
echo 3. Source: main branch
echo 4. Save
echo.
echo Tu sitio estará en:
echo https://Magicnoelin.github.io/rutasrurales
echo.
pause
