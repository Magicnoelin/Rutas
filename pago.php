<?php
// 1. Cargamos la librería manualmente usando el archivo init.php
require_once('stripe-php/init.php');

// 2. Configuramos la llave (usa tu llave de prueba 'sk_test_...')
$stripe = new \Stripe\StripeClient('TU_LLAVE_SECRETA_AQUI');

try {
    // 3. Prueba rápida: vamos a listar tus productos de Stripe
    // Si esto no da error, ¡está bien instalado!
    $productos = $stripe->products->all(['limit' => 1]);
    echo "¡Instalación exitosa! La librería de Stripe está respondiendo correctamente.";
} catch (Exception $e) {
    echo "Error en la configuración: " . $e->getMessage();
}
?>