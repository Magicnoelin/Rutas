<?php
/**
 * API: Crear sesión de pago Stripe para inscripción de bodega
 * POST /rutas-del-vino/api/checkout-bodega.php
 * Guarda la bodega en la tabla `users` con user_type='alojamiento' (pending)
 *
 * Precio fijo: 10€ IVA incluido (pago único)
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once dirname(__DIR__, 2) . '/api/stripe_config.php';
require_once dirname(__DIR__, 2) . '/api/config.php';

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

    // Extraer campos de la bodega
    $bodegaNombre  = isset($bodegaInfo['nombre'])   ? trim($bodegaInfo['nombre'])   : 'Bodega';
    $bodegaContact = isset($bodegaInfo['contacto']) ? trim($bodegaInfo['contacto']) : '';
    $bodegaTel     = isset($bodegaInfo['telefono']) ? trim($bodegaInfo['telefono']) : '';
    $bodegaDO      = isset($bodegaInfo['do'])       ? trim($bodegaInfo['do'])       : '';
    $bodegaWeb     = isset($bodegaInfo['web'])      ? trim($bodegaInfo['web'])      : '';

    // Descripción compacta para el campo business_description
    $bodegaDesc  = "Bodega: {$bodegaNombre}";
    if ($bodegaDO)  $bodegaDesc .= " | D.O.: {$bodegaDO}";
    if ($bodegaWeb) $bodegaDesc .= " | Web: {$bodegaWeb}";
    if ($bodegaTel) $bodegaDesc .= " | Tel: {$bodegaTel}";
    $bodegaDesc .= " | Pago pendiente 10€ IVA incluido";

    // Precio fijo: 10€ IVA incluido
    $totalAmount    = 10.00;
    $amountCentimos = 1000; // céntimos

    // ─── GUARDAR EN TABLA USERS ─────────────────────────────
    $userId = null;
    try {
        $pdo = getDBConnection();

        // Separar nombre de contacto en first_name / last_name
        $nameParts = explode(' ', $bodegaContact, 2);
        $firstName = $nameParts[0] ?? $bodegaContact;
        $lastName  = $nameParts[1] ?? '';

        // Comprobar si ya existe el email
        $stmtCheck = $pdo->prepare("SELECT id, user_type FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        $existingUser = $stmtCheck->fetch();

        if ($existingUser) {
            // Actualizar datos si ya existe
            $userId = $existingUser['id'];
            $stmtUpd = $pdo->prepare("
                UPDATE users SET
                    business_name        = ?,
                    business_description = ?,
                    user_type            = 'alojamiento',
                    verification_status  = 'pending',
                    updated_at           = NOW()
                WHERE id = ?
            ");
            $stmtUpd->execute([
                substr($bodegaNombre, 0, 255),
                substr($bodegaDesc,   0, 1000),
                $userId
            ]);
        } else {
            // Crear nuevo usuario bodega
            // Password aleatorio (no podrá logarse hasta verificación)
            $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

            $stmtIns = $pdo->prepare("
                INSERT INTO users
                    (email, first_name, last_name, password,
                     user_type, business_name, business_description,
                     verification_status, subscription_level, terms_accepted,
                     created_at)
                VALUES
                    (?, ?, ?, ?,
                     'alojamiento', ?, ?,
                     'pending', 'basic', 1,
                     NOW())
            ");
            $stmtIns->execute([
                $email,
                substr($firstName,     0, 100),
                substr($lastName,      0, 100),
                $randomPass,
                substr($bodegaNombre,  0, 255),
                substr($bodegaDesc,    0, 1000)
            ]);
            $userId = (int)$pdo->lastInsertId();
        }
    } catch (Exception $dbEx) {
        // No bloqueamos el flujo de pago si falla la BD
        error_log('checkout-bodega.php DB error: ' . $dbEx->getMessage());
    }

    // ─── CREAR SESIÓN STRIPE ────────────────────────────────
    $productName = "Las Rutas del Vino — Inscripción: " . substr($bodegaNombre, 0, 80);
    $productDesc = "Alta permanente en el mapa de bodegas · rutasrurales.io · IVA 21% incluido";
    if ($bodegaDO) $productDesc .= " · D.O. {$bodegaDO}";

    $metadata = [
        'type'            => 'bodega_inscription',
        'bodega_nombre'   => substr($bodegaNombre,  0, 100),
        'bodega_do'       => substr($bodegaDO,       0, 100),
        'bodega_telefono' => substr($bodegaTel,      0, 50),
        'bodega_web'      => substr($bodegaWeb,      0, 200),
        'user_id'         => $userId ? (string)$userId : 'nuevo',
        'total_iva_incl'  => '10.00',
        'source'          => 'rutas-del-vino',
    ];

    $successUrlFinal = $successUrl
        . (strpos($successUrl, '?') !== false ? '&' : '?')
        . 'session_id={CHECKOUT_SESSION_ID}'
        . '&bodega=' . urlencode($bodegaNombre);

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
        'payment',
        $successUrlFinal,
        $cancelUrl,
        $metadata
    );

    if (!$stripeSession || isset($stripeSession['error'])) {
        $errorMsg = $stripeSession['error']['message'] ?? 'Error al conectar con Stripe';
        error_log('checkout-bodega.php Stripe error: ' . $errorMsg);
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Error al crear el pago: ' . $errorMsg]);
        exit;
    }

    // ─── LOG INTERNO ────────────────────────────────────────
    $logDir  = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logLine = date('Y-m-d H:i:s')
        . ' | PENDIENTE | ' . $email
        . ' | ' . $bodegaNombre
        . ' | user_id=' . ($userId ?: 'err')
        . ' | session=' . $stripeSession['id']
        . PHP_EOL;
    @file_put_contents($logDir . '/inscripciones.log', $logLine, FILE_APPEND | LOCK_EX);

    echo json_encode([
        'success'      => true,
        'session_id'   => $stripeSession['id'],
        'checkout_url' => $stripeSession['url'],
        'user_id'      => $userId,
        'amount'       => 10.00,
        'currency'     => 'EUR',
        'message'      => 'Sesión de pago creada correctamente'
    ]);

} catch (Exception $e) {
    error_log('checkout-bodega.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
