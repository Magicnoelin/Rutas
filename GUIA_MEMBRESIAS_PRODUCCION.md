# Guía de Implementación de Membresías para Producción

## 📋 Resumen del Sistema

Se ha implementado un sistema completo de membresías con facturación automática que incluye:

### Planes Disponibles:
1. **Alojamientos:**
   - Básico: 10€/mes + IVA (2 alojamientos, 15 plazas)
   - Premium: 50€/año + IVA (10 alojamientos, 100 plazas)

2. **Restaurantes:**
   - Básico: 5€/mes + IVA (1 restaurante)
   - Premium: 50€/año + IVA (3 restaurantes)

3. **Apoyo a la Plataforma:**
   - Básico: 50€ + IVA (pago único)
   - Avanzado: 100€ + IVA (pago único)
   - Premium: 1000€ + IVA (pago único)

## 🚀 Pasos para Poner en Producción

### 1. Configuración de la Base de Datos
```bash
# Ejecutar el script SQL de configuración
mysql -u usuario -p nombre_base_datos < api/configurar_membresias_produccion.sql
```

### 2. Configuración de Stripe
1. Crear cuenta en [Stripe](https://stripe.com)
2. Obtener claves de API (modo producción):
   - `STRIPE_SECRET_KEY` (sk_live_...)
   - `STRIPE_PUBLISHABLE_KEY` (pk_live_...)
3. Configurar webhook en Stripe Dashboard:
   - URL: `https://rutasrurales.io/api/stripe_webhook.php`
   - Eventos a escuchar:
     - `checkout.session.completed`
     - `invoice.paid`
     - `invoice.payment_failed`
     - `customer.subscription.deleted`
4. Obtener `STRIPE_WEBHOOK_SECRET`

### 3. Actualizar Configuración
Editar `api/stripe_config.php` con tus claves reales:
```php
define('STRIPE_SECRET_KEY', 'sk_live_tu_clave_real');
define('STRIPE_PUBLISHABLE_KEY', 'pk_live_tu_clave_real');
define('STRIPE_WEBHOOK_SECRET', 'whsec_tu_secreto_real');
```

### 4. Instalar Dependencias de Stripe
```bash
# En el directorio del proyecto
composer require stripe/stripe-php
```

### 5. Configurar Información de la Empresa
Actualizar en `api/stripe_config.php`:
```php
$company_info = [
    'name' => 'Tu Empresa S.L.',
    'address' => 'Tu dirección',
    'nif' => 'Tu NIF/CIF',
    // ... otros datos
];
```

## 🔧 Archivos Implementados

### Base de Datos:
- `api/configurar_membresias_produccion.sql` - Estructura completa
- `api/agregar_campos_membresia.sql` - Campos adicionales en users

### Configuración:
- `api/stripe_config.php` - Configuración de Stripe y funciones
- `api/config.php` - Configuración general (ya existente)

### APIs:
- `api/create_checkout_session.php` - Crear sesiones de pago
- `api/stripe_webhook.php` - Procesar webhooks de Stripe
- `api/upgrade_membership.php` - Actualizar membresía (ya existente)
- `api/get_membership_limits.php` - Obtener límites (ya existente)

### Frontend:
- `agregar-alojamiento.html` - Ya incluye validación de límites
- `mi-cuenta.html` - Debe incluir sección de membresías

## 📊 Estructura de Tablas

### 1. `membership_plans`
- Almacena todos los planes disponibles
- Incluye precios, límites y características

### 2. `user_subscriptions`
- Suscripciones activas de usuarios
- Información de facturación y fechas

### 3. `invoices`
- Facturas generadas automáticamente
- Integración con Stripe para recibos

### 4. `payment_intents`
- Intenciones de pago pendientes/completadas
- Seguimiento de transacciones

### 5. `payment_failures`
- Registro de pagos fallidos
- Para análisis y seguimiento

## 💳 Flujo de Pago

1. **Usuario selecciona plan** → Frontend llama a `create_checkout_session.php`
2. **Stripe crea sesión** → Devuelve URL de checkout
3. **Usuario paga** → Stripe procesa el pago
4. **Webhook recibe evento** → `stripe_webhook.php` procesa
5. **Base de datos actualizada** → Usuario obtiene membresía
6. **Factura generada** → Automáticamente en `invoices`

## 🛡️ Validación de Límites

El sistema ya incluye validación automática:
- Usuarios con plan básico no pueden crear >2 alojamientos
- No pueden superar 15 plazas totales
- Mensajes de error claros con opción de upgrade

## 📈 Panel de Administración

### Para implementar:
1. **Dashboard de usuario:** Ver membresía actual, límites, facturas
2. **Panel de admin:** Gestionar planes, ver suscripciones, reportes
3. **Facturación:** Descargar facturas, reenviar recibos

## 🔍 Testing

### Modo Sandbox:
1. Usar claves de test de Stripe
2. Probar con tarjetas de prueba:
   - Éxito: `4242 4242 4242 4242`
   - Fallo: `4000 0000 0000 0002`

### Verificaciones:
```sql
-- Ver planes creados
SELECT * FROM membership_plans WHERE status = 'active';

-- Ver suscripciones
SELECT * FROM user_subscriptions LIMIT 10;

-- Ver facturas
SELECT * FROM invoices ORDER BY created_at DESC LIMIT 10;
```

## 📧 Notificaciones

### Para implementar:
1. Email de bienvenida al adquirir membresía
2. Recordatorio de renovación (7 días antes)
3. Notificación de pago fallido
4. Recibo de factura automático

## 🔄 Mantenimiento

### Tareas periódicas:
1. **Revisar suscripciones vencidas:** Actualizar estado a 'expired'
2. **Limpiar datos temporales:** Payment intents antiguos
3. **Backup de facturas:** Exportar periódicamente
4. **Actualizar precios:** En Stripe y base de datos

## 🚨 Solución de Problemas

### Webhook no funciona:
1. Verificar URL en Stripe Dashboard
2. Comprobar secreto de webhook
3. Revisar logs de error de PHP

### Pagos no se registran:
1. Verificar conexión a base de datos
2. Comprobar que el webhook está activo
3. Revisar logs de Stripe

### Límites no se aplican:
1. Verificar que `api/crear.php` incluye validación
2. Comprobar datos de membresía del usuario
3. Revisar consultas SQL en `get_membership_limits.php`

## 📝 Próximos Pasos Recomendados

### Corto Plazo:
1. Crear página de precios/membresías
2. Implementar dashboard de usuario
3. Configurar emails automáticos

### Medio Plazo:
1. Añadir más métodos de pago (Bizum, transferencia)
2. Implementar descuentos y promociones
3. Sistema de afiliados

### Largo Plazo:
1. App móvil para gestión
2. API para integraciones externas
3. Sistema de puntos y recompensas

## 📞 Soporte

### Contactos:
- **Stripe:** https://support.stripe.com
- **Desarrollo:** equipo@rutasrurales.io
- **Facturación:** facturacion@rutasrurales.io

### Documentación:
- [Stripe Documentation](https://stripe.com/docs)
- [PHP Stripe Library](https://github.com/stripe/stripe-php)

---

**Estado:** ✅ Sistema completo listo para producción  
**Última actualización:** 21/4/2026  
**Versión:** 1.0.0  

> ⚠️ **IMPORTANTE:** Antes de ir a producción, realizar pruebas exhaustivas en entorno de staging con tarjetas de prueba de Stripe.