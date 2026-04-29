## 📁 Estructura de archivos a subir

```
alojamiento-modular/
├── index.php                    (Página principal)
├── .htaccess                   (Configuración de URLs)
├── README.md                   (Documentación)
├── test.html                   (Página de prueba)
├── INSTRUCCIONES_FILEZILLA.md  (Este archivo)
├── api/
│   └── alojamiento-data.php    (API para datos)
├── css/
│   └── alojamiento.css         (CSS diferido)
├── js/
│   └── alojamiento.js          (JavaScript modular)
└── modules/
    ├── hero.php                (Módulo hero)
    ├── galeria.php             (Módulo galería)
    ├── descripcion.php         (Módulo descripción)
    ├── contacto.php            (Módulo contacto)
    ├── mapa.php                (Módulo mapa)
    ├── cercanos.php            (Módulo contenido cercano)
    └── cta.php                 (Módulo CTA)
```

## 📤 Pasos para subir con FileZilla

1. **Conectar al servidor**:
   - Host: `rutasrurales.io`
   - Usuario: (tu usuario)
   - Contraseña: (tu contraseña)
   - Puerto: 21 (FTP) o 22 (SFTP)

2. **Navegar al directorio correcto**:
   - Ir a: `/public_html/` (o donde esté el sitio web)

3. **Crear carpeta** (si no existe):
   - Crear carpeta `alojamiento-modular` en `/public_html/`

4. **Subir archivos**:
   - Arrastrar TODA la carpeta `alojamiento-modular/` desde tu computadora
   - Asegurarse de que se suban todos los subdirectorios (`api/`, `css/`, `js/`, `modules/`)

5. **Verificar permisos**:
   - Archivos PHP: 644 (rw-r--r--)
   - Directorios: 755 (rwxr-xr-x)

## 🔗 URLs para probar

1. **Página de prueba**: `https://rutasrurales.io/alojamiento-modular/test.html`
2. **Página con slug**: `https://rutasrurales.io/alojamiento-modular/index.php?slug=casa-enrique`
3. **API**: `https://rutasrurales.io/alojamiento-modular/api/alojamiento-data.php?slug=casa-enrique&mode=minimal`

## 🛠️ Solución de problemas

### Si ves warnings "Undefined array key":
- Los módulos ahora verifican todas las variables antes de usarlas
- Se han agregado verificaciones `isset()` en todos los módulos
- Se usan valores por defecto cuando las variables no existen

### Si no se muestra el contenido:
- Verificar que el slug exista en la base de datos
- Verificar conexión a la base de datos en `api/config.php`
- Revisar permisos de archivos

### Si el CSS/JS no carga:
- Verificar que los archivos estén en las rutas correctas
- Revisar la consola del navegador para errores 404

## ✅ Verificación después de subir

1. Acceder a: `https://rutasrurales.io/alojamiento-modular/test.html`
2. Deberías ver la página de prueba con todos los módulos listados
3. Probar con un slug real: `https://rutasrurales.io/alojamiento-modular/index.php?slug=casa-enrique`
4. Verificar que no haya warnings en la página

## 🚀 Migración a producción (cuando esté probado)

Cuando el sistema modular funcione correctamente:

1. Renombrar `alojamiento-modular/` a `alojamiento/` (o copiar contenido)
2. Actualizar `.htaccess` principal para redirigir `/alojamiento/{slug}`
3. Configurar redirecciones 301 desde URLs antiguas
4. Actualizar sitemap.xml

## 📞 Soporte

Si hay problemas después de subir:
1. Revisar logs de error del servidor
2. Verificar que `api/config.php` tenga la configuración correcta de BD
3. Probar con diferentes slugs de alojamiento
4. Verificar que las fotos existan en el servidor
