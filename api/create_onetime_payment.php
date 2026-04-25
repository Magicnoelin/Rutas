<?php
/**
 * API: Crear pago único (café, publicación negocio, apoyo, etc.)
 * POST /api/create_onetime_payment.php
 *
 * Body: {
 *   concept_code: string,   // Ej: 'CAFE_1', 'NEGOCIO_5', 'APOYO_10'
 *   custom_amount: float,   // Opcional: importe libre (solo para APOYO_LIBRE)
 *   success_url: string,    // Opcional
 *   cancel_url: string      // Opcional
 * }
 *
 * No requiere login para donaciones/café.
 * Sí requiere login para publicación de negocio.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
require_once 'stripe_config.php';

header('Content-Type: application/json; charset=utf-8');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || empty($data['concept_code'])) {
        jsonError('Se requiere concept_code', 400);
    }

    $conceptCode  = sanitizeInput($data['concept_code']);
    $customAmount = isset($data['custom_amount']) ? (float)$data['custom_amount'] : null;
    $userId       = $_SESSION['user_id'] ?? null;

    $successUrl = $data['success_url'] ?? 'https://rutasrurales.io/apoyar.html?payment=success';
    $cancelUrl  = $data['cancel_url']  ?? 'https://rutasrurales.io/apoyar.html?payment=canceled';

    $pdo = getDBConnection();

    // Obtener el concepto de billing_concepts
    $stmt = $pdo->prepare("
        SELECT * FROM billing_concepts
        WHERE code = ? AND billing_type = 'one_time' AND active = 1
    ");
    $stmt->execute([$conceptCode]);
    $concept = $stmt->fetch();

    if (!$concept) {
        jsonError('Concepto de pago no encontrado: ' . $conceptCode, 404);
    }

    // Determinar importe
    $amount = $customAmount ?? (float)$concept['amount'];

    // Validar mínimo de Stripe (0.50€)
    if ($amount < 0.50) {
        jsonError('El importe mínimo es 0.50€', 400);
    }

    // Calcular IVA
    $vatCalc     = calculateVAT($amount);
    $totalAmount = $vatCalc['total'];
    $vatAmount   = $vatCalc['vat_amount'];

    // Datos del usuario (si está logueado)
    $userEmail = null;
    if ($userId) {
        $stmtU = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE id = ?");
        $stmtU->execute([$userId]);
        $user = $stmtU->fetch();
        $userEmail = $user['email'] ?? null;
    }

    // Metadata para Stripe
    $metadata = [
        'concept_code'  => $conceptCode,
        'concept_name'  => $concept['concept_name'],
        'user_id'       => $userId ? (string)$userId : 'guest',
        'amount_base'   => (string)$amount,
        'vat_rate'      => (string)$vatCalc['vat_rate'],
        'total'         => (string)$totalAmount,
        'payment_type'  => 'one_time',
    ];

    // Crear sesión de Stripe (modo 'payment', no 'subscription')
    $lineItems = [[
        'name'        => $concept['concept_name'],
        'description' => $concept['description'],
        'unit_amount' => (int)round($totalAmount * 100), // céntimos, IVA incluido
        'currency'    => 'eur',
        'quantity'    => 1,
    ]];

    $successUrlWithSession = $successUrl
        . (strpos($successUrl, '?') !== false ? '&' : '?')
        . 'session_id={CHECKOUT_SESSION_ID}&concept=' . urlencode($conceptCode);

    $stripeSession = createStripeCheckoutSession(
        $userEmail,
        $lineItems,
        'payment',   // pago único, no suscripción
        $successUrlWithSession,
        $cancelUrl,
        $metadata
    );

    if (!$stripeSession || isset($stripeSession['error'])) {
        $errorMsg = $stripeSession['error']['message'] ?? 'Error al conectar con Stripe';
        error_log('create_onetime_payment.php Stripe error: ' . $errorMsg);
        jsonError('Error al crear el pago: ' . $errorMsg, 500);
    }

    $sessionId   = $stripeSession['id'];
    $checkoutUrl = $stripeSession['url'];

    // Registrar en payment_intents (plan_id = NULL para pagos únicos)
    try {
        $stmtIntent = $pdo->prepare("
            INSERT INTO payment_intents
                (user_id, plan_id, stripe_session_id, stripe_price_id,
                 amount, vat_amount, total_amount, billing_cycle, status, metadata)
            VALUES (?, NULL, ?, ?,
                    ?, ?, ?, 'monthly', 'pending', ?)
        ");
        $stmtIntent->execute([
            $userId ?? 0,
            $sessionId,
            'price_onetime_' . $conceptCode,
            $amount,
            $vatAmount,
            $totalAmount,
            json_encode($metadata),
        ]);
    } catch (PDOException $e) {
        error_log('create_onetime_payment.php: No se pudo registrar payment_intent: ' . $e->getMessage());
        // No bloquear el flujo
    }

    jsonSuccess([
        'session_id'    => $sessionId,
        'checkout_url'  => $checkoutUrl,
        'concept_code'  => $conceptCode,
        'concept_name'  => $concept['concept_name'],
        'amount'        => $amount,
        'vat_amount'    => $vatAmount,
        'total_amount'  => $totalAmount,
        'currency'      => 'EUR',
    ], 'Sesión de pago creada');

} catch (PDOException $e) {
    error_log('create_onetime_payment.php PDO: ' . $e->getMessage());
    jsonError('Error de base de datos', 500);
} catch (Exception $e) {
    error_log('create_onetime_payment.php: ' . $e->getMessage());
    jsonError('Error: ' . $e->getMessage(), 500);
}
?>
