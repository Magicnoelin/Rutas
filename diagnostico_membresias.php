<?php
/**
 * Script de diagnóstico para el sistema de membresías
 * Acceder desde: https://rutasrurales.io/diagnostico_membresias.php
 */

require_once 'api/config.php';

header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Sistema de Membresías</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2f5233; border-bottom: 2px solid #2f5233; padding-bottom: 10px; }
        h2 { color: #4a7c4e; margin-top: 30px; }
        .section { margin: 20px 0; padding: 15px; border-left: 4px solid #2f5233; background: #f9f9f9; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #2f5233; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico Sistema de Membresías</h1>
        <p><strong>Fecha:</strong> ' . date('Y-m-d H:i:s') . '</p>';

// 1. Verificar conexión a BD
echo '<div class="section">
        <h2>1. Conexión a Base de Datos</h2>';

try {
    $pdo = getDBConnection();
    echo '<p class="success">✅ Conexión a BD exitosa</p>';
    
    // Verificar tablas de membresía
    $tables = ['membership_plans', 'payment_intents', 'user_memberships'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        if ($exists) {
            echo "<p class='success'>✅ Tabla <strong>$table</strong> existe</p>";
        } else {
            echo "<p class='error'>❌ Tabla <strong>$table</strong> NO existe</p>";
        }
    }
} catch (PDOException $e) {
    echo '<p class="error">❌ Error de conexión a BD: ' . $e->getMessage() . '</p>';
}

echo '</div>';

// 2. Verificar planes de membresía
echo '<div class="section">
        <h2>2. Planes de Membresía en BD</h2>';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT id, name, price_monthly, price_yearly, 
               stripe_monthly_price_id, stripe_yearly_price_id
        FROM membership_plans 
        ORDER BY id
    ");
    $plans = $stmt->fetchAll();
    
    if ($plans) {
        echo '<table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Precio Mensual</th>
                    <th>Precio Anual</th>
                    <th>Stripe Monthly ID</th>
                    <th>Stripe Yearly ID</th>
                </tr>';
        foreach ($plans as $plan) {
            echo '<tr>
                    <td>' . $plan['id'] . '</td>
                    <td>' . htmlspecialchars($plan['name']) . '</td>
                    <td>' . $plan['price_monthly'] . '€</td>
                    <td>' . $plan['price_yearly'] . '€</td>
                    <td>' . ($plan['stripe_monthly_price_id'] ? '✅' : '❌') . '</td>
                    <td>' . ($plan['stripe_yearly_price_id'] ? '✅' : '❌') . '</td>
                  </tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠ No hay planes en la tabla membership_plans</p>';
        echo '<p>Usando planes por defecto de get_membership_options.php:</p>';
        
        // Mostrar planes por defecto
        $defaultPlans = [
            ['id' => 1, 'name' => 'Gratuito Alojamiento', 'price_monthly' => 0, 'price_yearly' => 0],
            ['id' => 2, 'name' => 'Básico Alojamiento', 'price_monthly' => 10.00, 'price_yearly' => 50.00],
            ['id' => 3, 'name' => 'Premium Alojamiento', 'price_monthly' => 10.00, 'price_yearly' => 100.00]
        ];
        
        echo '<table>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Precio Mensual</th>
                    <th>Precio Anual</th>
                </tr>';
        foreach ($defaultPlans as $plan) {
            echo '<tr>
                    <td>' . $plan['id'] . '</td>
                    <td>' . $plan['name'] . '</td>
                    <td>' . $plan['price_monthly'] . '€</td>
                    <td>' . $plan['price_yearly'] . '€</td>
                  </tr>';
        }
        echo '</table>';
    }
} catch (PDOException $e) {
    echo '<p class="error">❌ Error al consultar planes: ' . $e->getMessage() . '</p>';
}

echo '</div>';

// 3. Verificar API get_membership_options.php
echo '<div class="section">
        <h2>3. API get_membership_options.php</h2>';

$apiUrl = 'https://rutasrurales.io/api/get_membership_options.php';
echo '<p><strong>URL:</strong> <a href="' . $apiUrl . '" target="_blank">' . $apiUrl . '</a></p>';

// Intentar llamar a la API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo '<p class="success">✅ API responde correctamente (HTTP ' . $httpCode . ')</p>';
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo '<p><strong>Planes devueltos:</strong></p>';
        echo '<pre>' . json_encode($data['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
    } else {
        echo '<p class="error">❌ API devuelve error: ' . ($data['error'] ?? 'Desconocido') . '</p>';
    }
} else {
    echo '<p class="error">❌ API no responde (HTTP ' . $httpCode . ')</p>';
    echo '<p><strong>Respuesta:</strong> ' . htmlspecialchars($response) . '</p>';
}

echo '</div>';

// 4. Verificar Stripe config
echo '<div class="section">
        <h2>4. Configuración de Stripe</h2>';

$stripeConfigFile = __DIR__ . '/api/stripe_config.php';
if (file_exists($stripeConfigFile)) {
    echo '<p class="success">✅ Archivo stripe_config.php existe</p>';
    
    // Leer contenido (sin incluir claves reales)
    $content = file_get_contents($stripeConfigFile);
    
    // Verificar placeholders
    if (strpos($content, 'sk_live_...') !== false) {
        echo '<p class="warning">⚠ Stripe tiene claves placeholder (sk_live_...)</p>';
        echo '<p>Modo: <strong>SIMULADO</strong> - Redirigirá a simulated-checkout.html</p>';
    } else {
        echo '<p class="success">✅ Stripe parece configurado con claves reales</p>';
    }
    
    // Verificar función createCheckoutSession
    if (strpos($content, 'function createCheckoutSession') !== false) {
        echo '<p class="success">✅ Función createCheckoutSession() definida</p>';
    } else {
        echo '<p class="error">❌ Función createCheckoutSession() NO definida</p>';
    }
} else {
    echo '<p class="error">❌ Archivo stripe_config.php NO existe</p>';
}

echo '</div>';

// 5. Verificar archivo simulated-checkout.html
echo '<div class="section">
        <h2>5. Archivo simulated-checkout.html</h2>';

$simulatedFile = __DIR__ . '/simulated-checkout.html';
if (file_exists($simulatedFile)) {
    echo '<p class="success">✅ Archivo simulated-checkout.html existe</p>';
    echo '<p><strong>URL:</strong> <a href="https://rutasrurales.io/simulated-checkout.html" target="_blank">https://rutasrurales.io/simulated-checkout.html</a></p>';
    
    // Verificar tamaño
    $size = filesize($simulatedFile);
    echo '<p><strong>Tamaño:</strong> ' . round($size / 1024, 2) . ' KB</p>';
} else {
    echo '<p class="error">❌ Archivo simulated-checkout.html NO existe</p>';
    echo '<p><strong>Acción requerida:</strong> Subir simulated-checkout.html al servidor</p>';
}

echo '</div>';

// 6. Problema reportado por el usuario
echo '<div class="section">
        <h2>6. Problema Reportado</h2>
        <p><strong>URL generada:</strong> https://rutasrurales.io/simulated-checkout.html?session_id=cs_test_...&plan_id=2&billing_cycle=monthly&plan_name=Premium&amount=9.99&total_amount=12.0879...</p>
        <p><strong>Problemas identificados:</strong></p>
        <ol>
            <li><span class="error">❌ plan_name=Premium</span> pero plan_id=2 debería ser "Básico Alojamiento"</li>
            <li><span class="error">❌ amount=9.99</span> pero debería ser 10.00€</li>
            <li><span class="warning">⚠ simulated-checkout.html puede no estar en el servidor</span></li>
        </ol>
        <p><strong>Causas posibles:</strong></p>
        <ul>
            <li>Tabla membership_plans en producción tiene datos incorrectos</li>
            <li>Versión antigua de create_checkout_session.php en producción</li>
            <li>Cache del navegador mostrando planes antiguos</li>
        </ul>
        <p><strong>Soluciones:</strong></p>
        <ol>
            <li>Subir simulated-checkout.html al servidor</li>
            <li>Actualizar create_checkout_session.php y get_membership_options.php en producción</li>
            <li>Ejecutar script SQL para corregir datos en membership_plans</li>
            <li>Usuario debe presionar Ctrl+F5 para limpiar cache</li>
        </ol>
    </div>';

// 7. Script SQL para corregir datos
echo '<div class="section">
        <h2>7. Script SQL para Corregir Planes</h2>
        <p>Ejecutar en phpMyAdmin o MySQL:</p>
        <pre>
-- Corregir planes de membresía
UPDATE membership_plans SET 
    name = CASE id
        WHEN 1 THEN "Gratuito Alojamiento"
        WHEN 2 THEN "Básico Alojamiento" 
        WHEN 3 THEN "Premium Alojamiento"
    END,
    price_monthly = CASE id
        WHEN 1 THEN 0.00
        WHEN 2 THEN 10.00
        WHEN 3 THEN 10.00
    END,
    price_yearly = CASE id
        WHEN 1 THEN 0.00
        WHEN 2 THEN 50.00
        WHEN 3 THEN 100.00
    END
WHERE id IN (1, 2, 3);

-- Verificar cambios
SELECT * FROM membership_plans ORDER BY id;
        </pre>
    </div>';

echo '</div></body></html>';
?>