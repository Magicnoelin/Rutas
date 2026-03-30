# 🚀 GUÍA RÁPIDA - PASARELA DE PAGOS
## Poner en marcha en 10 minutos

---

## ✅ PASO 1: Ejecutar SQL (2 minutos)

1. Accede a **phpMyAdmin** en tu hosting
2. Selecciona la base de datos: `u412199647_Rutas`
3. Ve a la pestaña **SQL**
4. Abre el archivo: `api/crear_sistema_billing.sql`
5. Copia todo el contenido y pégalo en phpMyAdmin
6. Haz clic en **Continuar**

**Resultado esperado**: ✅ "Sistema de facturación creado exitosamente"

---

## ✅ PASO 2: Verificar Instalación (1 minuto)

Accede a: `https://rutasrurales.io/api/diagnostico_billing.php`

**Debes ver**:
- ✅ 9 tablas creadas (todas en verde)
- ✅ Billing Concept 12: Premium Mensual - 9.99€
- ✅ Billing Concept 15: Premium Anual - 99.99€
- ✅ 3 planes de membresía (Free, Premium, Business)

Si ves esto, ¡perfecto! Continúa al siguiente paso.

---

## ✅ PASO 3: Configurar Stripe (5 minutos)

### 3.1 Crear cuenta Stripe (si no la tienes)
- Ir a: https://dashboard.stripe.com/register
- Completar registro

### 3.2 Obtener claves API
1. Ir a: https://dashboard.stripe.com/test/apikeys
2. Copiar:
   - **Clave publicable**: `pk_test_...`
   - **Clave secreta**: `sk_test_...`

### 3.3 Configurar en el servidor
1. Editar el archivo: `api/stripe_config.php`
2. Reemplazar:
```php
define('STRIPE_TEST_PUBLIC_KEY', 'pk_test_TU_CLAVE_AQUI');
define('STRIPE_TEST_SECRET_KEY', 'sk_test_TU_CLAVE_AQUI');
```

3. Guardar el archivo

---

## ✅ PASO 4: Configurar Webhook (2 minutos)

1. Ir a: https://dashboard.stripe.com/test/webhooks
2. Clic en **"Añadir endpoint"**
3. URL del endpoint: `https://rutasrurales.io/api/stripe_webhook.php`
4. Seleccionar eventos:
   - `checkout.session.completed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.paid`
   - `invoice.payment_failed`
5. Clic en **"Añadir endpoint"**
6. Copiar el **Signing secret** (empieza con `whsec_...`)
7. Editar `api/stripe_config.php`:
```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_TU_SECRET_AQUI');
```

---

## ✅ PASO 5: Probar el Sistema (OPCIONAL - Requiere Stripe PHP)

### Instalar Stripe PHP (si tienes acceso SSH):
```bash
cd /home/u412199647/domains/rutasrurales.io/public_html
composer require stripe/stripe-php
```

### Si NO tienes Composer:
El sistema funcionará en modo básico. Para activar Stripe completo:
1. Descarga: https://github.com/stripe/stripe-php/releases
2. Extrae en: `vendor/stripe/stripe-php/`
3. Edita `api/create_checkout_session.php`
4. Descomenta las líneas marcadas con `/* ... */`

---

## 🎯 INTEGRACIÓN EN TU PÁGINA

### Opción A: Usar el JavaScript incluido

1. En tu HTML (preferences.html o donde tengas los planes):
```html
<!-- Incluir Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<!-- Incluir nuestro script -->
<script src="/js/payment-integration.js"></script>

<!-- Contenedor para los planes -->
<div id="membership-plans-container"></div>
```

Los planes se renderizarán automáticamente.

### Opción B: Botones manuales

```html
<script src="https://js.stripe.com/v3/"></script>
<script src="/js/payment-integration.js"></script>

<button onclick="upgradeToPremium('monthly')">
    Premium Mensual - 9.99€/mes
</button>

<button onclick="upgradeToPremium('yearly')">
    Premium Anual - 99.99€/año (2 meses gratis)
</button>
```

---

## 🧪 PROBAR CON TARJETA DE TEST

1. Inicia sesión en tu sitio
2. Haz clic en "Seleccionar Plan Premium"
3. Usa esta tarjeta de prueba:
   - **Número**: `4242 4242 4242 4242`
   - **Fecha**: Cualquier fecha futura (ej: 12/25)
   - **CVC**: Cualquier 3 dígitos (ej: 123)
   - **Código postal**: Cualquiera (ej: 12345)

4. Completa el pago
5. Deberías ser redirigido a `payment-success.html`
6. Tu membresía debería cambiar a "Premium"

---

## 📊 VERIFICAR QUE FUNCIONA

### En la base de datos:
```sql
-- Ver intenciones de upgrade
SELECT * FROM membership_upgrade_intents ORDER BY created_at DESC LIMIT 5;

-- Ver usuarios Premium
SELECT id, email, membership_type, membership_status 
FROM users 
WHERE membership_type = 'Premium';
```

### En Stripe Dashboard:
- Ir a: https://dashboard.stripe.com/test/payments
- Deberías ver el pago de prueba

---

## 🔄 PASAR A PRODUCCIÓN

Cuando estés listo para pagos reales:

1. **Activar cuenta Stripe**:
   - Completar información de negocio en Stripe
   - Verificar identidad

2. **Obtener claves de producción**:
   - Ir a: https://dashboard.stripe.com/apikeys
   - Copiar claves LIVE (pk_live_... y sk_live_...)

3. **Actualizar configuración**:
```php
// En api/stripe_config.php
define('STRIPE_MODE', 'live'); // Cambiar de 'test' a 'live'

define('STRIPE_LIVE_PUBLIC_KEY', 'pk_live_TU_CLAVE');
define('STRIPE_LIVE_SECRET_KEY', 'sk_live_TU_CLAVE');
```

4. **Configurar webhook de producción**:
   - Crear nuevo endpoint en modo LIVE
   - Actualizar STRIPE_WEBHOOK_SECRET con el nuevo secret

---

## ❓ PROBLEMAS COMUNES

### "No se crean las tablas"
- Verifica que tienes permisos en la base de datos
- Ejecuta el SQL línea por línea para ver dónde falla

### "Stripe keys no funcionan"
- Verifica que copiaste las claves completas
- Asegúrate de usar claves de TEST para pruebas

### "El webhook no se ejecuta"
- Verifica que la URL sea accesible públicamente
- Revisa los logs en Stripe Dashboard > Webhooks

### "No tengo Composer"
- El sistema funciona sin Stripe PHP
- Muestra la información del pago pero no redirige a Stripe
- Para producción, es recomendable instalarlo

---

## 📞 NECESITAS AYUDA?

1. **Revisa la documentación completa**: `SISTEMA_PASARELA_PAGOS.md`
2. **Ejecuta el diagnóstico**: `api/diagnostico_billing.php`
3. **Revisa los logs**: Stripe Dashboard > Webhooks > Ver eventos
4. **Contacto**: olgamarin@rutasrurales.io

---

## ✅ CHECKLIST FINAL

- [ ] SQL ejecutado correctamente
- [ ] Diagnóstico muestra todo en verde
- [ ] Claves de Stripe configuradas
- [ ] Webhook configurado
- [ ] Probado con tarjeta de test
- [ ] Pago de prueba aparece en Stripe Dashboard
- [ ] Membresía se actualiza a Premium
- [ ] Usuario redirigido a payment-success.html

**Si todos los checks están ✅, ¡tu pasarela está lista!**

---

**Tiempo total estimado**: 10-15 minutos
**Dificultad**: ⭐⭐ (Fácil-Medio)
**Última actualización**: 10/02/2026
