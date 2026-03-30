# Dashboard de Usuarios (Propietarios)

Este directorio contiene los archivos PHP para el panel de control de **propietarios de alojamientos**.

## Estructura

```
dashboard/
├── .htaccess                      # Configuración de seguridad
├── editar-mi-alojamiento.php      # Formulario de edición para propietarios
├── guardar-mi-alojamiento.php     # Procesa los cambios del formulario
├── guardar-orden-fotos.php        # Guarda el orden de las fotos
└── gestion-fotos-v2.html          # Interfaz para ordenar fotos (arrastrar)
```

## Diferencias con otras carpetas

### `/dashboard/` (Este directorio)
- **Usuarios:** Propietarios de alojamientos
- **Acceso:** Requiere sesión de usuario registrado
- **Funciones:** Editar su propio alojamiento, gestionar fotos
- **Permisos:** Solo pueden editar sus propios recursos

### `/admin_tablas/`
- **Usuarios:** Administradores del sitio
- **Acceso:** Requiere autenticación HTTP (.htpasswd)
- **Funciones:** Gestión completa de todos los recursos
- **Permisos:** Acceso total a la base de datos

### `/` (Raíz)
- **Usuarios:** Público general
- **Acceso:** Libre
- **Funciones:** Visualización de contenido público

## Acceso

Los propietarios acceden desde:
- **URL:** `https://rutasrurales.io/user-dashboard.html`
- **Botón:** "Editar" en la sección "Mis Alojamientos"
- **Destino:** `/dashboard/editar-mi-alojamiento.php?id={id}`

## Seguridad

1. **Verificación de sesión:** Todos los archivos PHP verifican `$_SESSION['user_id']`
2. **Verificación de permisos:** Se comprueba en `user_resources` que el usuario sea propietario
3. **Sin listado de directorios:** Configurado en `.htaccess`
4. **Rutas relativas:** Usa `../api/config.php` para acceder a la configuración

## Cambios Recientes (24/02/2026)

### Problema Resuelto
El archivo `editar-mi-alojamiento.php` mostraba código en lugar de ejecutarse.

### Solución Aplicada
1. **`.htaccess` principal:** Añadido `dashboard` a la lista de carpetas excluidas de reescritura
2. **Archivo duplicado:** Eliminado `/editar-mi-alojamiento.php` (raíz) - era redundante
3. **Seguridad:** Creado `dashboard/.htaccess` con configuración específica
4. **Estructura:** Consolidada la separación entre usuarios y administradores

### Archivos Modificados
- `/.htaccess` - Línea 11: Añadido `dashboard` a carpetas del sistema
- `/editar-mi-alojamiento.php` - ELIMINADO (duplicado)
- `/dashboard/.htaccess` - CREADO (seguridad)

## Notas Importantes

- **NO** mover archivos de esta carpeta a la raíz
- **NO** mezclar lógica de admin con lógica de usuario
- Los propietarios **NO** pueden cambiar:
  - Capacidad del alojamiento (readonly)
  - Fotos (solo visualización, se gestionan desde otra herramienta)
- Después de guardar, redirige a: `https://rutasrurales.io/user-dashboard.html#mis-alojamientos`
