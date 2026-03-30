<?php
/**
 * Test script to verify the crear.php fix
 */

// Simulate JSON data being sent from the frontend
$testData = [
    'Nombre' => 'Casa Rural Test',
    'Tipo' => 'Casa',
    'Direccion' => 'Calle Test 123, Vinuesa, Soria',
    'Plazas' => 6,
    'Telefono1' => '975123456',
    'Notaspublicas' => 'Descripción de prueba para el alojamiento',
    'Precio' => 120,
    'Telefono2' => '605249696',
    'Email' => 'test@example.com',
    'Web' => 'https://www.example.com',
    'Instagram' => '@test',
    'Booking' => 'https://www.booking.com/test',
    'Foto1' => 'https://example.com/photo1.jpg',
    'Foto2' => 'https://example.com/photo2.jpg',
    'recaptchaToken' => 'test-token'
];

// Convert to JSON
$jsonData = json_encode($testData);

// Set up the request
$url = 'api/crear.php';
$options = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => $jsonData
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Error making request to API\n";
} else {
    echo "API Response:\n";
    echo $result . "\n\n";

    $responseData = json_decode($result, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($responseData['success']) && $responseData['success']) {
            echo "✅ SUCCESS: The fix is working! Alojamiento created with ID: " . $responseData['data']['id'] . "\n";
        } else {
            echo "❌ ERROR: " . ($responseData['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ ERROR: Invalid JSON response from API\n";
    }
}