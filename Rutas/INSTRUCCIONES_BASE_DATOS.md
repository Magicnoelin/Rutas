# 📋 Instrucciones para Configurar la Base de Datos

## ⚠️ IMPORTANTE: Debes ejecutar estos pasos para que el sistema funcione

### 🎯 Objetivo
Agregar la columna `Estado` a la tabla de alojamientos para implementar el sistema de estados (pendiente/publicado).

---

## 📝 Opción 1: Usando phpMyAdmin (Recomendado)

### Paso 1: Acceder a phpMyAdmin
1. Accede a tu panel de hosting
2. Busca y abre **phpMyAdmin**
3. Selecciona la base de datos: `u412199647_Rutas`

### Paso 2: Ejecutar el Script SQL
1. Haz clic en la pestaña **SQL** en la parte superior
2. Copia y pega el siguiente código:

```sql
-- 1. Agregar columna Estado si no existe
ALTER TABLE alojamientos_csv 
ADD COLUMN IF NOT EXISTS Estado VARCHAR(20) DEFAULT 'pendiente';

-- 2. Actualizar alojamientos existentes a estado 'publicado'
UPDATE alojamientos_csv 
SET Estado = 'publicado' 
WHERE Estado IS NULL OR Estado = '';

-- 3. Crear índice para mejorar el rendimiento
CREATE INDEX IF NOT EXISTS idx_estado ON alojamientos_csv(Estado);

-- 4. Verificar los cambios
SELECT 
    COUNT(*) as total_alojamientos,
    SUM(CASE WHEN Estado = 'publicado' THEN 1 ELSE 0 END) as publicados,
    SUM(CASE WHEN Estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
FROM alojamientos_csv;
```

3. Haz clic en el botón **Continuar** o **Go**
4. Deberías ver un mensaje de éxito y una tabla con las estadísticas

---

## 📝 Opción 2: Usando el Archivo SQL

### Paso 1: Localizar el Archivo
El archivo está en: `api/agregar_columna_estado.sql`

### Paso 2: Importar en phpMyAdmin
1. En phpMyAdmin, selecciona la base de datos `u412199647_Rutas`
2. Haz clic en la pestaña **Importar**
3. Haz clic en **Seleccionar archivo**
4. Selecciona el archivo `agregar_columna_estado.sql`
5. Haz clic en **Continuar**

---

## 📝 Opción 3: Línea de Comandos MySQL

Si tienes acceso SSH a tu servidor:

```bash
mysql -h 127.0.0.1 -u u412199647_rutasrurales -p u412199647_Rutas < api/agregar_columna_estado.sql
```

Cuando te pida la contraseña, ingresa: `Rutas5Rurales7$`

---

## ✅ Verificación

Después de ejecutar el script, verifica que todo funcionó:

### En phpMyAdmin:
1. Selecciona la tabla `alojamientos_csv`
2. Haz clic en la pestaña **Estructura**
3. Deberías ver una columna llamada `Estado` de tipo `VARCHAR(20)`

### Ejecuta esta consulta para ver las estadísticas:
```sql
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN Estado = 'publicado' THEN 1 ELSE 0 END) as publicados,
    SUM(CASE WHEN Estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
FROM alojamientos_csv;
```

**Resultado esperado:**
- Total: Número de alojamientos existentes
- Publicados: Todos los alojamientos existentes (se actualizaron automáticamente)
- Pendientes: 0 (los nuevos alojamientos tendrán este estado)

---

## 🧪 Probar el Formulario

Una vez ejecutado el script SQL:

### 1. Acceder al Formulario
Abre en tu navegador: `https://rutasrurales.io/agregar-alojamiento.html`

### 2. Llenar con Datos de Prueba
- **Nombre**: Casa de Prueba
- **Tipo**: Casa
- **Dirección**: Calle Test 123
- **Localidad**: Vinuesa
- **Provincia**: Soria
- **Plazas**: 4
- **Teléfono**: 975000000
- **Descripción**: Alojamiento de prueba para verificar el sistema

### 3. Guardar
1. Haz clic en "Vista Previa" (opcional)
2. Haz clic en "Guardar Alojamiento"
3. Espera a que aparezca el mensaje de éxito

### 4. Verificar en la Base de Datos
En phpMyAdmin, ejecuta:
```sql
SELECT ID, Nombre, Estado 
FROM alojamientos_csv 
ORDER BY ID DESC 
LIMIT 5;
```

Deberías ver tu alojamiento de prueba con `Estado = 'pendiente'`

### 5. Verificar que NO Aparece Públicamente
1. Abre: `https://rutasrurales.io/alojamientos.html`
2. El alojamiento de prueba NO debería aparecer en la lista
3. Esto confirma que el filtro por estado funciona correctamente

---

## 🔧 Cambiar Estado de un Alojamiento

Para hacer visible un alojamiento (después del pago en Fase 2):

```sql
UPDATE alojamientos_csv 
SET Estado = 'publicado' 
WHERE ID = 'ID_DEL_ALOJAMIENTO';
```

Reemplaza `ID_DEL_ALOJAMIENTO` con el ID real.

---

## ❓ Solución de Problemas

### Error: "Column 'Estado' already exists"
✅ Esto es normal si ya ejecutaste el script antes. Ignora el error.

### Error: "Table 'alojamientos_csv' doesn't exist"
❌ Verifica que estás en la base de datos correcta: `u412199647_Rutas`

### El formulario no guarda
1. Abre la consola del navegador (F12)
2. Ve a la pestaña "Console"
3. Busca errores en rojo
4. Verifica que la URL de la API sea correcta: `https://rutasrurales.io/api/crear.php`

### reCAPTCHA no funciona
1. Verifica que las keys en `api/config.php` sean correctas
2. Verifica que el dominio `rutasrurales.io` esté registrado en Google reCAPTCHA

---

## 📞 Soporte

Si encuentras algún problema:
1. Revisa los logs de error de PHP en tu hosting
2. Verifica los permisos de los archivos en `/api/`
3. Asegúrate de que la base de datos esté accesible

---

## ✅ Checklist Final

- [ ] Script SQL ejecutado exitosamente
- [ ] Columna `Estado` visible en la estructura de la tabla
- [ ] Alojamientos existentes tienen estado "publicado"
- [ ] Formulario guarda correctamente
- [ ] Nuevos alojamientos tienen estado "pendiente"
- [ ] Alojamientos pendientes NO aparecen en la lista pública
- [ ] reCAPTCHA funciona correctamente
