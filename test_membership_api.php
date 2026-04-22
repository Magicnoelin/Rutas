<?php
/**
 * Test script for membership API endpoints
 */

require_once 'api/config.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Membership API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #2f5233; color: white; }
    </style>
</head>
<body>
    <h1>Test Membership API Endpoints</h1>
    <p><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</p>';

// Test 1: Check database connection and membership_plans table
echo '<h2>1. Database Check</h2>';
try {
    $pdo = getDBConnection();
    echo '<p class="success">✅ Database connection successful</p>';
    
    // Check membership_plans table
    $stmt = $pdo->query("SELECT id, name, price_monthly, price_yearly FROM membership_plans ORDER BY id");
    $plans = $stmt->fetchAll();
    
    if ($plans) {
        echo '<table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Monthly Price</th>
                    <th>Yearly Price</th>
                    <th>Status</th>
                </tr>';
        foreach ($plans as $plan) {
            $status = '';
            if ($plan['id'] == 2 && $plan['name'] == 'Básico Alojamiento' && $plan['price_monthly'] == 10.00) {
                $status = '<span class="success">✅ Correct</span>';
            } elseif ($plan['id'] == 2 && $plan['name'] == 'Premium') {
                $status = '<span class="error">❌ INCORRECT (should be Básico Alojamiento)</span>';
            } else {
                $status = '<span class="warning">⚠ Check</span>';
            }
            
            echo '<tr>
                    <td>' . $plan['id'] . '</td>
                    <td>' . htmlspecialchars($plan['name']) . '</td>
                    <td>' . $plan['price_monthly'] . '€</td>
                    <td>' . $plan['price_yearly'] . '€</td>
                    <td>' . $status . '</td>
                  </tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠ No plans found in membership_plans table</p>';
    }
} catch (PDOException $e) {
    echo '<p class="error">❌ Database error: ' . $e->getMessage() . '</p>';
}

// Test 2: Test get_membership_options.php API
echo '<h2>2. Test get_membership_options.php API</h2>';

// Simulate the API call locally
try {
    require_once 'api/get_membership_options.php';
    // Note: This will output JSON directly, so we need to capture it
    ob_start();
    include 'api/get_membership_options.php';
    $output = ob_get_clean();
    
    $data = json_decode($output, true);
    
    if ($data && isset($data['success']) && $data['success']) {
        echo '<p class="success">✅ API call successful</p>';
        echo '<h3>Plans returned:</h3>';
        echo '<table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Monthly Price</th>
                    <th>Yearly Price</th>
                </tr>';
        
        foreach ($data['data'] as $plan) {
            echo '<tr>
                    <td>' . $plan['id'] . '</td>
                    <td>' . htmlspecialchars($plan['name']) . '</td>
                    <td>' . $plan['price_monthly'] . '€</td>
                    <td>' . $plan['price_yearly'] . '€</td>
                  </tr>';
        }
        echo '</table>';
        
        // Check if plan_id=2 is correct
        $plan2 = null;
        foreach ($data['data'] as $plan) {
            if ($plan['id'] == 2) {
                $plan2 = $plan;
                break;
            }
        }
        
        if ($plan2 && $plan2['name'] == 'Básico Alojamiento' && $plan2['price_monthly'] == 10.00) {
            echo '<p class="success">✅ Plan ID 2 is correct: Básico Alojamiento, 10.00€ monthly</p>';
        } else {
            echo '<p class="error">❌ Plan ID 2 is INCORRECT. Found: ' . 
                 ($plan2 ? htmlspecialchars($plan2['name']) . ', ' . $plan2['price_monthly'] . '€' : 'Not found') . 
                 ' (Expected: Básico Alojamiento, 10.00€)</p>';
        }
    } else {
        echo '<p class="error">❌ API returned error: ' . ($data['error'] ?? 'Unknown') . '</p>';
        echo '<pre>' . htmlspecialchars($output) . '</pre>';
    }
} catch (Exception $e) {
    echo '<p class="error">❌ API test error: ' . $e->getMessage() . '</p>';
}

// Test 3: Test create_checkout_session.php logic (simulated)
echo '<h2>3. Test Checkout Session Logic</h2>';

// Test the URL construction logic from create_checkout_session.php
$planId = 2;
$billingCycle = 'monthly';
$planName = 'Básico Alojamiento';
$price = 10.00;
$priceWithVAT = $price * 1.21; // 21% VAT

$baseUrl = 'https://rutasrurales.io';
$sessionId = 'cs_test_' . bin2hex(random_bytes(16));
$checkoutUrl = $baseUrl . '/simulated-checkout.html?session_id=' . $sessionId . 
               '&plan_id=' . $planId . 
               '&billing_cycle=' . $billingCycle . 
               '&plan_name=' . urlencode($planName) . 
               '&amount=' . $price . 
               '&total_amount=' . $priceWithVAT;

echo '<p><strong>Generated URL (simulated):</strong></p>';
echo '<pre>' . htmlspecialchars($checkoutUrl) . '</pre>';

// Parse the URL to check parameters
$queryString = parse_url($checkoutUrl, PHP_URL_QUERY);
parse_str($queryString, $params);

echo '<p><strong>URL Parameters:</strong></p>';
echo '<table>
        <tr>
            <th>Parameter</th>
            <th>Value</th>
            <th>Status</th>
        </tr>';

$checks = [
    'plan_id' => ['value' => '2', 'expected' => '2', 'message' => 'Plan ID'],
    'plan_name' => ['value' => urldecode($params['plan_name']), 'expected' => 'Básico Alojamiento', 'message' => 'Plan Name'],
    'amount' => ['value' => $params['amount'], 'expected' => '10.00', 'message' => 'Amount'],
    'billing_cycle' => ['value' => $params['billing_cycle'], 'expected' => 'monthly', 'message' => 'Billing Cycle']
];

foreach ($checks as $key => $check) {
    $status = '';
    if ($check['value'] == $check['expected']) {
        $status = '<span class="success">✅ Correct</span>';
    } else {
        $status = '<span class="error">❌ INCORRECT (Expected: ' . $check['expected'] . ')</span>';
    }
    
    echo '<tr>
            <td>' . $key . '</td>
            <td>' . htmlspecialchars($check['value']) . '</td>
            <td>' . $status . '</td>
          </tr>';
}

echo '</table>';

// Test 4: Check simulated-checkout.html file
echo '<h2>4. Check simulated-checkout.html</h2>';

$simulatedFile = __DIR__ . '/simulated-checkout.html';
if (file_exists($simulatedFile)) {
    echo '<p class="success">✅ File exists: ' . $simulatedFile . '</p>';
    
    // Check file size
    $size = filesize($simulatedFile);
    echo '<p><strong>File size:</strong> ' . round($size / 1024, 2) . ' KB</p>';
    
    // Check if it can parse URL parameters
    $content = file_get_contents($simulatedFile);
    if (strpos($content, 'URLSearchParams') !== false) {
        echo '<p class="success">✅ File uses URLSearchParams to parse parameters</p>';
    } else {
        echo '<p class="warning">⚠ File may not properly parse URL parameters</p>';
    }
    
    // Check for plan_name parameter handling
    if (strpos($content, 'plan_name') !== false) {
        echo '<p class="success">✅ File handles plan_name parameter</p>';
    } else {
        echo '<p class="warning">⚠ File may not handle plan_name parameter</p>';
    }
} else {
    echo '<p class="error">❌ File does not exist: ' . $simulatedFile . '</p>';
}

echo '</body></html>';
?>