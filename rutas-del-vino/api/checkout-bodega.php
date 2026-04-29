<?php
/**
 * API: Crear sesión de pago Stripe para inscripción de bodega
 * POST /rutas-del-vino/api/checkout-bodega.php
 *
 * Body JSON: {
 *   email: string,          // Email de la bodega
 *   bodega_info: object,    // Datos de la bodega
 *   success_url: string,    // URL de éxito
 *   cancel_url: string      // URL de cancelación
 * }
 *
 * Precio fijo: 10€ IVA incluido (pago único)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://rutasrurales.io');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Cargar config de Stripe
require_once dirname(__DIR__, 2) . '/api/stripe_config.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $email       = isset($data['email']) ? filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL) : null;
    $bodegaInfo  = isset($data['bodega_info']) ? $data['bodega_info'] : [];
    $successUrl  = $data['success_url'] ?? 'https://rutasrurales.io/rutas-del-vino/gracias.php';
    $cancelUrl   = $data['cancel_url']  ?? 'https://rutasrurales.io/rutas-del-vino/';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email inválido o no proporcionado']);
        exit;
    }

    // Precio fijo: 10€ IVA incluido
    $totalAmount    = 10.00; // € IVA incluido
    $vatRate        = 21.0;
    $baseAmount     = round($totalAmount / (1 + $vatRate / 100), 4);
    $vatAmount      = round($totalAmount - $baseAmount, 4);
    $amountCentimos = (int)round($totalAmount * 100); // 1000 céntimos = 10€

    // Nombre de la bodega para el concepto
    $bodegaNombre = isset($bodegaInfo['nombre']) ? substr(trim($bodegaInfo['nombre']), 0, 80) : 'Bodega';
    $bodegaLocal  = isset($bodegaInfo['localidad']) ? trim($bodegaInfo['localidad']) : '';
    $bodegaDO     = isset($bodegaInfo['do']) ? trim($bodegaInfo['do']) : '';

    $productName  = "Las Rutas del Vino — Inscripción: {$bodegaNombre}";
    $productDesc  = "Alta permanente en el mapa de bodegas de rutasrurales.io";
    if ($bodegaLocal) $productDesc .= " · {$bodegaLocal}";
    if ($bodegaDO)    $productDesc .= " · D.O. {$bodegaDO}";
    $productDesc .= " · IVA 21% incluido";

    // Metadata para Stripe (máx 500 chars por campo)
    $metadata = [
        'type'             => 'bodega_inscription',
        'bodega_nombre'    => substr($bodegaNombre, 0, 100),
        'bodega_localidad' => substr($bodegaLocal, 0, 100),
        'bodega_provincia' => substr($bodegaInfo['provincia'] ?? '', 0, 100),
        'bodega_do'        => substr($bodegaDO, 0, 100),
        'bodega_telefono'  => substr($bodegaInfo['telefono'] ?? '', 0, 50),
        'bodega_web'       => substr($bodegaInfo['web'] ?? '', 0, 200),
        'total_iva_incl'   => (string)$totalAmount,
        'vat_rate'         => (string)$vatRate,
        'source'           => 'rutas-del-vino',
    ];

    // URL de éxito con session_id
    $successUrlFinal = $successUrl
        . (strpos($successUrl, '?') !== false ? '&' : '?')
        . 'session_id={CHECKOUT_SESSION_ID}'
        . '&bodega=' . urlencode($bodegaNombre);

    // Crear sesión de Stripe
    $lineItems = [[
        'name'        => $productName,
        'description' => $productDesc,
        'unit_amount' => $amountCentimos,
        'currency'    => 'eur',
        'quantity'    => 1,
    ]];

    $stripeSession = createStripeCheckoutSession(
        $email,
        $lineItems,
        'payment',          // pago único, no suscripción
        $successUrlFinal,
        $cancelUrl,
        $metadata
    );

    if (!$stripeSession || isset($stripeSession['error'])) {
        $errorMsg = isset($stripeSession['error']['message'])
            ? $stripeSession['error']['message']
            : 'Error al conectar con Stripe';
        error_log('checkout-bodega.php Stripe error: ' . $errorMsg);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear el pago: ' . $errorMsg
        ]);
        exit;
    }

    // Registrar la solicitud en un log interno (sin BD, archivo de texto)
    $logEntry = date('Y-m-d H:i:s') . ' | BODEGA_PAGO_PENDIENTE'
        . ' | ' . $email
        . ' | ' . $bodegaNombre
        . ' | Session: ' . $stripeSession['id']
        . PHP_EOL;

    $logDir  = dirname(__DIR__) . '/logs';
    $logFile = $logDir . '/inscripciones.log';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    echo json_encode([
        'success'      => true,
        'session_id'   => $stripeSession['id'],
        'checkout_url' => $stripeSession['url'],
        'amount'       => $totalAmount,
        'currency'     => 'EUR',
        'message'      => 'Sesión de pago creada correctamente'
    ]);

} catch (Exception $e) {
    error_log('checkout-bodega.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>
