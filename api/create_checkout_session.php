<?php
/**
 * API: Crear Sesión de Checkout de Stripe (PRODUCCIÓN)
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

    $userId    = $_SESSION['user_id'];
    $planId    = (int)$data['plan_id'];
    $billingCycle = sanitizeInput($data['billing_cycle']);
    $successUrl   = isset($data['success_url']) ? $data['success_url'] : 'https://rutasrurales.io/user-dashboard.html?payment=success';
    $cancelUrl    = isset($data['cancel_url'])  ? $data['cancel_url']  : 'https://rutasrurales.io/user-dashboard.html?payment=canceled';

    // Validar billing_cycle
    if (!in_array($billingCycle, ['monthly', 'yearly'])) {
        jsonError('Ciclo de facturación inválido. Usa "monthly" o "yearly"', 400);
    }

    $pdo = getDBConnection();

    // Obtener información del usuario
    $stmtUser = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        jsonError('Usuario no encontrado', 404);
    }

    // Obtener información del plan
    $plan = null;
    try {
        $stmtPlan = $pdo->prepare("
            SELECT id, name, price_monthly, price_yearly, plan_type,
                   stripe_product_id, stripe_monthly_price_id, stripe_yearly_price_id
            FROM membership_plans
            WHERE id = ? AND status = 'active'
        ");
        $stmtPlan->execute([$planId]);
        $plan = $stmtPlan->fetch();
    } catch (PDOException $e) {
        error_log('create_checkout_session.php: Tabla membership_plans no disponible: ' . $e->getMessage());
    }

    // Planes por defecto si la tabla no existe o no se encontró el plan
    if (!$plan) {
        $defaultPlans = [
            1 => ['id' => 1, 'name' => 'Gratuito',           'price_monthly' => 0,     'price_yearly' => 0,     'plan_type' => 'alojamiento', 'stripe_product_id' => null, 'stripe_monthly_price_id' => null, 'stripe_yearly_price_id' => null],
            2 => ['id' => 2, 'name' => 'Básico Alojamiento',  'price_monthly' => 10.00, 'price_yearly' => 50.00, 'plan_type' => 'alojamiento', 'stripe_product_id' => null, 'stripe_monthly_price_id' => null, 'stripe_yearly_price_id' => null],
            3 => ['id' => 3, 'name' => 'Premium Alojamiento', 'price_monthly' => 10.00, 'price_yearly' => 100.00,'plan_type' => 'alojamiento', 'stripe_product_id' => null, 'stripe_monthly_price_id' => null, 'stripe_yearly_price_id' => null],
        ];
        if (isset($defaultPlans[$planId])) {
            $plan = $defaultPlans[$planId];
        } else {
            jsonError('Plan de membresía no encontrado', 404);
        }
    }

    // Determinar precio según ciclo de facturación
    $price = ($billingCycle === 'monthly') ? (float)$plan['price_monthly'] : (float)$plan['price_yearly'];

    if ($price <= 0) {
        jsonError('Este plan es gratuito, no requiere pago', 400);
    }

    // Los precios mostrados al usuario YA INCLUYEN IVA.
    // El precio final cobrado en Stripe es el mismo que se muestra (IVA incluido).
    $priceWithVAT = $price; // El precio es ya el precio final con IVA incluido

    // Desglose de IVA para contabilidad (21% incluido en el precio)
    $vatRate          = 0.21;
    $priceWithoutVAT  = round($price / (1 + $vatRate), 2);
    $vatAmount        = round($price - $priceWithoutVAT, 2);

    // Metadata para Stripe y para nuestra BD
    $metadata = [
        'user_id'          => (string)$userId,
        'plan_id'          => (string)$planId,
        'plan_name'        => $plan['name'],
        'plan_type'        => $plan['plan_type'] ?? 'alojamiento',
        'billing_cycle'    => $billingCycle,
        'price_with_vat'   => (string)$priceWithVAT,
        'price_without_vat'=> (string)$priceWithoutVAT,
        'vat_rate'         => '21',
        'vat_amount'       => (string)$vatAmount,
        'vat_included'     => 'true',
    ];

    // ============================================================
    // CREAR SESIÓN REAL DE STRIPE
    // ============================================================

    // Determinar modo: suscripción o pago único
    $planType = $plan['plan_type'] ?? 'alojamiento';
    $isOneTime = ($planType === 'apoyo_plataforma');
    $stripeMode = $isOneTime ? 'payment' : 'subscription';

    // Construir line_items
    // Si el plan tiene un price_id de Stripe en BD, usarlo directamente
    $stripePriceId = ($billingCycle === 'monthly')
        ? ($plan['stripe_monthly_price_id'] ?? null)
        : ($plan['stripe_yearly_price_id'] ?? null);

    if (!empty($stripePriceId) && strpos($stripePriceId, 'price_') === 0 && strlen($stripePriceId) > 10) {
        // Usar price_id existente de Stripe
        $lineItems = [[
            'price'    => $stripePriceId,
            'quantity' => 1,
        ]];
    } else {
        // Crear precio inline (price_data) — no requiere crear productos en Stripe previamente
        $billingLabel = ($billingCycle === 'monthly') ? 'mensual' : 'anual';
        $lineItems = [[
            'name'        => $plan['name'] . ' (' . $billingLabel . ')',
            'description' => 'Membresía Rutas Rurales - ' . $plan['name'] . ' - Facturación ' . $billingLabel . ' (IVA incluido)',
            'unit_amount' => (int)round($priceWithVAT * 100), // en céntimos
            'currency'    => 'eur',
            'interval'    => ($billingCycle === 'monthly') ? 'month' : 'year',
            'quantity'    => 1,
        ]];
    }

    // Añadir session_id a las URLs de retorno para poder verificar el pago
    $successUrlWithSession = $successUrl . (strpos($successUrl, '?') !== false ? '&' : '?') . 'session_id={CHECKOUT_SESSION_ID}';

    // Llamar a Stripe
    $stripeSession = createStripeCheckoutSession(
        $user['email'],
        $lineItems,
        $stripeMode,
        $successUrlWithSession,
        $cancelUrl,
        $metadata
    );

    if (!$stripeSession || isset($stripeSession['error'])) {
        $errorMsg = isset($stripeSession['error']['message']) ? $stripeSession['error']['message'] : 'Error al conectar con Stripe';
        error_log('create_checkout_session.php: Stripe error: ' . $errorMsg);
        jsonError('Error al crear la sesión de pago con Stripe: ' . $errorMsg, 500);
    }

    $sessionId   = $stripeSession['id'];
    $checkoutUrl = $stripeSession['url'];

    // ============================================================
    // REGISTRAR INTENCIÓN DE PAGO EN BD
    // ============================================================
    try {
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
            $stripePriceId ?: 'price_inline',
            $priceWithoutVAT,
            $vatAmount,
            $priceWithVAT,
            $billingCycle,
            json_encode($metadata),
        ]);
    } catch (PDOException $e) {
        // No bloquear el flujo si la tabla no existe
        error_log('create_checkout_session.php: No se pudo registrar payment_intent: ' . $e->getMessage());
    }

    // ============================================================
    // RESPUESTA EXITOSA
    // ============================================================
    jsonSuccess([
        'session_id'   => $sessionId,
        'checkout_url' => $checkoutUrl,
        'plan_name'    => $plan['name'],
        'billing_cycle'=> $billingCycle,
        'amount'       => $priceWithoutVAT,
        'vat_amount'   => $vatAmount,
        'total_amount' => $priceWithVAT,
        'currency'     => 'EUR',
        'expires_at'   => date('c', strtotime('+24 hours')),
        'metadata'     => $metadata,
        'mode'         => 'live',
    ], 'Sesión de pago creada correctamente');

} catch (PDOException $e) {
    error_log('create_checkout_session.php PDO Error: ' . $e->getMessage());
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('create_checkout_session.php General Error: ' . $e->getMessage());
    jsonError('Error: ' . $e->getMessage(), 500);
}
?>
