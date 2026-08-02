# Sistema de Cuentas de Usuario y Multi-Negocio
**Proyecto:** Rutas Rurales — rutasrurales.io  
**Última actualización:** 2026-08-02  
**Versión:** 2.0

---

## 📋 Resumen ejecutivo

Un **usuario** es una persona real que se identifica con un único email de acceso.  
Un **negocio** es un alojamiento, artesanía, actividad o evento que esa persona gestiona.  
Una persona puede tener **múltiples negocios** bajo una sola cuenta.

```
Raúl Gradillas Lobato (1 cuenta, 1 email de login)
├── Negocio 1: "Abuela Nines"       → email negocio: xxx@abuelanines.com
└── Negocio 2: "Artesanía Hadaleanan" → email negocio: xxx@hadaleanan.com
```

---

## 🗄️ Arquitectura de Base de Datos

### Tabla `users` — La persona que se loguea

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT PK | Identificador único de la persona |
| `email` | VARCHAR(255) **UNIQUE** | Email de login. Solo uno por persona. Siempre en minúsculas. |
| `phone` | VARCHAR(20) UNIQUE NULL | Teléfono personal. Opcional. NULL si no se proporciona. |
| `first_name` / `last_name` | VARCHAR | Nombre real de la persona |
| `password_hash` | VARCHAR(255) | Hash bcrypt de la contraseña |
| `user_type` | ENUM | `turista`, `alojamiento`, `promotor_eventos`, `actividad_cultural` |
| `status` | ENUM | `active`, `inactive`, `suspended` |
| `google_id` / `facebook_id` | VARCHAR | IDs de OAuth social (Google/Facebook) |
| `auth_provider` | VARCHAR | `google`, `facebook` o NULL (registro tradicional) |

**Regla de oro:** El `email` de `users` es el **email de acceso al panel**, no el email de contacto público del negocio.

---

### Tabla `accommodations` — Los alojamientos/negocios

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT PK | Identificador del alojamiento |
| `created_by` | INT INDEX (no UNIQUE) | **FK → users.id** del propietario. Un usuario puede tener N alojamientos. |
| `name` | VARCHAR | Nombre del alojamiento (visible al público) |
| `email` | VARCHAR | Email de contacto **del negocio** (diferente al email de login) |
| `phone` | VARCHAR | Teléfono **del negocio** |
| `owner_notes` | TEXT | Notas internas del propietario (no visibles al turista) |

**El `email` y `phone` de `accommodations` NO tienen UNIQUE** porque:
- Raúl puede usar el mismo teléfono personal en sus dos negocios.
- Dos negocios distintos pueden tener el mismo email de contacto.

---

### Tabla `user_businesses` — Panel multi-negocio (NUEVA)

Tabla puente que conecta usuarios con todos sus negocios de forma organizada.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | INT PK | |
| `user_id` | INT FK→users.id | El propietario |
| `business_name` | VARCHAR(255) | Nombre comercial |
| `business_type` | ENUM | `alojamiento`, `promotor_eventos`, `actividad_cultural`, `artesania`, `restauracion`, `otro` |
| `business_email` | VARCHAR(255) NULL | Email público del negocio |
| `business_phone` | VARCHAR(20) NULL | Teléfono público del negocio |
| `business_web` | VARCHAR(500) NULL | Web del negocio |
| `accommodation_id` | INT NULL FK→accommodations.id | Vinculación con la ficha del alojamiento si aplica |
| `is_primary` | TINYINT(1) | `1` = negocio principal que ve primero en su panel |
| `status` | ENUM | `active`, `inactive`, `pending` |

---

## 🔐 Unicidad: Qué es único y qué no

| Tabla | Campo | UNIQUE | Razón |
|---|---|---|---|
| `users` | `email` | ✅ SÍ | Una persona = un email de login |
| `users` | `phone` | ✅ SÍ (NULL-safe) | Un teléfono personal no puede pertenecer a dos personas |
| `accommodations` | `email` | ❌ NO | Es el email del negocio, no del propietario |
| `accommodations` | `phone` | ❌ NO | Puede compartirse entre negocios del mismo dueño |
| `accommodations` | `created_by` | ❌ NO (solo INDEX) | Un propietario puede tener N alojamientos |
| `accommodations` | `slug` | ✅ SÍ | URL única por alojamiento |

---

## 🔄 Flujos de Registro y Login

### Flujo 1: Registro nuevo (formulario tradicional)

```
Usuario rellena formulario
        ↓
PHP normaliza email: trim() + strtolower()
PHP normaliza teléfono: elimina espacios/guiones, conserva el +
        ↓
¿El email ya existe en users?
    → SÍ + cuenta normal:  Error 409 "Ya tienes cuenta → inicia sesión"
    → SÍ + cuenta Google:  Error 409 "Usa Google para entrar"
    → SÍ + cuenta Facebook: Error 409 "Usa Facebook para entrar"
    → NO: continuar
        ↓
¿El teléfono existe en OTRA cuenta?
    → SÍ: Error 409 "Teléfono ya registrado en otra cuenta"
    → NO o vacío: continuar
        ↓
INSERT INTO users (email normalizado, phone normalizado)
        ↓
Sesión iniciada → redirect al dashboard
```

### Flujo 2: Login con email/contraseña

```
Usuario introduce email + password
        ↓
PHP normaliza email: trim() + strtolower()
        ↓
SELECT * FROM users WHERE email = :email_normalizado
        ↓
¿Encontrado?
    → NO: "Credenciales incorrectas" (sin especificar si es email o password)
    → SÍ: verificar password_hash con password_verify()
        → Mal: "Credenciales incorrectas"
        → Bien: sesión iniciada → redirect
```

### Flujo 3: Login con Google/Facebook (Social Login)

```
Frontend recibe token OAuth del proveedor
        ↓
PHP decodifica token → obtiene email del proveedor
PHP normaliza ese email: trim() + strtolower()
        ↓
¿Existe ya en users con ese google_id/facebook_id?
    → SÍ: sesión directa (usuario recurrente)
        ↓
¿Existe en users con ese EMAIL normalizado?
    → SÍ: vincular google_id a la cuenta existente → sesión iniciada
         (un usuario que se registró por formulario ahora entra con Google)
    → NO: crear usuario nuevo con email normalizado → sesión iniciada
```

**Este flujo es el que resolvió el caso `elcampanario.vut.villoria@gmail.com`:**  
Antes, Google devolvía el email con mayúsculas y el sistema no lo encontraba → creaba duplicado.  
Ahora, el email siempre se normaliza antes de buscar → no hay duplicados.

---

## 👤 Tipos de Usuario

| Tipo | Verificación | Puede crear contenido |
|---|---|---|
| `turista` | Automática (acceso inmediato) | ❌ Solo lectura |
| `alojamiento` | Manual (pendiente aprobación) | ✅ Sus propios alojamientos |
| `promotor_eventos` | Manual | ✅ Sus propios eventos |
| `actividad_cultural` | Manual | ✅ Lugares e actividades |
| `admin` / `gestor` | Manual | ✅ Todo |

---

## 🏢 Modelo Multi-Negocio: Cómo gestionar varios negocios

### Para el propietario (panel de usuario)

Un propietario con múltiples negocios ve en su dashboard una lista de sus negocios:

```
Panel de Raúl Gradillas
├── 🏠 Abuela Nines       [Alojamiento] [Activo]   [Editar]
└── 🎨 Artesanía Hadaleanan [Artesanía] [Pendiente] [Editar]
```

Raúl inicia sesión **una sola vez** con su email principal (el de `users.email`) y gestiona todos sus negocios desde ahí.

### Para el administrador (panel de admin)

En lugar de ver 2 filas repetidas para Raúl, el admin ve **1 fila** con N negocios asociados.

---

## 🔌 API Endpoints

### `POST /api/register.php`
Registra un nuevo usuario. Normaliza email y teléfono automáticamente.

**Body:**
```json
{
  "firstName": "Raúl",
  "lastName": "Gradillas Lobato",
  "email": "raul@gmail.com",
  "phone": "+34 655 256 304",
  "password": "mipassword123",
  "confirmPassword": "mipassword123",
  "terms": true
}
```

**Respuestas de error diferenciadas:**
```json
// Email ya existe (registro normal):
{ "success": false, "error": "Ya existe una cuenta...", "error_type": "email_exists", "action": "redirect_login" }

// Email ya existe (cuenta Google):
{ "success": false, "error": "Ya tienes cuenta con Google...", "error_type": "email_exists_social", "action": "redirect_login" }

// Teléfono en otra cuenta:
{ "success": false, "error": "Este teléfono ya está registrado...", "error_type": "phone_exists" }
```

---

### `POST /api/login.php`
Login con email y contraseña. El email se normaliza antes de buscar.

---

### `POST /api/social_login.php`
Login con Google o Facebook. Normaliza el email del proveedor y fusiona con cuenta existente si es necesario.

---

### `GET /api/user_businesses.php?user_id=X`
Lista todos los negocios de un usuario.

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "user_id": 131,
    "total": 2,
    "businesses": [
      {
        "id": 1,
        "business_name": "Abuela Nines",
        "business_type": "alojamiento",
        "business_email": "info@abuelanines.com",
        "business_phone": "+34655256304",
        "accommodation_id": 45,
        "is_primary": 1,
        "status": "active"
      },
      {
        "id": 2,
        "business_name": "Artesanía Hadaleanan",
        "business_type": "artesania",
        "business_email": "artesania@hadaleanan.com",
        "accommodation_id": null,
        "is_primary": 0,
        "status": "active"
      }
    ]
  }
}
```

---

### `POST /api/user_businesses.php` — Acciones disponibles

| `action` | Descripción | Quién puede |
|---|---|---|
| `create` | Crear un nuevo negocio | El propio usuario |
| `update` | Actualizar datos del negocio | El propio usuario |
| `link` | Vincular un alojamiento existente a un usuario | Solo admin |
| `set_primary` | Marcar un negocio como principal | El propio usuario |

---

## 🛠️ Scripts de Migración / DDL

### `api/unicidad_email_telefono_DDL.php`
**Ejecutado: 2026-08-02 ✅**

- Normaliza emails a minúsculas en BD
- Añade `UNIQUE INDEX uq_users_email` en `users.email`
- Añade `UNIQUE INDEX uq_users_phone` en `users.phone` (NULL-safe)

**Cómo ejecutar:**
```
https://rutasrurales.io/api/unicidad_email_telefono_DDL.php?token=rutas2026_ddl_unicidad_abc123
```

---

### `api/multi_negocio_DDL.php`
**Pendiente de ejecutar en producción.**

- Verifica que `accommodations.created_by` no tiene UNIQUE (ya confirmado ✅)
- Verifica que `accommodations.email` y `.phone` no tienen UNIQUE ✅
- Añade columna `owner_notes` a accommodations
- Crea tabla `user_businesses`
- Migra alojamientos existentes a `user_businesses`

**Cómo ejecutar:**
```
https://rutasrurales.io/api/multi_negocio_DDL.php?token=rutas2026_multinegocio_xyz789
```

> ⚠️ Eliminar ambos archivos del servidor tras ejecutarlos.

---

## 📁 Archivos del sistema

### Nuevos (creados en v2)
| Archivo | Descripción |
|---|---|
| `api/user_normalizer.php` | Funciones de normalización: `normalizeEmail()`, `normalizePhone()`, `checkEmailExists()`, `checkPhoneExists()`, `handleDuplicateKeyException()` |
| `api/user_businesses.php` | API REST para gestión de múltiples negocios por usuario |
| `api/unicidad_email_telefono_DDL.php` | Script DDL de unicidad (ejecutado ✅) |
| `api/multi_negocio_DDL.php` | Script DDL de arquitectura multi-negocio |

### Modificados (v2)
| Archivo | Cambio |
|---|---|
| `api/register.php` | Email normalizado, check de teléfono, catch PDO 23000, respuestas con `error_type` |
| `api/login.php` | Email normalizado antes de la query |
| `api/social_login.php` | Email del proveedor normalizado, lógica de merge mejorada |

---

## 🔒 Seguridad implementada

| Medida | Dónde |
|---|---|
| Consultas preparadas PDO | Todos los endpoints |
| Email normalizado antes de guardar y buscar | `user_normalizer.php` |
| Teléfono normalizado (sin separadores) | `user_normalizer.php` |
| UNIQUE en BD como última barrera | `users.email`, `users.phone` |
| PDOException 23000 capturada y mapeada | `register.php`, `social_login.php` |
| Mensajes de error internos de BD nunca expuestos | Todos los endpoints |
| session_regenerate_id(true) en login | `login.php`, `social_login.php`, `register.php` |
| Verificación de propiedad antes de editar | `user_businesses.php`, `actualizar_alojamiento.php` |

---

## ⚡ Integración con el Frontend (AJAX/JSON)

El frontend debe leer el campo `error_type` de las respuestas de error para mostrar el mensaje y la acción adecuados:

```javascript
fetch('/api/register.php', { method: 'POST', body: JSON.stringify(formData) })
  .then(res => res.json())
  .then(data => {
    if (!data.success) {
      switch (data.error_type) {
        case 'email_exists':
          // Mostrar: "Ya tienes cuenta. ¿Quieres iniciar sesión?"
          // Acción: mostrar botón → /login.html
          break;
        case 'email_exists_social':
          // Mostrar: "Accede con Google/Facebook"
          // Acción: mostrar botón OAuth
          break;
        case 'phone_exists':
          // Mostrar: "Este teléfono ya está en otra cuenta"
          // Acción: limpiar campo teléfono del formulario
          break;
        default:
          // Mostrar mensaje genérico: data.error
      }
    }
  });
```

---

*Documentación generada automáticamente. No editar manualmente sin actualizar también el código.*
