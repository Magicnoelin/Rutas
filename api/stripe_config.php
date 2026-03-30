<?php
/**
 * Configuración de Stripe
 * RutasRurales.io
 */

// Claves de Stripe (IMPORTANTE: Cambiar por las claves reales)
// Obtener las claves en: https://dashboard.stripe.com/apikeys

// MODO TEST (para desarrollo)
define('STRIPE_TEST_PUBLIC_KEY', 'pk_test_51Sz9bf4avIGj2oLAQlGgkmFgAvH9nq7GBjKDjSmkT6GJzpkOT8QgXQxIIxMqnA18S3lUrVinz4w7XCvyO94h98SX00k8qpGTyB');
define('STRIPE_TEST_SECRET_KEY', 'sk_test_51Sz9bf4avIGj2oLASGJVt4sSlJgVBEri5sQWSIQYo8PpFWTnCV7DueogA7ffSF61vPcYPnwRZMAy1H4FLFuk3PgW00gfmvxXnK');

// MODO PRODUCCIÓN (para el servidor en vivo)
define('STRIPE_LIVE_PUBLIC_KEY', 'pk_live_TU_CLAVE_PUBLICA_LIVE');
define('STRIPE_LIVE_SECRET_KEY', 'sk_live_TU_CLAVE_SECRETA_LIVE');

// Determinar si estamos en modo test o producción
define('STRIPE_MODE', 'test'); // Cambiar a 'live' para producción

// Seleccionar las claves según el modo
if (STRIPE_MODE === 'live') {
    define('STRIPE_PUBLIC_KEY', STRIPE_LIVE_PUBLIC_KEY);
    define('STRIPE_SECRET_KEY', STRIPE_LIVE_SECRET_KEY);
} else {
    define('STRIPE_PUBLIC_KEY', STRIPE_TEST_PUBLIC_KEY);
    define('STRIPE_SECRET_KEY', STRIPE_TEST_SECRET_KEY);
}

// URL de retorno después del pago
define('STRIPE_SUCCESS_URL', 'https://rutasrurales.io/payment-success.html');
define('STRIPE_CANCEL_URL', 'https://rutasrurales.io/payment-cancel.html');

// Webhook secret (para verificar eventos de Stripe)
define('STRIPE_WEBHOOK_SECRET', 'whsec_TU_WEBHOOK_SECRET');

/**
 * Función para obtener la clave pública de Stripe
 */
function getStripePublicKey() {
    return STRIPE_PUBLIC_KEY;
}

/**
 * Función para obtener la clave secreta de Stripe
 */
function getStripeSecretKey() {
    return STRIPE_SECRET_KEY;
}

/**
 * Función para verificar si estamos en modo test
 */
function isStripeTestMode() {
    return STRIPE_MODE === 'test';
}
