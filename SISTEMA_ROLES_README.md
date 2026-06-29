# Sistema de Roles y Perfiles de Usuario
**RutasRurales.io** — Documentación técnica

---

## 📋 Resumen

Se ha implementado un sistema de roles flexible que **no modifica ni elimina** ninguna tabla existente (users, billing_*, subscriptions, payments, etc.). Todo es **aditivo**.

---

## 🗄️ Estructura de Tablas

### Diagrama

```
users (existente, sin cambios)
  │
  ├──< role_user (pivot) >──── roles (catálogo maestro)
  │
  ├──< profile_turistas       (perfil específico turista)
  │
  └──< profile_alojamientos   (perfil específico alojamiento)
```

### Tabla `roles` — Catálogo maestro

| Campo       | Tipo         | Descripción                        |
|-------------|-------------|-------------------------------------|
| id          | INT PK       | Identificador                      |
| nombre      | VARCHAR(100) | Nombre legible: "Turista"          |
| slug        | VARCHAR(100) | Clave única: "turista"             |
| descripcion | TEXT         | Descripción del rol                |
| created_at  | TIMESTAMP    | Fecha de creación                  |

**Roles predefinidos:**

| id | nombre               | slug               |
|----|---------------------|--------------------|
| 1  | Turista             | turista            |
| 2  | Alojamiento         | alojamiento        |
| 3  | Promotor de Eventos | promotor_eventos   |
| 4  | Actividad Cultural  | actividad_cultural |
| 5  | Administrador       | admin              |

---

### Tabla `role_user` — Pivot (un usuario puede tener varios roles)

| Campo       | Tipo      | Descripción                        |
|-------------|----------|------------------------------------|
| user_id     | INT FK   | Referencia a `users.id`            |
| role_id     | INT FK   | Referencia a `roles.id`            |
| assigned_at | TIMESTAMP | Cuándo se asignó el rol           |

> **Clave primaria compuesta:** `(user_id, role_id)` — evita duplicados.

---

### Tabla `profile_alojamientos` — Perfil específico de propietarios

| Campo               | Tipo         | Descripción                          |
|--------------------|-------------|---------------------------------------|
| user_id            | INT FK UNIQUE| Un perfil por usuario                |
| nif                | VARCHAR(20)  | NIF/CIF del propietario              |
| razon_social       | VARCHAR(255) | Nombre legal o razón social          |
| direccion          | TEXT         | Dirección del negocio                |
| municipio          | VARCHAR(150) | Municipio                            |
| provincia          | VARCHAR(100) | Provincia                            |
| codigo_postal      | VARCHAR(10)  | Código postal                        |
| telefono_negocio   | VARCHAR(30)  | Teléfono de contacto del negocio     |
| web                | VARCHAR(255) | Web del alojamiento                  |
| capacidad_total    | INT          | Total de plazas gestionadas          |
| num_alojamientos   | INT          | Número de alojamientos registrados   |
| descripcion_negocio| TEXT         | Descripción del negocio              |
| logo_url           | VARCHAR(500) | URL del logo                         |

---

### Tabla `profile_turistas` — Perfil específico de turistas

| Campo            | Tipo         | Descripción                                      |
|-----------------|-------------|--------------------------------------------------|
| avatar_url      | VARCHAR(500) | URL a la imagen de perfil del usuario            |
| user_id         | INT FK UNIQUE| Un perfil por usuario                           |
| intereses_json  | JSON         | Array: ["naturaleza","cultura","aventura",...]  |
| presupuesto     | ENUM         | bajo / medio / alto / sin_limite               |
| duracion_viaje  | ENUM         | fin_semana / puente / semana / mas_semana       |
| viaja_con       | ENUM         | solo / pareja / familia / amigos / grupo        |
| provincia_origen| VARCHAR(100) | Provincia de origen del turista                 |
| pais_origen     | VARCHAR(100) | País de origen (default: España)                |
| idioma_preferido| VARCHAR(10)  | Idioma (default: es)                            |
| notas           | TEXT         | Notas adicionales                               |

---

## 🚀 Instalación

### Paso 1 — Ejecutar el SQL en phpMyAdmin

1. Abre **phpMyAdmin** → base de datos `u412199647_Rutas`
2. Pestaña **SQL**
3. Copia y ejecuta el contenido de: **`api/crear_sistema_roles.sql`**

El script:
- Crea las 4 tablas nuevas (`roles`, `role_user`, `profile_alojamientos`, `profile_turistas`)
- Inserta los 5 roles base
- **Migra automáticamente** los usuarios existentes: lee `users.user_type` y crea las entradas en `role_user`
- **Migra preferencias** existentes de `users.preferences_json` a `profile_turistas`
- Es **idempotente**: se puede ejecutar varias veces sin duplicar datos

### Paso 2 — Subir los archivos PHP nuevos al servidor

Archivos a subir:

```
api/crear_sistema_roles.sql      ← Script SQL de instalación
api/roles.php                    ← API de gestión de roles
api/profile_alojamiento.php      ← API perfil alojamiento
api/profile_turista.php          ← API perfil turista
api/register.php                 ← Actualizado (asigna rol en registro)
admin_tablas/usuarios_roles.php  ← Panel admin de roles
admin_tablas/sidebar.php         ← Actualizado (nuevo enlace en menú)
```

---

## 🔌 API Endpoints

### `GET /api/roles.php`
Lista todos los roles disponibles.

```json
{
  "success": true,
  "data": [
    {"id": 1, "nombre": "Turista", "slug": "turista", "descripcion": "..."},
    {"id": 2, "nombre": "Alojamiento", "slug": "alojamiento", "descripcion": "..."}
  ]
}
```

### `GET /api/roles.php?action=mis_roles`
Roles del usuario autenticado (requiere sesión).

### `GET /api/roles.php?action=user_roles&user_id=X`
Roles de un usuario concreto (requiere sesión).

### `POST /api/roles.php`
Gestionar roles. Body JSON:

```json
// Asignar un rol
{ "action": "assign", "role_slug": "alojamiento" }

// Quitar un rol
{ "action": "remove", "role_slug": "turista" }

// Reemplazar todos los roles
{ "action": "set_roles", "role_slugs": ["turista", "alojamiento"] }

// Asignar rol a otro usuario (admin)
{ "action": "assign", "role_slug": "admin", "user_id": 42 }
```

---

### `GET /api/profile_turista.php`
Obtiene el perfil turista del usuario autenticado.

### `POST /api/profile_turista.php`
Crea o actualiza el perfil turista. Body JSON:

```json
{
  "intereses_json": ["naturaleza", "cultura", "gastronomia"],
  "presupuesto": "medio",
  "duracion_viaje": "fin_semana",
  "viaja_con": "pareja",
  "provincia_origen": "Madrid",
  "pais_origen": "España"
}
```

---

### `GET /api/profile_alojamiento.php`
Obtiene el perfil de alojamiento del usuario autenticado.

### `POST /api/profile_alojamiento.php`
Crea o actualiza el perfil de alojamiento. Body JSON:

```json
{
  "nif": "12345678A",
  "razon_social": "Casa Rural El Pinar S.L.",
  "direccion": "Calle Mayor 1",
  "municipio": "Cuenca",
  "provincia": "Cuenca",
  "codigo_postal": "16001",
  "telefono_negocio": "969000000",
  "web": "https://elpinar.es",
  "capacidad_total": 12,
  "num_alojamientos": 2,
  "descripcion_negocio": "Casa rural en plena naturaleza..."
}
```

---

## 🛡️ Panel de Administración

Accede a: **`/admin_tablas/usuarios_roles.php`**

Funcionalidades:
- Ver todos los usuarios con sus roles asignados
- Contador de usuarios por rol
- Buscador por nombre, email o rol
- Modal para editar roles con checkboxes (multi-rol)
- Sincronización automática de `user_type` (compatibilidad legacy)

---

## ⚙️ Compatibilidad con el sistema anterior

El campo `users.user_type` **se mantiene intacto** y se sincroniza automáticamente:

- Cuando se asigna/quita un rol → `user_type` se actualiza al primer rol del usuario
- Cuando se registra un nuevo usuario → se asigna rol en `role_user` Y se mantiene `user_type`
- Todo el código existente que lee `user_type` sigue funcionando sin cambios

---

## 🔒 Tablas de Facturación — Sin cambios

Las siguientes tablas **NO han sido modificadas**:

- `billing_concepts`
- `billing_profiles`
- `subscriptions`
- `invoices`
- `invoice_items`
- `payments`
- `membership_plans`
- `user_subscriptions`
- `membership_upgrade_intents`

---

## 📁 Archivos creados/modificados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `api/crear_sistema_roles.sql` | NUEVO | Script SQL de instalación |
| `api/roles.php` | NUEVO | API gestión de roles |
| `api/profile_alojamiento.php` | NUEVO | API perfil alojamiento |
| `api/profile_turista.php` | NUEVO | API perfil turista |
| `api/register.php` | MODIFICADO | Asigna rol en `role_user` al registrar |
| `admin_tablas/usuarios_roles.php` | NUEVO | Panel admin de roles |
| `admin_tablas/sidebar.php` | MODIFICADO | Añadido enlace "Roles de Usuarios" |

---

*Creado: Febrero 2026 — RutasRurales.io*
