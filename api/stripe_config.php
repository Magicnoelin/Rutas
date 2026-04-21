<?php
/**
 * Configuración de Stripe para Sistema de Membresías
 * Rutas Rurales - Producción
 */

// ============================================
// CONFIGURACIÓN DE STRIPE
// ============================================

// Claves de Stripe - PRODUCCIÓN
define('STRIPE_SECRET_KEY', 'sk_live_...'); // Reemplazar con clave secreta real
define('STRIPE_PUBLISHABLE_KEY', 'pk_live_...'); // Reemplazar con clave pública real
define('STRIPE_WEBHOOK_SECRET', 'whsec_...'); // Reemplazar con secreto de webhook real

// Configuración de precios (IDs de Stripe - se crearán automáticamente)
$stripe_price_ids = [
    // Alojamientos
    'basico-alojamiento-mensual' => 'price_...', // 10€/mes + IVA
    'premium-alojamiento-anual' => 'price_...',  // 50€/año + IVA
    
    // Restaurantes
    'basico-restaurante-mensual' => 'price_...', // 5€/mes + IVA
    'premium-restaurante-anual' => 'price_...',  // 50€/año + IVA
    
    // Apoyo a la plataforma (pagos únicos)
    'apoyo-basico' => 'price_...',      // 50€ + IVA
    'apoyo-avanzado' => 'price_...',    // 100€ + IVA
    'apoyo-premium' => 'price_...',     // 1000€ + IVA
];

// Configuración de productos en Stripe
$stripe_products = [
    'basico-alojamiento' => [
        'name' => 'Básico Alojamiento',
        'description' => 'Plan básico para alojamientos rurales - 10€/mes + IVA',
        'metadata' => ['plan_type' => 'alojamiento', 'max_accommodations' => '2', 'max_places' => '15']
    ],
    'premium-alojamiento' => [
        'name' => 'Premium Alojamiento',
        'description' => 'Plan premium para alojamientos rurales - 50€/año + IVA',
        'metadata' => ['plan_type' => 'alojamiento', 'max_accommodations' => '10', 'max_places' => '100']
    ],
    'basico-restaurante' => [
        'name' => 'Básico Restaurante',
        'description' => 'Plan básico para restaurantes - 5€/mes + IVA',
        'metadata' => ['plan_type' => 'restaurante', 'max_restaurants' => '1']
    ],
    'premium-restaurante' => [
        'name' => 'Premium Restaurante',
        'description' => 'Plan premium para restaurantes - 50€/año + IVA',
        'metadata' => ['plan_type' => 'restaurante', 'max_restaurants' => '3']
    ],
    'apoyo-basico' => [
        'name' => 'Apoyo Básico',
        'description' => 'Contribución básica para apoyar la plataforma - 50€ + IVA',
        'metadata' => ['plan_type' => 'apoyo_plataforma', 'payment_type' => 'one_time']
    ],
    'apoyo-avanzado' => [
        'name' => 'Apoyo Avanzado',
        'description' => 'Contribución avanzada para apoyar la plataforma - 100€ + IVA',
        'metadata' => ['plan_type' => 'apoyo_plataforma', 'payment_type' => 'one_time']
    ],
    'apoyo-premium' => [
        'name' => 'Apoyo Premium',
        'description' => 'Contribución premium para apoyar la plataforma - 1000€ + IVA',
        'metadata' => ['plan_type' => 'apoyo_plataforma', 'payment_type' => 'one_time']
    ]
];

// ============================================
// FUNCIONES DE STRIPE
// ============================================

/**
 * Inicializar cliente de Stripe
 */
function getStripeClient() {
    require_once 'vendor/autoload.php'; // Asegúrate de tener instalado stripe-php
    
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    \Stripe\Stripe::setApiVersion('2023-10-16'); // Usar versión estable
    
    return new \Stripe\StripeClient(STRIPE_SECRET_KEY);
}

/**
 * Crear o actualizar producto en Stripe
 */
function createOrUpdateStripeProduct($slug, $planData) {
    $stripe = getStripeClient();
    
    try {
        // Buscar producto existente por metadata
        $products = $stripe->products->all(['limit' => 100]);
        $existingProduct = null;
        
        foreach ($products->data as $product) {
            if (isset($product->metadata->slug) && $product->metadata->slug === $slug) {
                $existingProduct = $product;
                break;
            }
        }
        
        if ($existingProduct) {
            // Actualizar producto existente
            $product = $stripe->products->update($existingProduct->id, [
                'name' => $planData['name'],
                'description' => $planData['description'],
                'metadata' => array_merge($planData['metadata'], ['slug' => $slug])
            ]);
        } else {
            // Crear nuevo producto
            $product = $stripe->products->create([
                'name' => $planData['name'],
                'description' => $planData['description'],
                'metadata' => array_merge($planData['metadata'], ['slug' => $slug])
            ]);
        }
        
        return $product;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('Stripe Product Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Crear precio en Stripe
 */
function createStripePrice($productId, $amount, $currency = 'eur', $interval = null) {
    $stripe = getStripeClient();
    
    $priceData = [
        'product' => $productId,
        'unit_amount' => $amount * 100, // Convertir a céntimos
        'currency' => $currency,
        'metadata' => [
            'amount_eur' => $amount,
            'vat_rate' => '21%',
            'amount_with_vat' => $amount * 1.21
        ]
    ];
    
    if ($interval) {
        $priceData['recurring'] = ['interval' => $interval];
    }
    
    try {
        $price = $stripe->prices->create($priceData);
        return $price;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('Stripe Price Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Crear sesión de checkout
 */
function createCheckoutSession($customerEmail, $priceId, $successUrl, $cancelUrl, $metadata = []) {
    $stripe = getStripeClient();
    
    try {
        $sessionData = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription', // o 'payment' para pagos únicos
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $customerEmail,
            'metadata' => $metadata,
            'automatic_tax' => [
                'enabled' => true, // Stripe calculará automáticamente el IVA
            ],
            'locale' => 'es', // Español
        ];
        
        // Para pagos únicos, cambiar el modo
        if (isset($metadata['payment_type']) && $metadata['payment_type'] === 'one_time') {
            $sessionData['mode'] = 'payment';
        }
        
        $session = $stripe->checkout->sessions->create($sessionData);
        return $session;
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('Stripe Checkout Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Procesar webhook de Stripe
 */
function handleStripeWebhook() {
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $event = null;
    
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, STRIPE_WEBHOOK_SECRET
        );
    } catch(\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(400);
        exit();
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        http_response_code(400);
        exit();
    }
    
    // Procesar el evento
    switch ($event->type) {
        case 'checkout.session.completed':
            $session = $event->data->object;
            handleSuccessfulPayment($session);
            break;
            
        case 'invoice.paid':
            $invoice = $event->data->object;
            handleInvoicePaid($invoice);
            break;
            
        case 'invoice.payment_failed':
            $invoice = $event->data->object;
            handlePaymentFailed($invoice);
            break;
            
        case 'customer.subscription.deleted':
            $subscription = $event->data->object;
            handleSubscriptionCancelled($subscription);
            break;
    }
    
    http_response_code(200);
}

/**
 * Manejar pago exitoso
 */
function handleSuccessfulPayment($session) {
    // Aquí actualizarías la base de datos
    // $session->customer_email, $session->metadata, etc.
    
    error_log('Payment successful for session: ' . $session->id);
    
    // Ejemplo: Actualizar suscripción en base de datos
    // updateUserSubscription($session->metadata['user_id'], $session->metadata['plan_id'], 'active');
}

/**
 * Manejar factura pagada
 */
function handleInvoicePaid($invoice) {
    error_log('Invoice paid: ' . $invoice->id);
    
    // Aquí podrías generar factura en tu sistema
    // createInvoiceInDatabase($invoice);
}

// ============================================
// CONFIGURACIÓN DE IVA
// ============================================

// Tasas de IVA por país (España 21% por defecto)
$vat_rates = [
    'ES' => 21.00, // España
    'PT' => 23.00, // Portugal
    'FR' => 20.00, // Francia
    'DE' => 19.00, // Alemania
    'IT' => 22.00, // Italia
    'GB' => 20.00, // Reino Unido
    'US' => 0.00,  // Estados Unidos (sin IVA)
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

// Información de la empresa para facturas
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

// Términos de facturación
$billing_terms = [
    'payment_due_days' => 30,
    'late_fee_percentage' => 5,
    'currency' => 'EUR',
    'language' => 'es'
];

?>