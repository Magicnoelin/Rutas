# Sistema de Registro Multi-Rol - Rutas

## 📋 Descripción General

Sistema completo de registro de usuarios con diferentes roles y permisos, diseñado para distinguir entre turistas y proveedores de servicios turísticos.

## 🎭 Tipos de Usuario

### 1. **Turista** 🧳
- **Descripción**: Usuario que busca y consume servicios turísticos
- **Verificación**: Automática (inmediata)
- **Permisos**:
  - ✅ Lectura en todos los recursos
  - ❌ No puede crear/editar/eliminar contenido
- **Acceso a**:
  - Ver alojamientos, eventos, lugares de interés
  - Guardar favoritos
  - Hacer reservas
  - Dejar reseñas

### 2. **Alojamiento Turístico** 🏠
- **Descripción**: Propietarios de casas rurales, hoteles, apartamentos
- **Verificación**: Manual (pendiente de aprobación)
- **Permisos**:
  - ✅ CRUD completo en `accommodations` (solo sus propios registros)
  - ✅ Lectura en otros recursos
- **Acceso a**:
  - Crear y gestionar sus alojamientos
  - Ver estadísticas de visualizaciones
  - Gestionar disponibilidad y precios
  - Responder a reseñas

### 3. **Promotor de Eventos Culturales** 🎪
- **Descripción**: Organizadores de eventos, festivales, actividades culturales
- **Verificación**: Manual (pendiente de aprobación)
- **Permisos**:
  - ✅ CRUD completo en `cultural_events` (solo sus propios eventos)
  - ✅ Lectura en otros recursos
- **Acceso a**:
  - Crear y gestionar eventos culturales
  - Ver asistentes registrados
  - Gestionar fechas y horarios
  - Publicar actualizaciones

### 4. **Actividad Cultural / Lugar de Interés** 🎭
- **Descripción**: Museos, monumentos, rutas turísticas, actividades
- **Verificación**: Manual (pendiente de aprobación)
- **Permisos**:
  - ✅ CRUD completo en `places_of_interest` y `activities`
  - ✅ Lectura en otros recursos
- **Acceso a**:
  - Crear y gestionar lugares de interés
  - Gestionar actividades turísticas
  - Actualizar horarios y precios
  - Publicar información relevante

## 🗄️ Estructura de Base de Datos

### Tabla: `users`
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('turista', 'alojamiento', 'promotor_eventos', 'actividad_cultural') NOT NULL DEFAULT 'turista',
    business_name VARCHAR(255) NULL,
    business_description TEXT NULL,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    subscription_level ENUM('basic', 'premium') DEFAULT 'basic',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    password_hash VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    terms_accepted TINYINT(1) DEFAULT 1,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_user_type (user_type),
    INDEX idx_verification_status (verification_status)
);
```

### Tabla: `user_permissions`
```sql
CREATE TABLE user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    resource_type ENUM('accommodations', 'events', 'places', 'activities') NOT NULL,
    can_create BOOLEAN DEFAULT FALSE,
    can_read BOOLEAN DEFAULT TRUE,
    can_update BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_resource_type (resource_type),
    UNIQUE KEY unique_user_resource (user_id, resource_type)
);
```

### Tabla: `user_preferences`
```sql
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    interests JSON,
    accommodation_types JSON,
    budget VARCHAR(20),
    group_size VARCHAR(20),
    trip_duration VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## 🔐 Sistema de Permisos

### Matriz de Permisos por Tipo de Usuario

| Recurso | Turista | Alojamiento | Promotor Eventos | Actividad Cultural |
|---------|---------|-------------|------------------|-------------------|
| **Accommodations** | Read | CRUD | Read | Read |
| **Events** | Read | Read | CRUD | Read |
| **Places** | Read | Read | Read | CRUD |
| **Activities** | Read | Read | Read | CRUD |

**Leyenda:**
- **Read**: Solo lectura
- **CRUD**: Create, Read, Update, Delete (completo)

### Funciones de Autenticación (auth.php)

```php
// Verificar autenticación
isAuthenticated()

// Obtener usuario actual
getCurrentUser()

// Verificar permiso específico
hasPermission($resource, $action)

// Requerir autenticación (o terminar)
requireAuth()

// Requerir permiso específico (o terminar)
requirePermission($resource, $action)

// Verificar propiedad de recurso
isResourceOwner($resourceType, $resourceId)

// Verificar si está verificado
isVerified()

// Requerir verificación
requireVerification()

// Verificar suscripción premium
isPremium()

// Obtener todos los permisos
getUserPermissions($userId)

// Cerrar sesión
logout()
```

## 📝 Flujo de Registro

```
1. Usuario accede a register.html
   ↓
2. Selecciona tipo de usuario
   ↓
3. Formulario se adapta (muestra campos de negocio si no es turista)
   ↓
4. Completa datos personales y de negocio
   ↓
5. API register.php procesa:
   - Valida datos
   - Crea usuario en tabla users
   - Asigna verification_status:
     * turista → 'verified' (acceso inmediato)
     * otros → 'pending' (requiere aprobación)
   - Crea permisos automáticos en user_permissions
   - Inicia sesión
   ↓
6. Redirige a preferences.html
   ↓
7. Usuario configura preferencias
   ↓
8. Redirige a dashboard correspondiente
```

## 🎨 Archivos del Sistema

### Frontend
- **register.html**: Formulario de registro con selector de tipo
- **preferences.html**: Configuración de preferencias de usuario
- **login.html**: Inicio de sesión
- **dashboard-[tipo].html**: Dashboards personalizados (pendiente)

### Backend (API)
- **api/register.php**: Endpoint de registro
- **api/auth.php**: Sistema de autenticación y permisos
- **api/save-preferences.php**: Guardar preferencias de usuario
- **api/config.php**: Configuración y funciones comunes

## 🔧 Uso del Sistema de Permisos

### Ejemplo 1: Proteger endpoint de creación
```php
<?php
require_once 'auth.php';

// Requerir permiso para crear alojamientos
requirePermission('accommodations', 'create');

// Requerir que esté verificado
requireVerification();

// El código continúa solo si tiene permisos
// ...
```

### Ejemplo 2: Verificar propiedad antes de editar
```php
<?php
require_once 'auth.php';

$accommodationId = $_GET['id'];

// Verificar que sea el propietario
if (!isResourceOwner('accommodations', $accommodationId)) {
    jsonError('No puedes editar este alojamiento', 403);
    exit;
}

// Continuar con la edición
// ...
```

### Ejemplo 3: Mostrar opciones según permisos
```php
<?php
require_once 'auth.php';

$user = getCurrentUser();
$permissions = getUserPermissions();

if ($permissions['accommodations']['create']) {
    // Mostrar botón "Agregar Alojamiento"
}

if ($user['user_type'] === 'turista') {
    // Mostrar opciones de turista
}
```

## 🚀 Próximos Pasos

### Pendientes de Implementación

1. **Dashboards Personalizados**
   - [ ] dashboard-turista.html
   - [ ] dashboard-alojamiento.html
   - [ ] dashboard-promotor.html
   - [ ] dashboard-actividad.html

2. **Panel de Administración**
   - [ ] admin-dashboard.html
   - [ ] Aprobar/rechazar negocios
   - [ ] Gestionar usuarios
   - [ ] Ver estadísticas

3. **Actualizar APIs Existentes**
   - [ ] api/crear.php - Agregar verificación de permisos
   - [ ] api/actualizar.php - Verificar propiedad
   - [ ] api/eliminar.php - Verificar propiedad

4. **Sistema de Suscripciones**
   - [ ] Planes básico/premium
   - [ ] Límites por plan
   - [ ] Pasarela de pago

5. **Notificaciones**
   - [ ] Email de verificación
   - [ ] Notificación de aprobación/rechazo
   - [ ] Alertas de actividad

## 📊 Estados de Verificación

| Estado | Descripción | Acciones Permitidas |
|--------|-------------|---------------------|
| **pending** | Cuenta creada, esperando aprobación | Solo lectura |
| **verified** | Cuenta aprobada | Según permisos del rol |
| **rejected** | Cuenta rechazada | Solo lectura |

## 🔒 Seguridad

- ✅ Contraseñas hasheadas con `password_hash()`
- ✅ Validación de email
- ✅ Protección contra SQL injection (prepared statements)
- ✅ Sanitización de inputs
- ✅ Verificación de permisos en cada acción
- ✅ Tokens de verificación únicos
- ✅ Sesiones seguras

## 📞 Soporte

Para dudas o problemas con el sistema de registro:
- Email: olgamarin@rutasrurales.io
- Teléfono: +34 605 249 696

---

**Última actualización**: 11/12/2025
**Versión**: 1.0.0
