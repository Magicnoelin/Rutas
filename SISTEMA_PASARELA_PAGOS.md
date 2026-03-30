# 🔐 SISTEMA DE PASARELA DE PAGOS - STRIPE
## RutasRurales.io

---

## 📋 ÍNDICE
1. [Descripción General](#descripción-general)
2. [Estructura del Sistema](#estructura-del-sistema)
3. [Instalación Paso a Paso](#instalación-paso-a-paso)
4. [Configuración de Stripe](#configuración-de-stripe)
5. [Vinculación con Billing Concepts](#vinculación-con-billing-concepts)
6. [Integración Frontend](#integración-frontend)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## 📖 DESCRIPCIÓN GENERAL

Este sistema integra Stripe como pasarela de pagos para gestionar las suscripciones Premium de RutasRurales.io. Incluye:

- ✅ Gestión de planes de membresía (Free, Premium, Business)
- ✅ Procesamiento de pagos recurrentes (mensual/anual)
- ✅ Webhooks para actualización automática de suscripciones
- ✅ Sistema de facturación integrado
- ✅ Vinculación con billing_concepts 12 y 15

### Plan Premium
- **Precio Mensual**: 9.99€ (billing_concept_id: 12)
- **Precio Anual**: 99.99€ (billing_concept_id: 15) - 2 meses gratis
- **Características**:
  - Publicar hasta 2 alojamientos
  - Enviar ofertas a turistas
  - Mensajes ilimitados
  - Estadísticas avanzadas
  - Posicionamiento destacado
  - Soporte prioritario
  - Acceso a promociones especiales

---

## 🏗️ ESTRUCTURA DEL SISTEMA

### Archivos Creados

```
api/
├── diagnostico_billing.php          # Diagnóstico del sistema
├── crear_sistema_billing.sql        # Estructura de base de datos
├── stripe_config.php                # Configuración de Stripe
├── create_checkout_session.php      # Crear sesión de pago
├── stripe_webhook.php               # Webhook de Stripe
├── upgrade_membership.php           # Actualizar membresía (ya existía)
└── get_membership_options.php       # Obtener planes (ya existía)

payment-success.html                 # Página de pago exitoso
payment-cancel.html                  # Página de pago cancelado
```

### Tablas de Base de Datos

1. **billing_concepts** - Catálogo de productos/servicios
2. **billing_profiles** - Datos fiscales de clientes
3. **subscriptions** - Suscripciones activas
4. **invoices** - Facturas generadas
5. **invoice_items** - Líneas de factura
6. **payments** - Pagos recibidos
7. **membership_plans** - Planes de membresía
8. **user_subscriptions** - Suscripciones de usuarios
9. **membership_upgrade_intents** - Intenciones de upgrade

---

## 🚀 INSTALACIÓN PASO A PASO

### PASO 1: Ejecutar el Script SQL

```bash
# Acceder a phpMyAdmin o MySQL
# Ejecutar el archivo: api/crear_sistema_billing.sql
```

O desde línea de comandos:
```bash
mysql -u u412199647_olgamarin -p u412199647_Rutas < api/crear_sistema_billing.sql
```

Esto creará:
- ✅ Todas las tablas necesarias
- ✅ Billing concepts 12 (Premium Mensual) y 15 (Premium Anual)
- ✅ Planes de membresía (Free, Premium, Business)

### PASO 2: Verificar la Instalación

Accede a: `https://rutasrurales.io/api/diagnostico_billing.php`

Deberías ver:
- ✅ Todas las tablas creadas
- ✅ Billing concepts 12 y 15 configurados
- ✅ Planes de membresía disponibles

### PASO 3: Configurar Stripe

1. **Crear cuenta en Stripe** (si no la tienes):
   - Ir a: https://dashboard.stripe.com/register
   - Completar el registro

2. **Obtener las claves API**:
   - Ir a: https://dashboard.stripe.com/apikeys
   - Copiar:
     - Clave publicable (pk_test_...)
     - Clave secreta (sk_test_...)

3. **Editar `api/stripe_config.php`**:
```php
// MODO TEST (para desarrollo)
define('STRIPE_TEST_PUBLIC_KEY', 'pk_test_TU_CLAVE_AQUI');
define('STRIPE_TEST_SECRET_KEY', 'sk_test_TU_CLAVE_AQUI');
```

### PASO 4: Instalar Stripe PHP (Opcional pero Recomendado)

Si tienes acceso a Composer en el servidor:

```bash
cd /ruta/a/tu/proyecto
composer require stripe/stripe-php
```

Si no tienes Composer, puedes descargar manualmente:
1. Ir a: https://github.com/stripe/stripe-php/releases
2. Descargar la última versión
3. Extraer en `vendor/stripe/stripe-php/`

### PASO 5: Configurar Webhook de Stripe

1. **Ir al Dashboard de Stripe**:
   - https://dashboard.stripe.com/webhooks

2. **Añadir endpoint**:
   - URL: `https://rutasrurales.io/api/stripe_webhook.php`
   - Eventos a escuchar:
     - `checkout.session.completed`
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`
     - `invoice.paid`
     - `invoice.payment_failed`

3. **Copiar el Webhook Secret**:
   - Editar `api/stripe_config.php`:
```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_TU_SECRET_AQUI');
```

### PASO 6: Actualizar URLs de Retorno

Editar `api/stripe_config.php`:
```php
define('STRIPE_SUCCESS_URL', 'https://rutasrurales.io/payment-success.html');
define('STRIPE_CANCEL_URL', 'https://rutasrurales.io/payment-cancel.html');
```

---

## 🔗 VINCULACIÓN CON BILLING CONCEPTS

El sistema ya está configurado para vincular automáticamente:

### Billing Concept 12 - Premium Mensual
- **ID**: 12
- **Código**: PREMIUM_MONTHLY
- **Precio**: 9.99€
- **Tipo**: monthly
- **Plan**: Premium (plan_id: 2)

### Billing Concept 15 - Premium Anual
- **ID**: 15
- **Código**: PREMIUM_YEARLY
- **Precio**: 99.99€
- **Tipo**: yearly
- **Plan**: Premium (plan_id: 2)

La vinculación se realiza automáticamente cuando:
1. Usuario selecciona plan Premium
2. Elige ciclo de facturación (mensual/anual)
3. Sistema crea `membership_upgrade_intent` con el billing_concept correspondiente
4. Al completar el pago, se crea una `subscription` vinculada al billing_concept

---

## 💻 INTEGRACIÓN FRONTEND

### En preferences.html o donde tengas los planes

```javascript
// Cuando el usuario hace clic en "Seleccionar Plan Premium"
async function selectPremiumPlan(billingCycle) {
    try {
        const response = await fetch('/api/create_checkout_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                plan_id: 2, // Premium
                billing_cycle: billingCycle // 'monthly' o 'yearly'
            })
        });

        const data = await response.json();

        if (data.success) {
            // Si tienes Stripe.js instalado:
            const stripe = Stripe(data.data.stripe_public_key);
            
            // Redirigir a Stripe Checkout
            if (data.data.stripe_checkout_url) {
                window.location.href = data.data.stripe_checkout_url;
            } else {
                // Alternativa: usar Stripe.js
                // stripe.redirectToCheckout({ sessionId: data.data.stripe_session_id });
                
                // Por ahora, mostrar información
                alert('Sesión de pago creada. Intent ID: ' + data.data.intent_id);
            }
        } else {
            alert('Error: ' + data.error);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al procesar el pago');
    }
}

// Ejemplo de uso:
// <button onclick="selectPremiumPlan('monthly')">Premium Mensual - 9.99€</button>
// <button onclick="selectPremiumPlan('yearly')">Premium Anual - 99.99€</button>
```

### Incluir Stripe.js en tu HTML

```html
<script src="https://js.stripe.com/v3/"></script>
```

---

## 🧪 TESTING

### 1. Probar con Tarjetas de Test de Stripe

Stripe proporciona tarjetas de prueba:

- **Pago exitoso**: `4242 4242 4242 4242`
- **Pago rechazado**: `4000 0000 0000 0002`
- **Requiere autenticación**: `4000 0025 0000 3155`

Fecha de expiración: Cualquier fecha futura
CVC: Cualquier 3 dígitos
Código postal: Cualquiera

### 2. Verificar el Flujo Completo

1. ✅ Usuario selecciona plan Premium
2. ✅ Se crea checkout session
3. ✅ Usuario completa el pago en Stripe
4. ✅ Webhook actualiza la membresía
5. ✅ Usuario es redirigido a payment-success.html
6. ✅ Membresía aparece como "Premium" en el dashboard

### 3. Verificar en Base de Datos

```sql
-- Ver intenciones de upgrade
SELECT * FROM membership_upgrade_intents ORDER BY created_at DESC LIMIT 10;

-- Ver suscripciones activas
SELECT * FROM user_subscriptions WHERE status = 'active';

-- Ver usuarios con Premium
SELECT id, email, membership_type, membership_status 
FROM users 
WHERE membership_type = 'Premium';

-- Ver pagos recibidos
SELECT * FROM payments ORDER BY created_at DESC LIMIT 10;
```

---

## 🔧 TROUBLESHOOTING

### Problema: "Tabla billing_concepts no existe"

**Solución**: Ejecutar `api/crear_sistema_billing.sql`

### Problema: "Stripe keys no configuradas"

**Solución**: Editar `api/stripe_config.php` con tus claves reales

### Problema: "Webhook no funciona"

**Solución**:
1. Verificar que la URL del webhook sea accesible públicamente
2. Verificar que el webhook secret esté configurado correctamente
3. Revisar logs de Stripe Dashboard para ver errores

### Problema: "El pago se completa pero la membresía no se actualiza"

**Solución**:
1. Verificar que el webhook esté configurado
2. Revisar logs del servidor (error_log)
3. Verificar que la tabla `membership_upgrade_intents` tenga el registro

### Problema: "No puedo instalar Composer/Stripe PHP"

**Solución**: El sistema funciona sin Stripe PHP, pero con funcionalidad limitada. Para producción, es altamente recomendado instalarlo.

---

## 📊 MONITOREO

### Dashboard de Stripe

Accede a: https://dashboard.stripe.com

Aquí puedes ver:
- Pagos recibidos
- Suscripciones activas
- Eventos del webhook
- Disputas y reembolsos

### Logs del Sistema

```bash
# Ver logs de PHP
tail -f /var/log/php_errors.log

# Ver logs específicos de Stripe
grep "stripe" /var/log/php_errors.log
```

---

## 🔐 SEGURIDAD

### Recomendaciones:

1. ✅ **Nunca** expongas las claves secretas de Stripe en el frontend
2. ✅ Usa HTTPS en producción (ya lo tienes)
3. ✅ Verifica la firma del webhook en producción
4. ✅ Mantén las claves de test y producción separadas
5. ✅ Revisa regularmente el Dashboard de Stripe

---

## 📞 SOPORTE

Si necesitas ayuda:
- **Documentación de Stripe**: https://stripe.com/docs
- **Soporte de Stripe**: https://support.stripe.com
- **Email**: olgamarin@rutasrurales.io

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

- [ ] Ejecutar `crear_sistema_billing.sql`
- [ ] Verificar con `diagnostico_billing.php`
- [ ] Configurar claves de Stripe en `stripe_config.php`
- [ ] Configurar webhook en Stripe Dashboard
- [ ] Actualizar URLs de retorno
- [ ] Integrar botones de pago en frontend
- [ ] Probar con tarjetas de test
- [ ] Verificar que la membresía se actualiza correctamente
- [ ] Configurar modo producción cuando esté listo
- [ ] Monitorear primeros pagos reales

---

**Última actualización**: 10/02/2026
**Versión**: 1.0
**Estado**: ✅ Listo para implementar
