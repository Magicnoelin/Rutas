<?php
/**
 * Webhook de Stripe — Rutas Rurales
 * POST /api/stripe_webhook.php
 *
 * Usa las tablas reales del sistema de facturación:
 *   payment_intents  → intención de pago (ya existe con id=21)
 *   billing_profiles → datos fiscales del cliente
 *   subscriptions    → suscripción activa
 *   billing_concepts → catálogo de productos
 *   invoices         → documento legal
 *   invoice_items    → líneas de la factura
 *   payments         → cobro real registrado
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

// Leer payload raw
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

// Decodificar evento
$event = json_decode($payload, true);
if (!$event || !isset($event['type'])) {
    http_response_code(400);
    exit('Invalid payload');
}

error_log('Stripe webhook recibido: ' . $event['type']);

switch ($event['type']) {
    case 'checkout.session.completed':
        handleCheckoutCompleted($event['data']['object']);
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

// ============================================================
// HANDLER: checkout.session.completed
// ============================================================
function handleCheckoutCompleted($session) {
    $pdo = getDBConnection();

    try {
        $sessionId = $session['id'] ?? '';

        // 1. Buscar el payment_intent pendiente
        $stmt = $pdo->prepare("
            SELECT pi.*, mp.name AS plan_name, mp.plan_type,
                   u.email, u.first_name, u.last_name
            FROM payment_intents pi
            JOIN membership_plans mp ON mp.id = pi.plan_id
            JOIN users u ON u.id = pi.user_id
            WHERE pi.stripe_session_id = ? AND pi.status = 'pending'
        ");
        $stmt->execute([$sessionId]);
        $pi = $stmt->fetch();

        if (!$pi) {
            error_log('Webhook: payment_intent no encontrado para session: ' . $sessionId);
            return;
        }

        $userId       = $pi['user_id'];
        $planId       = $pi['plan_id'];
        $billingCycle = $pi['billing_cycle'];
        $amount       = (float)$pi['amount'];       // sin IVA
        $vatAmount    = (float)$pi['vat_amount'];   // IVA
        $totalAmount  = (float)$pi['total_amount']; // total con IVA
        $planName     = $pi['plan_name'];
        $planType     = $pi['plan_type'];

        $stripeCustomerId    = $session['customer']     ?? null;
        $stripeSubId         = $session['subscription'] ?? null;
        $stripeInvoiceId     = $session['invoice']      ?? null;
        $stripePaymentIntent = $session['payment_intent'] ?? null;
        $customerEmail       = $session['customer_email'] ?? $pi['email'];

        // 2. Obtener o crear billing_profile del usuario
        $billingProfileId = getOrCreateBillingProfile($pdo, $userId, $pi);

        // 3. Obtener o crear billing_concept para este plan
        $billingConceptId = getOrCreateBillingConcept($pdo, $planId, $planName, $amount, $billingCycle);

        // 4. Calcular fechas de suscripción
        $startDate       = date('Y-m-d');
        $nextBillingDate = ($billingCycle === 'monthly')
            ? date('Y-m-d', strtotime('+1 month'))
            : date('Y-m-d', strtotime('+1 year'));
        $endDate = $nextBillingDate;

        // 5. Crear suscripción en tabla `subscriptions`
        $stmtSub = $pdo->prepare("
            INSERT INTO subscriptions
                (billing_profile_id, billing_concept_id, billing_cycle,
                 start_date, next_billing_date, end_date, active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmtSub->execute([
            $billingProfileId,
            $billingConceptId,
            $billingCycle,
            $startDate,
            $nextBillingDate,
            $endDate,
        ]);
        $subscriptionId = $pdo->lastInsertId();

        // 6. Actualizar users con datos de membresía y Stripe
        $membershipType = strtolower(str_replace(' ', '_', $planName));
        $pdo->prepare("
            UPDATE users
            SET membership_type         = ?,
                membership_status       = 'active',
                membership_start_date   = ?,
                membership_end_date     = ?,
                stripe_customer_id      = ?,
                stripe_subscription_id  = ?,
                updated_at              = CURRENT_TIMESTAMP
            WHERE id = ?
        ")->execute([$membershipType, $startDate, $endDate, $stripeCustomerId, $stripeSubId, $userId]);

        // 7. Marcar payment_intent como completado
        $pdo->prepare("
            UPDATE payment_intents
            SET status = 'completed', completed_at = CURRENT_TIMESTAMP
            WHERE stripe_session_id = ?
        ")->execute([$sessionId]);

        // 8. Registrar cobro en tabla `payments`
        $transactionId = 'stripe_' . $sessionId;
        $pdo->prepare("
            INSERT INTO payments
                (user_id, transaction_id, gateway_transaction_id, gateway_name,
                 amount, currency, tax_amount, total_amount,
                 status, payment_type, reference_type, reference_id,
                 description, metadata, completed_at)
            VALUES (?, ?, ?, 'stripe',
                    ?, 'EUR', ?, ?,
                    'completed', 'subscription', 'subscriptions', ?,
                    ?, ?, CURRENT_TIMESTAMP)
        ")->execute([
            $userId,
            $transactionId,
            $stripePaymentIntent,
            $amount,
            $vatAmount,
            $totalAmount,
            $subscriptionId,
            'Membresía ' . $planName . ' (' . $billingCycle . ')',
            json_encode([
                'stripe_session_id'    => $sessionId,
                'stripe_customer_id'   => $stripeCustomerId,
                'stripe_subscription_id' => $stripeSubId,
                'stripe_invoice_id'    => $stripeInvoiceId,
                'customer_email'       => $customerEmail,
                'plan_id'              => $planId,
                'billing_cycle'        => $billingCycle,
            ]),
        ]);
        $paymentDbId = $pdo->lastInsertId();

        // 9. Crear factura completa (invoices + invoice_items)
        createInvoiceComplete(
            $pdo, $userId, $subscriptionId, $billingProfileId,
            $billingConceptId, $planName, $billingCycle,
            $amount, $vatAmount, $totalAmount,
            $stripeInvoiceId, $stripePaymentIntent, $customerEmail,
            $pi
        );

        error_log("Webhook: pago completado. user=$userId plan=$planName total={$totalAmount}€");

    } catch (Exception $e) {
        error_log('Webhook handleCheckoutCompleted error: ' . $e->getMessage());
    }
}

// ============================================================
// HANDLER: invoice.paid (renovaciones automáticas)
// ============================================================
function handleInvoicePaid($stripeInvoice) {
    $pdo = getDBConnection();

    try {
        $stripeSubId = $stripeInvoice['subscription'] ?? null;
        if (!$stripeSubId) return;

        // Buscar suscripción activa por stripe_subscription_id en users
        $stmt = $pdo->prepare("
            SELECT u.id AS user_id, u.stripe_subscription_id,
                   s.id AS sub_id, s.billing_cycle, s.billing_profile_id, s.billing_concept_id
            FROM users u
            JOIN subscriptions s ON s.billing_profile_id = (
                SELECT id FROM billing_profiles WHERE user_id = u.id LIMIT 1
            )
            WHERE u.stripe_subscription_id = ? AND s.active = 1
            LIMIT 1
        ");
        $stmt->execute([$stripeSubId]);
        $row = $stmt->fetch();

        if (!$row) {
            error_log('Webhook invoice.paid: suscripción no encontrada para ' . $stripeSubId);
            return;
        }

        $newEndDate = ($row['billing_cycle'] === 'monthly')
            ? date('Y-m-d', strtotime('+1 month'))
            : date('Y-m-d', strtotime('+1 year'));

        // Renovar suscripción
        $pdo->prepare("
            UPDATE subscriptions
            SET next_billing_date = ?, end_date = ?
            WHERE id = ?
        ")->execute([$newEndDate, $newEndDate, $row['sub_id']]);

        // Actualizar fecha en users
        $pdo->prepare("
            UPDATE users SET membership_end_date = ? WHERE id = ?
        ")->execute([$newEndDate, $row['user_id']]);

        // Registrar cobro en payments
        $subtotal    = ($stripeInvoice['subtotal'] ?? 0) / 100;
        $taxAmount   = ($stripeInvoice['tax'] ?? 0) / 100;
        $totalAmount = ($stripeInvoice['total'] ?? 0) / 100;

        $pdo->prepare("
            INSERT INTO payments
                (user_id, transaction_id, gateway_transaction_id, gateway_name,
                 amount, currency, tax_amount, total_amount,
                 status, payment_type, reference_type, reference_id,
                 description, metadata, completed_at)
            VALUES (?, ?, ?, 'stripe',
                    ?, 'EUR', ?, ?,
                    'completed', 'subscription', 'subscriptions', ?,
                    ?, ?, CURRENT_TIMESTAMP)
        ")->execute([
            $row['user_id'],
            'stripe_inv_' . ($stripeInvoice['id'] ?? uniqid()),
            $stripeInvoice['payment_intent'] ?? null,
            $subtotal,
            $taxAmount,
            $totalAmount,
            $row['sub_id'],
            'Renovación membresía',
            json_encode($stripeInvoice),
        ]);

        // Crear factura de renovación
        $stmtBp = $pdo->prepare("SELECT * FROM billing_profiles WHERE id = ?");
        $stmtBp->execute([$row['billing_profile_id']]);
        $bp = $stmtBp->fetch();

        $stmtBc = $pdo->prepare("SELECT * FROM billing_concepts WHERE id = ?");
        $stmtBc->execute([$row['billing_concept_id']]);
        $bc = $stmtBc->fetch();

        if ($bp && $bc) {
            createInvoiceComplete(
                $pdo, $row['user_id'], $row['sub_id'], $row['billing_profile_id'],
                $row['billing_concept_id'], $bc['concept_name'], $row['billing_cycle'],
                $subtotal, $taxAmount, $totalAmount,
                $stripeInvoice['id'] ?? null, $stripeInvoice['payment_intent'] ?? null,
                $bp['address'] ?? '',
                ['first_name' => '', 'last_name' => '', 'email' => '']
            );
        }

        error_log('Webhook: renovación procesada para user=' . $row['user_id']);

    } catch (Exception $e) {
        error_log('Webhook handleInvoicePaid error: ' . $e->getMessage());
    }
}

// ============================================================
// HANDLER: invoice.payment_failed
// ============================================================
function handlePaymentFailed($stripeInvoice) {
    $pdo = getDBConnection();

    try {
        $stripeSubId = $stripeInvoice['subscription'] ?? null;
        if (!$stripeSubId) return;

        // Marcar suscripción como past_due en users
        $pdo->prepare("
            UPDATE users
            SET membership_status = 'past_due', updated_at = CURRENT_TIMESTAMP
            WHERE stripe_subscription_id = ?
        ")->execute([$stripeSubId]);

        // Desactivar suscripción
        $pdo->prepare("
            UPDATE subscriptions s
            JOIN billing_profiles bp ON bp.id = s.billing_profile_id
            JOIN users u ON u.id = bp.user_id
            SET s.active = 0
            WHERE u.stripe_subscription_id = ?
        ")->execute([$stripeSubId]);

        // Registrar pago fallido en payments
        $totalAmount = ($stripeInvoice['amount_due'] ?? 0) / 100;
        $pdo->prepare("
            INSERT INTO payments
                (user_id, transaction_id, gateway_name,
                 amount, currency, total_amount,
                 status, payment_type, description, metadata)
            SELECT u.id, ?, 'stripe',
                   ?, 'EUR', ?,
                   'failed', 'subscription', 'Pago fallido - membresía', ?
            FROM users u WHERE u.stripe_subscription_id = ? LIMIT 1
        ")->execute([
            'stripe_fail_' . ($stripeInvoice['id'] ?? uniqid()),
            $totalAmount,
            $totalAmount,
            json_encode(['reason' => $stripeInvoice['last_payment_error']['message'] ?? 'Unknown']),
            $stripeSubId,
        ]);

        error_log('Webhook: pago fallido para subscription=' . $stripeSubId);

    } catch (Exception $e) {
        error_log('Webhook handlePaymentFailed error: ' . $e->getMessage());
    }
}

// ============================================================
// HANDLER: customer.subscription.deleted
// ============================================================
function handleSubscriptionCancelled($stripeSub) {
    $pdo = getDBConnection();

    try {
        $stripeSubId = $stripeSub['id'] ?? null;
        if (!$stripeSubId) return;

        // Desactivar suscripción
        $pdo->prepare("
            UPDATE subscriptions s
            JOIN billing_profiles bp ON bp.id = s.billing_profile_id
            JOIN users u ON u.id = bp.user_id
            SET s.active = 0, s.end_date = CURDATE()
            WHERE u.stripe_subscription_id = ?
        ")->execute([$stripeSubId]);

        // Actualizar users
        $pdo->prepare("
            UPDATE users
            SET membership_type = 'free',
                membership_status = 'expired',
                membership_end_date = CURDATE(),
                stripe_subscription_id = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE stripe_subscription_id = ?
        ")->execute([$stripeSubId]);

        error_log('Webhook: suscripción cancelada: ' . $stripeSubId);

    } catch (Exception $e) {
        error_log('Webhook handleSubscriptionCancelled error: ' . $e->getMessage());
    }
}

// ============================================================
// HELPER: Obtener o crear billing_profile del usuario
// ============================================================
function getOrCreateBillingProfile($pdo, $userId, $pi) {
    // Buscar perfil existente
    $stmt = $pdo->prepare("SELECT id FROM billing_profiles WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $bp = $stmt->fetch();

    if ($bp) return $bp['id'];

    // Crear perfil básico con los datos disponibles
    $fullName = trim(($pi['first_name'] ?? '') . ' ' . ($pi['last_name'] ?? ''));
    if (empty($fullName)) $fullName = $pi['email'] ?? 'Cliente';

    $pdo->prepare("
        INSERT INTO billing_profiles (user_id, legal_name, tax_id, address, city, country)
        VALUES (?, ?, '', 'Dirección pendiente de completar', '', 'Spain')
    ")->execute([$userId, $fullName]);

    return $pdo->lastInsertId();
}

// ============================================================
// HELPER: Obtener o crear billing_concept para el plan
// ============================================================
function getOrCreateBillingConcept($pdo, $planId, $planName, $amount, $billingCycle) {
    $code = 'MEMB_' . $planId . '_' . strtoupper(substr($billingCycle, 0, 1));

    $stmt = $pdo->prepare("SELECT id FROM billing_concepts WHERE code = ?");
    $stmt->execute([$code]);
    $bc = $stmt->fetch();

    if ($bc) return $bc['id'];

    $billingType = ($billingCycle === 'monthly') ? 'monthly' : 'yearly';

    $pdo->prepare("
        INSERT INTO billing_concepts (code, concept_name, description, amount, billing_type, active)
        VALUES (?, ?, ?, ?, ?, 1)
    ")->execute([
        $code,
        'Membresía ' . $planName,
        'Plan de membresía Rutas Rurales - ' . $planName . ' - Facturación ' . $billingCycle,
        $amount,
        $billingType,
    ]);

    return $pdo->lastInsertId();
}

// ============================================================
// HELPER: Crear factura completa (invoices + invoice_items)
// ============================================================
function createInvoiceComplete(
    $pdo, $userId, $subscriptionId, $billingProfileId,
    $billingConceptId, $planName, $billingCycle,
    $subtotal, $vatAmount, $totalAmount,
    $stripeInvoiceId, $stripePaymentIntentId, $customerEmail,
    $pi
) {
    try {
        // Datos del billing_profile
        $stmtBp = $pdo->prepare("SELECT * FROM billing_profiles WHERE id = ?");
        $stmtBp->execute([$billingProfileId]);
        $bp = $stmtBp->fetch();

        $customerName  = $bp['legal_name'] ?? trim(($pi['first_name'] ?? '') . ' ' . ($pi['last_name'] ?? ''));
        $customerTaxId = $bp['tax_id'] ?? null;
        $email         = $customerEmail ?: ($pi['email'] ?? '');

        // Número de factura único: RR-AÑO-XXXXXX
        $invoiceNumber = 'RR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6));

        // Descripción de la línea
        $billingLabel = ($billingCycle === 'monthly') ? 'mensual' : 'anual';
        $description  = 'Membresía Rutas Rurales - ' . $planName . ' - Facturación ' . $billingLabel;

        // Insertar en invoices
        $stmtInv = $pdo->prepare("
            INSERT INTO invoices
                (invoice_number, customer_name, customer_tax_id, customer_email,
                 invoice_date, due_date,
                 subtotal, tax_rate, tax_amount, total,
                 status, notes, user_id, payment_status, total_amount)
            VALUES
                (?, ?, ?, ?,
                 CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                 ?, 21.00, ?, ?,
                 'paid', ?, ?, 'paid', ?)
        ");
        $stmtInv->execute([
            $invoiceNumber,
            $customerName,
            $customerTaxId,
            $email,
            $subtotal,
            $vatAmount,
            $totalAmount,
            $description . ($stripeInvoiceId ? ' | Stripe: ' . $stripeInvoiceId : ''),
            $userId,
            $totalAmount,
        ]);
        $invoiceId = $pdo->lastInsertId();

        // Insertar línea en invoice_items
        $stmtItem = $pdo->prepare("
            INSERT INTO invoice_items
                (invoice_id, concept_code, concept_name, description,
                 quantity, unit_price, line_total, billing_type, subscription_id)
            VALUES
                (?, ?, ?, ?,
                 1, ?, ?, ?, ?)
        ");

        // Obtener code del billing_concept
        $stmtBc = $pdo->prepare("SELECT code FROM billing_concepts WHERE id = ?");
        $stmtBc->execute([$billingConceptId]);
        $bc = $stmtBc->fetch();
        $conceptCode = $bc['code'] ?? ('MEMB_' . $subscriptionId);

        $billingType = ($billingCycle === 'monthly') ? 'monthly' : 'one_time';

        $stmtItem->execute([
            $invoiceId,
            $conceptCode,
            'Membresía ' . $planName,
            $description,
            $subtotal,  // unit_price sin IVA
            $subtotal,  // line_total sin IVA (el IVA va en la cabecera)
            $billingType,
            $subscriptionId,
        ]);

        error_log("Factura creada: $invoiceNumber para user=$userId total={$totalAmount}€");
        return $invoiceId;

    } catch (Exception $e) {
        error_log('createInvoiceComplete error: ' . $e->getMessage());
        return null;
    }
}
?>
