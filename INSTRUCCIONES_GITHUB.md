# 📦 Instrucciones para Subir el Proyecto a GitHub

## Paso 1: Instalar Git (si no lo tienes)

1. Descarga Git desde: https://git-scm.com/download/win
2. Instala Git con las opciones por defecto
3. Reinicia tu terminal/PowerShell

## Paso 2: Configurar Git (primera vez)

Abre PowerShell o Git Bash y ejecuta:

```bash
git config --global user.name "Tu Nombre"
git config --global user.email "olgamarin@rutasrurales.io"
```

## Paso 3: Crear Repositorio en GitHub

1. Ve a https://github.com
2. Inicia sesión (o crea una cuenta si no tienes)
3. Haz clic en el botón "+" arriba a la derecha
4. Selecciona "New repository"
5. Configura el repositorio:
   - **Repository name**: `rutasrurales`
   - **Description**: "Red Unificada de Turistas, Alojamientos y Servicios - Plataforma web con IA para turismo rural en Soria"
   - **Visibility**: Public (o Private si prefieres)
   - **NO marques** "Initialize this repository with a README" (ya tenemos uno)
6. Haz clic en "Create repository"

## Paso 4: Subir el Proyecto

Abre PowerShell en la carpeta del proyecto y ejecuta estos comandos:

```bash
# Navegar a la carpeta del proyecto
cd "C:\Users\olgam\Documents\Visual Studio code proyectos\rutasrurales"

# Inicializar repositorio Git
git init

# Añadir todos los archivos
git add .

# Hacer el primer commit
git commit -m "Initial commit: Rutas - Red Unificada de Turistas, Alojamientos y Servicios"

# Añadir el repositorio remoto
git remote add origin https://github.com/Magicnoelin/rutasrurales.git

# Cambiar a la rama main
git branch -M main

# Subir los archivos a GitHub
git push -u origin main
```

## Paso 5: Verificar

1. Ve a tu repositorio en GitHub: `https://github.com/Magicnoelin/rutasrurales`
2. Deberías ver todos los archivos del proyecto
3. El README.md se mostrará automáticamente en la página principal

## 🌐 Configurar GitHub Pages (Opcional)

Para que tu sitio esté disponible en `https://Magicnoelin.github.io/rutasrurales`:

1. Ve a tu repositorio en GitHub
2. Haz clic en "Settings" (Configuración)
3. En el menú lateral, haz clic en "Pages"
4. En "Source", selecciona "main" branch
5. Haz clic en "Save"
6. Espera unos minutos y tu sitio estará disponible en la URL que te muestre

## 📝 Comandos Git Útiles para el Futuro

```bash
# Ver el estado de los archivos
git status

# Añadir cambios
git add .

# Hacer commit de los cambios
git commit -m "Descripción de los cambios"

# Subir cambios a GitHub
git push

# Descargar cambios de GitHub
git pull

# Ver el historial de commits
git log
```

## 🔗 Enlaces Útiles

- **Documentación de Git**: https://git-scm.com/doc
- **Guía de GitHub**: https://docs.github.com/es
- **GitHub Pages**: https://pages.github.com/

## ⚠️ Notas Importantes

- Asegúrate de que el archivo `.gitignore` esté presente para evitar subir archivos innecesarios
- Nunca subas información sensible (contraseñas, claves API, etc.)
- Haz commits frecuentes con mensajes descriptivos
- Si trabajas en equipo, siempre haz `git pull` antes de empezar a trabajar

## 🎯 Estructura del Repositorio

```
rutasrurales/
├── .gitignore                    # Archivos a ignorar por Git
├── index.html                    # Página principal
├── styles.css                    # Estilos
├── script.js                     # JavaScript + IA
├── Logo.png                      # Logo de Rutas
├── README.md                     # Documentación del proyecto
└── INSTRUCCIONES_GITHUB.md       # Este archivo
```

---

**¿Necesitas ayuda?** Contacta: olgamarin@rutasrurales.io
