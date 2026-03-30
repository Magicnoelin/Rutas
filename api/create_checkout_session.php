<?php
/**
 * API: Crear Sesión de Checkout de Stripe
 * POST /api/create_checkout_session.php
 */

session_start();
require_once 'config.php';
require_once 'stripe_config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión para continuar', 401);
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

    // Validar billing_cycle
    if (!in_array($billingCycle, ['monthly', 'yearly'])) {
        jsonError('Ciclo de facturación inválido. Usa "monthly" o "yearly"', 400);
    }

    $pdo = getDBConnection();

    // Obtener información del usuario
    $stmtUser = $pdo->prepare("
        SELECT id, email, first_name, last_name, membership_type
        FROM users
        WHERE id = ?
    ");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        jsonError('Usuario no encontrado', 404);
    }

    // Obtener información del plan
    $stmtPlan = $pdo->prepare("
        SELECT id, name, price_monthly, price_yearly, description,
                stripe_product_id, stripe_monthly_price_id, stripe_yearly_price_id
        FROM membership_plans
        WHERE id = ? AND is_active = TRUE
    ");
    $stmtPlan->execute([$planId]);
    $plan = $stmtPlan->fetch();

    if (!$plan) {
        jsonError('Plan de membresía no encontrado', 404);
    }

    // Calcular precio y obtener ID de Stripe
    $price = ($billingCycle === 'monthly') ? $plan['price_monthly'] : $plan['price_yearly'];
    $stripePriceId = ($billingCycle === 'monthly') ? $plan['stripe_monthly_price_id'] : $plan['stripe_yearly_price_id'];

    // Verificar si el plan es gratuito
    if ($price <= 0) {
        jsonError('Este plan no requiere pago', 400);
    }

    // Crear intención de upgrade en base de datos
    $stmtIntent = $pdo->prepare("
        INSERT INTO membership_upgrade_intents
        (user_id, plan_id, plan_name, billing_cycle, price, currency, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'EUR', 'pending', CURRENT_TIMESTAMP)
    ");
    $stmtIntent->execute([$userId, $planId, $plan['name'], $billingCycle, $price]);
    $intentId = $pdo->lastInsertId();

    // --- INTEGRACIÓN CON STRIPE ---
    
    // Ajustamos la ruta al archivo init.php que subiste manualmente
    // Si el archivo está en /public_html/api/ y la librería en /public_html/stripe-php/, usa '../stripe-php/init.php'
    require_once '../stripe-php/init.php';
    
    \Stripe\Stripe::setApiKey(getStripeSecretKey());

    // Crear sesión de Stripe Checkout
    $sessionData = [
        'payment_method_types' => ['card'],
        'mode' => 'subscription',
        'success_url' => STRIPE_SUCCESS_URL . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => STRIPE_CANCEL_URL,
        'customer_email' => $user['email'],
        'client_reference_id' => $intentId,
        'metadata' => [
            'user_id' => $userId,
            'plan_id' => $planId,
            'intent_id' => $intentId,
            'billing_cycle' => $billingCycle
        ]
    ];

    // Si tenemos un Price ID de Stripe configurado, usarlo
    if (!empty($stripePriceId)) {
        $sessionData['line_items'] = [[
            'price' => $stripePriceId,
            'quantity' => 1,
        ]];
    } else {
        // Si no hay Price ID, crear el precio dinámicamente
        $sessionData['line_items'] = [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $plan['name'],
                    'description' => $plan['description'],
                ],
                'unit_amount' => $price * 100, // Stripe usa centavos
                'recurring' => [
                    'interval' => $billingCycle === 'monthly' ? 'month' : 'year',
                ],
            ],
            'quantity' => 1,
        ]];
    }

    $session = \Stripe\Checkout\Session::create($sessionData);

    // Actualizar la intención con el session_id de Stripe para seguimiento
    $stmtUpdate = $pdo->prepare("
        UPDATE membership_upgrade_intents
        SET stripe_session_id = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$session->id, $intentId]);

    // Preparamos la respuesta para el frontend
    $checkoutData = [
        'intent_id' => $intentId,
        'stripe_session_id' => $session->id,
        'stripe_checkout_url' => $session->url, // Esta es la URL a la que debes redirigir al usuario
        'stripe_public_key' => getStripePublicKey(),
        'plan_name' => $plan['name'],
        'billing_cycle' => $billingCycle,
        'price' => $price,
        'currency' => 'EUR'
    ];

    jsonSuccess($checkoutData, 'Sesión de checkout creada correctamente');

} catch (PDOException $e) {
    error_log('create_checkout_session.php Error PDO: ' . $e->getMessage());
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('Stripe Error: ' . $e->getMessage());
    jsonError('Error de Stripe: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('create_checkout_session.php Error General: ' . $e->getMessage());
    jsonError('Error inesperado: ' . $e->getMessage(), 500);
}