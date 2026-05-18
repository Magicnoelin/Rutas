<?php
/**
 * API: Crear sesión de pago Stripe para inscripción de Ayuntamiento
 * POST /ayuntamientos/api/checkout-ayuntamiento.php
 *
 * Planes (oferta lanzamiento Mayo 2026):
 *   basico     → 60€  IVA incl. (precio regular 120€)
 *   cultural   → 80€  IVA incl. (precio regular 160€)
 *   territorio → 100€ IVA incl. (precio regular 200€)
 *
 * Guarda en tabla `users` con user_type='promotor_eventos' + verification_status='pending'
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']); exit;
}

// ─── Cargar configuraciones ────────────────────────────────────
require_once dirname(__DIR__, 2) . '/api/stripe_config.php';

// config.php es opcional (BD). Si falla, seguimos sin BD
$dbAvailable = false;
try {
    require_once dirname(__DIR__, 2) . '/api/config.php';
    $dbAvailable = true;
} catch (Exception $configEx) {
    error_log('checkout-ayuntamiento.php: config.php no cargado: ' . $configEx->getMessage());
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || !is_array($data)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']); exit;
    }

    $plan          = isset($data['plan']) ? strtolower(trim($data['plan'])) : 'basico';
    $email         = isset($data['email']) ? filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL) : null;
    $municipioInfo = $data['municipio_info'] ?? [];
    $successUrl    = $data['success_url'] ?? 'https://rutasrurales.io/ayuntamientos/gracias.php';
    $cancelUrl     = $data['cancel_url']  ?? 'https://rutasrurales.io/ayuntamientos/#inscribir';

    // Validar email
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email inválido']); exit;
    }

    // ─── Planes válidos — precios en CÉNTIMOS ─────────────────
    // Los precios ya incluyen IVA (son los de oferta lanzamiento)
    $planesValidos = [
        'basico'     => [
            'label'    => 'Plan Básico',
            'precio'   => 60,
            'centimos' => 6000,
            'desc'     => '5 lugares de interés · Mensajería turistas · 5 idiomas · IVA incluido',
        ],
        'cultural'   => [
            'label'    => 'Plan Cultural',
            'precio'   => 80,
            'centimos' => 8000,
            'desc'     => '5 lugares + 5 eventos culturales · Mensajería turistas · 5 idiomas · IVA incluido',
        ],
        'territorio' => [
            'label'    => 'Plan Territorio',
            'precio'   => 100,
            'centimos' => 10000,
            'desc'     => '5 lugares + 5 eventos + 5 actividades · Mensajería turistas · 5 idiomas · IVA incluido',
        ],
    ];

    if (!array_key_exists($plan, $planesValidos)) $plan = 'basico';
    $planData = $planesValidos[$plan];

    // ─── Extraer datos del municipio ──────────────────────────
    $municipio   = isset($municipioInfo['municipio'])   ? trim($municipioInfo['municipio'])   : 'Municipio';
    $provincia   = isset($municipioInfo['provincia'])   ? trim($municipioInfo['provincia'])   : '';
    $contacto    = isset($municipioInfo['contacto'])    ? trim($municipioInfo['contacto'])    : '';
    $cargo       = isset($municipioInfo['cargo'])       ? trim($municipioInfo['cargo'])       : '';
    $telefono    = isset($municipioInfo['telefono'])    ? trim($municipioInfo['telefono'])    : '';
    $web         = isset($municipioInfo['web'])         ? trim($municipioInfo['web'])         : '';
    $descripcion = isset($municipioInfo['descripcion']) ? trim($municipioInfo['descripcion']) : '';

    $bizDesc  = "Municipio: {$municipio}";
    if ($provincia)   $bizDesc .= " | Provincia: {$provincia}";
    if ($cargo)       $bizDesc .= " | Cargo: {$cargo}";
    if ($web)         $bizDesc .= " | Web: {$web}";
    if ($telefono)    $bizDesc .= " | Tel: {$telefono}";
    if ($descripcion) $bizDesc .= " | Notas: " . substr($descripcion, 0, 200);
    $bizDesc .= " | Plan: {$planData['label']} {$planData['precio']}€ IVA incl. | OFERTA LANZAMIENTO MAYO 2026";

    // ─── Guardar en BD (no bloqueante) ───────────────────────
    $userId = null;
    if ($dbAvailable) {
        try {
            $pdo = getDBConnection();
            $nameParts = explode(' ', $contacto, 2);
            $firstName = $nameParts[0] ?? $contacto;
            $lastName  = $nameParts[1] ?? '';

            $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
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
            // Seguimos con el pago aunque falle la BD
        }
    }

    // ─── Crear sesión de Stripe Checkout ─────────────────────
    $productName = "Rutas Rurales · {$planData['label']} — Ayuntamiento de {$municipio}";
    $productDesc = $planData['desc'];
    if ($provincia) $productDesc .= " · {$provincia}";

    $metadata = [
        'type'        => 'ayuntamiento_inscripcion',
        'plan'        => $plan,
        'plan_label'  => $planData['label'],
        'municipio'   => substr($municipio,  0, 100),
        'provincia'   => substr($provincia,  0, 100),
        'contacto'    => substr($contacto,   0, 100),
        'cargo'       => substr($cargo,      0, 100),
        'telefono'    => substr($telefono,   0, 50),
        'web'         => substr($web,        0, 200),
        'user_id'     => $userId ? (string)$userId : 'nuevo',
        'total_iva'   => (string)$planData['precio'],
        'oferta'      => 'lanzamiento_mayo_2026',
        'source'      => 'ayuntamientos',
    ];

    $successUrlFinal = $successUrl
        . (strpos($successUrl, '?') !== false ? '&' : '?')
        . 'session_id={CHECKOUT_SESSION_ID}'
        . '&plan=' . urlencode($plan)
        . '&municipio=' . urlencode($municipio);

    $lineItems = [[
        'name'        => $productName,
        'description' => $productDesc,
        'unit_amount' => $planData['centimos'],  // ya incluye IVA
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
        $errMsg = isset($stripeSession['error']['message'])
            ? $stripeSession['error']['message']
            : 'Error al comunicarse con Stripe';
        error_log('checkout-ayuntamiento.php Stripe error: ' . $errMsg);
        http_response_code(502);
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear el pago: ' . $errMsg
        ]);
        exit;
    }

    // ─── Log de inscripción ───────────────────────────────────
    $logDir = dirname(__DIR__) . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logLine = date('Y-m-d H:i:s')
        . " | PENDIENTE | {$email} | Ayto. {$municipio} ({$provincia})"
        . " | {$planData['label']} {$planData['precio']}€"
        . " | user_id=" . ($userId ?: 'no-db')
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
        'message'      => 'Sesión de pago creada. Redirigiendo a Stripe...'
    ]);

} catch (Exception $e) {
    error_log('checkout-ayuntamiento.php exception: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
