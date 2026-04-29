<?php
/**
 * API: Crear sesión de pago Stripe para inscripción de Ayuntamiento
 * POST /ayuntamientos/api/checkout-ayuntamiento.php
 * Planes: básico 19€ | cultural 39€
 * Guarda en tabla `users` con user_type='promotor_eventos' + verification_status='pending'
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

require_once dirname(__DIR__, 2) . '/api/stripe_config.php';
require_once dirname(__DIR__, 2) . '/api/config.php';

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
    }

    $plan          = isset($data['plan']) ? trim($data['plan']) : 'basico';
    $email         = isset($data['email']) ? filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL) : null;
    $municipioInfo = $data['municipio_info'] ?? [];
    $successUrl    = $data['success_url'] ?? 'https://rutasrurales.io/ayuntamientos/gracias.php';
    $cancelUrl     = $data['cancel_url']  ?? 'https://rutasrurales.io/ayuntamientos/';
    $precioEnv     = isset($data['precio']) ? (int)$data['precio'] : 0;

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email inválido']); exit;
    }

    // Validar y fijar precio según plan (no nos fiamos del cliente)
    $planesValidos = [
        'basico'   => ['precio' => 19, 'label' => 'Plan Básico',   'centimos' => 1900],
        'cultural' => ['precio' => 39, 'label' => 'Plan Cultural', 'centimos' => 3900],
    ];
    if (!array_key_exists($plan, $planesValidos)) $plan = 'basico';
    $planData = $planesValidos[$plan];

    // Extraer datos del municipio
    $municipio  = isset($municipioInfo['municipio'])   ? trim($municipioInfo['municipio'])   : 'Municipio';
    $provincia  = isset($municipioInfo['provincia'])   ? trim($municipioInfo['provincia'])   : '';
    $contacto   = isset($municipioInfo['contacto'])    ? trim($municipioInfo['contacto'])    : '';
    $cargo      = isset($municipioInfo['cargo'])       ? trim($municipioInfo['cargo'])       : '';
    $telefono   = isset($municipioInfo['telefono'])    ? trim($municipioInfo['telefono'])    : '';
    $web        = isset($municipioInfo['web'])         ? trim($municipioInfo['web'])         : '';
    $descripcion= isset($municipioInfo['descripcion']) ? trim($municipioInfo['descripcion']) : '';

    // Descripción para business_description
    $bizDesc  = "Municipio: {$municipio}";
    if ($provincia)   $bizDesc .= " | Provincia: {$provincia}";
    if ($cargo)       $bizDesc .= " | Cargo: {$cargo}";
    if ($web)         $bizDesc .= " | Web: {$web}";
    if ($telefono)    $bizDesc .= " | Tel: {$telefono}";
    if ($descripcion) $bizDesc .= " | Notas: " . substr($descripcion, 0, 200);
    $bizDesc .= " | Plan: {$planData['label']} {$planData['precio']}€ IVA incl. | Estado: pago_pendiente";

    // ─── GUARDAR EN TABLA USERS ────────────────────────────
    $userId = null;
    try {
        $pdo = getDBConnection();
        $nameParts = explode(' ', $contacto, 2);
        $firstName = $nameParts[0] ?? $contacto;
        $lastName  = $nameParts[1] ?? '';

        // ¿Existe ya este email?
        $stmtCheck = $pdo->prepare("SELECT id, user_type FROM users WHERE email = ? LIMIT 1");
        $stmtCheck->execute([$email]);
        $existing = $stmtCheck->fetch();

        if ($existing) {
            $userId = $existing['id'];
            $stmtUpd = $pdo->prepare("
                UPDATE users SET
                    business_name        = ?,
                    business_description = ?,
                    user_type            = 'promotor_eventos',
                    verification_status  = 'pending',
                    updated_at           = NOW()
                WHERE id = ?
            ");
            $stmtUpd->execute([
                substr("Ayuntamiento de {$municipio}", 0, 255),
                substr($bizDesc, 0, 1000),
                $userId
            ]);
        } else {
            $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
            $stmtIns = $pdo->prepare("
                INSERT INTO users
                    (email, first_name, last_name, password,
                     user_type, business_name, business_description,
                     verification_status, subscription_level, terms_accepted, created_at)
                VALUES
                    (?, ?, ?, ?,
                     'promotor_eventos', ?, ?,
                     'pending', 'basic', 1, NOW())
            ");
            $stmtIns->execute([
                $email,
                substr($firstName, 0, 100),
                substr($lastName,  0, 100),
                $randomPass,
                substr("Ayuntamiento de {$municipio}", 0, 255),
                substr($bizDesc, 0, 1000),
            ]);
            $userId = (int)$pdo->lastInsertId();
        }
    } catch (Exception $dbEx) {
        error_log('checkout-ayuntamiento.php DB error: ' . $dbEx->getMessage());
        // No bloqueamos el pago por error de BD
    }

    // ─── CREAR SESIÓN STRIPE ───────────────────────────────
    $productName = "Rutas Rurales · {$planData['label']} — Ayuntamiento de {$municipio}";
    $productDesc = "Inscripción municipio en mapa turístico · {$planData['precio']}€ IVA incluido · rutasrurales.io";
    if ($provincia) $productDesc .= " · {$provincia}";

    $metadata = [
        'type'       => 'ayuntamiento_inscripcion',
        'plan'       => $plan,
        'plan_label' => $planData['label'],
        'municipio'  => substr($municipio,  0, 100),
        'provincia'  => substr($provincia,  0, 100),
        'telefono'   => substr($telefono,   0, 50),
        'web'        => substr($web,        0, 200),
        'user_id'    => $userId ? (string)$userId : 'nuevo',
        'total_iva'  => (string)$planData['precio'],
        'source'     => 'ayuntamientos',
    ];

    $successUrlFinal = $successUrl
        . (strpos($successUrl, '?') !== false ? '&' : '?')
        . 'session_id={CHECKOUT_SESSION_ID}'
        . '&plan=' . urlencode($plan)
        . '&municipio=' . urlencode($municipio);

    $lineItems = [[
        'name'        => $productName,
        'description' => $productDesc,
        'unit_amount' => $planData['centimos'],
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
        $errMsg = $stripeSession['error']['message'] ?? 'Error Stripe desconocido';
        error_log('checkout-ayuntamiento.php Stripe error: ' . $errMsg);
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Error al crear el pago: ' . $errMsg]); exit;
    }

    // ─── LOG ───────────────────────────────────────────────
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logLine = date('Y-m-d H:i:s')
        . " | PENDIENTE | {$email} | Ayto. {$municipio} ({$provincia}) | {$planData['label']} {$planData['precio']}€"
        . " | user_id=" . ($userId ?: 'err')
        . " | session=" . $stripeSession['id']
        . PHP_EOL;
    @file_put_contents($logDir . '/inscripciones-aytos.log', $logLine, FILE_APPEND | LOCK_EX);

    echo json_encode([
        'success'      => true,
        'session_id'   => $stripeSession['id'],
        'checkout_url' => $stripeSession['url'],
        'user_id'      => $userId,
        'plan'         => $plan,
        'amount'       => $planData['precio'],
        'currency'     => 'EUR',
        'message'      => 'Sesión de pago creada correctamente'
    ]);

} catch (Exception $e) {
    error_log('checkout-ayuntamiento.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>
