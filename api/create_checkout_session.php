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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Intentar cargar configuración de Stripe, si falla entrar en modo simulado
$stripeAvailable = false;
try {
    if (file_exists(__DIR__ . '/stripe_config.php')) {
        require_once 'stripe_config.php';
        // Verificar si Stripe está realmente configurado (no placeholders)
        if (defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY !== 'sk_live_...') {
            $stripeAvailable = true;
        }
    }
} catch (Exception $e) {
    error_log('Stripe config not available, using simulated mode: ' . $e->getMessage());
    $stripeAvailable = false;
}

// Definir calculateVAT como fallback si no está definida en stripe_config.php
if (!function_exists('calculateVAT')) {
    function calculateVAT($amount, $countryCode = 'ES') {
        $vat_rate = 21.00;
        $vat_amount = ($amount * $vat_rate) / 100;
        return [
            'amount' => $amount,
            'vat_rate' => $vat_rate,
            'vat_amount' => $vat_amount,
            'total' => $amount + $vat_amount
        ];
    }
}

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

    // Intentar crear sesión de Stripe
    $stripePriceId = null;
    
    // Primero intentar con los IDs de la base de datos
    $stripePriceId = ($billingCycle === 'monthly') ? $plan['stripe_monthly_price_id'] : $plan['stripe_yearly_price_id'];
    
    // Si no hay IDs en BD, intentar con configuración global
    if (empty($stripePriceId)) {
        global $stripe_price_ids;
        $priceKey = strtolower($plan['name']) . '-alojamiento-' . ($billingCycle === 'monthly' ? 'mensual' : 'anual');
        if (isset($stripe_price_ids[$priceKey])) {
            $stripePriceId = $stripe_price_ids[$priceKey];
        }
    }

    if (!empty($stripePriceId)) {
        // Crear sesión real de Stripe
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
        
        $sessionId = $checkoutSession->id;
        $checkoutUrl = $checkoutSession->url;
    } else {
        // Modo simulado: generar URLs de prueba
        $sessionId = 'cs_test_' . bin2hex(random_bytes(16));
        $checkoutUrl = $successUrl . '&session_id=' . $sessionId . '&plan_id=' . $planId . '&billing_cycle=' . $billingCycle;
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
        $sessionId,
        $stripePriceId ?: 'price_simulated',
        $price,
        $vatCalculation['vat_amount'],
        $priceWithVAT,
        $billingCycle,
        json_encode($metadata)
    ]);

    // Respuesta exitosa
    jsonSuccess([
        'session_id' => $sessionId,
        'checkout_url' => $checkoutUrl,
        'plan_name' => $plan['name'],
        'billing_cycle' => $billingCycle,
        'amount' => $price,
        'vat_amount' => $vatCalculation['vat_amount'],
        'total_amount' => $priceWithVAT,
        'currency' => 'EUR',
        'expires_at' => date('c', strtotime('+1 hour')),
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