<?php
/**
 * Función para calcular precio dinámico del plan premium
 * 10€/mes por alojamiento o cada 15 plazas
 */

/**
 * Calcular precio mensual para plan premium dinámico
 * 
 * @param int $numAccommodations Número de alojamientos
 * @param int $totalPlaces Total de plazas
 * @return array Precio calculado
 */
function calculateDynamicPremiumPrice($numAccommodations, $totalPlaces) {
    // Precio base por alojamiento: 10€/mes
    $pricePerAccommodation = 10.00;
    
    // Precio por bloques de 15 plazas: 10€/mes por cada 15 plazas
    $pricePer15Places = 10.00;
    
    // Calcular precio por alojamientos
    $priceByAccommodations = $numAccommodations * $pricePerAccommodation;
    
    // Calcular precio por plazas (cada 15 plazas = 10€)
    $blocksOf15Places = ceil($totalPlaces / 15);
    $priceByPlaces = $blocksOf15Places * $pricePer15Places;
    
    // Tomar el mayor de los dos cálculos
    $monthlyPrice = max($priceByAccommodations, $priceByPlaces);
    
    // Precio anual (10 meses en lugar de 12 como descuento)
    $yearlyPrice = $monthlyPrice * 10;
    
    // Calcular IVA (21%)
    $vatRate = 21.00;
    $vatAmountMonthly = ($monthlyPrice * $vatRate) / 100;
    $vatAmountYearly = ($yearlyPrice * $vatRate) / 100;
    
    return [
        'monthly' => [
            'price_without_vat' => round($monthlyPrice, 2),
            'vat_rate' => $vatRate,
            'vat_amount' => round($vatAmountMonthly, 2),
            'price_with_vat' => round($monthlyPrice + $vatAmountMonthly, 2)
        ],
        'yearly' => [
            'price_without_vat' => round($yearlyPrice, 2),
            'vat_rate' => $vatRate,
            'vat_amount' => round($vatAmountYearly, 2),
            'price_with_vat' => round($yearlyPrice + $vatAmountYearly, 2)
        ],
        'calculation_details' => [
            'num_accommodations' => $numAccommodations,
            'total_places' => $totalPlaces,
            'price_by_accommodations' => round($priceByAccommodations, 2),
            'price_by_places' => round($priceByPlaces, 2),
            'blocks_of_15_places' => $blocksOf15Places,
            'applied_calculation' => $priceByAccommodations >= $priceByPlaces ? 'por_alojamientos' : 'por_plazas'
        ]
    ];
}

/**
 * Obtener precio actualizado para usuario premium
 * 
 * @param int $userId ID del usuario
 * @return array Precio calculado y detalles
 */
function getPremiumPriceForUser($userId) {
    require_once 'config.php';
    
    try {
        $pdo = getDBConnection();
        
        // Obtener número de alojamientos y plazas del usuario
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as num_accommodations,
                SUM(places) as total_places
            FROM accommodations 
            WHERE user_id = ? AND status IN ('active', 'pending')
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        $numAccommodations = $result['num_accommodations'] ?? 0;
        $totalPlaces = $result['total_places'] ?? 0;
        
        // Calcular precio dinámico
        $price = calculateDynamicPremiumPrice($numAccommodations, $totalPlaces);
        
        return [
            'success' => true,
            'user_id' => $userId,
            'current_usage' => [
                'accommodations' => (int)$numAccommodations,
                'places' => (int)$totalPlaces
            ],
            'pricing' => $price,
            'recommendation' => getPriceRecommendation($price, $numAccommodations, $totalPlaces)
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Error al calcular precio: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtener recomendación de precio
 */
function getPriceRecommendation($price, $numAccommodations, $totalPlaces) {
    $monthly = $price['monthly']['price_without_vat'];
    $yearly = $price['yearly']['price_without_vat'];
    
    // Calcular ahorro anual
    $monthlyYearlyCost = $monthly * 12;
    $yearlySavings = $monthlyYearlyCost - $yearly;
    $savingsPercentage = ($yearlySavings / $monthlyYearlyCost) * 100;
    
    $recommendation = "Mensual: {$monthly}€/mes | Anual: {$yearly}€/año";
    
    if ($yearlySavings > 0) {
        $recommendation .= " (Ahorras {$yearlySavings}€ anuales, {$savingsPercentage}%)";
    }
    
    // Recomendación basada en uso
    if ($numAccommodations <= 2 && $totalPlaces <= 15) {
        $recommendation .= " | Considera plan gratuito";
    } elseif ($numAccommodations <= 4 && $totalPlaces <= 30) {
        $recommendation .= " | Considera plan básico (10€/mes fijo)";
    }
    
    return $recommendation;
}

/**
 * Crear precio dinámico en Stripe para usuario premium
 * 
 * @param int $userId ID del usuario
 * @param string $billingCycle 'monthly' o 'yearly'
 * @return array Resultado con precio de Stripe
 */
function createDynamicStripePrice($userId, $billingCycle) {
    require_once 'stripe_config.php';
    
    $userPrice = getPremiumPriceForUser($userId);
    
    if (!$userPrice['success']) {
        return $userPrice;
    }
    
    $priceData = $userPrice['pricing'][$billingCycle];
    $amount = $priceData['price_without_vat'] * 100; // Stripe usa céntimos
    
    // Obtener producto premium de Stripe
    $stripe = getStripeClient();
    
    try {
        // Buscar producto premium
        $products = $stripe->products->all(['limit' => 100]);
        $premiumProduct = null;
        
        foreach ($products->data as $product) {
            if (isset($product->metadata->slug) && $product->metadata->slug === 'premium-alojamiento') {
                $premiumProduct = $product;
                break;
            }
        }
        
        if (!$premiumProduct) {
            return [
                'success' => false,
                'error' => 'Producto premium no encontrado en Stripe'
            ];
        }
        
        // Crear precio personalizado
        $priceParams = [
            'product' => $premiumProduct->id,
            'unit_amount' => $amount,
            'currency' => 'eur',
            'metadata' => [
                'user_id' => $userId,
                'calculation_type' => 'dynamic',
                'num_accommodations' => $userPrice['current_usage']['accommodations'],
                'total_places' => $userPrice['current_usage']['places'],
                'billing_cycle' => $billingCycle,
                'price_details' => json_encode($userPrice['pricing']['calculation_details'])
            ]
        ];
        
        if ($billingCycle === 'monthly') {
            $priceParams['recurring'] = ['interval' => 'month'];
        } elseif ($billingCycle === 'yearly') {
            $priceParams['recurring'] = ['interval' => 'year'];
        }
        
        $stripePrice = $stripe->prices->create($priceParams);
        
        return [
            'success' => true,
            'stripe_price_id' => $stripePrice->id,
            'price_data' => $priceData,
            'user_data' => $userPrice['current_usage'],
            'checkout_url' => null // Se generará después
        ];
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        return [
            'success' => false,
            'error' => 'Error de Stripe: ' . $e->getMessage()
        ];
    }
}

/**
 * API endpoint para calcular precio premium
 */
if (isset($_GET['action']) && $_GET['action'] === 'calculate') {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['error' => 'Usuario no autenticado'], JSON_PRETTY_PRINT);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $result = getPremiumPriceForUser($userId);
    
    header('Content-Type: application/json');
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// Ejemplo de uso:
// $price = calculateDynamicPremiumPrice(3, 40);
// echo "Precio mensual: " . $price['monthly']['price_with_vat'] . "€\n";
// echo "Precio anual: " . $price['yearly']['price_with_vat'] . "€\n";

?>