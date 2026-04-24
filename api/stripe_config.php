<?php
/**
 * Configuración de Stripe para Sistema de Membresías
 * Rutas Rurales - Producción
 */

// ============================================
// CONFIGURACIÓN DE STRIPE - PRODUCCIÓN
// ============================================

define('STRIPE_SECRET_KEY', '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');
define('STRIPE_PUBLISHABLE_KEY', 'pk_live_51Sz9aj8WI0tE7gHHC06mOHkWFtLtDIbnJJbNyTxkSUHt4DqMZZ2tm6lrQIdQCMaxRytKY6AiWueraVUIflEkJXti00qlbIkdq9');
// WEBHOOK SECRET: Configurar en https://dashboard.stripe.com/webhooks
// URL del webhook: https://rutasrurales.io/api/stripe_webhook.php
// Eventos: checkout.session.completed, invoice.paid, invoice.payment_failed, customer.subscription.deleted
define('STRIPE_WEBHOOK_SECRET', 'whsec_PENDIENTE_CONFIGURAR_EN_STRIPE_DASHBOARD');

// ============================================
// STRIPE API VIA CURL (sin necesidad de Composer)
// ============================================

/**
 * Realizar llamada a la API de Stripe via cURL
 */
function stripeRequest($method, $endpoint, $data = []) {
    $url = 'https://api.stripe.com/v1/' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Stripe-Version: 2023-10-16'
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    } elseif ($method === 'GET' && !empty($data)) {
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log('Stripe cURL error: ' . $curlError);
        return null;
    }
    
    $decoded = json_decode($response, true);
    
    if ($httpCode >= 400) {
        $errorMsg = isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Error desconocido de Stripe';
        error_log('Stripe API error (' . $httpCode . '): ' . $errorMsg);
        return ['error' => $decoded['error'] ?? ['message' => $errorMsg]];
    }
    
    return $decoded;
}

/**
 * Crear sesión de Checkout de Stripe
 * Soporta tanto suscripciones (recurring) como pagos únicos (one_time)
 */
function createStripeCheckoutSession($customerEmail, $lineItems, $mode, $successUrl, $cancelUrl, $metadata = []) {
    // Construir los datos del formulario para Stripe
    $data = [
        'payment_method_types[]' => 'card',
        'mode' => $mode, // 'subscription' o 'payment'
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'customer_email' => $customerEmail,
        'locale' => 'es',
    ];
    
    // Añadir line_items
    foreach ($lineItems as $i => $item) {
        if (isset($item['price'])) {
            // Usar price_id existente de Stripe
            $data["line_items[{$i}][price]"] = $item['price'];
            $data["line_items[{$i}][quantity]"] = $item['quantity'] ?? 1;
        } else {
            // Crear precio inline (price_data)
            $data["line_items[{$i}][price_data][currency]"] = $item['currency'] ?? 'eur';
            $data["line_items[{$i}][price_data][product_data][name]"] = $item['name'];
            if (!empty($item['description'])) {
                $data["line_items[{$i}][price_data][product_data][description]"] = $item['description'];
            }
            $data["line_items[{$i}][price_data][unit_amount]"] = $item['unit_amount']; // en céntimos
            if ($mode === 'subscription') {
                $data["line_items[{$i}][price_data][recurring][interval]"] = $item['interval'] ?? 'month';
            }
            $data["line_items[{$i}][quantity]"] = $item['quantity'] ?? 1;
        }
    }
    
    // Añadir metadata
    foreach ($metadata as $key => $value) {
        $data["metadata[{$key}]"] = $value;
    }
    
    $result = stripeRequest('POST', 'checkout/sessions', $data);
    
    if (!$result || isset($result['error'])) {
        $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : 'Error al crear sesión de Stripe';
        error_log('createStripeCheckoutSession error: ' . $errorMsg);
        return null;
    }
    
    return $result;
}

/**
 * Verificar firma del webhook de Stripe
 */
function verifyStripeWebhookSignature($payload, $sigHeader, $secret) {
    $parts = explode(',', $sigHeader);
    $timestamp = null;
    $signatures = [];
    
    foreach ($parts as $part) {
        $part = trim($part);
        if (strpos($part, 't=') === 0) {
            $timestamp = substr($part, 2);
        } elseif (strpos($part, 'v1=') === 0) {
            $signatures[] = substr($part, 3);
        }
    }
    
    if (!$timestamp || empty($signatures)) {
        return false;
    }
    
    // Verificar que el timestamp no sea demasiado antiguo (5 minutos)
    if (abs(time() - (int)$timestamp) > 300) {
        return false;
    }
    
    $signedPayload = $timestamp . '.' . $payload;
    $expectedSig = hash_hmac('sha256', $signedPayload, $secret);
    
    foreach ($signatures as $sig) {
        if (hash_equals($expectedSig, $sig)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Recuperar sesión de checkout de Stripe
 */
function retrieveStripeCheckoutSession($sessionId) {
    return stripeRequest('GET', 'checkout/sessions/' . $sessionId);
}

// ============================================
// CONFIGURACIÓN DE IVA
// ============================================

$vat_rates = [
    'ES' => 21.00,
    'PT' => 23.00,
    'FR' => 20.00,
    'DE' => 19.00,
    'IT' => 22.00,
    'GB' => 20.00,
    'US' => 0.00,
    'default' => 21.00
];

/**
 * Calcular IVA para un monto
 */
function calculateVAT($amount, $countryCode = 'ES') {
    global $vat_rates;
    $vat_rate = isset($vat_rates[$countryCode]) ? $vat_rates[$countryCode] : $vat_rates['default'];
    $vat_amount = ($amount * $vat_rate) / 100;
    return [
        'amount' => $amount,
        'vat_rate' => $vat_rate,
        'vat_amount' => $vat_amount,
        'total' => $amount + $vat_amount
    ];
}

/**
 * Formatear precio para mostrar
 */
function formatPrice($amount, $showVAT = true) {
    $vat = calculateVAT($amount);
    if ($showVAT) {
        return number_format($vat['total'], 2) . '€ (IVA incluido)';
    } else {
        return number_format($amount, 2) . '€ + IVA';
    }
}

// ============================================
// CONFIGURACIÓN DE FACTURACIÓN
// ============================================

$company_info = [
    'name' => 'Rutas Rurales S.L.',
    'address' => 'Calle Ejemplo, 123',
    'city' => 'Soria',
    'postal_code' => '42001',
    'country' => 'España',
    'nif' => 'B12345678',
    'email' => 'facturacion@rutasrurales.io',
    'phone' => '+34 605 249 696',
    'website' => 'https://rutasrurales.io'
];

$billing_terms = [
    'payment_due_days' => 30,
    'late_fee_percentage' => 5,
    'currency' => 'EUR',
    'language' => 'es'
];
?>
