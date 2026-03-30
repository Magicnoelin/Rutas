# 📋 Sistema de Moderación de Contenido - Guía de Instalación y Uso

## 🎯 Resumen del Sistema

Has implementado un sistema completo que permite a los **alojamientos** crear:
- ✅ **Eventos culturales**
- ✅ **Actividades turísticas**
- ✅ **Lugares de interés**

Todo el contenido pasa por **moderación** antes de ser publicado.

---

## 📦 Archivos Creados

### **1. Base de Datos**
- `api/extender_moderacion_contenido.sql` - Script SQL principal

### **2. Formularios HTML**
- `agregar-actividad.html` - Formulario para actividades turísticas
- `agregar-lugar-interes.html` - Formulario para lugares de interés
- `agregar-evento.html` - Ya existía (necesita actualización menor)

### **3. APIs PHP**
- `api/crear_actividad.php` - API para crear actividades
- `api/crear_lugar.php` - API para crear lugares de interés
- `api/crear_evento.php` - Ya existe (necesita actualización menor)

---

## 🚀 Instalación Paso a Paso

### **PASO 1: Ejecutar el Script SQL**

1. Abre **phpMyAdmin** en tu servidor
2. Selecciona la base de datos `u412199647_Rutas`
3. Ve a la pestaña **SQL**
4. Copia y pega el contenido completo de `api/extender_moderacion_contenido.sql`
5. Haz clic en **Ejecutar**

**Resultado esperado:**
```
✅ Sistema de moderación extendido correctamente
✅ Tablas creadas: activities, places_of_interest
✅ Tabla cultural_events extendida con campos de moderación
✅ Vistas creadas: v_moderation_queue_unified, v_moderation_stats_unified
```

### **PASO 2: Subir Archivos al Servidor**

Sube estos archivos a tu servidor:

```
/agregar-actividad.html
/agregar-lugar-interes.html
/api/crear_actividad.php
/api/crear_lugar.php
/api/extender_moderacion_contenido.sql
```

### **PASO 3: Verificar Instalación**

Ejecuta esta consulta SQL para verificar:

```sql
-- Ver estadísticas de moderación
SELECT * FROM v_moderation_stats_unified;

-- Ver cola de moderación
SELECT * FROM v_moderation_queue_unified;
```

---

## 🎨 Cómo Funciona

### **Para Usuarios (Alojamientos)**

1. **Acceden a los formularios:**
   - `https://rutasrurales.io/agregar-actividad.html`
   - `https://rutasrurales.io/agregar-lugar-interes.html`
   - `https://rutasrurales.io/agregar-evento.html`

2. **Completan el formulario** con la información

3. **Eligen una acción:**
   - 🟡 **Guardar Borrador** → Estado: `draft` (pueden editarlo después)
   - 📤 **Enviar para Revisión** → Estado: `pending` (va a moderación)

4. **Reciben confirmación** con el ID y estado

5. **Pueden ver su contenido** en `user-dashboard.html#mis-actividades` (o #mis-lugares, #mis-eventos)

### **Para Ti (Admin)**

1. **Recibes notificación** cuando hay contenido pendiente

2. **Accedes al panel de moderación:**
   - Panel actual: `admin_tablas/moderacion_alojamientos.php`
   - Panel unificado (próximamente): `admin_tablas/moderacion_universal.php`

3. **Revisas el contenido:**
   - Ver detalles completos
   - Verificar fotos, descripción, ubicación
   - Comprobar que la información es correcta

4. **Tomas una decisión:**
   - ✅ **Aprobar** → El contenido se publica (is_active = 1)
   - ❌ **Rechazar** → El usuario recibe el motivo y puede corregir

---

## 📊 Estados de Moderación

| Estado | Descripción | Visible Públicamente | Usuario Puede Editar |
|--------|-------------|---------------------|---------------------|
| **draft** | Borrador | ❌ No | ✅ Sí |
| **pending** | Pendiente de revisión | ❌ No | ❌ No |
| **approved** | Aprobado y publicado | ✅ Sí | ⚠️ Con moderación |
| **rejected** | Rechazado | ❌ No | ✅ Sí (puede corregir) |

---

## 🔄 Flujo Completo de Ejemplo

```
DÍA 1 - 10:00 AM
Usuario (Alojamiento "Casa Rural El Roble") crea:
→ Actividad: "Ruta del Cañón del Río Lobos"
→ Elige: "Enviar para Revisión"
→ Estado: PENDING

DÍA 1 - 11:00 AM
Tú (Admin) recibes notificación
→ Accedes al panel de moderación
→ Ves: "Ruta del Cañón del Río Lobos" - Pendiente (1 día)

DÍA 1 - 11:30 AM
Revisas la actividad:
→ Fotos: ✅ Buena calidad
→ Descripción: ✅ Completa y clara
→ Ubicación: ✅ Correcta
→ Decides: APROBAR

DÍA 1 - 11:31 AM
Sistema automáticamente:
→ Cambia estado a: APPROVED
→ Activa la actividad: is_active = 1
→ Registra en historial
→ Notifica al usuario: "¡Tu actividad ha sido aprobada!"
→ La actividad aparece en la web pública
```

---

## 🗄️ Estructura de Tablas Creadas

### **Tabla: activities**
```sql
- id (PK)
- name, slug, description
- activity_type, difficulty, duration
- municipality, province
- price, max_participants
- phone, email, website
- photo1, photo2, photo3, photo4
- moderation_status (draft/pending/approved/rejected)
- created_by, reviewed_by
- is_active
- created_at, updated_at
```

### **Tabla: places_of_interest**
```sql
- id (PK)
- name, slug, description
- place_type
- municipality, province
- entry_fee, opening_hours, accessibility
- phone, email, website
- photo1, photo2, photo3, photo4
- moderation_status (draft/pending/approved/rejected)
- created_by, reviewed_by
- is_active
- created_at, updated_at
```

### **Tabla: content_moderation_history** (renombrada)
```sql
- id (PK)
- content_type (accommodation/event/activity/place)
- content_id
- action (created/submitted/approved/rejected)
- performed_by
- previous_status, new_status
- notes, rejection_reason
- created_at
```

---

## 🎯 Próximos Pasos (Opcional)

### **Fase 2: Panel de Moderación Unificado**
Crear un panel que muestre TODO el contenido pendiente en un solo lugar:
- Alojamientos
- Eventos
- Actividades
- Lugares de interés

### **Fase 3: Sistema de Confirmación con Alojamientos Cercanos**
Cuando apruebes un evento/actividad/lugar:
1. Buscar alojamientos en un radio de 10-20 km
2. Enviar notificación a los propietarios
3. Pedir confirmación de la información
4. Publicar cuando haya 2-3 confirmaciones

---

## 📝 Consultas SQL Útiles

### **Ver todo el contenido pendiente**
```sql
SELECT * FROM v_moderation_queue_unified 
ORDER BY last_submitted_at ASC;
```

### **Ver estadísticas generales**
```sql
SELECT * FROM v_moderation_stats_unified;
```

### **Ver historial de un contenido específico**
```sql
SELECT * FROM content_moderation_history 
WHERE content_type = 'activity' AND content_id = 1
ORDER BY created_at DESC;
```

### **Ver notificaciones pendientes de un usuario**
```sql
SELECT * FROM moderation_notifications 
WHERE user_id = 94 AND is_read = 0
ORDER BY created_at DESC;
```

### **Aprobar manualmente un contenido**
```sql
-- Actividad
UPDATE activities 
SET moderation_status = 'approved', 
    is_active = 1, 
    reviewed_by = 1,
    reviewed_at = NOW(),
    published_at = NOW()
WHERE id = 1;

-- Lugar
UPDATE places_of_interest 
SET moderation_status = 'approved', 
    is_active = 1, 
    reviewed_by = 1,
    reviewed_at = NOW(),
    published_at = NOW()
WHERE id = 1;
```

---

## 🐛 Solución de Problemas

### **Problema: Error al crear tabla activities**
```sql
-- Verificar si ya existe
SHOW TABLES LIKE 'activities';

-- Si existe, eliminarla y volver a crearla
DROP TABLE IF EXISTS activities;
-- Luego ejecutar el script completo
```

### **Problema: Error en content_moderation_history**
```sql
-- Verificar si la tabla antigua existe
SHOW TABLES LIKE 'accommodation_moderation_history';

-- Si no existe, crear directamente la nueva
CREATE TABLE content_moderation_history (...);
```

### **Problema: Los formularios no guardan**
1. Verificar que las APIs existen en `/api/`
2. Comprobar permisos de archivos (644 para PHP)
3. Revisar logs de PHP: `/var/log/php_errors.log`
4. Verificar que la sesión del usuario está activa

---

## ✅ Checklist de Instalación

- [ ] Script SQL ejecutado correctamente
- [ ] Tablas `activities` y `places_of_interest` creadas
- [ ] Vistas SQL creadas
- [ ] Archivos HTML subidos al servidor
- [ ] APIs PHP subidas a `/api/`
- [ ] Formularios accesibles desde el navegador
- [ ] Prueba de crear una actividad en borrador
- [ ] Prueba de enviar una actividad para revisión
- [ ] Verificar que aparece en la cola de moderación
- [ ] Prueba de aprobar/rechazar contenido

---

## 📞 Soporte

Si encuentras algún problema:
1. Revisa los logs de PHP
2. Verifica la consola del navegador (F12)
3. Comprueba que las tablas se crearon correctamente
4. Asegúrate de que el usuario tiene sesión activa

---

## 🎉 ¡Listo!

Tu sistema de moderación de contenido está completo. Los alojamientos ahora pueden:
- ✅ Crear eventos culturales
- ✅ Crear actividades turísticas
- ✅ Crear lugares de interés

Y tú puedes:
- ✅ Revisar todo antes de publicar
- ✅ Aprobar o rechazar con motivos
- ✅ Mantener la calidad del contenido
- ✅ Ver historial completo de cambios

**Fecha de implementación:** 02/11/2026  
**Versión:** 1.0.0
