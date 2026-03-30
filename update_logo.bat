@echo off
REM Script para actualizar el logo en todos los archivos HTML
echo Actualizando logo en todos los archivos HTML...

REM Actualizar alojamientos.html
powershell -Command "(Get-Content 'alojamientos.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'alojamientos.html'"

REM Actualizar agradecimientos.html
powershell -Command "(Get-Content 'agradecimientos.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'agradecimientos.html'"

REM Actualizar agregar-alojamiento.html
powershell -Command "(Get-Content 'agregar-alojamiento.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'agregar-alojamiento.html'"

REM Actualizar agregar-evento.html
powershell -Command "(Get-Content 'agregar-evento.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'agregar-evento.html'"

REM Actualizar aviso-legal.html
powershell -Command "(Get-Content 'aviso-legal.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'aviso-legal.html'"

REM Actualizar compromiso-social.html
powershell -Command "(Get-Content 'compromiso-social.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'compromiso-social.html'"

REM Actualizar dashboard.html
powershell -Command "(Get-Content 'dashboard.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'dashboard.html'"

REM Actualizar eventos-culturales.html
powershell -Command "(Get-Content 'eventos-culturales.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'eventos-culturales.html'"

REM Actualizar login.html
powershell -Command "(Get-Content 'login.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'login.html'"

REM Actualizar politica-cookies.html
powershell -Command "(Get-Content 'politica-cookies.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'politica-cookies.html'"

REM Actualizar preferences.html
powershell -Command "(Get-Content 'preferences.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'preferences.html'"

REM Actualizar register.html
powershell -Command "(Get-Content 'register.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'register.html'"

REM Actualizar rutas-turisticas.html
powershell -Command "(Get-Content 'rutas-turisticas.html') -replace 'logo_990x1076_verde.png', 'menu_images/Logo%20transparente.webp' | Set-Content 'rutas-turisticas.html'"

echo Logo actualizado en todos los archivos HTML!
pause
