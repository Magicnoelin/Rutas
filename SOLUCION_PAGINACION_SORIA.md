# Solución: Mostrar Todos los Alojamientos en Soria

## 🔍 Problema Identificado

Solo se muestran **3 alojamientos** en la provincia de Soria cuando deberían mostrarse **8 alojamientos**.

### Causa del Problema

El problema es que algunos alojamientos en la base de datos tienen el campo `is_active = 0` (inactivo), por lo que la API los filtra automáticamente y no los muestra en la página de paginación.

La API `api/alojamientos.php` incluye esta condición:
```php
if (in_array('is_active', $columns)) {
    $where[] = "is_active = :is_active";
    $params[':is_active'] = 1;
}
```

Esto significa que **solo se muestran los alojamientos con `is_active = 1`**.

---

## ✅ Soluciones Disponibles

Tienes **3 opciones** para solucionar este problema:

### **Opción 1: Ejecutar Script PHP (Recomendado - Más fácil)**

1. Sube el archivo `activar-alojamientos-soria.php` a tu servidor
2. Accede a la URL en tu navegador:
   ```
   https://rutasrurales.io/activar-alojamientos-soria.php
   ```
3. El script te mostrará:
   - Estado actual de los alojamientos (activos/inactivos)
   - Activará automáticamente todos los alojamientos de Soria
   - Mostrará el estado después de la actualización
4. ¡Listo! Ahora todos los alojamientos de Soria estarán visibles

---

### **Opción 2: Ejecutar Script SQL**

Si prefieres usar la base de datos directamente:

1. Accede a tu panel de phpMyAdmin o tu gestor de base de datos
2. Selecciona la base de datos de tu proyecto
3. Abre el archivo `activar-todos-alojamientos-soria.sql`
4. Copia y pega el contenido en la pestaña SQL
5. Ejecuta el script
6. Verifica los resultados mostrados

El script SQL ejecutará:
```sql
UPDATE accommodations 
SET is_active = 1 
WHERE province = 'Soria';
```

---

### **Opción 3: Activar Manualmente (Para casos específicos)**

Si solo quieres activar alojamientos específicos:

1. Accede a tu base de datos
2. Ejecuta esta consulta para ver los alojamientos inactivos:
   ```sql
   SELECT id, name, municipality, is_active 
   FROM accommodations 
   WHERE province = 'Soria' AND is_active = 0;
   ```
3. Para activar un alojamiento específico:
   ```sql
   UPDATE accommodations 
   SET is_active = 1 
   WHERE id = [ID_DEL_ALOJAMIENTO];
   ```

---

## 🎯 Aplicar la Solución a Todas las Provincias

Si el mismo problema ocurre en **otras provincias**, puedes activar todos los alojamientos de todas las provincias:

```sql
-- Activar TODOS los alojamientos en la base de datos
UPDATE accommodations 
SET is_active = 1;
```

O crear una versión del script PHP para todas las provincias modificando la línea:
```php
$updateStmt = $pdo->prepare("UPDATE accommodations SET is_active = 1");
```

---

## 🔧 Verificación

Después de ejecutar cualquiera de las soluciones:

1. **Refresca la caché del navegador** (Ctrl + F5 o Cmd + Shift + R)
2. Visita la página de alojamientos turísticos:
   ```
   https://rutasrurales.io/alojamientos-turisticos-paginacion.html
   ```
3. Selecciona "Soria" en el filtro de provincia
4. Deberías ver **todos los 8 alojamientos** (o los que correspondan)

---

## 📊 Verificar Cuántos Alojamientos Hay

Para verificar cuántos alojamientos existen en cada provincia:

### Script PHP de diagnóstico:
Accede a: `test-soria-count.php` (ya creado)

### Consulta SQL:
```sql
SELECT 
    province as Provincia,
    COUNT(*) as Total,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as Activos,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as Inactivos
FROM accommodations 
GROUP BY province
ORDER BY province;
```

---

## 📝 Notas Importantes

1. **Backup**: Antes de ejecutar cualquier UPDATE en la base de datos, es recomendable hacer un backup
2. **Permisos**: Asegúrate de tener permisos de escritura en la base de datos
3. **Caché**: Si después de la actualización no ves los cambios, limpia la caché del navegador
4. **API**: La paginación funciona correctamente; el problema es solo el estado `is_active` de los registros

---

## 🚀 Archivos Creados

- ✅ `activar-alojamientos-soria.php` - Script PHP para activar alojamientos de Soria
- ✅ `activar-todos-alojamientos-soria.sql` - Script SQL para activar alojamientos de Soria
- ✅ `test-soria-count.php` - Script de diagnóstico para verificar el estado
- ✅ `SOLUCION_PAGINACION_SORIA.md` - Este documento de instrucciones

---

## ❓ Preguntas Frecuentes

**P: ¿Por qué algunos alojamientos están inactivos?**
R: Probablemente se marcaron como inactivos durante la importación de datos o por alguna configuración inicial.

**P: ¿Afectará esto a otras funcionalidades?**
R: No, solo activará los alojamientos para que sean visibles en el listado público.

**P: ¿Puedo desactivar un alojamiento después?**
R: Sí, simplemente cambia `is_active = 0` para ese alojamiento específico.

**P: ¿Necesito hacer esto para cada provincia?**
R: Solo si otras provincias también tienen alojamientos inactivos. Puedes ejecutar el script para todas las provincias a la vez.

---

## ✨ Resultado Esperado

Después de aplicar la solución:
- ✅ Soria mostrará **8 alojamientos** (todos los disponibles)
- ✅ La paginación funcionará correctamente
- ✅ Los filtros (provincia, localidad, tipo, plazas) funcionarán normalmente
- ✅ Las estadísticas se actualizarán correctamente
