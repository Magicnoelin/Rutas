<?php
// Test simple para verificar que create_checkout_session.php funciona
session_start();
$_SESSION['user_id'] = 1; // Usuario de prueba

$testData = [
    'plan_id' => 2,
    'billing_cycle' => 'yearly',
    'success_url' => 'https://rutasrurales.io/test-success',
    'cancel_url' => 'https://rutasrurales.io/test-cancel'
];

echo "Testing create_checkout_session.php...\n";
echo "Data: " . json_encode($testData) . "\n\n";

$ch = curl_init('http://localhost/api/create_checkout_session.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "\n✅ SUCCESS! Checkout URL: " . $data['data']['checkout_url'] . "\n";
    } else {
        echo "\n❌ ERROR: " . ($data['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "\n❌ HTTP ERROR $httpCode\n";
}
?>