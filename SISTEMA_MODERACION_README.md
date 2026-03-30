# 📋 Sistema de Moderación de Alojamientos - Documentación Completa

## 🎯 Descripción General

Sistema avanzado de moderación con versionado para alojamientos turísticos. Permite mantener la calidad del contenido publicado mediante un flujo de revisión profesional que incluye estados, historial, notificaciones y versionado de cambios.

---

## 🏗️ Arquitectura del Sistema

### **Componentes Principales**

1. **Base de Datos**
   - Tabla `accommodations` (modificada con campos de moderación)
   - Tabla `accommodation_pending_changes` (cambios pendientes)
   - Tabla `accommodation_moderation_history` (historial completo)
   - Tabla `moderation_notifications` (notificaciones)
   - Vistas SQL para estadísticas y cola de moderación
   - Procedimientos almacenados para aprobar/rechazar

2. **API Endpoints**
   - `/api/moderation/list_pending.php` - Lista de pendientes
   - `/api/moderation/approve.php` - Aprobar alojamiento
   - `/api/moderation/reject.php` - Rechazar alojamiento
   - `/api/moderation/get_details.php` - Detalles completos

3. **Panel de Administración**
   - `/admin_tablas/moderacion_alojamientos.php` - Panel visual de moderación

4. **Integración con Usuario**
   - `api/crear.php` - Actualizado para usar estados
   - `user-dashboard.html` - Muestra estados al usuario

---

## 📊 Estados de Moderación

### **Estados Disponibles**

| Estado | Descripción | Visible Públicamente | Puede Editar Usuario |
|--------|-------------|---------------------|---------------------|
| **draft** | Borrador en creación | ❌ No | ✅ Sí |
| **pending** | Enviado para revisión | ❌ No | ❌ No |
| **approved** | Aprobado y publicado | ✅ Sí | ⚠️ Con moderación |
| **rejected** | Rechazado con motivo | ❌ No | ✅ Sí (puede corregir) |

---

## 🔄 Flujos de Trabajo

### **1. Primer Registro (Nuevo Alojamiento)**

```
Usuario completa formulario
         ↓
    Elige acción:
    ├─→ "Guardar Borrador" → status: draft
    └─→ "Enviar para Revisión" → status: pending
                                        ↓
                                  Notificación al admin
                                        ↓
                                  Admin revisa
                                        ↓
                        ┌───────────────┴───────────────┐
                        ↓                               ↓
                  APROBAR                          RECHAZAR
                        ↓                               ↓
            status: approved                  status: rejected
            is_active: 1                      + rejection_reason
            published_at: NOW()               + Notificación al usuario
            Visible públicamente              Usuario puede corregir
```

### **2. Actualización de Alojamiento Publicado**

```
Usuario edita alojamiento aprobado
         ↓
Cambios se guardan en accommodation_pending_changes
         ↓
Alojamiento original sigue visible (status: approved)
has_pending_changes: TRUE
         ↓
Admin revisa cambios pendientes
         ↓
    ┌────────┴────────┐
    ↓                 ↓
APROBAR           RECHAZAR
    ↓                 ↓
Aplicar cambios   Mantener versión actual
a versión actual  + Notificación con motivo
```

---

## 🗄️ Estructura de Base de Datos

### **Tabla: accommodations (Modificada)**

```sql
-- Nuevos campos agregados:
moderation_status ENUM('draft', 'pending', 'approved', 'rejected')
has_pending_changes BOOLEAN
rejection_reason TEXT
reviewed_by INT (FK → users.id)
reviewed_at DATETIME
published_at DATETIME
last_submitted_at DATETIME
```

### **Tabla: accommodation_pending_changes**

```sql
id INT PRIMARY KEY
accommodation_id INT (FK → accommodations.id)
change_type ENUM('new', 'update')
pending_data JSON  -- Datos completos pendientes
submitted_by INT (FK → users.id)
submitted_at DATETIME
status ENUM('pending', 'approved', 'rejected')
reviewed_by INT
reviewed_at DATETIME
rejection_reason TEXT
admin_notes TEXT
```

### **Tabla: accommodation_moderation_history**

```sql
id INT PRIMARY KEY
accommodation_id INT
action ENUM('created', 'submitted', 'approved', 'rejected', 'updated', 'resubmitted')
performed_by INT
previous_status VARCHAR(50)
new_status VARCHAR(50)
notes TEXT
rejection_reason TEXT
created_at DATETIME
```

### **Tabla: moderation_notifications**

```sql
id INT PRIMARY KEY
user_id INT
accommodation_id INT
notification_type ENUM('submitted', 'approved', 'rejected', 'changes_requested')
title VARCHAR(255)
message TEXT
is_read BOOLEAN
email_sent BOOLEAN
created_at DATETIME
read_at DATETIME
```

---

## 🚀 Instalación

### **Paso 1: Ejecutar Script SQL**

```bash
# Conectar a MySQL
mysql -u u412199647_olgamarin -p u412199647_Rutas

# O usar phpMyAdmin y ejecutar:
```

```sql
-- Ejecutar el archivo completo:
SOURCE /ruta/a/api/crear_sistema_moderacion.sql;
```

### **Paso 2: Verificar Instalación**

```sql
-- Verificar que las tablas se crearon
SHOW TABLES LIKE '%moderation%';
SHOW TABLES LIKE '%pending_changes%';

-- Ver estadísticas
SELECT * FROM v_moderation_stats;

-- Ver cola de moderación
SELECT * FROM v_moderation_queue;
```

### **Paso 3: Configurar Permisos**

Asegúrate de que tu usuario admin tenga `user_type = 'admin'` en la tabla `users`.

---

## 💻 Uso del Panel de Moderación

### **Acceso**

```
URL: https://rutasrurales.io/admin_tablas/moderacion_alojamientos.php
Requiere: Sesión activa con user_type = 'admin'
```

### **Funcionalidades**

1. **Dashboard con Estadísticas**
   - Pendientes de revisión
   - Total aprobados
   - Total rechazados
   - Cambios pendientes

2. **Filtros**
   - Todos los pendientes
   - Solo nuevos alojamientos
   - Solo actualizaciones

3. **Acciones por Alojamiento**
   - 👁️ **Ver Detalles**: Información completa + historial
   - ✅ **Aprobar**: Publica el alojamiento
   - ❌ **Rechazar**: Solicita correcciones con motivo

### **Flujo de Revisión Matutina**

```
1. Abrir panel de moderación
2. Ver estadísticas del día
3. Revisar cada alojamiento:
   - Verificar fotos (calidad, relevancia)
   - Verificar descripción (completa, sin errores)
   - Verificar datos de contacto (válidos)
   - Verificar ubicación (correcta)
4. Aprobar o rechazar con motivo
5. Usuario recibe notificación automática
```

---

## 📱 Experiencia del Usuario

### **En el Formulario de Creación**

```html
<!-- Botones disponibles -->
<button type="button" onclick="guardarBorrador()">
    💾 Guardar Borrador
</button>
<button type="submit">
    📤 Enviar para Revisión
</button>
```

### **En el Dashboard del Usuario**

```
Estado: 🟡 Pendiente de Revisión
"Tu alojamiento está siendo revisado. Te notificaremos pronto."

Estado: ✅ Aprobado
"Tu alojamiento está publicado y visible para todos."

Estado: ❌ Rechazado
"Tu alojamiento fue rechazado. Motivo: [razón]"
[Botón: Corregir y Reenviar]
```

---

## 🔔 Sistema de Notificaciones

### **Notificaciones Automáticas**

1. **Usuario envía para revisión**
   - ✉️ Email al admin: "Nuevo alojamiento pendiente"
   - 🔔 Notificación en panel admin

2. **Admin aprueba**
   - ✉️ Email al usuario: "¡Alojamiento aprobado!"
   - 🔔 Notificación en dashboard usuario

3. **Admin rechaza**
   - ✉️ Email al usuario: "Alojamiento requiere correcciones"
   - 📝 Incluye motivo detallado
   - 🔔 Notificación en dashboard usuario

---

## 🛡️ Seguridad

### **Validaciones Implementadas**

- ✅ Autenticación requerida para todas las operaciones
- ✅ Verificación de permisos de admin
- ✅ Sanitización de datos de entrada
- ✅ Protección contra SQL injection (PDO prepared statements)
- ✅ Validación de campos requeridos
- ✅ Historial completo de acciones

### **Permisos**

```php
// Solo admins pueden moderar
if ($_SESSION['user_type'] !== 'admin') {
    jsonError('Acceso denegado', 403);
}
```

---

## 📈 Estadísticas y Reportes

### **Vista: v_moderation_stats**

```sql
SELECT * FROM v_moderation_stats;
```

Retorna:
- `pending_count`: Alojamientos pendientes
- `approved_count`: Total aprobados
- `rejected_count`: Total rechazados
- `draft_count`: Borradores
- `pending_changes_count`: Cambios pendientes
- `avg_review_time_hours`: Tiempo promedio de revisión

### **Vista: v_moderation_queue**

```sql
SELECT * FROM v_moderation_queue;
```

Retorna cola completa con:
- Datos del alojamiento
- Datos del propietario
- Tipo de cambio (new/update)
- Días pendientes

---

## 🔧 Mantenimiento

### **Limpiar Historial Antiguo**

```sql
-- Eliminar historial mayor a 1 año
DELETE FROM accommodation_moderation_history 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

### **Limpiar Notificaciones Leídas**

```sql
-- Eliminar notificaciones leídas mayores a 30 días
DELETE FROM moderation_notifications 
WHERE is_read = 1 
  AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### **Backup de Datos**

```sql
-- Backup de tablas de moderación
mysqldump -u usuario -p u412199647_Rutas \
  accommodation_pending_changes \
  accommodation_moderation_history \
  moderation_notifications \
  > backup_moderacion_$(date +%Y%m%d).sql
```

---

## 🐛 Troubleshooting

### **Problema: No aparecen alojamientos pendientes**

```sql
-- Verificar estado de alojamientos
SELECT id, name, moderation_status, has_pending_changes 
FROM accommodations 
WHERE moderation_status = 'pending' OR has_pending_changes = 1;
```

### **Problema: Error al aprobar**

```sql
-- Verificar que exista el usuario admin
SELECT id, email, user_type FROM users WHERE user_type = 'admin';

-- Verificar foreign keys
SHOW CREATE TABLE accommodations;
```

### **Problema: Notificaciones no se crean**

```sql
-- Verificar tabla de notificaciones
SELECT * FROM moderation_notifications ORDER BY created_at DESC LIMIT 10;

-- Verificar logs de PHP
tail -f /var/log/php_errors.log
```

---

## 📞 Soporte

Para problemas o mejoras:
1. Revisar logs de PHP: `/var/log/php_errors.log`
2. Revisar logs de MySQL: `/var/log/mysql/error.log`
3. Verificar permisos de usuario
4. Contactar al desarrollador

---

## 🎉 Características Futuras (Roadmap)

- [ ] Sistema de emails automáticos (actualmente TODO)
- [ ] Filtros automáticos de calidad (fotos, descripción)
- [ ] Sistema de puntuación de usuarios
- [ ] Dashboard de estadísticas avanzadas
- [ ] Notificaciones en tiempo real (WebSockets)
- [ ] App móvil para moderación
- [ ] IA para pre-moderación automática

---

## 📝 Changelog

### v1.0.0 (02/08/2026)
- ✅ Sistema completo de moderación implementado
- ✅ Versionado de cambios
- ✅ Panel de administración
- ✅ Historial completo
- ✅ Notificaciones en base de datos
- ✅ Procedimientos almacenados
- ✅ Vistas SQL optimizadas

---

**Desarrollado para Rutas Rurales**  
**Fecha:** 02/08/2026  
**Versión:** 1.0.0
