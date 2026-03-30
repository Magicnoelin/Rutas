# 📖 Instrucciones de Uso - Solución Paginación Soria

## ⚠️ IMPORTANTE: Diferencia entre archivos PHP y SQL

### Archivos PHP (*.php)
Se ejecutan en el **NAVEGADOR**, NO en phpMyAdmin.

**Cómo usarlos:**
1. Sube el archivo PHP a tu servidor
2. Accede a la URL en tu navegador: `https://rutasrurales.io/nombre-archivo.php`

### Archivos SQL (*.sql)
Se ejecutan en **phpMyAdmin** o en la consola MySQL.

**Cómo usarlos:**
1. Abre phpMyAdmin
2. Selecciona tu base de datos
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido del archivo .sql
5. Ejecuta

---

## 🔧 Para Solucionar el Problema de Soria

### Opción 1: Usar el archivo PHP (MÁS FÁCIL)

1. **Sube estos archivos a tu servidor:**
   - `activar-alojamientos-soria.php`
   - `test-api-soria-directo.php`

2. **Accede en tu navegador a:**
   ```
   https://rutasrurales.io/test-api-soria-directo.php
   ```
   Este script te mostrará:
   - Cuántos alojamientos hay realmente en Soria
   - Qué devuelve la API
   - Si hay problemas de caracteres

3. **Luego accede a:**
   ```
   https://rutasrurales.io/activar-alojamientos-soria.php
   ```
   Este script activará todos los alojamientos de Soria (si hay alguno inactivo)

### Opción 2: Usar SQL directamente en phpMyAdmin

Si prefieres usar SQL directamente, **SOLO copia el contenido de `activar-todos-alojamientos-soria.sql`** (sin la primera línea que dice `<?php`).

El contenido correcto a copiar en phpMyAdmin es:

```sql
-- 1. Ver estado actual (ANTES de actualizar)
SELECT 
    'ANTES DE ACTUALIZAR' as Estado,
    COUNT(*) as Total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as Activos,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as Inactivos
FROM accommodations 
WHERE province = 'Soria';

-- 2. Ver detalle de alojamientos inactivos en Soria
SELECT id, name, municipality, is_active 
FROM accommodations 
WHERE province = 'Soria' AND is_active = 0
ORDER BY name;

-- 3. ACTIVAR todos los alojamientos de Soria
UPDATE accommodations 
SET is_active = 1 
WHERE province = 'Soria';

-- 4. Ver estado después de actualizar
SELECT 
    'DESPUÉS DE ACTUALIZAR' as Estado,
    COUNT(*) as Total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as Activos,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as Inactivos
FROM accommodations 
WHERE province = 'Soria';

-- 5. Ver todos los alojamientos de Soria
SELECT id, name, municipality, is_active 
FROM accommodations 
WHERE province = 'Soria'
ORDER BY name;
```

---

## 🎯 Pero espera... ¡Ya no necesitas esto!

Según tu mensaje anterior, **ya hay 16 alojamientos activos en Soria** (Total: 16, Activos: 16, Inactivos: 0).

Entonces el problema NO era `is_active`, sino la **lógica del JavaScript** que ya corregí.

### La Solución Real (Ya Aplicada)

He modificado el archivo `alojamientos-turisticos-paginacion.html` para que:

1. **NO auto-seleccione ninguna provincia al cargar**
2. Muestre todos los alojamientos por defecto
3. Cuando selecciones "Soria", haga una llamada a la API para traer TODOS los alojamientos de Soria

### ✅ Para Probar la Solución:

1. **Sube el archivo corregido al servidor:**
   - `alojamientos-turisticos-paginacion.html`

2. **Accede a la página:**
   ```
   https://rutasrurales.io/alojamientos-turisticos-paginacion.html
   ```

3. **Prueba:**
   - Sin seleccionar nada: deberías ver los primeros 20 alojamientos de todas las provincias
   - Selecciona "Soria" en el filtro: deberías ver LOS 16 alojamientos de Soria

4. **Abre la consola del navegador** (F12) para ver los logs:
   - Verás mensajes como: "✅ Página 1: X alojamientos (16 total, 1 páginas)"

---

## 🐛 Si Aún No Funciona

Si después de subir el archivo corregido aún ves solo 3 alojamientos en Soria:

### 1. Limpia la caché del navegador
   - Chrome/Edge: Ctrl + Shift + Delete
   - O simplemente: Ctrl + F5 para refrescar sin caché

### 2. Verifica qué devuelve la API
Accede directamente a:
```
https://rutasrurales.io/api/alojamientos.php?table=accommodations&page=1&limit=20&provincia=Soria
```

Deberías ver un JSON con 16 alojamientos de Soria.

### 3. Si la API devuelve solo 3 alojamientos
Entonces el problema está en la base de datos o en el nombre de la provincia. Ejecuta en phpMyAdmin:

```sql
-- Ver TODOS los alojamientos y sus provincias
SELECT id, name, province, is_active 
FROM accommodations 
WHERE province LIKE '%Soria%' OR province LIKE '%soria%'
ORDER BY province, name;
```

Si sale solo 3, entonces en la BD solo hay 3 con province = 'Soria'.
Si salen 16 pero con diferentes variaciones del nombre (ej: 'Soria ', ' Soria', 'SORIA'), necesitarías normalizar los datos.

---

## 📝 Resumen

1. ✅ **Archivo HTML corregido** - Sube `alojamientos-turisticos-paginacion.html`
2. 🚫 **NO uses los scripts PHP en phpMyAdmin** - Son para ejecutar en navegador
3. ✅ **Prueba la página** - Debería mostrar los 16 alojamientos al seleccionar Soria
4. 🔍 **Si no funciona** - Verifica la API directamente en el navegador
