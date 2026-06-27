# 🌿 Pasaporte Rural by rutasrurales.io

> **Sella experiencias. Consigue ventajas. Descubre la España rural.**

Sistema de fidelización digital para turistas y alojamientos Premium de rutasrurales.io.

---

## 📋 Índice

1. [¿Qué es el Pasaporte Rural?](#qué-es)
2. [Estructura de archivos](#estructura)
3. [Instalación](#instalación)
4. [Flujo de uso](#flujo-de-uso)
5. [Lógica de puntos y descuentos](#lógica-de-puntos)
6. [Seguridad del QR](#seguridad-del-qr)
7. [Escalabilidad futura](#escalabilidad-futura)
8. [Variables de configuración](#variables-de-configuración)

---

## ¿Qué es? {#qué-es}

El Pasaporte Rural es un **carnet digital** que obtiene cada turista registrado en rutasrurales.io. Funciona así:

1. El **turista** abre `mi-pasaporte.php` en su móvil → ve un **código QR dinámico** que se renueva cada 45 segundos.
2. Al llegar a un **alojamiento Premium**, el propietario escanea el QR con la cámara de su móvil.
3. El sistema valida en tiempo real el pasaporte y muestra el **descuento aplicable (5-10%)** y el nombre del cliente.
4. Al terminar la estancia, el propietario valora al turista (limpieza + civismo, 1-5 ⭐) y pulsa **"Confirmar Sello Rural"**.
5. El turista acumula **puntos** que incrementan su descuento y le suben de **nivel**.

---

## Estructura de archivos {#estructura}

```
pasaporte_rural/
├── config.php              # Constantes y funciones de utilidad del módulo
├── schema.sql              # Script SQL: 3 tablas + índices + vista
│
├── mi-pasaporte.php        # Vista del TURISTA — QR dinámico + historial
├── generar_token_qr.php    # API endpoint AJAX — genera token OTP (GET → JSON)
│
├── validar_pasaporte.php   # Vista del PROPIETARIO — escanea y valora
├── procesar_sello.php      # Backend — guarda el sello (POST)
│
├── css/
│   └── pasaporte.css       # Estilos propios del módulo
│
└── README.md               # Este archivo
```

---

## Instalación {#instalación}

### 1. Base de datos

Ejecutar `schema.sql` en phpMyAdmin sobre la BD `u412199647_Rutas`:

```sql
-- Importar el archivo completo:
-- phpMyAdmin → Seleccionar BD → Importar → schema.sql
```

El script crea:
- `pasaporte_turistas` — Registro maestro de cada pasaporte digital
- `qr_temporales` — Tokens OTP de un solo uso (TTL: 60 s)
- `historico_sellos` — Registro inmutable de cada sello
- Vista `v_pasaportes_resumen` — Para consultas rápidas en el panel admin

### 2. Subir archivos

Subir el directorio `pasaporte_rural/` completo a la raíz del proyecto en el servidor (junto a `api/`, `dashboard/`, etc.).

### 3. Verificar dependencias

El módulo usa `api/config.php` del proyecto principal (ya existente). No necesita dependencias adicionales de Composer.

### 4. Marcar alojamientos Premium

Para que un alojamiento pueda escanear pasaportes:

```sql
-- En phpMyAdmin, sobre la BD u412199647_Rutas:
UPDATE accommodations SET is_premium = 1 WHERE id = TU_ID_ALOJAMIENTO;
```

El propietario también debe tener vinculación en `user_resources`:

```sql
-- Verificar vinculación (el alojamiento ya debería tenerla):
SELECT * FROM user_resources 
WHERE resource_type = 'accommodation' 
  AND resource_id = TU_ID_ALOJAMIENTO 
  AND role = 'owner';
```

---

## Flujo de uso {#flujo-de-uso}

### Perspectiva del turista

```
1. Ir a: https://rutasrurales.io/pasaporte_rural/mi-pasaporte.php
2. Iniciar sesión si no lo está → se crea el pasaporte automáticamente (primera vez)
3. Ver el QR dinámico (se renueva cada 45 segundos)
4. Mostrar el QR al propietario del alojamiento Premium
5. Recibir el sello al final de la estancia y ver los puntos sumados
```

### Perspectiva del propietario Premium

```
1. Abrir la cámara del móvil → escanear el QR del turista
2. El navegador abre: validar_pasaporte.php?token=HASH
3. Iniciar sesión si no lo está
4. Ver pantalla verde con: nombre turista, descuento, puntos, nivel
5. Valorar limpieza (1-5⭐) y civismo (1-5⭐)
6. Opcional: añadir notas privadas sobre la estancia
7. Pulsar "Confirmar Sello Rural"
8. Ver pantalla de confirmación con los puntos sumados
```

---

## Lógica de puntos y descuentos {#lógica-de-puntos}

### Cálculo de puntos por sello

| Criterio | Valor |
|---|---|
| Puntos base | `limpieza + civismo` (máximo 10 pts) |
| Bonus Huésped Excelente | +2 pts si ambas puntuaciones ≥ 4 |
| **Máximo por sello** | **12 pts** |

### Escalado de descuento

| Puntos acumulados | Descuento |
|---|---|
| 0 – 49 | 5% |
| 50 – 99 | 6% |
| 100 – 149 | 7% |
| 150 – 199 | 8% |
| 200 – 249 | 9% |
| 250+ | **10% (máximo)** |

### Niveles de gamificación

| Nivel | Puntos mínimos | Emoji |
|---|---|---|
| Viajero | 0 | 🌱 |
| Explorador | 101 | 🗺️ |
| Embajador Rural | 301 | 🏅 |

*Todos los umbrales son configurables en `config.php` sin tocar el código.*

---

## Seguridad del QR {#seguridad-del-qr}

El sistema implementa múltiples capas de seguridad:

### Token OTP
- Generado con `bin2hex(random_bytes(48))` → **96 caracteres hexadecimales**
- Usa el CSPRNG del SO (cryptographically secure)
- Entropía: 384 bits (imposible de adivinar por fuerza bruta)

### TTL (Time-To-Live)
- El token expira a los **60 segundos** de su generación
- El QR se rota en el cliente cada **45 segundos** (con margen de 15s)
- Si el turista vuelve a la pestaña tras ocultarla, el QR se renueva inmediatamente

### Uso único (burn after read)
- Al sellar, el token pasa de `pendiente` → `usado`
- Un token `usado` ya no puede volver a generar un sello
- Protege contra capturas de pantalla o reutilización del QR

### Defensa en profundidad
- El `alojamiento_id` NUNCA se toma del POST; se re-lee de BD
- El `pasaporte_id` del formulario se verifica cruzando con el token en BD
- Token CSRF en el formulario del sello, con rotación tras cada uso
- Todas las queries usan prepared statements con parámetros

### Limpieza automática (lazy GC)
- En cada llamada a `generar_token_qr.php`, se invalidan tokens propios con más de 2 minutos
- Evita acumulación de registros obsoletos sin necesitar un cron job

---

## Escalabilidad futura {#escalabilidad-futura}

La arquitectura está diseñada para crecer. Ideas identificadas:

### Marketing / gamificación
- **Rutas selladas**: coleccionar sellos de alojamientos en una misma ruta temática (Ruta de la Lana, etc.)
- **Badges / trofeos**: "5 noches en Castilla y León", "Viajero de invierno"
- **Temporadas**: resetear `puntos_periodo` anualmente, rankings por temporada
- **Referidos**: sistema de invitación entre turistas con puntos bonus

### Propietarios
- Panel web para propietarios con historial de sellos, estadísticas de huéspedes
- Descuentos adicionales configurables por alojamiento (encima del 5-10% base)
- Notificaciones cuando un turista de alto nivel visita el alojamiento

### Técnico
- API REST pública para integración con apps móviles nativas
- Webhook al sellar (para notificaciones push, emails de agradecimiento)
- Tabla `niveles` dinámica en BD en lugar de constantes PHP
- Caché de tokens válidos en Redis para reducir queries en momentos de alta carga

---

## Variables de configuración {#variables-de-configuración}

Todas en `config.php`. Cambiables sin tocar lógica:

| Constante | Valor actual | Descripción |
|---|---|---|
| `QR_TTL_SEGUNDOS` | 60 | Validez de cada token QR en segundos |
| `QR_ROTACION_SEGUNDOS` | 45 | Frecuencia de rotación en el cliente |
| `DESCUENTO_BASE` | 5 | % de descuento inicial |
| `DESCUENTO_MAXIMO` | 10 | % de descuento máximo alcanzable |
| `PUNTOS_POR_DESCUENTO` | 50 | Puntos para subir 1% de descuento |
| `BONUS_EXCELENCIA` | 2 | Puntos extra por puntuaciones ≥ 4 |
| `UMBRAL_EXCELENCIA` | 4 | Mínimo en ambas dimensiones para el bonus |
| `NIVELES_GAMIFICACION` | array | Mapa nombre → puntos mínimos por nivel |

---

## URLs del módulo

| URL | Quién accede | Función |
|---|---|---|
| `/pasaporte_rural/mi-pasaporte.php` | Turista | Ver pasaporte y QR |
| `/pasaporte_rural/generar_token_qr.php` | JS (AJAX) | Obtener nuevo token OTP |
| `/pasaporte_rural/validar_pasaporte.php?token=XXX` | Propietario Premium | Escanear y valorar |
| `/pasaporte_rural/procesar_sello.php` | POST interno | Guardar el sello |

---

*Desarrollado para rutasrurales.io — Junio 2026*
