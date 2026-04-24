<?php
/**
 * Webhook de Stripe para procesar pagos y actualizar membresías
 * POST /api/stripe_webhook.php
 * 
 * Configurar en: https://dashboard.stripe.com/webhooks
 * URL: https://rutasrurales.io/api/stripe_webhook.php
 * Eventos: checkout.session.completed, invoice.paid,
 *          invoice.payment_failed, customer.subscription.deleted
 */

require_once 'config.php';
require_once 'stripe_config.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Leer payload raw (antes de cualquier procesamiento)
$payload   = file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verificar firma del webhook (solo si ya está configurado)
if (STRIPE_WEBHOOK_SECRET !== 'whsec_PENDIENTE_CONFIGURAR_EN_STRIPE_DASHBOARD') {
    if (!verifyStripeWebhookSignature($payload, $sigHeader, STRIPE_WEBHOOK_SECRET)) {
        error_log('Stripe webhook: firma inválida');
        http_response_code(400);
        exit('Invalid signature');
    }
}

// Decodificar evento (array asociativo)
$event = json_decode($payload, true);
if (!$event || !isset($event['type'])) {
    http_response_code(400);
    exit('Invalid payload');
}

error_log('Stripe webhook recibido: ' . $event['type']);

// Procesar según tipo de evento
switch ($event['type']) {
    case 'checkout.session.completed':
        handleSuccessfulPayment($event['data']['object']);
        break;
    case 'invoice.paid':
        handleInvoicePaid($event['data']['object']);
        break;
    case 'invoice.payment_failed':
        handlePaymentFailed($event['data']['object']);
        break;
    case 'customer.subscription.deleted':
        handleSubscriptionCancelled($event['data']['object']);
        break;
    default:
        error_log('Stripe webhook: evento no manejado: ' . $event['type']);
}

http_response_code(200);
exit('OK');

// ============================================
// FUNCIONES DE MANEJO DE WEBHOOK
// Nota: $session, $invoice, $subscription son arrays asociativos
// ============================================

/**
 * Manejar pago exitoso (checkout.session.completed)
 */
function handleSuccessfulPayment($session) {
    $pdo = getDBConnection();

    try {
        $sessionId = $session['id'] ?? '';

        // Buscar la intención de pago por session_id
        $stmt = $pdo->prepare("
            SELECT * FROM payment_intents
            WHERE stripe_session_id = ? AND status = 'pending'
        ");
        $stmt->execute([$sessionId]);
        $paymentIntent = $stmt->fetch();

        if (!$paymentIntent) {
            error_log('Payment intent not found for session: ' . $sessionId);
            return;
        }

        $userId = $paymentIntent['user_id'];
        $planId = $paymentIntent['plan_id'];

        // Obtener información del plan
        $stmtPlan = $pdo->prepare("SELECT * FROM membership_plans WHERE id = ?");
        $stmtPlan->execute([$planId]);
        $plan = $stmtPlan->fetch();

        if (!$plan) {
            error_log('Plan not found for payment: ' . $planId);
            return;
        }

        // Determinar fechas de la suscripción
        $startDate = date('Y-m-d');
        if ($plan['plan_type'] === 'apoyo_plataforma') {
            $endDate = date('Y-m-d', strtotime('+1 year'));
        } elseif ($paymentIntent['billing_cycle'] === 'monthly') {
            $endDate = date('Y-m-d', strtotime('+1 month'));
        } else {
            $endDate = date('Y-m-d', strtotime('+1 year'));
        }

        $stripeSubscriptionId = $session['subscription'] ?? null;
        $stripeCustomerId     = $session['customer'] ?? null;
        $stripeInvoiceId      = $session['invoice'] ?? null;
        $paymentStatus        = $session['payment_status'] ?? 'paid';
        $customerEmail        = $session['customer_email'] ?? null;
        $paymentIntentId      = $session['payment_intent'] ?? null;

        // Crear o actualizar suscripción
        $stmtSub = $pdo->prepare("
            INSERT INTO user_subscriptions
            (user_id, plan_id, plan_name, billing_cycle, price, vat_amount, total_amount,
             currency, stripe_subscription_id, stripe_customer_id, stripe_invoice_id,
             start_date, end_date, next_billing_date, status, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
            ON DUPLICATE KEY UPDATE
            plan_id = VALUES(plan_id),
            plan_name = VALUES(plan_name),
            billing_cycle = VALUES(billing_cycle),
            price = VALUES(price),
            vat_amount = VALUES(vat_amount),
            total_amount = VALUES(total_amount),
            stripe_subscription_id = VALUES(stripe_subscription_id),
            stripe_customer_id = VALUES(stripe_customer_id),
            stripe_invoice_id = VALUES(stripe_invoice_id),
            end_date = VALUES(end_date),
            next_billing_date = VALUES(next_billing_date),
            status = 'active',
            updated_at = CURRENT_TIMESTAMP
        ");

        $stmtSub->execute([
            $userId,
            $planId,
            $plan['name'],
            $paymentIntent['billing_cycle'],
            $paymentIntent['amount'],
            $paymentIntent['vat_amount'],
            $paymentIntent['total_amount'],
            'EUR',
            $stripeSubscriptionId,
            $stripeCustomerId,
            $stripeInvoiceId,
            $startDate,
            $endDate,
            $endDate,
            json_encode([
                'stripe_session_id'  => $sessionId,
                'payment_intent_id'  => $paymentIntentId,
                'customer_email'     => $customerEmail,
                'payment_status'     => $paymentStatus,
            ]),
        ]);

        // Actualizar usuario con información de membresía
        $membershipType = $plan['plan_type'] . '_' . strtolower(str_replace(' ', '-', $plan['name']));

        $stmtUser = $pdo->prepare("
            UPDATE users
            SET membership_type = ?,
                membership_status = 'active',
                membership_start_date = ?,
                membership_end_date = ?,
                stripe_customer_id = ?,
                stripe_subscription_id = ?
            WHERE id = ?
        ");
        $stmtUser->execute([
            $membershipType,
            $startDate,
            $endDate,
            $stripeCustomerId,
            $stripeSubscriptionId,
            $userId,
        ]);

        // Actualizar estado del payment intent
        $stmtUpdate = $pdo->prepare("
            UPDATE payment_intents
            SET status = 'completed',
                completed_at = CURRENT_TIMESTAMP,
                metadata = ?
            WHERE id = ?
        ");
        $stmtUpdate->execute([
            json_encode([
                'stripe_customer_id'      => $stripeCustomerId,
                'stripe_subscription_id'  => $stripeSubscriptionId,
                'stripe_invoice_id'       => $stripeInvoiceId,
                'payment_status'          => $paymentStatus,
            ]),
            $paymentIntent['id'],
        ]);

        // Crear factura
        createInvoice($userId, $paymentIntent['id'], $session);

        error_log('Payment processed successfully for user: ' . $userId . ', plan: ' . $plan['name']);

    } catch (PDOException $e) {
        error_log('Database error in handleSuccessfulPayment: ' . $e->getMessage());
    }
}

/**
 * Manejar factura pagada (invoice.paid) — renovaciones automáticas
 */
function handleInvoicePaid($invoice) {
    $pdo = getDBConnection();

    try {
        $subscriptionId = $invoice['subscription'] ?? null;
        if (!$subscriptionId) return;

        $stmt = $pdo->prepare("
            SELECT * FROM user_subscriptions
            WHERE stripe_subscription_id = ? AND status = 'active'
        ");
        $stmt->execute([$subscriptionId]);
        $subscription = $stmt->fetch();

        if (!$subscription) {
            error_log('Subscription not found for invoice: ' . ($invoice['id'] ?? ''));
            return;
        }

        $newEndDate = date('Y-m-d', strtotime('+' . ($subscription['billing_cycle'] === 'monthly' ? '1 month' : '1 year')));

        $stmtUpdate = $pdo->prepare("
            UPDATE user_subscriptions
            SET end_date = ?,
                next_billing_date = ?,
                stripe_invoice_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmtUpdate->execute([$newEndDate, $newEndDate, $invoice['id'] ?? null, $subscription['id']]);

        $stmtUser = $pdo->prepare("
            UPDATE users
            SET membership_end_date = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmtUser->execute([$newEndDate, $subscription['user_id']]);

        createInvoiceFromStripeInvoice($subscription['user_id'], $subscription['id'], $invoice);

        error_log('Invoice paid and subscription renewed: ' . ($invoice['id'] ?? ''));

    } catch (PDOException $e) {
        error_log('Database error in handleInvoicePaid: ' . $e->getMessage());
    }
}

/**
 * Manejar pago fallido (invoice.payment_failed)
 */
function handlePaymentFailed($invoice) {
    $pdo = getDBConnection();

    try {
        $subscriptionId = $invoice['subscription'] ?? null;
        if (!$subscriptionId) return;

        $stmt = $pdo->prepare("
            SELECT * FROM user_subscriptions
            WHERE stripe_subscription_id = ? AND status = 'active'
        ");
        $stmt->execute([$subscriptionId]);
        $subscription = $stmt->fetch();

        if (!$subscription) return;

        $pdo->prepare("UPDATE user_subscriptions SET status = 'past_due', updated_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$subscription['id']]);

        $pdo->prepare("UPDATE users SET membership_status = 'past_due', updated_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$subscription['user_id']]);

        $lastError = $invoice['last_payment_error']['message'] ?? 'Unknown';

        $stmtLog = $pdo->prepare("
            INSERT INTO payment_failures
            (user_id, subscription_id, stripe_invoice_id, amount, failure_reason, metadata)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtLog->execute([
            $subscription['user_id'],
            $subscription['id'],
            $invoice['id'] ?? null,
            ($invoice['amount_due'] ?? 0) / 100,
            $lastError,
            json_encode($invoice),
        ]);

        error_log('Payment failed for subscription: ' . $subscription['id']);

    } catch (PDOException $e) {
        error_log('Database error in handlePaymentFailed: ' . $e->getMessage());
    }
}

/**
 * Manejar suscripción cancelada (customer.subscription.deleted)
 */
function handleSubscriptionCancelled($subscription) {
    $pdo = getDBConnection();

    try {
        $stripeSubId = $subscription['id'] ?? null;
        if (!$stripeSubId) return;

        $stmt = $pdo->prepare("
            SELECT * FROM user_subscriptions
            WHERE stripe_subscription_id = ? AND status IN ('active', 'past_due')
        ");
        $stmt->execute([$stripeSubId]);
        $userSubscription = $stmt->fetch();

        if (!$userSubscription) return;

        $pdo->prepare("
            UPDATE user_subscriptions
            SET status = 'canceled', canceled_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ")->execute([$userSubscription['id']]);

        $pdo->prepare("
            UPDATE users
            SET membership_type = 'free', membership_status = 'expired',
                membership_end_date = CURRENT_TIMESTAMP, stripe_subscription_id = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ")->execute([$userSubscription['user_id']]);

        error_log('Subscription cancelled: ' . $stripeSubId . ' for user: ' . $userSubscription['user_id']);

    } catch (PDOException $e) {
        error_log('Database error in handleSubscriptionCancelled: ' . $e->getMessage());
    }
}

/**
 * Crear factura en la base de datos tras checkout.session.completed
 */
function createInvoice($userId, $paymentIntentDbId, $session) {
    $pdo = getDBConnection();

    try {
        // Datos de facturación del usuario
        $stmtUser = $pdo->prepare("
            SELECT billing_name, billing_nif, billing_address, billing_email,
                   billing_city, billing_postal_code, billing_country,
                   first_name, last_name, email
            FROM users WHERE id = ?
        ");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();

        $billingName    = !empty($user['billing_name'])    ? $user['billing_name']    : trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        $billingEmail   = !empty($user['billing_email'])   ? $user['billing_email']   : ($user['email'] ?? '');
        $billingAddress = !empty($user['billing_address']) ? $user['billing_address'] : 'Dirección no especificada';
        $billingNif     = $user['billing_nif'] ?? null;

        // Datos del payment intent
        $stmtPayment = $pdo->prepare("SELECT * FROM payment_intents WHERE id = ?");
        $stmtPayment->execute([$paymentIntentDbId]);
        $payment = $stmtPayment->fetch();
        if (!$payment) return;

        $invoiceNumber = generateInvoiceNumber($userId);
        $metaDecoded   = json_decode($payment['metadata'], true);
        $description   = 'Membresía: ' . ($metaDecoded['plan_name'] ?? 'Plan') . ' (' . $payment['billing_cycle'] . ')';

        $stmtInvoice = $pdo->prepare("
            INSERT INTO invoices
            (subscription_id, user_id, invoice_number, invoice_date, due_date,
             subtotal, vat_rate, vat_amount, total_amount,
             stripe_invoice_id, stripe_payment_intent_id, stripe_receipt_url,
             payment_status, paid_at,
             billing_name, billing_nif, billing_address, billing_email,
             description, metadata)
            VALUES (NULL, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                    ?, 21.00, ?, ?,
                    ?, ?, ?,
                    'paid', CURRENT_TIMESTAMP,
                    ?, ?, ?, ?,
                    ?, ?)
        ");
        $stmtInvoice->execute([
            $userId,
            $invoiceNumber,
            $payment['amount'],
            $payment['vat_amount'],
            $payment['total_amount'],
            $session['invoice'] ?? null,
            $session['payment_intent'] ?? null,
            null, // receipt_url no disponible en checkout session
            $billingName,
            $billingNif,
            $billingAddress,
            $billingEmail,
            $description,
            json_encode([
                'stripe_session_id' => $session['id'] ?? null,
                'customer_email'    => $session['customer_email'] ?? null,
                'plan_details'      => $metaDecoded,
            ]),
        ]);

        error_log('Invoice created: ' . $invoiceNumber . ' for user: ' . $userId);

    } catch (PDOException $e) {
        error_log('Database error in createInvoice: ' . $e->getMessage());
    }
}

/**
 * Crear factura desde invoice de Stripe (para pagos recurrentes)
 */
function createInvoiceFromStripeInvoice($userId, $subscriptionId, $stripeInvoice) {
    $pdo = getDBConnection();

    try {
        $stmtUser = $pdo->prepare("
            SELECT billing_name, billing_nif, billing_address, billing_email, email
            FROM users WHERE id = ?
        ");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();

        $invoiceNumber = generateInvoiceNumber($userId);

        $subtotal    = ($stripeInvoice['subtotal'] ?? 0) / 100;
        $vatAmount   = ($stripeInvoice['tax'] ?? 0) / 100;
        $totalAmount = ($stripeInvoice['total'] ?? 0) / 100;
        $createdAt   = isset($stripeInvoice['created']) ? date('Y-m-d', $stripeInvoice['created']) : date('Y-m-d');
        $dueDate     = isset($stripeInvoice['due_date']) ? date('Y-m-d', $stripeInvoice['due_date']) : date('Y-m-d', strtotime('+30 days'));
        $paidAt      = isset($stripeInvoice['status_transitions']['paid_at'])
                        ? date('Y-m-d H:i:s', $stripeInvoice['status_transitions']['paid_at'])
                        : date('Y-m-d H:i:s');

        $stmtInvoice = $pdo->prepare("
            INSERT INTO invoices
            (subscription_id, user_id, invoice_number, invoice_date, due_date,
             subtotal, vat_rate, vat_amount, total_amount,
             stripe_invoice_id, stripe_payment_intent_id, stripe_receipt_url,
             payment_status, paid_at,
             billing_name, billing_nif, billing_address, billing_email,
             description, metadata)
            VALUES (?, ?, ?, ?, ?,
                    ?, 21.00, ?, ?,
                    ?, ?, ?,
                    'paid', ?,
                    ?, ?, ?, ?,
                    ?, ?)
        ");
        $stmtInvoice->execute([
            $subscriptionId,
            $userId,
            $invoiceNumber,
            $createdAt,
            $dueDate,
            $subtotal,
            $vatAmount,
            $totalAmount,
            $stripeInvoice['id'] ?? null,
            $stripeInvoice['payment_intent'] ?? null,
            $stripeInvoice['receipt_url'] ?? null,
            $paidAt,
            $user['billing_name'] ?? 'Cliente',
            $user['billing_nif'] ?? null,
            $user['billing_address'] ?? 'Dirección no especificada',
            $user['billing_email'] ?? $user['email'] ?? ($stripeInvoice['customer_email'] ?? ''),
            'Renovación de membresía - ' . ($stripeInvoice['description'] ?? ''),
            json_encode($stripeInvoice),
        ]);

    } catch (PDOException $e) {
        error_log('Database error in createInvoiceFromStripeInvoice: ' . $e->getMessage());
    }
}

/**
 * Generar número de factura único
 */
function generateInvoiceNumber($userId) {
    return 'RR-' . date('Y') . '-' . str_pad($userId, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(uniqid(), -6));
}
?>
