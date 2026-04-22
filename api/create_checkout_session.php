<?php
/**
 * API: Crear Sesión de Checkout de Stripe
 * POST /api/create_checkout_session.php
 * 
 * Body: {
 *   plan_id: int,
 *   billing_cycle: 'monthly'|'yearly',
 *   success_url: string,
 *   cancel_url: string
 * }
 */

session_start();
require_once 'config.php';
require_once 'stripe_config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión para realizar un pago', 401);
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
    $successUrl = isset($data['success_url']) ? sanitizeInput($data['success_url']) : 'https://rutasrurales.io/mi-cuenta.html?payment=success';
    $cancelUrl = isset($data['cancel_url']) ? sanitizeInput($data['cancel_url']) : 'https://rutasrurales.io/mi-cuenta.html?payment=canceled';

    // Validar billing_cycle
    if (!in_array($billingCycle, ['monthly', 'yearly'])) {
        jsonError('Ciclo de facturación inválido. Usa "monthly" o "yearly"', 400);
    }

    $pdo = getDBConnection();

    // Obtener información del usuario
    $stmtUser = $pdo->prepare("
        SELECT id, email, first_name, last_name 
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
        SELECT id, name,
               price_monthly, price_yearly,
               stripe_product_id, stripe_monthly_price_id, stripe_yearly_price_id
        FROM membership_plans
        WHERE id = ?
    ");
    $stmtPlan->execute([$planId]);
    $plan = $stmtPlan->fetch();

    if (!$plan) {
        jsonError('Plan de membresía no encontrado o inactivo', 404);
    }

    // Determinar precio según ciclo de facturación
    $price = ($billingCycle === 'monthly') ? $plan['price_monthly'] : $plan['price_yearly'];
    
    if ($price <= 0) {
        jsonError('Este plan es gratuito, no requiere pago', 400);
    }

    // Calcular precios con IVA
    $vatCalculation = calculateVAT($price);
    $priceWithVAT = $vatCalculation['total'];

    // Determinar ID de precio de Stripe
    $stripePriceId = null;
    $priceKey = $plan['name'] . '-' . ($billingCycle === 'monthly' ? 'mensual' : 'anual');
    
    global $stripe_price_ids;
    
    if (isset($stripe_price_ids[$priceKey])) {
        $stripePriceId = $stripe_price_ids[$priceKey];
    } else {
        // Si no existe el precio en la configuración, usar los IDs de la base de datos
        $stripePriceId = ($billingCycle === 'monthly') ? $plan['stripe_monthly_price_id'] : $plan['stripe_yearly_price_id'];
    }

    if (empty($stripePriceId)) {
        jsonError('Configuración de pago no disponible para este plan. Contacta con soporte.', 500);
    }

    // Crear sesión de checkout
    $metadata = [
        'user_id' => $userId,
        'plan_id' => $planId,
        'plan_name' => $plan['name'],
        'plan_type' => 'alojamiento',
        'billing_cycle' => $billingCycle,
        'price_without_vat' => $price,
        'vat_rate' => $vatCalculation['vat_rate'],
        'price_with_vat' => $priceWithVAT
    ];

    $checkoutSession = createCheckoutSession(
        $user['email'],
        $stripePriceId,
        $successUrl,
        $cancelUrl,
        $metadata
    );

    if (!$checkoutSession) {
        jsonError('Error al crear la sesión de pago', 500);
    }

    // Registrar la intención de pago en la base de datos
    $stmtIntent = $pdo->prepare("
        INSERT INTO payment_intents 
        (user_id, plan_id, stripe_session_id, stripe_price_id, 
         amount, vat_amount, total_amount, billing_cycle, status, metadata)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");
    
    $stmtIntent->execute([
        $userId,
        $planId,
        $checkoutSession->id,
        $stripePriceId,
        $price,
        $vatCalculation['vat_amount'],
        $priceWithVAT,
        $billingCycle,
        json_encode($metadata)
    ]);

    // Respuesta exitosa
    jsonSuccess([
        'session_id' => $checkoutSession->id,
        'checkout_url' => $checkoutSession->url,
        'plan_name' => $plan['name'],
        'billing_cycle' => $billingCycle,
        'amount' => $price,
        'vat_amount' => $vatCalculation['vat_amount'],
        'total_amount' => $priceWithVAT,
        'currency' => 'EUR',
        'expires_at' => date('c', $checkoutSession->expires_at),
        'metadata' => $metadata
    ], 'Sesión de pago creada correctamente');

} catch (PDOException $e) {
    error_log('create_checkout_session.php Error: ' . $e->getMessage());
    jsonError('Error al crear sesión de pago: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('create_checkout_session.php General Error: ' . $e->getMessage());
    jsonError('Error: ' . $e->getMessage(), 500);
}
?>