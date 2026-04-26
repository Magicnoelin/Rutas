e# ✅ Stripe en Producción — Último Paso: Configurar Webhook

## Estado actual

| Componente | Estado |
|---|---|
| `sk_live_...` (Secret Key) | ✅ Configurada en `api/stripe_config.php` |
| `pk_live_...` (Publishable Key) | ✅ Configurada en `api/stripe_config.php` |
| `api/create_checkout_session.php` | ✅ Usa Stripe real (cURL, sin Composer) |
| `api/stripe_webhook.php` | ✅ Listo, esperando webhook secret |
| Webhook secret (`whsec_...`) | ⏳ **PENDIENTE — ver instrucciones abajo** |

---

## 🔧 Paso único: Crear el Webhook en Stripe Dashboard

### 1. Ir al Dashboard de Stripe
👉 https://dashboard.stripe.com/webhooks

### 2. Hacer clic en **"Add endpoint"** (Añadir endpoint)

### 3. Configurar el endpoint:
- **URL del endpoint:** `https://rutasrurales.io/api/stripe_webhook.php`
- **Versión de la API:** `2023-10-16` (o la más reciente)
- **Eventos a escuchar** (seleccionar estos 4):
  - `checkout.session.completed`
  - `invoice.paid`
  - `invoice.payment_failed`
  - `customer.subscription.deleted`

### 4. Hacer clic en **"Add endpoint"** para guardar

### 5. Copiar el **Signing secret** (empieza por `whsec_...`)
- Aparece en la página del webhook recién creado
- Hacer clic en "Reveal" para verlo

### 6. Actualizar `api/stripe_config.php`
Abrir el archivo y reemplazar esta línea:
```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_PENDIENTE_CONFIGURAR_EN_STRIPE_DASHBOARD');
```
Por:
```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_TU_SECRETO_REAL_AQUI');
```

---

## 🧪 Verificar que funciona

### Opción A: Desde Stripe Dashboard
1. En la página del webhook, hacer clic en **"Send test webhook"**
2. Seleccionar `checkout.session.completed`
3. Verificar que devuelve **200 OK**

### Opción B: Hacer un pago real de prueba
1. Ir a `https://rutasrurales.io/user-dashboard.html`
2. Seleccionar un plan de pago
3. Completar el pago con una tarjeta real
4. Verificar en Stripe Dashboard → Payments que aparece el pago
5. Verificar en la BD que se creó la suscripción

---

## 📋 Resumen de archivos modificados

### `api/stripe_config.php`
- ✅ Claves reales de producción configuradas
- ✅ Implementación de Stripe API via cURL (sin necesidad de Composer/vendor)
- ✅ Funciones: `stripeRequest()`, `createStripeCheckoutSession()`, `verifyStripeWebhookSignature()`

### `api/create_checkout_session.php`
- ✅ Eliminado el modo simulado
- ✅ Crea sesiones reales de Stripe Checkout
- ✅ Soporta precios inline (no necesita crear productos en Stripe previamente)
- ✅ Añade `{CHECKOUT_SESSION_ID}` en la URL de éxito para verificación

### `api/stripe_webhook.php`
- ✅ Verificación de firma HMAC (sin librería externa)
- ✅ Maneja arrays asociativos (compatible con json_decode nativo)
- ✅ Procesa: pagos exitosos, renovaciones, fallos, cancelaciones
- ✅ Crea facturas automáticamente en la BD

---

## 💡 Notas importantes

- **No se necesita Composer** ni la librería `stripe/stripe-php`. Todo funciona via cURL nativo de PHP.
- El `composer.json` creado puede ignorarse si el servidor no tiene Composer.
- Los precios se crean **inline** en Stripe (no hace falta crear productos/precios en el Dashboard de Stripe previamente).
- El IVA (21%) se incluye en el precio que ve el usuario. Stripe no calcula IVA adicional.
