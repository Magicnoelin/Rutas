# 🚀 Instrucciones de Instalación - Sistema de Moderación

## ✅ Resumen de lo Implementado

Se ha creado un **sistema completo de moderación avanzado con versionado** para alojamientos turísticos que incluye:

### 📦 Archivos Creados

1. **Base de Datos**
   - ✅ `api/crear_sistema_moderacion.sql` - Script SQL completo

2. **API Endpoints** (carpeta `api/moderation/`)
   - ✅ `list_pending.php` - Lista alojamientos pendientes
   - ✅ `approve.php` - Aprobar alojamientos
   - ✅ `reject.php` - Rechazar alojamientos
   - ✅ `get_details.php` - Ver detalles completos

3. **Panel de Administración**
   - ✅ `admin_tablas/moderacion_alojamientos.php` - Panel visual completo
   - ✅ `admin_tablas/sidebar.php` - Actualizado con enlace a moderación

4. **Archivos Modificados**
   - ✅ `api/crear.php` - Integrado con sistema de estados

5. **Documentación**
   - ✅ `SISTEMA_MODERACION_README.md` - Documentación completa
   - ✅ `INSTRUCCIONES_INSTALACION_MODERACION.md` - Este archivo

---

## 📋 Pasos de Instalación

### **PASO 1: Ejecutar Script SQL** ⚠️ IMPORTANTE

Debes ejecutar el script SQL para crear las tablas y estructuras necesarias:

#### Opción A: Usando phpMyAdmin (Recomendado)

1. Accede a phpMyAdmin: https://tu-hosting.com/phpmyadmin
2. Selecciona la base de datos `u412199647_Rutas`
3. Ve a la pestaña "SQL"
4. Abre el archivo `api/crear_sistema_moderacion.sql`
5. Copia TODO el contenido
6. Pégalo en el editor SQL
7. Haz clic en "Continuar" o "Go"
8. Verifica que aparezca: "Sistema de moderación instalado correctamente"

#### Opción B: Usando línea de comandos

```bash
mysql -u u412199647_olgamarin -p u412199647_Rutas < api/crear_sistema_moderacion.sql
```

### **PASO 2: Verificar Instalación**

Ejecuta estas consultas en phpMyAdmin para verificar:

```sql
-- Ver estadísticas
SELECT * FROM v_moderation_stats;

-- Ver si hay alojamientos pendientes
SELECT * FROM v_moderation_queue;

-- Verificar tablas creadas
SHOW TABLES LIKE '%moderation%';
SHOW TABLES LIKE '%pending_changes%';
```

### **PASO 3: Configurar Usuario Admin**

Asegúrate de tener un usuario con permisos de admin:

```sql
-- Verificar tu usuario
SELECT id, email, user_type FROM users WHERE email = 'tu-email@ejemplo.com';

-- Si no eres admin, actualizar:
UPDATE users SET user_type = 'admin' WHERE email = 'tu-email@ejemplo.com';
```

### **PASO 4: Subir Archivos al Servidor**

Sube estos archivos/carpetas a tu servidor:

```
📁 api/
  📁 moderation/
    - list_pending.php
    - approve.php
    - reject.php
    - get_details.php
  - crear_sistema_moderacion.sql
  - crear.php (modificado)

📁 admin_tablas/
  - moderacion_alojamientos.php
  - sidebar.php (modificado)

📄 SISTEMA_MODERACION_README.md
📄 INSTRUCCIONES_INSTALACION_MODERACION.md
```

### **PASO 5: Probar el Sistema**

1. **Accede al panel de moderación:**
   ```
   https://rutasrurales.io/admin_tablas/moderacion_alojamientos.php
   ```

2. **Verifica que se carguen las estadísticas**
   - Deberías ver 4 tarjetas con números
   - Si aparece "Error de conexión", revisa el PASO 1

3. **Prueba crear un alojamiento:**
   - Ve a: https://rutasrurales.io/agregar-alojamiento.html
   - Completa el formulario
   - Haz clic en "Guardar Alojamiento"
   - El alojamiento se creará con estado "pending"

4. **Prueba aprobar/rechazar:**
   - Vuelve al panel de moderación
   - Deberías ver el alojamiento en la lista
   - Prueba los botones de aprobar/rechazar

---

## 🎯 Cómo Usar el Sistema

### **Para Ti (Admin) - Flujo Matutino**

1. **Accede al panel:**
   ```
   https://rutasrurales.io/admin_tablas/moderacion_alojamientos.php
   ```

2. **Revisa las estadísticas del día**

3. **Para cada alojamiento pendiente:**
   - Haz clic en "Ver Detalles" para revisar toda la información
   - Verifica:
     - ✅ Fotos de calidad
     - ✅ Descripción completa
     - ✅ Datos de contacto válidos
     - ✅ Ubicación correcta
   
4. **Toma una decisión:**
   - **Aprobar**: El alojamiento se publica inmediatamente
   - **Rechazar**: Escribe el motivo (el usuario lo recibirá)

5. **El usuario recibe notificación automática**

### **Para los Usuarios**

1. **Crear alojamiento:**
   - Completan el formulario
   - Hacen clic en "Enviar para Revisión"
   - Estado: "Pendiente"

2. **Reciben notificación:**
   - Si apruebas: "¡Alojamiento aprobado!"
   - Si rechazas: "Requiere correcciones: [motivo]"

3. **Pueden ver el estado en su dashboard:**
   - https://rutasrurales.io/user-dashboard.html#mis-alojamientos

---

## 🔧 Configuración Adicional (Opcional)

### **Filtrar Solo Alojamientos Aprobados en el Público**

Actualiza `api/accommodations.php` para mostrar solo aprobados:

```php
// Agregar esta condición en la consulta:
WHERE moderation_status = 'approved' AND is_active = 1
```

### **Agregar Botones Draft/Submit en el Formulario**

En `agregar-alojamiento.html`, modifica el botón de envío:

```html
<!-- Reemplazar el botón actual por: -->
<div class="form-actions">
    <button type="button" class="btn-draft" onclick="guardarBorrador()">
        <i class="fas fa-save"></i> Guardar Borrador
    </button>
    <button type="submit" class="btn-submit">
        <i class="fas fa-paper-plane"></i> Enviar para Revisión
    </button>
</div>

<script>
function guardarBorrador() {
    // Agregar campo submit_action
    const form = document.getElementById('alojamientoForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'submit_action';
    input.value = 'draft';
    form.appendChild(input);
    form.submit();
}

// Para el submit normal, agregar:
document.getElementById('alojamientoForm').addEventListener('submit', function(e) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'submit_action';
    input.value = 'submit';
    this.appendChild(input);
});
</script>
```

---

## 📊 Consultas Útiles

### **Ver todos los alojamientos pendientes:**
```sql
SELECT id, name, municipality, moderation_status, last_submitted_at 
FROM accommodations 
WHERE moderation_status = 'pending' 
ORDER BY last_submitted_at ASC;
```

### **Ver historial de un alojamiento:**
```sql
SELECT * FROM accommodation_moderation_history 
WHERE accommodation_id = 123 
ORDER BY created_at DESC;
```

### **Ver notificaciones pendientes:**
```sql
SELECT * FROM moderation_notifications 
WHERE is_read = 0 
ORDER BY created_at DESC;
```

### **Estadísticas generales:**
```sql
SELECT * FROM v_moderation_stats;
```

---

## 🐛 Solución de Problemas

### **Error: "Acceso denegado. Solo administradores"**

**Solución:**
```sql
-- Verifica tu tipo de usuario
SELECT id, email, user_type FROM users WHERE email = 'tu-email@ejemplo.com';

-- Actualiza a admin si es necesario
UPDATE users SET user_type = 'admin' WHERE email = 'tu-email@ejemplo.com';
```

### **Error: "Table doesn't exist"**

**Solución:** No ejecutaste el script SQL del PASO 1. Vuelve a ejecutarlo.

### **No aparecen alojamientos pendientes**

**Solución:**
```sql
-- Verifica si hay alojamientos pendientes
SELECT COUNT(*) FROM accommodations WHERE moderation_status = 'pending';

-- Si no hay, crea uno de prueba:
UPDATE accommodations SET moderation_status = 'pending', last_submitted_at = NOW() WHERE id = 1;
```

### **Error 500 en el panel**

**Solución:**
1. Revisa los logs de PHP: `/var/log/php_errors.log`
2. Verifica que todos los archivos se subieron correctamente
3. Verifica permisos de archivos (644 para PHP)

---

## ✨ Características del Sistema

### **Lo que YA funciona:**
- ✅ Estados de moderación (draft, pending, approved, rejected)
- ✅ Panel visual de moderación
- ✅ Aprobar/Rechazar con motivos
- ✅ Historial completo de acciones
- ✅ Notificaciones en base de datos
- ✅ Estadísticas en tiempo real
- ✅ Versionado de cambios (estructura lista)
- ✅ Filtros por tipo de cambio

### **Pendiente de implementar (opcional):**
- ⏳ Emails automáticos (estructura lista, falta configurar SMTP)
- ⏳ Actualización del user-dashboard con badges de estado
- ⏳ Botones draft/submit en formulario público

---

## 📞 Soporte

Si tienes problemas:

1. **Revisa la documentación completa:**
   - `SISTEMA_MODERACION_README.md`

2. **Verifica los logs:**
   - PHP: `/var/log/php_errors.log`
   - MySQL: `/var/log/mysql/error.log`

3. **Consultas de diagnóstico:**
   ```sql
   -- Ver estructura de tabla
   DESCRIBE accommodations;
   
   -- Ver últimos errores
   SHOW WARNINGS;
   ```

---

## 🎉 ¡Listo!

Una vez completados los pasos, tendrás:

✅ Sistema de moderación completamente funcional  
✅ Panel de administración profesional  
✅ Control total sobre la calidad del contenido  
✅ Historial completo de todas las acciones  
✅ Notificaciones automáticas a usuarios  
✅ Estadísticas en tiempo real  

**¡Disfruta de tu nuevo sistema de moderación!** 🚀

---

**Fecha de creación:** 02/08/2026  
**Versión:** 1.0.0  
**Desarrollado para:** Rutas Rurales
