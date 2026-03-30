# 🔧 Guía de Solución de Errores - Rutas

## 📋 Errores Identificados

### 1. Error 404: Logo.png
### 2. Error 405: api/crear.php (Method Not Allowed)
### 3. Error JSON: SyntaxError en agregar-alojamiento.html

---

## 🎯 SOLUCIONES PASO A PASO

### ✅ PASO 1: Verificar Archivos en el Servidor

**Archivos que DEBEN estar en el servidor:**

```
rutasrurales.io/
├── Logo.png                    ← IMPORTANTE: Debe estar en la raíz
├── favicon.png
├── index.html
├── alojamientos.html
├── agregar-alojamiento.html
├── dashboard.html
├── compromiso-social.html
├── rutas-turisticas.html
├── styles.css
├── script.js
└── api/
    ├── .htaccess              ← IMPORTANTE: Configuración CORS
    ├── config.php
    ├── crear.php              ← IMPORTANTE: Endpoint principal
    ├── alojamientos.php
    ├── actualizar.php
    ├── eliminar.php
    ├── test.php               ← NUEVO: Para pruebas
    └── .htaccess.debug        ← NUEVO: Backup simplificado
```

**Acción:** Sube el archivo `Logo.png` a la raíz del servidor si falta.

---

### ✅ PASO 2: Probar que PHP Funciona

**2.1. Accede a:** `https://rutasrurales.io/api/test.php`

**Respuesta esperada:**
```json
{
    "success": true,
    "message": "PHP está funcionando correctamente",
    "timestamp": "2025-11-26 12:40:00",
    "method": "GET",
    "server_info": {
        "php_version": "8.x.x",
        "server_software": "Apache/2.x.x"
    }
}
```

**Si ves HTML o error 404:**
- El archivo `test.php` no está en el servidor
- PHP no está configurado correctamente
- Contacta con tu proveedor de hosting

---

### ✅ PASO 3: Verificar Configuración CORS

**3.1. Problema:** Error 405 (Method Not Allowed)

**Causa:** El `.htaccess` puede estar bloqueando peticiones POST

**Solución A - Usar .htaccess simplificado:**

1. En el servidor, renombra el `.htaccess` actual:
   ```
   api/.htaccess → api/.htaccess.backup
   ```

2. Renombra el archivo de debug:
   ```
   api/.htaccess.debug → api/.htaccess
   ```

3. Prueba de nuevo el formulario

**Solución B - Verificar módulos Apache:**

Contacta con tu hosting y verifica que estén habilitados:
- `mod_rewrite`
- `mod_headers`
- `mod_mime`

---

### ✅ PASO 4: Verificar Base de Datos

**4.1. Revisa el archivo `api/config.php`**

Asegúrate de que las credenciales sean correctas:

```php
define('DB_HOST', 'localhost');           // ← Verifica con tu hosting
define('DB_NAME', 'tu_base_de_datos');    // ← Nombre correcto
define('DB_USER', 'tu_usuario');          // ← Usuario correcto
define('DB_PASS', 'tu_contraseña');       // ← Contraseña correcta
define('DB_TABLE', 'alojamientos');       // ← Nombre de la tabla
```

**4.2. Verifica que la tabla existe:**

Ejecuta en phpMyAdmin:
```sql
SHOW TABLES LIKE 'alojamientos';
```

**4.3. Verifica que la columna Estado existe:**

```sql
DESCRIBE alojamientos;
```

Si no existe la columna `Estado`, ejecuta:
```sql
ALTER TABLE alojamientos 
ADD COLUMN Estado VARCHAR(20) DEFAULT 'pendiente' 
AFTER Notasprivadas;
```

---

### ✅ PASO 5: Probar el Endpoint crear.php

**5.1. Prueba con cURL (desde terminal):**

```bash
curl -X POST https://rutasrurales.io/api/crear.php \
  -H "Content-Type: application/json" \
  -d '{
    "Nombre": "Casa de Prueba",
    "Tipo": "Casa",
    "Direccion": "Calle Test 123, Soria, Soria",
    "Plazas": 4,
    "Telefono1": "975123456",
    "Notaspublicas": "Alojamiento de prueba",
    "recaptchaToken": "test_token"
  }'
```

**Respuesta esperada (si funciona):**
```json
{
    "success": true,
    "message": "¡Alojamiento guardado exitosamente!",
    "data": {
        "id": "...",
        "nombre": "Casa de Prueba",
        "estado": "pendiente"
    }
}
```

**Si recibes HTML en lugar de JSON:**
- Hay un error de PHP
- El archivo no existe
- El .htaccess está bloqueando la petición

---

### ✅ PASO 6: Verificar Errores de PHP

**6.1. Habilitar logs de error temporalmente**

Edita `api/.htaccess` y agrega:
```apache
php_flag display_errors On
php_flag log_errors On
```

**6.2. Revisa los logs del servidor**

Busca archivos de log en:
- `/logs/error_log`
- `/public_html/error_log`
- Panel de control del hosting → Logs

---

## 🔍 DIAGNÓSTICO RÁPIDO

### Test 1: ¿PHP funciona?
```
✅ https://rutasrurales.io/api/test.php devuelve JSON
❌ Devuelve error 404 o HTML
```

### Test 2: ¿CORS configurado?
```
✅ Headers incluyen Access-Control-Allow-Origin
❌ Error de CORS en consola del navegador
```

### Test 3: ¿Base de datos conecta?
```
✅ api/alojamientos.php devuelve lista de alojamientos
❌ Error de conexión o JSON vacío
```

### Test 4: ¿Endpoint POST funciona?
```
✅ crear.php acepta peticiones POST
❌ Error 405 Method Not Allowed
```

---

## 🚨 ERRORES COMUNES Y SOLUCIONES

### Error: "Failed to load resource: 404"
**Causa:** Archivo no existe en el servidor
**Solución:** Sube el archivo faltante

### Error: "Failed to load resource: 405"
**Causa:** Servidor rechaza método POST
**Solución:** 
1. Verifica .htaccess
2. Usa .htaccess.debug
3. Contacta con hosting

### Error: "Unexpected token '<', "<html>..."
**Causa:** API devuelve HTML en vez de JSON
**Solución:**
1. Verifica que crear.php existe
2. Revisa errores de PHP
3. Verifica config.php

### Error: "CORS policy"
**Causa:** Headers CORS no configurados
**Solución:** Verifica que .htaccess tiene configuración CORS

---

## 📞 CHECKLIST FINAL

Antes de contactar soporte, verifica:

- [ ] Logo.png está en la raíz del servidor
- [ ] Todos los archivos de /api están subidos
- [ ] test.php devuelve JSON correctamente
- [ ] config.php tiene credenciales correctas
- [ ] La tabla alojamientos existe en la BD
- [ ] La columna Estado existe en la tabla
- [ ] .htaccess está en /api
- [ ] mod_rewrite y mod_headers están habilitados

---

## 🎯 PRÓXIMOS PASOS

1. **Sube Logo.png** a la raíz del servidor
2. **Prueba test.php** para verificar que PHP funciona
3. **Si test.php falla:** Contacta con tu hosting
4. **Si test.php funciona pero crear.php no:** Usa .htaccess.debug
5. **Revisa los logs** del servidor para más detalles

---

## 📧 Soporte

Si después de seguir esta guía sigues teniendo problemas:

1. Anota qué tests pasaron y cuáles fallaron
2. Copia los mensajes de error exactos
3. Revisa los logs del servidor
4. Contacta con: olgamarin@rutasrurales.io

---

**Última actualización:** 26/11/2025
