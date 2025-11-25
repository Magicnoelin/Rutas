# 📦 Guía de Instalación - API Backend Rutas

## 🎯 Sistema Completo de Gestión de Alojamientos

Esta guía te ayudará a instalar y configurar el backend API para conectar tu web con la base de datos MySQL en Hostinger.

---

## 📋 Requisitos Previos

✅ Hosting en Hostinger con:
- PHP 7.4 o superior
- MySQL/MariaDB
- Acceso a phpMyAdmin
- Acceso FTP o File Manager

✅ Base de datos:
- Nombre: `u412199647_Alojamientos`
- Servidor: `localhost` (127.0.0.1:3306)
- Tabla con 29 campos (según tu CSV)

---

## 🚀 Paso 1: Subir Archivos a Hostinger

### Opción A: Usando File Manager (Recomendado)

1. **Accede al panel de Hostinger** (hpanel.hostinger.com)
2. Ve a **"Archivos"** → **"Administrador de archivos"**
3. Navega a la carpeta `public_html`
4. **Crea una carpeta llamada `api`**
5. **Sube todos los archivos** de la carpeta `Rutas/api/` a `public_html/api/`:
   - config.php
   - alojamientos.php
   - alojamiento.php
   - crear.php
   - actualizar.php
   - eliminar.php
   - estadisticas.php
   - importar_csv.php
   - .htaccess

### Opción B: Usando FTP

1. Conecta con tu cliente FTP (FileZilla, etc.)
2. Navega a `public_html`
3. Crea carpeta `api`
4. Sube todos los archivos

---

## ⚙️ Paso 2: Configurar Conexión a Base de Datos

1. **Abre el archivo `config.php`** en el File Manager
2. **Edita estas líneas** con tus credenciales reales:

```php
define('DB_HOST', 'localhost');  // Dejar como está
define('DB_NAME', 'u412199647_Alojamientos');  // Dejar como está
define('DB_USER', 'TU_USUARIO_MYSQL');  // ← CAMBIAR
define('DB_PASS', 'TU_PASSWORD_MYSQL'); // ← CAMBIAR
define('DB_TABLE', 'alojamientos');     // ← CONFIRMAR nombre de tabla
```

### ¿Cómo encontrar tus credenciales?

**Usuario MySQL:**
1. En phpMyAdmin, mira arriba a la izquierda
2. Verás algo como: `Usuario: u412199647_admin`
3. Ese es tu usuario

**Contraseña:**
- Es la contraseña que usas para acceder a phpMyAdmin
- Si no la recuerdas, puedes cambiarla en el panel de Hostinger:
  - **Bases de datos** → **MySQL** → **Cambiar contraseña**

**Nombre de la tabla:**
1. En phpMyAdmin, selecciona tu base de datos
2. En el panel izquierdo verás el nombre de la tabla
3. Probablemente sea `alojamientos` (en minúsculas)

---

## 📊 Paso 3: Importar los 148 Alojamientos

1. **Sube el archivo CSV** `Alojamientos 148.csv` a la carpeta `api/`

2. **Accede a la URL:**
   ```
   https://rutasrurales.io/api/importar_csv.php
   ```

3. **Verás una pantalla con el resultado:**
   - ✅ Alojamientos importados
   - ⚠️ Duplicados (si los hay)
   - ❌ Errores (si los hay)

4. **⚠️ IMPORTANTE:** Después de la importación exitosa:
   - **ELIMINA** el archivo `importar_csv.php` por seguridad
   - **ELIMINA** el archivo `Alojamientos 148.csv`

---

## 🧪 Paso 4: Probar la API

### Prueba 1: Obtener Estadísticas
Abre en tu navegador:
```
https://rutasrurales.io/api/estadisticas.php
```

Deberías ver un JSON con:
```json
{
  "success": true,
  "data": {
    "resumen": {
      "total_alojamientos": 148,
      "total_plazas": 1234,
      "precio_medio": 135.50
    }
  }
}
```

### Prueba 2: Obtener Todos los Alojamientos
```
https://rutasrurales.io/api/alojamientos.php
```

### Prueba 3: Obtener Un Alojamiento
```
https://rutasrurales.io/api/alojamiento.php?id=1613
```

---

## 🌐 Paso 5: Conectar el Frontend

Los archivos HTML ya están preparados para conectarse a la API. Solo necesitas:

1. **Verificar que el dominio en `config.php` sea correcto:**
   ```php
   header('Access-Control-Allow-Origin: https://rutasrurales.io');
   ```

2. **Si usas `www.rutasrurales.io`, cambia a:**
   ```php
   header('Access-Control-Allow-Origin: https://www.rutasrurales.io');
   ```

---

## 📁 Estructura Final en Hostinger

```
public_html/
├── index.html
├── alojamientos.html
├── agregar-alojamiento.html
├── dashboard.html
├── styles.css
├── script.js
├── Logo.png
└── api/
    ├── config.php
    ├── alojamientos.php
    ├── alojamiento.php
    ├── crear.php
    ├── actualizar.php
    ├── eliminar.php
    ├── estadisticas.php
    └── .htaccess
```

---

## 🔒 Seguridad

### Configuración SSL (HTTPS)

Si tienes SSL activado en Hostinger:

1. Edita `api/.htaccess`
2. Descomenta estas líneas:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Protección Adicional

El archivo `.htaccess` ya incluye:
- ✅ Protección contra inyección SQL
- ✅ Prevención de listado de directorios
- ✅ Configuración CORS segura
- ✅ Límites de tamaño de archivo

---

## 🐛 Solución de Problemas

### Error: "Error de conexión a la base de datos"
- ✅ Verifica usuario y contraseña en `config.php`
- ✅ Confirma que el nombre de la base de datos es correcto
- ✅ Asegúrate de que el host sea `localhost`

### Error: "Tabla no encontrada"
- ✅ Verifica el nombre de la tabla en phpMyAdmin
- ✅ Actualiza `DB_TABLE` en `config.php`

### Error: "CORS policy"
- ✅ Verifica que el dominio en `config.php` coincida con tu web
- ✅ Asegúrate de usar `https://` si tienes SSL

### Error 500
- ✅ Revisa los logs de PHP en el panel de Hostinger
- ✅ Verifica que la versión de PHP sea 7.4 o superior

---

## ✅ Checklist de Instalación

- [ ] Archivos PHP subidos a `public_html/api/`
- [ ] `config.php` configurado con credenciales correctas
- [ ] CSV importado exitosamente
- [ ] Archivos de importación eliminados
- [ ] API probada y funcionando
- [ ] Frontend conectado a la API
- [ ] SSL configurado (si aplica)

---

## 📞 Endpoints Disponibles

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/alojamientos.php` | GET | Obtener todos (paginado) |
| `/api/alojamiento.php?id=X` | GET | Obtener uno específico |
| `/api/crear.php` | POST | Crear nuevo alojamiento |
| `/api/actualizar.php` | PUT/POST | Actualizar alojamiento |
| `/api/eliminar.php?id=X` | DELETE/POST | Eliminar alojamiento |
| `/api/estadisticas.php` | GET | Estadísticas dashboard |

---

## 🎉 ¡Listo!

Una vez completados todos los pasos, tu sistema estará funcionando con:
- ✅ 148 alojamientos en la base de datos
- ✅ API REST completa y segura
- ✅ Frontend conectado en tiempo real
- ✅ Dashboard con estadísticas reales
- ✅ Formularios para añadir/editar alojamientos

