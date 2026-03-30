# 📋 Sistema de User Dashboard Unificado - Rutas Rurales

## 🎯 Concepto Clave

**Un único dashboard para todos los usuarios**. Lo que cambia no es la pantalla, sino los **permisos** y los **datos** que ve cada usuario según sus roles.

---

## 🏗️ Arquitectura del Sistema

### 1. Estructura de Roles

```
Usuario → Roles → Recursos Vinculados → Permisos
```

**Roles disponibles:**
- 🧳 **Turista** (todos los usuarios por defecto)
- 🏠 **Gestor de Alojamiento**
- 📍 **Gestor de Lugar de Interés**
- 🎯 **Gestor de Actividad**
- 📅 **Gestor de Evento**

⚠️ **Importante**: Un mismo usuario puede tener **múltiples roles simultáneamente**.

---

## 📊 Estructura de Base de Datos

### Tablas Principales

#### 1. `users` (existente, modificada)
```sql
- id
- email
- password_hash
- first_name, last_name
- phone
- avatar_url
- user_type (legacy, mantener por compatibilidad)
- membership_status (pending/validated/blocked) ← NUEVO
- membership_type (free/premium/enterprise) ← NUEVO
- validated_at ← NUEVO
- validated_by ← NUEVO
```

#### 2. `user_resources` ⭐ (NUEVA - LA CLAVE)
```sql
- id
- user_id → users.id
- resource_type (accommodation/place/activity/event)
- resource_id → [tabla correspondiente].id
- role (owner/manager/collaborator)
- status (pending/active/suspended)
- permissions (JSON)
- created_at, updated_at
- validated_at, validated_by
```

**Esta tabla permite:**
- Vincular un usuario con múltiples recursos
- Que un usuario tenga varios roles simultáneamente
- Gestionar permisos granulares por recurso

#### 3. `resource_offers` (NUEVA)
```sql
- id
- user_id → users.id
- resource_type, resource_id
- title, description
- offer_type (discount/package/special/seasonal)
- original_price, offer_price, discount_percentage
- valid_from, valid_until
- max_uses, current_uses
- terms_conditions
- min_people, max_people
- status (draft/active/paused/expired/cancelled)
- is_featured
```

#### 4. `resource_stats` (NUEVA)
```sql
- id
- resource_type, resource_id
- views_count
- interests_count
- messages_count
- favorites_count
- offers_count
- last_view_at, last_interest_at, last_message_at
```

#### 5. Tablas de Recursos (existentes)
- `accommodations` - Alojamientos
- `places_of_interest` - Lugares de interés
- `tourist_activities` - Actividades turísticas
- `cultural_events` - Eventos culturales

#### 6. `conversations` (existente, modificada)
```sql
- Añadido: resource_type, resource_id
- Permite mensajería contextual por recurso
```

---

## 🔌 APIs Creadas

### 1. `api/get_user_resources.php` (GET)
**Obtiene todos los recursos vinculados al usuario**

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "user": { ... },
    "roles": {
      "tourist": true,
      "accommodation_manager": true,
      "place_manager": false,
      ...
    },
    "resources": {
      "accommodation": [...],
      "place": [...],
      "activity": [...],
      "event": [...]
    },
    "summary": {
      "total_resources": 5,
      "accommodations": 2,
      "places": 1,
      "activities": 1,
      "events": 1,
      "active_offers": 3,
      "unread_messages": 7
    }
  }
}
```

### 2. `api/link_resource.php` (POST)
**Vincula un recurso existente con el usuario**

**Body:**
```json
{
  "resource_type": "accommodation",
  "resource_id": 123,
  "role": "owner"
}
```

### 3. `api/manage_resource_offers.php` (GET/POST/PUT/DELETE)
**CRUD completo de ofertas por recurso**

**GET**: Listar ofertas del usuario
- Query params: `resource_type`, `resource_id`, `status`

**POST**: Crear nueva oferta
```json
{
  "resource_type": "accommodation",
  "resource_id": 123,
  "title": "Oferta Fin de Semana",
  "description": "2 noches + desayuno",
  "original_price": 200,
  "offer_price": 150,
  "valid_from": "2026-03-01",
  "valid_until": "2026-03-31",
  "status": "active"
}
```

**PUT**: Actualizar oferta existente
**DELETE**: Eliminar oferta

### 4. `api/track_resource_stat.php` (POST)
**Registra estadísticas de recursos**

**Body:**
```json
{
  "resource_type": "accommodation",
  "resource_id": 123,
  "stat_type": "view" // view, interest, message, favorite
}
```

---

## 🎨 Frontend - User Dashboard

### Estructura del Dashboard

El dashboard tiene **un menú lateral dinámico** que se construye según los roles del usuario:

#### Secciones Comunes (todos los usuarios)
- 🏠 **Inicio** - Resumen y acciones rápidas
- 👤 **Mi Perfil** - Datos personales
- ⚙️ **Preferencias** - Intereses y configuración
- 💬 **Mensajes** - Conversaciones
- 🔔 **Notificaciones**

#### Secciones de Turista
- ❤️ **Favoritos** - Recursos guardados
- 📅 **Mis Reservas** - Reservas activas
- 🎁 **Ofertas Disponibles** - Ofertas filtradas

#### Secciones de Gestor (dinámicas)
Si el usuario tiene recursos vinculados, aparecen:

- 🏠 **Mis Alojamientos** (si tiene alojamientos)
- 📍 **Mis Lugares** (si tiene lugares)
- 🎯 **Mis Actividades** (si tiene actividades)
- 📅 **Mis Eventos** (si tiene eventos)
- 🎁 **Mis Ofertas** (consolidadas de todos sus recursos)
- 📊 **Estadísticas** (consolidadas)

### Carga Dinámica del Menú

```javascript
async function loadDynamicDashboard() {
    // 1. Obtener recursos del usuario
    const response = await fetch('api/get_user_resources.php');
    const data = await response.json();
    
    // 2. Construir menú según roles
    buildDynamicMenu(data.roles, data.resources);
    
    // 3. Cargar sección de inicio personalizada
    buildHomeSection(data);
}

function buildDynamicMenu(roles, resources) {
    const menu = document.querySelector('.sidebar-menu');
    
    // Secciones comunes
    addMenuItem(menu, 'inicio', 'Inicio', 'fa-th-large');
    addMenuItem(menu, 'profile', 'Mi Perfil', 'fa-user-circle');
    addMenuItem(menu, 'mensajes', 'Mensajes', 'fa-comments');
    
    // Secciones de turista
    if (roles.tourist) {
        addMenuItem(menu, 'favoritos', 'Favoritos', 'fa-heart');
        addMenuItem(menu, 'reservas', 'Mis Reservas', 'fa-calendar-check');
    }
    
    // Secciones de gestor
    if (roles.accommodation_manager) {
        addMenuItem(menu, 'mis-alojamientos', 'Mis Alojamientos', 'fa-bed');
    }
    if (roles.place_manager) {
        addMenuItem(menu, 'mis-lugares', 'Mis Lugares', 'fa-map-marker-alt');
    }
    if (roles.activity_manager) {
        addMenuItem(menu, 'mis-actividades', 'Mis Actividades', 'fa-hiking');
    }
    if (roles.event_manager) {
        addMenuItem(menu, 'mis-eventos', 'Mis Eventos', 'fa-calendar-alt');
    }
    
    // Si es gestor de algo, añadir ofertas y estadísticas
    if (roles.accommodation_manager || roles.place_manager || 
        roles.activity_manager || roles.event_manager) {
        addMenuItem(menu, 'mis-ofertas', 'Mis Ofertas', 'fa-tags');
        addMenuItem(menu, 'estadisticas', 'Estadísticas', 'fa-chart-line');
    }
}
```

---

## 🔄 Flujos de Usuario

### Flujo 1: Usuario Nuevo (Turista)
1. Se registra en el sistema
2. Valida membresía (gratis por defecto)
3. Accede al dashboard
4. Ve secciones de turista: Favoritos, Reservas, Ofertas
5. Puede navegar y usar el botón "Estoy Interesado"

### Flujo 2: Usuario quiere ser Gestor
1. Desde el dashboard, sección "Inicio"
2. Ve tarjetas: "Añadir Alojamiento", "Añadir Lugar", etc.
3. Hace clic en una tarjeta
4. **Opción A**: Crea un nuevo recurso
   - Rellena formulario
   - Se crea el recurso
   - Se vincula automáticamente en `user_resources` (status: pending)
5. **Opción B**: Vincula un recurso existente
   - Busca el recurso por nombre/ID
   - Solicita vinculación
   - Admin aprueba (status: pending → active)
6. El menú del dashboard se actualiza automáticamente
7. Ahora ve secciones de gestor

### Flujo 3: Turista contacta a Gestor
1. Turista navega a una ficha pública (alojamiento-detalle.html)
2. Ve botón "Estoy Interesado"
3. Hace clic
4. Sistema verifica si está logueado
5. Si no → redirige a login
6. Si sí → crea conversación vinculada al recurso
7. Redirige a `user-dashboard.html?action=contact&resource_type=accommodation&resource_id=123`
8. Se abre automáticamente el chat con el gestor

### Flujo 4: Gestor gestiona Ofertas
1. Gestor accede a "Mis Ofertas"
2. Ve lista de ofertas activas/expiradas
3. Puede crear nueva oferta:
   - Selecciona recurso
   - Define precio, descuento, fechas
   - Publica
4. La oferta aparece en:
   - Ficha pública del recurso
   - Sección "Ofertas Disponibles" para turistas
   - Dashboard del gestor

---

## 🎁 Botón "Estoy Interesado"

### Implementación en Fichas Públicas

En cada archivo de detalle (alojamiento-detalle.html, lugar-interes.html, etc.):

```html
<button onclick="contactarGestor('accommodation', 123)" class="btn-primary">
    <i class="fas fa-envelope"></i> Estoy Interesado
</button>

<script>
async function contactarGestor(resourceType, resourceId) {
    // Verificar autenticación
    const response = await fetch('api/get_profile.php');
    const user = await response.json();
    
    if (!user.success) {
        // Guardar intención y redirigir a login
        sessionStorage.setItem('pending_contact', JSON.stringify({
            resource_type: resourceType,
            resource_id: resourceId
        }));
        window.location.href = 'login.html?redirect=' + encodeURIComponent(window.location.href);
        return;
    }
    
    // Registrar estadística
    await fetch('api/track_resource_stat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            resource_type: resourceType,
            resource_id: resourceId,
            stat_type: 'interest'
        })
    });
    
    // Redirigir al dashboard con parámetros
    window.location.href = `user-dashboard.html?action=contact&resource_type=${resourceType}&resource_id=${resourceId}`;
}
</script>
```

---

## 📈 Estadísticas y Tracking

### Tipos de Estadísticas
- **view**: Visita a la ficha del recurso
- **interest**: Clic en "Estoy Interesado"
- **message**: Mensaje recibido
- **favorite**: Añadido a favoritos

### Uso
```javascript
// Al cargar una ficha de recurso
await fetch('api/track_resource_stat.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        resource_type: 'accommodation',
        resource_id: 123,
        stat_type: 'view'
    })
});
```

---

## 🔐 Sistema de Permisos

### Niveles de Acceso
1. **Owner** (Propietario)
   - Puede editar todo
   - Puede eliminar el recurso
   - Puede gestionar ofertas
   - Puede añadir colaboradores

2. **Manager** (Gestor)
   - Puede editar contenido
   - Puede gestionar ofertas
   - No puede eliminar
   - No puede añadir colaboradores

3. **Collaborator** (Colaborador)
   - Puede ver estadísticas
   - Puede responder mensajes
   - No puede editar
   - No puede gestionar ofertas

### Validación en APIs
```php
// Verificar que el usuario tiene permisos sobre el recurso
$stmt = $pdo->prepare("
    SELECT role, status 
    FROM user_resources 
    WHERE user_id = :user_id 
    AND resource_type = :resource_type 
    AND resource_id = :resource_id
    AND status = 'active'
");

if (!$permission || $permission['role'] !== 'owner') {
    jsonError('No tienes permisos', 403);
}
```

---

## 🚀 Instalación y Configuración

### 1. Ejecutar Scripts SQL
```bash
# En tu servidor MySQL
mysql -u usuario -p nombre_bd < api/crear_tabla_user_resources.sql
```

Esto creará:
- Tabla `user_resources`
- Tabla `resource_offers`
- Tabla `resource_stats`
- Modificará `users` (añade campos de membresía)
- Modificará `conversations` (añade resource_type, resource_id)
- Creará vistas y procedimientos almacenados

### 2. Verificar APIs
Todas las APIs están en la carpeta `/api/`:
- ✅ `get_user_resources.php`
- ✅ `link_resource.php`
- ✅ `manage_resource_offers.php`
- ✅ `track_resource_stat.php`

### 3. Actualizar Dashboard
El archivo `user-dashboard.html` actual necesita ser refactorizado para:
- Cargar recursos del usuario al inicio
- Construir menú dinámicamente
- Mostrar secciones según roles

---

## 📝 Próximos Pasos de Implementación

### Fase 1: Backend (✅ COMPLETADO)
- [x] Crear tabla `user_resources`
- [x] Crear tabla `resource_offers`
- [x] Crear tabla `resource_stats`
- [x] API `get_user_resources.php`
- [x] API `link_resource.php`
- [x] API `manage_resource_offers.php`
- [x] API `track_resource_stat.php`

### Fase 2: Frontend Dashboard (PENDIENTE)
- [ ] Refactorizar `user-dashboard.html`
- [ ] Implementar carga dinámica de menú
- [ ] Crear secciones de gestor:
  - [ ] Mis Alojamientos
  - [ ] Mis Lugares
  - [ ] Mis Actividades
  - [ ] Mis Eventos
  - [ ] Mis Ofertas
  - [ ] Estadísticas
- [ ] Implementar formularios de creación de ofertas
- [ ] Implementar visualización de estadísticas

### Fase 3: Integración en Fichas Públicas (PENDIENTE)
- [ ] Añadir botón "Estoy Interesado" en:
  - [ ] alojamiento-detalle.html
  - [ ] lugar-interes.html
  - [ ] actividad.html
  - [ ] evento-detalle.html
- [ ] Implementar tracking de estadísticas en cada ficha
- [ ] Mostrar ofertas activas en fichas públicas

### Fase 4: Mensajería Contextual (PENDIENTE)
- [ ] Modificar `api/chat.php` para soportar resource_type y resource_id
- [ ] Actualizar conversaciones para mostrar contexto del recurso
- [ ] Implementar notificaciones por nuevo mensaje

---

## 🎯 Ventajas de esta Arquitectura

✅ **Escalable**: Añadir nuevos tipos de recursos es trivial
✅ **Flexible**: Un usuario puede tener múltiples roles simultáneamente
✅ **Mantenible**: Un solo dashboard, una sola lógica
✅ **SEO-friendly**: Las fichas públicas están separadas del sistema interno
✅ **SaaS-ready**: Preparado para monetización futura con membresías
✅ **Seguro**: Permisos granulares por recurso y rol
✅ **Auditable**: Tracking completo de estadísticas y acciones

---

## 📞 Soporte

Para dudas o problemas:
- Email: olgamarin@rutasrurales.io
- Documentación adicional en `/SISTEMA_TURISTADASHBOARD_README.md`

---

**Última actualización**: 2 de febrero de 2026
**Versión**: 1.0
**Autor**: Sistema Rutas Rurales
