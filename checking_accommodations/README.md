# 🏡 Sistema de Check-in Digital para Alojamientos Rurales

> Cumplimiento del **Real Decreto 933/2021** y del portal **SES.MIR** del Ministerio del Interior de España.

---

## 🔄 ¿Cómo funciona? — Flujo completo

```
[ADMINISTRADOR]          [HUÉSPED]
     │                       │
     │  1. Setup              │
     ▼                       │
setup.php                    │
 └─ Importa schema.sql       │
 └─ Activa check-in          │
 └─ Asigna contraseña        │
 └─ Genera URL única ────────►  checkin.php?token=XXXX
                             │   └─ Rellena el formulario
                             │   └─ Pulsa "Enviar"
                             ▼
                      huespedes_registro (BD)
                             │
     ◄───────────────────────┘
     │  4. Ver registros
     ▼
  panel.php
  └─ Tabla de huéspedes
  └─ Ver ficha completa (ficha.php)
```

---

## 📁 Archivos del sistema

| Archivo | Quién lo usa | Para qué |
|---|---|---|
| `setup.php` | Administrador (1 vez) | Activar check-in en cada alojamiento |
| `checkin.php` | Huéspedes (público) | Formulario de registro |
| `login.php` | Administrador | Acceso al panel privado |
| `panel.php` | Administrador | Ver listado de huéspedes |
| `ficha.php` | Administrador | Ver ficha completa de un huésped |
| `logout.php` | Administrador | Cerrar sesión |
| `db.php` | Sistema | Conexión a la base de datos |
| `config.php` | Sistema | Configuración general |
| `schema.sql` | Instalación | SQL para crear/migrar la BD |

---

## 🔧 Instalación paso a paso

### 1. Preparar la base de datos

En **phpMyAdmin** (hPanel → Bases de datos → phpMyAdmin):

1. Selecciona la BD `u412199647_Rutas`
2. Haz clic en la pestaña **"Importar"**
3. Selecciona el archivo `schema.sql`
4. Pulsa **"Continuar"**

Esto añade dos columnas a tu tabla `accommodations`:
- `token_publico` — clave única para identificar cada alojamiento
- `password_hash` — contraseña del administrador del alojamiento

Y crea la tabla `huespedes_registro` donde se guardan los datos de los huéspedes.

---

### 2. Configurar credenciales de la BD

Abre `db.php` y edita las 3 líneas marcadas con ⚠️:

```php
$instance = new PDO(
    'mysql:host=localhost;port=3306;dbname=u412199647_Rutas;charset=utf8mb4',
    'u412199647_Rutas',     // ⚠️ TU USUARIO DE BD
    'PON_AQUI_TU_PASSWORD', // ⚠️ TU CONTRASEÑA DE BD
```

Encuéntralos en: **hPanel → Bases de datos → MySQL → ver detalles**

---

### 3. Configurar la URL base

Abre `config.php` y ajusta:

```php
define('CHECKIN_APP_URL', 'https://rutasrurales.io/checking_accommodations');
```

---

### 4. Activar check-in en cada alojamiento

Abre en el navegador:
```
https://rutasrurales.io/checking_accommodations/setup.php?key=cambia_esta_clave_secreta_2026
```

Verás una tabla con todos tus alojamientos. Para cada uno:
1. Haz clic en **"Activar check-in"** (se despliega un campo de contraseña)
2. Escribe la contraseña que usará ese alojamiento para acceder al panel
3. Pulsa ✅ para guardar

El sistema genera automáticamente una **URL única** de check-in para ese alojamiento.

---

### 5. Compartir el enlace con los huéspedes

Cada alojamiento tiene su propia URL única:
```
https://rutasrurales.io/checking_accommodations/checkin.php?token=a1b2c3...
```

Cómo enviársela a los huéspedes:
- **Por WhatsApp** — justo antes de la llegada
- **Por email** — en el email de confirmación de reserva
- **Código QR** — impreso en la recepción o habitación

> ⚠️ **Cada huésped mayor de 14 años** debe rellenar su propio formulario.

---

### 6. Acceder al panel de administración

```
https://rutasrurales.io/checking_accommodations/login.php
```

- **Email**: el email del alojamiento (el que está en la tabla `accommodations`)
- **Contraseña**: la que asignaste en el paso 4

---

## 📋 Campos del formulario (RD 933/2021)

| Campo | Obligatorio | Notas |
|---|---|---|
| Nombre | ✅ | |
| Apellidos | ✅ | |
| Sexo | ✅ | H / M / X |
| Fecha de nacimiento | ✅ | Solo mayores de 14 años |
| Nacionalidad | ✅ | |
| Tipo de documento | ✅ | DNI / NIE / Pasaporte / Otro |
| Número de documento | ✅ | |
| Fecha de expedición | ✅ | |
| Número de Soporte | ✅ si DNI | Reverso del DNI — 3 letras + 6 números (ej: ABC123456) |
| Teléfono móvil | ✅ | |
| Correo electrónico | ✅ | |
| Calle / Vía | ✅ | |
| Nº / Piso / Puerta | ✅ | |
| Provincia | ✅ | |
| Código postal | ✅ | 5 dígitos si España |
| País | ✅ | Por defecto: España |
| Fecha de entrada | ✅ | |
| Fecha de salida prevista | ✅ | |

---

## 🔒 Seguridad — Aislamiento Multi-tenant

Cada alojamiento **solo puede ver sus propios huéspedes**:

- Las consultas SQL siempre filtran por `alojamiento_id = $_SESSION['alojamiento_id']`
- El ID del alojamiento **nunca** se obtiene de la URL ni del POST
- Si un alojamiento intenta ver la ficha de otro huésped (`ficha.php?id=X`), el sistema lo bloquea y registra el intento en el log
- Tokens CSRF en todos los formularios
- `password_verify()` con bcrypt para el login
- `session_regenerate_id()` tras cada login correcto

---

## 🗑️ Seguridad post-instalación

Una vez configurados todos los alojamientos:

**Elimina `setup.php` del servidor:**
```
Ruta: checking_accommodations/setup.php
```

---

## 🗃️ Estructura de la base de datos

### Tabla `accommodations` (ya existía — se le añaden 2 columnas)

```sql
ALTER TABLE accommodations
  ADD COLUMN token_publico  VARCHAR(64)  NULL UNIQUE,
  ADD COLUMN password_hash  VARCHAR(255) NULL;
```

### Tabla `huespedes_registro` (nueva)

```
id, alojamiento_id (FK→accommodations), nombre, apellidos, sexo,
fecha_nacimiento, nacionalidad, tipo_documento, numero_documento,
fecha_expedicion_doc, numero_soporte, telefono, email,
direccion_calle, direccion_numero, provincia, codigo_postal, pais,
fecha_entrada, fecha_salida_prevista, ip_registro, created_at
```

---

## ❓ Solución de problemas

| Error | Causa probable | Solución |
|---|---|---|
| `Unknown column 'status'` | La tabla no tiene esa columna | Ya solucionado en la versión actual |
| `Undefined constant CHECKIN_DB_HOST` | OPcache o auto_prepend_file | Ya solucionado: credenciales en db.php |
| `Access denied for user` | Contraseña incorrecta en db.php | Verifica en hPanel → MySQL |
| `Table huespedes_registro doesn't exist` | No se importó schema.sql | Importa schema.sql en phpMyAdmin |
| Login incorrecto | El alojamiento no tiene password_hash asignado | Usa setup.php para asignar contraseña |
