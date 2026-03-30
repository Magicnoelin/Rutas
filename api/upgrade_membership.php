<?php
/**
 * API: Actualizar Membresía
 * POST /api/upgrade_membership.php
 * Body: {
 *   plan_id: int,
 *   billing_cycle: 'monthly'|'yearly',
 *   payment_method_id?: string (Stripe payment method ID)
 * }
 */

session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión para actualizar tu membresía', 401);
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || empty($data['plan_id']) || empty($data['billing_cycle'])) {
        jsonError('Datos incompletos. Se requieren plan_id y billing_cycle', 400);
    }

    $userId = $_SESSION['user_id'];
    $planId = (int)$data['plan_id'];
    $billingCycle = sanitizeInput($data['billing_cycle']);
    $paymentMethodId = isset($data['payment_method_id']) ? sanitizeInput($data['payment_method_id']) : null;

    // Validar billing_cycle
    if (!in_array($billingCycle, ['monthly', 'yearly'])) {
        jsonError('Ciclo de facturación inválido. Usa "monthly" o "yearly"', 400);
    }

    $pdo = getDBConnection();

    // Obtener información del usuario actual
    $stmtUser = $pdo->prepare("
        SELECT id, email, first_name, last_name, membership_type, membership_status
        FROM users
        WHERE id = ?
    ");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        jsonError('Usuario no encontrado', 404);
    }

    // Obtener información del plan seleccionado
    $stmtPlan = $pdo->prepare("
        SELECT id, name, price_monthly, price_yearly,
               stripe_product_id, stripe_monthly_price_id, stripe_yearly_price_id
        FROM membership_plans
        WHERE id = ?
    ");
    $stmtPlan->execute([$planId]);
    $plan = $stmtPlan->fetch();

    if (!$plan) {
        jsonError('Plan de membresía no encontrado', 404);
    }

    // Calcular precio según el ciclo de facturación
    $price = ($billingCycle === 'monthly') ? $plan['price_monthly'] : $plan['price_yearly'];
    $stripePriceId = ($billingCycle === 'monthly') ? $plan['stripe_monthly_price_id'] : $plan['stripe_yearly_price_id'];

    // Verificar si el plan es gratuito (no requiere pago)
    if ($price <= 0) {
        // Actualizar membresía directamente
        $stmtUpdate = $pdo->prepare("
            UPDATE users
            SET membership_type = ?, membership_status = 'active', membership_updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmtUpdate->execute([$plan['name'], $userId]);

        // Crear registro de suscripción
        $stmtSub = $pdo->prepare("
            INSERT INTO user_subscriptions
            (user_id, plan_id, plan_name, billing_cycle, price, currency, status, created_at, valid_until)
            VALUES (?, ?, ?, ?, ?, 'EUR', 'active', CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 YEAR))
        ");
        $stmtSub->execute([$userId, $planId, $plan['name'], $billingCycle, $price]);

        jsonSuccess([
            'message' => 'Membresía actualizada correctamente',
            'membership' => $plan['name'],
            'billing_cycle' => $billingCycle,
            'price' => $price,
            'currency' => 'EUR',
            'status' => 'active',
            'requires_payment' => false
        ], 'Membresía actualizada con éxito');
    }

    // Para planes de pago, necesitaríamos integrar con Stripe o otro procesador de pagos
    // Esto es un placeholder para la integración real

    // Crear un registro de intención de upgrade
    $stmtIntent = $pdo->prepare("
        INSERT INTO membership_upgrade_intents
        (user_id, plan_id, plan_name, billing_cycle, price, currency, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'EUR', 'pending', CURRENT_TIMESTAMP)
    ");
    $stmtIntent->execute([$userId, $planId, $plan['name'], $billingCycle, $price]);

    $intentId = $pdo->lastInsertId();

    // Devolver información para que el frontend llame a create_checkout_session
    jsonSuccess([
        'message' => 'Intención de upgrade creada. Redirigiendo al proceso de pago...',
        'membership' => $plan['name'],
        'billing_cycle' => $billingCycle,
        'price' => $price,
        'currency' => 'EUR',
        'requires_payment' => true,
        'payment_intent_id' => $intentId,
        'plan_id' => $planId,
        'next_step' => 'create_checkout_session',
        // El frontend debe hacer una nueva llamada a create_checkout_session.php
        'checkout_url' => '/api/create_checkout_session.php'
    ], 'Intención de upgrade creada');

} catch (PDOException $e) {
    error_log('upgrade_membership.php Error: ' . $e->getMessage());
    jsonError('Error al actualizar la membresía: ' . $e->getMessage(), 500);
}