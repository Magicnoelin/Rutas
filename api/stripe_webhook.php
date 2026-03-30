<?php
/**
 * Webhook de Stripe
 * Maneja eventos de Stripe (pagos completados, suscripciones, etc.)
 * URL: https://rutasrurales.io/api/stripe_webhook.php
 */

require_once 'config.php';
require_once 'stripe_config.php';

// Leer el cuerpo de la petición
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$event = null;

try {
    // Si tienes Stripe instalado, descomenta este código:
    /*
    require_once '../vendor/stripe/stripe-php/init.php';
    
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, STRIPE_WEBHOOK_SECRET
    );
    */
    
    // Por ahora, parseamos el JSON directamente (NO SEGURO EN PRODUCCIÓN)
    $event = json_decode($payload, true);
    
    if (!$event) {
        http_response_code(400);
        exit();
    }

    $pdo = getDBConnection();

    // Manejar el evento según su tipo
    switch ($event['type']) {
        case 'checkout.session.completed':
            handleCheckoutSessionCompleted($event['data']['object'], $pdo);
            break;

        case 'customer.subscription.created':
            handleSubscriptionCreated($event['data']['object'], $pdo);
            break;

        case 'customer.subscription.updated':
            handleSubscriptionUpdated($event['data']['object'], $pdo);
            break;

        case 'customer.subscription.deleted':
            handleSubscriptionDeleted($event['data']['object'], $pdo);
            break;

        case 'invoice.paid':
            handleInvoicePaid($event['data']['object'], $pdo);
            break;

        case 'invoice.payment_failed':
            handleInvoicePaymentFailed($event['data']['object'], $pdo);
            break;

        default:
            error_log('Evento de Stripe no manejado: ' . $event['type']);
    }

    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    error_log('Error en webhook de Stripe: ' . $e->getMessage());
    http_response_code(400);
    exit();
}

/**
 * Manejar sesión de checkout completada
 */
function handleCheckoutSessionCompleted($session, $pdo) {
    $intentId = $session['client_reference_id'] ?? null;
    $stripeSessionId = $session['id'];
    $stripeCustomerId = $session['customer'];
    $stripeSubscriptionId = $session['subscription'];

    if (!$intentId) {
        error_log('No se encontró intent_id en la sesión de checkout');
        return;
    }

    // Obtener la intención de upgrade
    $stmt = $pdo->prepare("
        SELECT * FROM membership_upgrade_intents
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->execute([$intentId]);
    $intent = $stmt->fetch();

    if (!$intent) {
        error_log('No se encontró la intención de upgrade: ' . $intentId);
        return;
    }

    // Calcular fechas de validez
    $startDate = date('Y-m-d H:i:s');
    $validUntil = ($intent['billing_cycle'] === 'monthly') 
        ? date('Y-m-d H:i:s', strtotime('+1 month'))
        : date('Y-m-d H:i:s', strtotime('+1 year'));

    // Actualizar la membresía del usuario con fechas
    $stmtUser = $pdo->prepare("
        UPDATE users
        SET membership_type = ?,
            membership_status = 'active',
            membership_start_date = ?,
            membership_end_date = ?,
            stripe_customer_id = ?,
            stripe_subscription_id = ?,
            membership_updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmtUser->execute([
        $intent['plan_name'], 
        $startDate,
        $validUntil,
        $stripeCustomerId,
        $stripeSubscriptionId,
        $intent['user_id']
    ]);

    // Crear suscripción de usuario

    $stmtSub = $pdo->prepare("
        INSERT INTO user_subscriptions
        (user_id, plan_id, plan_name, billing_cycle, price, currency, status,
         stripe_subscription_id, stripe_customer_id, current_period_start,
         current_period_end, valid_until, created_at)
        VALUES (?, ?, ?, ?, ?, 'EUR', 'active', ?, ?, CURRENT_TIMESTAMP, ?, ?, CURRENT_TIMESTAMP)
    ");
    $stmtSub->execute([
        $intent['user_id'],
        $intent['plan_id'],
        $intent['plan_name'],
        $intent['billing_cycle'],
        $intent['price'],
        $stripeSubscriptionId,
        $stripeCustomerId,
        $validUntil,
        $validUntil
    ]);

    // Marcar la intención como completada
    $stmtIntent = $pdo->prepare("
        UPDATE membership_upgrade_intents
        SET status = 'completed',
            completed_at = CURRENT_TIMESTAMP,
            stripe_session_id = ?
        WHERE id = ?
    ");
    $stmtIntent->execute([$stripeSessionId, $intentId]);

    error_log('Membresía actualizada exitosamente para usuario: ' . $intent['user_id']);
}

/**
 * Manejar suscripción creada
 */
function handleSubscriptionCreated($subscription, $pdo) {
    error_log('Suscripción creada: ' . $subscription['id']);
    // Aquí puedes agregar lógica adicional si es necesario
}

/**
 * Manejar suscripción actualizada
 */
function handleSubscriptionUpdated($subscription, $pdo) {
    $stripeSubscriptionId = $subscription['id'];
    $status = $subscription['status'];

    // Actualizar el estado de la suscripción
    $stmt = $pdo->prepare("
        UPDATE user_subscriptions
        SET status = ?,
            current_period_end = FROM_UNIXTIME(?),
            updated_at = CURRENT_TIMESTAMP
        WHERE stripe_subscription_id = ?
    ");
    $stmt->execute([
        $status,
        $subscription['current_period_end'],
        $stripeSubscriptionId
    ]);

    error_log('Suscripción actualizada: ' . $stripeSubscriptionId);
}

/**
 * Manejar suscripción cancelada
 */
function handleSubscriptionDeleted($subscription, $pdo) {
    $stripeSubscriptionId = $subscription['id'];

    // Marcar la suscripción como cancelada
    $stmt = $pdo->prepare("
        UPDATE user_subscriptions
        SET status = 'cancelled',
            updated_at = CURRENT_TIMESTAMP
        WHERE stripe_subscription_id = ?
    ");
    $stmt->execute([$stripeSubscriptionId]);

    // Obtener el usuario y actualizar su membresía
    $stmt = $pdo->prepare("
        SELECT user_id FROM user_subscriptions
        WHERE stripe_subscription_id = ?
    ");
    $stmt->execute([$stripeSubscriptionId]);
    $sub = $stmt->fetch();

    if ($sub) {
        $stmtUser = $pdo->prepare("
            UPDATE users
            SET membership_type = 'Free',
                membership_status = 'active',
                membership_updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmtUser->execute([$sub['user_id']]);
    }

    error_log('Suscripción cancelada: ' . $stripeSubscriptionId);
}

/**
 * Manejar factura pagada
 */
function handleInvoicePaid($invoice, $pdo) {
    $stripeSubscriptionId = $invoice['subscription'];
    $amount = $invoice['amount_paid'] / 100; // Convertir de centavos a euros

    // Registrar el pago
    $stmt = $pdo->prepare("
        SELECT user_id FROM user_subscriptions
        WHERE stripe_subscription_id = ?
    ");
    $stmt->execute([$stripeSubscriptionId]);
    $sub = $stmt->fetch();

    if ($sub) {
        $stmtPayment = $pdo->prepare("
            INSERT INTO payments
            (user_id, amount, currency, payment_method, payment_status,
             stripe_payment_intent_id, stripe_charge_id, payment_date, created_at)
            VALUES (?, ?, 'EUR', 'stripe', 'completed', ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmtPayment->execute([
            $sub['user_id'],
            $amount,
            $invoice['payment_intent'],
            $invoice['charge']
        ]);
    }

    error_log('Factura pagada: ' . $invoice['id']);
}

/**
 * Manejar fallo de pago de factura
 */
function handleInvoicePaymentFailed($invoice, $pdo) {
    $stripeSubscriptionId = $invoice['subscription'];

    // Actualizar el estado de la suscripción
    $stmt = $pdo->prepare("
        UPDATE user_subscriptions
        SET status = 'pending',
            updated_at = CURRENT_TIMESTAMP
        WHERE stripe_subscription_id = ?
    ");
    $stmt->execute([$stripeSubscriptionId]);

    error_log('Fallo de pago en factura: ' . $invoice['id']);
}
