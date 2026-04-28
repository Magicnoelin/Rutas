# 🏗️ Arquitectura de Cola de Tareas — Rutas Rurales

> **Sin crons de Hostinger. Sin tocar código PHP. Todo desde MySQL.**

---

## ¿Qué es esto?

Un sistema de notificaciones y tareas automáticas que vive **100% en MySQL**. Los triggers de la base de datos detectan eventos (nuevas visitas, likes, registros...) y encolan tareas automáticamente. Tú controlas las reglas desde `admin_tablas` sin tocar código.

---

## 📁 Archivos del sistema

| Archivo | Descripción |
|---|---|
| `api/cola_tareas_PASO1_tablas.sql` | Crea las 4 tablas del sistema |
| `api/cola_tareas_PASO2_trigger_resource_stats.sql` | Trigger de visitas y likes |
| `api/cola_tareas_PASO3_trigger_users.sql` | Trigger de nuevos usuarios |
| `api/cola_tareas_PASO4_trigger_accommodations.sql` | Trigger de nuevos alojamientos |
| `api/cola_tareas_PASO5_datos_iniciales.sql` | Plantillas y reglas de ejemplo |
| `api/procesar_cola.php` | Procesador genérico — ejecuta las tareas pendientes |
| `ARQUITECTURA_COLA_TAREAS.md` | Esta documentación |

> ℹ️ El archivo `api/cola_tareas_sistema.sql` contiene todo junto (referencia), pero para phpMyAdmin/MariaDB usa los archivos PASO1-PASO5 por separado.

---

## 🗄️ Tablas creadas

### `plantillas_mensaje`
Los textos de los emails/notificaciones. **Editables desde admin sin tocar código.**

Variables disponibles en las plantillas:
- `{{nombre}}` — Nombre del destinatario
- `{{nombre_entidad}}` — Nombre del alojamiento/evento/ruta
- `{{valor_nuevo}}` — Valor actual (visitas, likes...)
- `{{slug}}` — Slug de la entidad
- `{{url}}` — URL completa de la entidad
- `{{provincia}}` — Provincia
- `{{fecha}}` — Fecha de ejecución
- `{{tipo_tarea}}` — Tipo de tarea
- `{{entidad_tipo}}` — Tipo de entidad
- `{{entidad_id}}` — ID de la entidad

### `reglas_notificacion`
El corazón del sistema. **Cada fila = una regla de negocio.**

| Campo | Descripción |
|---|---|
| `nombre` | Descripción legible |
| `activa` | 1=ON, 0=OFF |
| `tabla_origen` | Tabla MySQL que dispara: `resource_stats`, `users`, `accommodations`... |
| `evento_tipo` | `INSERT` o `UPDATE` |
| `campo_umbral` | Campo a evaluar: `views_count`, `favorites_count`... |
| `umbral_valor` | Número: 50, 100, 20... |
| `umbral_tipo` | `multiplo` / `mayor_igual` / `igual` |
| `resource_type_filtro` | Filtrar por tipo: `accommodation`, `event`, `route`... NULL=todos |
| `tipo_tarea` | `email_propietario`, `email_usuario`, `notif_admin` |
| `plantilla_id` | FK a `plantillas_mensaje` |
| `destinatario` | `propietario`, `admin`, `usuario`, `todos` |
| `requiere_moderacion` | 1=pasa por ti antes de enviarse |
| `cooldown_horas` | Horas mínimas entre disparos (evita spam) |
| `prioridad` | 1=urgente, 5=normal, 10=baja |

### `cola_tareas`
Las tareas generadas automáticamente por los triggers. **No tocar manualmente** (salvo para moderar).

Estados posibles:
- `pendiente` → Lista para procesar
- `moderacion` → Esperando tu aprobación en admin
- `procesando` → En ejecución ahora mismo
- `completada` → Ejecutada con éxito
- `error` → Falló (ver `error_msg`)
- `cancelada` → Cancelada manualmente

### `historial_tareas`
Log inmutable de todo lo ejecutado. Solo lectura. Útil para auditoría.

---

## ⚡ Triggers activos

| Trigger | Tabla | Evento | Qué detecta |
|---|---|---|---|
| `trg_resource_stats_after_update` | `resource_stats` | UPDATE | Cambios en visitas y likes de cualquier recurso |
| `trg_users_after_insert` | `users` | INSERT | Nuevos registros de usuarios |
| `trg_accommodations_after_insert` | `accommodations` | INSERT | Nuevos alojamientos publicados |

---

## 🚀 Instalación (una sola vez)

> ⚠️ **phpMyAdmin de Hostinger (MariaDB) no acepta `DELIMITER` en la pestaña SQL.**  
> Por eso los triggers están en archivos separados. Ejecuta cada PASO por separado.

### PASO 1 — Crear las 4 tablas

1. Abre **Hostinger → Bases de datos → phpMyAdmin**
2. Selecciona `u412199647_Rutas`
3. Pestaña **SQL** → pega el contenido de `api/cola_tareas_PASO1_tablas.sql` → **Ejecutar**

### PASO 2 — Trigger de visitas y likes

1. Pestaña **SQL** → pega el contenido de `api/cola_tareas_PASO2_trigger_resource_stats.sql` → **Ejecutar**

### PASO 3 — Trigger de nuevos usuarios

1. Pestaña **SQL** → pega el contenido de `api/cola_tareas_PASO3_trigger_users.sql` → **Ejecutar**

### PASO 4 — Trigger de nuevos alojamientos

1. Pestaña **SQL** → pega el contenido de `api/cola_tareas_PASO4_trigger_accommodations.sql` → **Ejecutar**

### PASO 5 — Datos iniciales (plantillas y reglas)

1. Pestaña **SQL** → pega el contenido de `api/cola_tareas_PASO5_datos_iniciales.sql` → **Ejecutar**
2. Verás una tabla de verificación con el conteo de registros en cada tabla

### Verificar que los triggers se crearon

```sql
SHOW TRIGGERS;
```

Deberías ver los 3 triggers: `trg_resource_stats_after_update`, `trg_users_after_insert`, `trg_accommodations_after_insert`.

### Verificar las reglas iniciales

```sql
SELECT id, nombre, activa, tabla_origen, campo_umbral, umbral_valor, umbral_tipo 
FROM reglas_notificacion;
```

---

## 🎛️ Uso diario desde admin_tablas

### Ver tareas pendientes de moderación

```sql
SELECT ct.id, ct.tipo_tarea, ct.entidad_tipo, ct.entidad_id, 
       ct.creada_en, ct.payload,
       rn.nombre as regla
FROM cola_tareas ct
LEFT JOIN reglas_notificacion rn ON rn.id = ct.regla_id
WHERE ct.estado = 'moderacion'
ORDER BY ct.creada_en DESC;
```

### Aprobar una tarea (enviarla)

```sql
UPDATE cola_tareas SET estado = 'pendiente' WHERE id = X;
```

### Cancelar una tarea

```sql
UPDATE cola_tareas SET estado = 'cancelada' WHERE id = X;
```

### Aprobar todas las tareas en moderación

```sql
UPDATE cola_tareas SET estado = 'pendiente' WHERE estado = 'moderacion';
```

---

## ⚙️ Gestión de reglas (sin tocar código)

### Cambiar el umbral de visitas (ej: de 50 a 30)

```sql
UPDATE reglas_notificacion SET umbral_valor = 30 WHERE nombre = 'Alerta cada 50 visitas - alojamiento';
```

### Desactivar una regla temporalmente

```sql
UPDATE reglas_notificacion SET activa = 0 WHERE id = 2;
```

### Añadir una nueva regla (ej: alerta a las 100 visitas para eventos)

```sql
INSERT INTO reglas_notificacion 
(nombre, activa, tabla_origen, evento_tipo, campo_umbral, umbral_valor, umbral_tipo, 
 resource_type_filtro, tipo_tarea, plantilla_id, destinatario, requiere_moderacion, cooldown_horas, prioridad)
VALUES
('Alerta 100 visitas - evento', 1, 'resource_stats', 'UPDATE',
 'views_count', 100, 'mayor_igual',
 'event', 'email_propietario', 2, 'propietario', 0, 72, 5);
```

### Añadir una regla con moderación (tú decides si se envía)

```sql
INSERT INTO reglas_notificacion 
(nombre, activa, tabla_origen, evento_tipo, campo_umbral, umbral_valor, umbral_tipo, 
 resource_type_filtro, tipo_tarea, plantilla_id, destinatario, requiere_moderacion, cooldown_horas, prioridad)
VALUES
('Ruta viral - 50 likes', 1, 'resource_stats', 'UPDATE',
 'favorites_count', 50, 'mayor_igual',
 'route', 'email_propietario', 3, 'propietario', 1, 0, 3);
-- requiere_moderacion = 1 → pasa por ti antes de enviarse
```

---

## 📧 Editar plantillas de email

### Ver todas las plantillas

```sql
SELECT id, nombre, canal, asunto FROM plantillas_mensaje;
```

### Editar el asunto de una plantilla

```sql
UPDATE plantillas_mensaje 
SET asunto = '🎉 ¡Tu alojamiento "{{nombre_entidad}}" tiene {{valor_nuevo}} visitas!'
WHERE id = 2;
```

### Editar el cuerpo HTML

```sql
UPDATE plantillas_mensaje 
SET cuerpo_html = '<h2>¡Enhorabuena!</h2><p>Tu alojamiento <strong>{{nombre_entidad}}</strong> ha alcanzado <strong>{{valor_nuevo}} visitas</strong>.</p>'
WHERE id = 2;
```

### Añadir una nueva plantilla

```sql
INSERT INTO plantillas_mensaje (nombre, canal, asunto, cuerpo_html, cuerpo_txt)
VALUES (
  'Mi nueva plantilla', 
  'email',
  'Asunto con {{variable}}',
  '<h2>Hola {{nombre}}</h2><p>Mensaje con {{variable}}.</p>',
  'Hola {{nombre}}. Mensaje con {{variable}}.'
);
```

---

## ▶️ Procesar la cola

### Opción A: Desde admin_tablas (manual)

Llama a esta URL desde tu navegador (con sesión admin activa):
```
https://rutasrurales.io/api/procesar_cola.php
```

O con token:
```
https://rutasrurales.io/api/procesar_cola.php?token=RutasRurales_Cola_2026_$ecret
```

### Opción B: Desde phpMyAdmin / admin_tablas con un botón

Puedes crear un enlace en tu panel de admin que llame a la URL anterior.

### Respuesta del procesador

```json
{
  "procesadas": 3,
  "completadas": 3,
  "errores": 0,
  "omitidas": 0,
  "tiempo_ms": 245,
  "detalle": [
    {"id": 1, "estado": "ok", "msg": "Email enviado a propietario@email.com"},
    {"id": 2, "estado": "ok", "msg": "Email enviado a usuario@email.com"},
    {"id": 3, "estado": "ok", "msg": "Notificación interna guardada"}
  ]
}
```

---

## 📊 Consultas de monitoreo

### Estado general de la cola

```sql
SELECT estado, COUNT(*) as total 
FROM cola_tareas 
GROUP BY estado;
```

### Tareas con errores

```sql
SELECT id, tipo_tarea, entidad_tipo, entidad_id, intentos, error_msg, creada_en
FROM cola_tareas 
WHERE estado = 'error'
ORDER BY creada_en DESC;
```

### Historial de los últimos 7 días

```sql
SELECT tipo_tarea, resultado, COUNT(*) as total
FROM historial_tareas
WHERE ejecutada_en > NOW() - INTERVAL 7 DAY
GROUP BY tipo_tarea, resultado
ORDER BY total DESC;
```

### Reglas más activas

```sql
SELECT rn.nombre, COUNT(ct.id) as tareas_generadas
FROM reglas_notificacion rn
LEFT JOIN cola_tareas ct ON ct.regla_id = rn.id
GROUP BY rn.id, rn.nombre
ORDER BY tareas_generadas DESC;
```

---

## 🔄 Flujo completo de ejemplo

```
1. Usuario visita alojamiento-detalle.php
         ↓
2. api/evento-stats.php actualiza resource_stats:
   UPDATE resource_stats SET views_count = 50 WHERE ...
         ↓
3. MySQL dispara trg_resource_stats_after_update
         ↓
4. El trigger lee reglas_notificacion:
   "¿Hay regla activa para views_count múltiplo de 50 en accommodation?"
   → SÍ (regla id=2)
         ↓
5. INSERT automático en cola_tareas:
   estado='pendiente', tipo_tarea='email_propietario', payload={...}
         ↓
6. Tú vas a admin_tablas y ves la tarea pendiente
         ↓
7. Llamas a procesar_cola.php (manual o con botón)
         ↓
8. El procesador:
   - Busca el email del propietario del alojamiento
   - Carga la plantilla id=2
   - Sustituye {{nombre_entidad}}, {{valor_nuevo}}, etc.
   - Envía el email
   - Marca tarea como 'completada'
   - Guarda en historial_tareas
```

---

## 🛡️ Seguridad

- El procesador requiere **token secreto** o **sesión admin activa**
- El token está en `api/procesar_cola.php` → constante `COLA_TOKEN`
- **Cambia el token** antes de subir a producción
- Las tareas con `requiere_moderacion=1` no se envían hasta que tú las apruebes

---

## 🔮 Escalabilidad futura

Para añadir soporte a **nuevas tablas** (ej: `routes`, `activities`):

1. Crear un nuevo trigger en MySQL:
```sql
CREATE TRIGGER trg_routes_after_insert
AFTER INSERT ON routes
FOR EACH ROW
BEGIN
  -- Mismo patrón que trg_accommodations_after_insert
END$$
```

2. Añadir reglas en `reglas_notificacion` apuntando a `tabla_origen = 'routes'`

3. El procesador ya lo maneja automáticamente (el `switch` en `enriquecer_payload` se puede extender)

**No hay que tocar nada más.**

---

*Sistema diseñado para Rutas Rurales — Abril 2026*
