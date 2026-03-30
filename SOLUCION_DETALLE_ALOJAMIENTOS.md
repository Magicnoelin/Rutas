# Solución: Código PHP visible en páginas de detalle

## Problema
Al acceder a URLs como `https://rutasrurales.io/alojamientos/casa-amrita`, se muestra código PHP en lugar de la página renderizada.

## Causas Posibles
1. **PHP no está siendo procesado** por el servidor
2. **Archivo PHP con error de sintaxis**
3. **Configuración incorrecta del .htaccess**
4. **Permisos de archivos incorrectos**

## Soluciones Implementadas

### 1. ✅ Corregido archivo `detalle-alojamiento.php`
**Problema:** Faltaba la etiqueta de cierre `?>`

**Antes:**
```php
<?php
require_once 'detalle-alojamiento-contenido.php';
```

**Después:**
```php
<?php
require_once 'detalle-alojamiento-contenido.php';
?>
```

### 2. ✅ Corregidas rutas absolutas en `detalle-alojamiento-contenido.php`
Todos los recursos ahora usan rutas absolutas (con `/` al inicio):
- CSS: `/styles.css`
- Favicon: `/favicon.png`
- Logo: `/logo_990x1076_verde.png`
- Enlaces: `/index.html`, `/alojamientos-turisticos.html`, etc.

### 3. ✅ Actualizado `.htaccess`
Añadidas directivas para asegurar que PHP se procesa correctamente:
```apache
# Asegurar que PHP está habilitado
AddHandler application/x-httpd-php .php
AddType application/x-httpd-php .php

RewriteEngine On
RewriteBase /
```

## Pasos de Verificación

### PASO 1: Verificar que PHP funciona
1. Accede a: `https://rutasrurales.io/test-php-simple.php`
2. **Deberías ver:** Una página con información detallada de PHP (phpinfo)
3. **Si ves código:** PHP NO está funcionando en tu servidor

### PASO 2: Verificar permisos de archivos
En tu servidor, asegúrate de que los archivos tienen los permisos correctos:
```bash
# Archivos PHP deben tener permisos 644
chmod 644 detalle-alojamiento.php
chmod 644 detalle-alojamiento-contenido.php

# .htaccess debe tener permisos 644
chmod 644 .htaccess

# Directorios deben tener permisos 755
chmod 755 .
chmod 755 api/
```

### PASO 3: Verificar la reescritura de URLs
1. Accede directamente a: `https://rutasrurales.io/detalle-alojamiento.php?slug=casa-amrita`
2. **Si funciona:** El problema está en el `.htaccess` (rewrite rules)
3. **Si no funciona:** El problema está en los archivos PHP

### PASO 4: Limpiar caché del navegador
1. Presiona `Ctrl + Shift + R` (Windows/Linux) o `Cmd + Shift + R` (Mac)
2. O abre en modo incógnito
3. O limpia la caché del navegador completamente

## Soluciones Adicionales según el Hosting

### Si usas **cPanel / SiteGround / Hostinger**:
1. Ve a **Panel de Control → PHP Manager** o **PHP Settings**
2. Asegúrate de que PHP está habilitado
3. Verifica que la versión de PHP sea 7.4 o superior
4. Activa el módulo `mod_rewrite` si está disponible

### Si usas **Apache local (XAMPP/WAMP)**:
1. Verifica que Apache está corriendo
2. Verifica que el módulo `mod_rewrite` está habilitado:
   ```apache
   # En httpd.conf, asegúrate de que esta línea NO está comentada:
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Reinicia Apache

### Si el problema persiste:

#### Opción A: Usar páginas HTML estáticas con JavaScript
Puedes cambiar el enfoque y crear `detalle-alojamiento.html` que cargue los datos vía JavaScript:

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle Alojamiento</title>
    <link rel="stylesheet" href="/styles.css">
</head>
<body>
    <div id="contenido">Cargando...</div>
    <script>
        // Obtener slug de la URL
        const slug = window.location.pathname.split('/').pop();
        
        // Cargar datos del alojamiento
        fetch(`/api/alojamientos.php?slug=${slug}`)
            .then(r => r.json())
            .then(data => {
                // Renderizar el contenido
                document.getElementById('contenido').innerHTML = `
                    <h1>${data.name}</h1>
                    <!-- resto del HTML -->
                `;
            });
    </script>
</body>
</html>
```

#### Opción B: Contactar al soporte de tu hosting
Si nada funciona, contacta al soporte técnico de tu hosting con esta información:
- "Las URLs con mod_rewrite funcionan, pero PHP no se está ejecutando"
- "Necesito que habiliten el procesamiento de PHP para archivos .php"
- "mod_rewrite está funcionando pero el contenido PHP se muestra como texto"

## Verificación Final

Para confirmar que todo funciona:

1. ✅ Accede a `https://rutasrurales.io/test-php-simple.php` → Debe mostrar phpinfo()
2. ✅ Accede a `https://rutasrurales.io/detalle-alojamiento.php?slug=casa-amrita` → Debe mostrar la página
3. ✅ Accede a `https://rutasrurales.io/alojamientos/casa-amrita` → Debe mostrar la página

## Comandos útiles para debugging

### Ver logs de Apache (si tienes acceso):
```bash
tail -f /var/log/apache2/error.log
```

### Probar la configuración de Apache:
```bash
apachectl configtest
```

### Verificar módulos de Apache cargados:
```bash
apache2ctl -M | grep rewrite
apache2ctl -M | grep php
```

## Archivos Modificados
- ✅ `detalle-alojamiento.php` - Añadida etiqueta de cierre PHP
- ✅ `detalle-alojamiento-contenido.php` - Corregidas todas las rutas a absolutas
- ✅ `.htaccess` - Añadidas directivas PHP y RewriteBase
- ✅ `test-php-simple.php` - Creado para diagnóstico

## Conclusión
Si después de todas estas correcciones sigue mostrando código PHP, el problema está en la configuración del servidor web (Apache/nginx) y necesitarás contactar a tu proveedor de hosting para que habiliten el procesamiento de PHP correctamente.
