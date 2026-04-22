# Ciclo de Membresía y Pago
## Desde el Dashboard hasta la Facturación

---

## 1. VISUALIZACIÓN EN EL DASHBOARD

### Archivos involucrados:
- **`user-dashboard.html`** → Sección `#membresia-section`
- **`api/get_membership_options.php`** → Obtiene los planes disponibles
- **`api/get_profile.php`** → Obtiene el estado actual del usuario

### Flujo:
1. El usuario hace clic en **"Mi Membresía"** en el menú lateral
2. Se ejecuta `loadMembershipData()` en JavaScript:
   - **`GET /api/get_membership_options.php`** → Devuelve los planes (id, name, description, price_monthly, price_yearly, features, is_popular)
   - **`GET /api/get_profile.php`** → Devuelve datos del usuario incluyendo `membership_type`, `membership_start_date`, `membership_end_date`
3. Se renderizan las tarjetas de planes con precios y características
4. Se muestra el plan actual del usuario con su estado (Gratuito/Activo/Vencido)

### Tablas que intervienen:
| Tabla | Columnas usadas | Propósito |
|-------|----------------|-----------|
| `membership_plans` | id, name, description, price_monthly, price_yearly, features, is_popular | Almacena los planes disponibles |
| `users` | membership_type, membership_start_date, membership_end_date | Almacena el plan actual del usuario |

---

## 2. SELECCIÓN DE PLAN Y CICLO

### Archivos involucrados:
- **`user-dashboard.html`** → JavaScript (`selectPlan()`, `redirectToCheckout()`)

### Flujo:
1. El usuario hace clic en **"Seleccionar Plan"** en una tarjeta
2. Se ejecuta `selectPlan(planId, planName, priceMonthly, priceYearly)`:
   - Muestra un modal con opciones: **Mensual** o **Anual**
3. El usuario elige un ciclo de facturación
4. Se ejecuta `redirectToCheckout(planId, billingCycle)`:
   - Muestra indicador de carga
   - Hace **POST** a `api/create_checkout_session.php`

---

## 3. CREACIÓN DE SESIÓN DE PAGO

### Archivos involucrados:
- **`api/create_checkout_session.php`** → Crea la sesión de pago
- **`api/stripe_config.php`** → Configuración de Stripe (opcional)
- **`api/config.php`** → Conexión a BD y funciones auxiliares

### Flujo:
1. Recibe `plan_id` y `billing_cycle` por POST
2. Verifica autenticación del usuario
3. Consulta el plan en `membership_plans`:
   ```sql
   SELECT id, name, price_monthly, price_yearly,
          stripe_product_id, stripe_monthly_price_id, stripe_yearly_price_id
   FROM membership_plans WHERE id = ?
   ```
4. Calcula el precio según el ciclo elegido
5. Calcula IVA (21% por defecto) usando `calculateVAT()`
6. **Intenta crear sesión en Stripe** (si está configurado):
   - Busca `stripePriceId` en BD o configuración global
   - Llama a `createCheckoutSession()` que usa Stripe API
7. **Si Stripe no está configurado** → Modo simulado:
   - Genera un `sessionId` ficticio
   - Crea una URL de prueba con los parámetros
8. Registra la intención de pago en `payment_intents`
9. Devuelve `checkout_url` al frontend

### Tablas que intervienen:
| Tabla | Columnas usadas | Propósito |
|-------|----------------|-----------|
| `membership_plans` | id, name, price_monthly, price_yearly, stripe_product_id, stripe_monthly_price_id, stripe_yearly_price_id | Obtener precio e IDs de Stripe del plan |
| `users` | id, email, first_name, last_name | Obtener datos del usuario para la sesión |
| `payment_intents` | user_id, plan_id, stripe_session_id, stripe_price_id, amount, vat_amount, total_amount, billing_cycle, status, metadata | Registrar la intención de pago |

---

## 4. REDIRECCIÓN A STRIPE (O MODO SIMULADO)

### Archivos involucrados:
- **`user-dashboard.html`** → JavaScript (recibe `checkout_url`)
- **`api/stripe_config.php`** → Función `createCheckoutSession()`

### Flujo:
1. El frontend recibe la respuesta con `checkout_url`
2. Redirige al usuario a esa URL:
   - **Modo real**: URL de Stripe Checkout (`https://checkout.stripe.com/...`)
   - **Modo simulado**: URL de éxito con parámetros (`/user-dashboard.html?payment=success&session_id=...&plan_id=...`)

### En modo real (Stripe):
- Stripe muestra la pantalla de pago con tarjeta
- El usuario introduce sus datos de pago
- Stripe procesa el pago y redirige a `success_url` o `cancel_url`

---

## 5. WEBHOOK DE STRIPE (POST-PAGO)

### Archivos involucrados:
- **`api/stripe_webhook.php`** → Procesa eventos de Stripe
- **`api/stripe_config.php`** → Funciones `handleSuccessfulPayment()`, `handleInvoicePaid()`, etc.

### Eventos procesados:
| Evento Stripe | Acción |
|--------------|--------|
| `checkout.session.completed` | Pago completado → actualizar suscripción |
| `invoice.paid` | Factura pagada → registrar en BD |
| `invoice.payment_failed` | Pago fallido → notificar al usuario |
| `customer.subscription.deleted` | Suscripción cancelada → desactivar membresía |

### Tablas que intervienen:
| Tabla | Propósito |
|-------|-----------|
| `user_subscriptions` | Almacena suscripciones activas del usuario |
| `invoices` | Almacena facturas generadas |
| `payment_intents` | Actualiza estado a 'paid' o 'failed' |
| `users` | Actualiza `membership_type`, `membership_status`, fechas |

---

## 6. FACTURACIÓN

### Archivos involucrados:
- **`api/stripe_webhook.php`** → Genera facturas automáticamente
- **`api/completar_tablas_membresias.sql`** → Vista `membership_summary` y `billing_reports`

### Tablas que intervienen:
| Tabla | Columnas principales | Propósito |
|-------|---------------------|-----------|
| `invoices` | id, user_id, invoice_date, subtotal, tax_amount, total, total_amount, status, payment_status | Almacena facturas emitidas |
| `user_subscriptions` | id, user_id, plan_id, plan_name, billing_cycle, price, total_amount, status, start_date, end_date | Almacena suscripciones |
| `payment_intents` | id, user_id, plan_id, stripe_session_id, amount, vat_amount, total_amount, billing_cycle, status | Registro de intentos de pago |

### Vistas:
| Vista | Propósito |
|-------|-----------|
| `membership_summary` | Resumen de membresía por usuario (tipo, estado, fechas, suscripción activa, estadísticas, límites) |
| `billing_reports` | Reportes mensuales de facturación (totales, IVA, métricas de pago) |

---

## 7. DIAGRAMA DE FLUJO COMPLETO

```
USUARIO                    FRONTEND                     API                        BD                    STRIPE
   |                          |                          |                         |                       |
   |--[Ver Membresía]-------->|                          |                         |                       |
   |                          |--GET /get_membership_options.php-->|                 |                       |
   |                          |                          |--SELECT membership_plans-->|                       |
   |                          |<--Planes disponibles-----|                         |                       |
   |                          |                          |                         |                       |
   |                          |--GET /get_profile.php--->|                         |                       |
   |                          |                          |--SELECT users---------->|                       |
   |                          |<--Estado del usuario-----|                         |                       |
   |                          |                          |                         |                       |
   |<--[Tarjetas de planes]---|                          |                         |                       |
   |                          |                          |                         |                       |
   |--[Selecciona Plan]------>|                          |                         |                       |
   |                          |--[Modal: Mensual/Anual]  |                         |                       |
   |--[Elige ciclo]---------->|                          |                         |                       |
   |                          |                          |                         |                       |
   |                          |--POST /create_checkout_session.php-->|              |                       |
   |                          |     {plan_id, billing_cycle}  |                    |                       |
   |                          |                          |                         |                       |
   |                          |                          |--SELECT plan----------->|                       |
   |                          |                          |--INSERT payment_intent->|                       |
   |                          |                          |                         |                       |
   |                          |                          |----[¿Stripe config?]----|                       |
   |                          |                          |   |                    |                       |
   |                          |                          |   Sí                    |                       |
   |                          |                          |   |--createCheckoutSession()-->|                 |
   |                          |                          |   |<--session.url-------|                       |
   |                          |                          |   No                    |                       |
   |                          |                          |   |--[Modo simulado]    |                       |
   |                          |                          |                         |                       |
   |                          |<--{checkout_url}---------|                         |                       |
   |                          |                          |                         |                       |
   |<--[Redirigir a pago]-----|                          |                         |                       |
   |                          |                          |                         |                       |
   |--[Paga en Stripe]--------|                          |                         |                       |
   |                          |                          |                         |--[Webhook]-->|        |
   |                          |                          |<--checkout.completed----|              |        |
   |                          |                          |                         |              |        |
   |                          |                          |--UPDATE payment_intent->|              |        |
   |                          |                          |--INSERT subscription--->|              |        |
   |                          |                          |--UPDATE users----------->|              |        |
   |                          |                          |--INSERT invoice-------->|              |        |
   |                          |                          |                         |                       |
   |<--[Redirigido a dashboard]|                         |                         |                       |
   |                          |                          |                         |                       |
```

---

## 8. RESUMEN DE TABLAS

| Tabla | Creada en | Propósito |
|-------|-----------|-----------|
| `membership_plans` | `configurar_membresias_produccion.sql` | Catálogo de planes disponibles |
| `users` | Original | Usuarios del sistema (con campos de membresía añadidos) |
| `payment_intents` | `completar_tablas_membresias.sql` | Registro de intentos de pago |
| `user_subscriptions` | `configurar_membresias_produccion.sql` | Suscripciones activas de usuarios |
| `invoices` | Original | Facturas emitidas |

---

## 9. ARCHIVOS DEL SISTEMA

| Archivo | Propósito |
|---------|-----------|
| `user-dashboard.html` | Frontend del dashboard con sección de membresía |
| `api/get_membership_options.php` | API para obtener planes disponibles |
| `api/get_profile.php` | API para obtener perfil y estado del usuario |
| `api/create_checkout_session.php` | API para crear sesión de pago |
| `api/stripe_config.php` | Configuración y funciones de Stripe |
| `api/stripe_webhook.php` | Webhook para procesar eventos de Stripe |
| `api/config.php` | Conexión a BD y funciones base |
| `api/completar_tablas_membresias.sql` | Script SQL para completar tablas faltantes |
| `api/configurar_membresias_produccion.sql` | Script SQL para crear tablas de membresías |
