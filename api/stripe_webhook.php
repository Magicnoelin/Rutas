<?php
/**
 * Webhook de Stripe para procesar pagos y actualizar membresías
 * POST /api/stripe_webhook.php
 * 
 * Este endpoint debe ser configurado en el dashboard de Stripe:
 * https://dashboard.stripe.com/webhooks
 */

require_once 'config.php';
require_once 'stripe_config.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Procesar el webhook
handleStripeWebhook();

// ============================================
// FUNCIONES DE MANEJO DE WEBHOOK
// ============================================

/**
 * Manejar pago exitoso (checkout.session.completed)
 */
function handleSuccessfulPayment($session) {
    $pdo = getDBConnection();
    
    try {
        // Buscar la intención de pago por session_id
        $stmt = $pdo->prepare("
            SELECT * FROM payment_intents 
            WHERE stripe_session_id = ? AND status = 'pending'
        ");
        $stmt->execute([$session->id]);
        $paymentIntent = $stmt->fetch();
        
        if (!$paymentIntent) {
            error_log('Payment intent not found for session: ' . $session->id);
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
        $endDate = null;
        
        if ($plan['plan_type'] === 'apoyo_plataforma') {
            // Pagos únicos: membresía de por vida (o 1 año)
            $endDate = date('Y-m-d', strtotime('+1 year'));
        } elseif ($paymentIntent['billing_cycle'] === 'monthly') {
            $endDate = date('Y-m-d', strtotime('+1 month'));
        } else {
            $endDate = date('Y-m-d', strtotime('+1 year'));
        }
        
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
            $session->subscription_id ?? null,
            $session->customer ?? null,
            $session->invoice_id ?? null,
            $startDate,
            $endDate,
            $endDate, // next_billing_date igual a end_date para renovación
            json_encode([
                'stripe_session_id' => $session->id,
                'payment_intent_id' => $session->payment_intent ?? null,
                'customer_email' => $session->customer_email,
                'payment_status' => $session->payment_status
            ])
        ]);
        
        // Actualizar usuario con información de membresía
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
        
        $membershipType = $plan['plan_type'] . '_' . strtolower(str_replace(' ', '-', $plan['name']));
        
        $stmtUser->execute([
            $membershipType,
            $startDate,
            $endDate,
            $session->customer ?? null,
            $session->subscription_id ?? null,
            $userId
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
                'stripe_customer_id' => $session->customer ?? null,
                'stripe_subscription_id' => $session->subscription_id ?? null,
                'stripe_invoice_id' => $session->invoice_id ?? null,
                'payment_status' => $session->payment_status
            ]),
            $paymentIntent['id']
        ]);
        
        // Crear factura
        createInvoice($userId, $paymentIntent['id'], $session);
        
        error_log('Payment processed successfully for user: ' . $userId . ', plan: ' . $plan['name']);
        
    } catch (PDOException $e) {
        error_log('Database error in handleSuccessfulPayment: ' . $e->getMessage());
    }
}

/**
 * Manejar factura pagada (invoice.paid)
 */
function handleInvoicePaid($invoice) {
    $pdo = getDBConnection();
    
    try {
        // Buscar suscripción por stripe_subscription_id
        $stmt = $pdo->prepare("
            SELECT * FROM user_subscriptions 
            WHERE stripe_subscription_id = ? AND status = 'active'
        ");
        $stmt->execute([$invoice->subscription]);
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            error_log('Subscription not found for invoice: ' . $invoice->id);
            return;
        }
        
        // Actualizar fechas de la suscripción
        $newEndDate = date('Y-m-d', strtotime('+' . ($subscription['billing_cycle'] === 'monthly' ? '1 month' : '1 year')));
        $nextBillingDate = $newEndDate;
        
        $stmtUpdate = $pdo->prepare("
            UPDATE user_subscriptions 
            SET end_date = ?,
                next_billing_date = ?,
                stripe_invoice_id = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmtUpdate->execute([
            $newEndDate,
            $nextBillingDate,
            $invoice->id,
            $subscription['id']
        ]);
        
        // Actualizar usuario
        $stmtUser = $pdo->prepare("
            UPDATE users 
            SET membership_end_date = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmtUser->execute([$newEndDate, $subscription['user_id']]);
        
        // Crear factura para este pago recurrente
        createInvoiceFromStripeInvoice($subscription['user_id'], $subscription['id'], $invoice);
        
        error_log('Invoice paid and subscription renewed: ' . $invoice->id);
        
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
        // Buscar suscripción
        $stmt = $pdo->prepare("
            SELECT * FROM user_subscriptions 
            WHERE stripe_subscription_id = ? AND status = 'active'
        ");
        $stmt->execute([$invoice->subscription]);
        $subscription = $stmt->fetch();
        
        if (!$subscription) {
            return;
        }
        
        // Actualizar estado a past_due
        $stmtUpdate = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'past_due',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmtUpdate->execute([$subscription['id']]);
        
        // Actualizar usuario
        $stmtUser = $pdo->prepare("
            UPDATE users 
            SET membership_status = 'past_due',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmtUser->execute([$subscription['user_id']]);
        
        // Registrar intento fallido
        $stmtLog = $pdo->prepare("
            INSERT INTO payment_failures 
            (user_id, subscription_id, stripe_invoice_id, amount, failure_reason, metadata)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmtLog->execute([
            $subscription['user_id'],
            $subscription['id'],
            $invoice->id,
            $invoice->amount_due / 100, // Convertir de céntimos
            $invoice->last_payment_error ? $invoice->last_payment_error->message : 'Unknown',
            json_encode($invoice)
        ]);
        
        error_log('Payment failed for subscription: ' . $subscription['id'] . ', invoice: ' . $invoice->id);
        
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
        // Buscar suscripción
        $stmt = $pdo->prepare("
            SELECT * FROM user_subscriptions 
            WHERE stripe_subscription_id = ? AND status IN ('active', 'past_due')
        ");
        $stmt->execute([$subscription->id]);
        $userSubscription = $stmt->fetch();
        
        if (!$userSubscription) {
            return;
        }
        
        // Actualizar suscripción a cancelada
        $stmtUpdate = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'canceled',
                canceled_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmtUpdate->execute([$userSubscription['id']]);
        
        // Actualizar usuario (devolver a free)
        $stmtUser = $pdo->prepare("
            UPDATE users 
            SET membership_type = 'free',
                membership_status = 'expired',
                membership_end_date = CURRENT_TIMESTAMP,
                stripe_subscription_id = NULL,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmtUser->execute([$userSubscription['user_id']]);
        
        error_log('Subscription cancelled: ' . $subscription->id . ' for user: ' . $userSubscription['user_id']);
        
    } catch (PDOException $e) {
        error_log('Database error in handleSubscriptionCancelled: ' . $e->getMessage());
    }
}

/**
 * Crear factura en la base de datos
 */
function createInvoice($userId, $paymentIntentId, $session) {
    $pdo = getDBConnection();
    
    try {
        // Obtener información del usuario para facturación
        $stmtUser = $pdo->prepare("
            SELECT billing_name, billing_nif, billing_address, billing_email,
                   billing_city, billing_postal_code, billing_country
            FROM users 
            WHERE id = ?
        ");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();
        
        // Si no tiene datos de facturación, usar datos básicos
        if (empty($user['billing_name'])) {
            $stmtBasic = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
            $stmtBasic->execute([$userId]);
            $basic = $stmtBasic->fetch();
            
            $user['billing_name'] = $basic['first_name'] . ' ' . $basic['last_name'];
            $user['billing_email'] = $basic['email'];
            $user['billing_address'] = 'Dirección no especificada';
            $user['billing_city'] = 'Ciudad no especificada';
            $user['billing_country'] = 'España';
        }
        
        // Obtener información del payment intent
        $stmtPayment = $pdo->prepare("SELECT * FROM payment_intents WHERE id = ?");
        $stmtPayment->execute([$paymentIntentId]);
        $payment = $stmtPayment->fetch();
        
        if (!$payment) {
            return;
        }
        
        // Generar número de factura
        $invoiceNumber = generate_invoice_number($userId);
        
        // Insertar factura
        $stmtInvoice = $pdo->prepare("
            INSERT INTO invoices 
            (subscription_id, user_id, invoice_number, invoice_date, due_date,
             subtotal, vat_rate, vat_amount, total_amount,
             stripe_invoice_id, stripe_payment_intent_id, stripe_receipt_url,
             payment_status, paid_at,
             billing_name, billing_nif, billing_address, billing_email,
             description, metadata)
            VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                    ?, 21.00, ?, ?,
                    ?, ?, ?,
                    'paid', CURRENT_TIMESTAMP,
                    ?, ?, ?, ?,
                    ?, ?)
        ");
        
        $description = "Membresía: " . json_decode($payment['metadata'], true)['plan_name'] . 
                      " (" . $payment['billing_cycle'] . ")";
        
        $stmtInvoice->execute([
            null, // subscription_id se actualizará después
            $userId,
            $invoiceNumber,
            $payment['amount'],
            $payment['vat_amount'],
            $payment['total_amount'],
            $session->invoice_id ?? null,
            $session->payment_intent ?? null,
            $session->receipt_url ?? null,
            $user['billing_name'],
            $user['billing_nif'] ?? null,
            $user['billing_address'],
            $user['billing_email'],
            $description,
            json_encode([
                'stripe_session_id' => $session->id,
                'customer_email' => $session->customer_email,
                'plan_details' => json_decode($payment['metadata'], true)
            ])
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
        // Obtener información del usuario
        $stmtUser = $pdo->prepare("
            SELECT billing_name, billing_nif, billing_address, billing_email
            FROM users 
            WHERE id = ?
        ");
        $stmtUser->execute([$userId]);
        $user = $stmtUser->fetch();
        
        // Generar número de factura
        $invoiceNumber = generate_invoice_number($userId);
        
        // Calcular montos (Stripe devuelve en céntimos)
        $subtotal = $stripeInvoice->subtotal / 100;
        $vatAmount = $stripeInvoice->tax / 100;
        $totalAmount = $stripeInvoice->total / 100;
        
        // Insertar factura
        $stmtInvoice = $pdo->prepare("
            INSERT INTO invoices 
            (subscription_id, user_id, invoice_number, invoice_date, due_date,
             subtotal, vat_rate, vat_amount, total_amount,
             stripe_invoice_id, stripe_payment_intent_id, stripe_receipt_url,
             payment_status, paid_at,
             billing_name, billing_nif, billing_address, billing_email,
             description, metadata)
            VALUES (?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?,
                    'paid', ?,
                    ?, ?, ?, ?,
                    ?, ?)
        ");
        
        $stmtInvoice->execute([
            $subscriptionId,
            $userId,
            $invoiceNumber,
            date('Y-m-d', $stripeInvoice->created),
            date('Y-m-d', $stripeInvoice->due_date ?? strtotime('+30 days')),
            $subtotal,
            21.00, // IVA español
            $vatAmount,
            $totalAmount,
            $stripeInvoice->id,
            $stripeInvoice->payment_intent,
            $stripeInvoice->receipt_url ?? null,
            date('Y-m-d H:i:s', $stripeInvoice->status_transitions->paid_at ?? time()),
            $user['billing_name'] ?? 'Cliente',
            $user['billing_nif'] ?? null,
            $user['billing_address'] ?? 'Dirección no especificada',
            $user['billing_email'] ?? $stripeInvoice->customer_email,
            'Renovación de membresía - ' . $stripeInvoice->description,
            json_encode($stripeInvoice)
        ]);
        
    } catch (PDOException $e) {
        error_log('Database