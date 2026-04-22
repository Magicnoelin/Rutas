<?php
// Script de prueba para verificar el funcionamiento de create_checkout_session.php
require_once 'api/config.php';

echo "<h1>Test de Checkout</h1>";

// Simular sesión de usuario
session_start();
$_SESSION['user_id'] = 1; // ID de usuario de prueba

// Datos de prueba
$testData = [
    'plan_id' => 2,
    'billing_cycle' => 'yearly',
    'success_url' => 'https://rutasrurales.io/test-success',
    'cancel_url' => 'https://rutasrurales.io/test-cancel'
];

echo "<h2>Datos de prueba:</h2>";
echo "<pre>" . print_r($testData, true) . "</pre>";

// Probar conexión a BD
try {
    $pdo = getDBConnection();
    echo "<p style='color: green;'>✓ Conexión a BD exitosa</p>";
    
    // Verificar si existe la tabla membership_plans
    $stmt = $pdo->query("SHOW TABLES LIKE 'membership_plans'");
    $tableExists = $stmt->fetch();
    if ($tableExists) {
        echo "<p style='color: green;'>✓ Tabla membership_plans existe</p>";
        
        // Verificar contenido
        $stmt = $pdo->query("SELECT * FROM membership_plans WHERE id = 2");
        $plan = $stmt->fetch();
        if ($plan) {
            echo "<p style='color: green;'>✓ Plan id=2 encontrado:</p>";
            echo "<pre>" . print_r($plan, true) . "</pre>";
        } else {
            echo "<p style='color: orange;'>⚠ Plan id=2 no encontrado en la BD</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Tabla membership_plans NO existe</p>";
    }
    
    // Verificar si existe la tabla payment_intents
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_intents'");
    $tableExists = $stmt->fetch();
    if ($tableExists) {
        echo "<p style='color: green;'>✓ Tabla payment_intents existe</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Tabla payment_intents NO existe</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error de conexión a BD: " . $e->getMessage() . "</p>";
}

// Probar la función calculateVAT
if (function_exists('calculateVAT')) {
    $vatTest = calculateVAT(50);
    echo "<p style='color: green;'>✓ Función calculateVAT disponible:</p>";
    echo "<pre>" . print_r($vatTest, true) . "</pre>";
} else {
    echo "<p style='color: orange;'>⚠ Función calculateVAT no definida</p>";
}

// Probar la función createCheckoutSession
echo "<h2>Probar llamada a create_checkout_session.php</h2>";
echo "<form method='POST' action='api/create_checkout_session.php' id='testForm'>";
echo "<input type='hidden' name='plan_id' value='2'>";
echo "<input type='hidden' name='billing_cycle' value='yearly'>";
echo "<input type='hidden' name='success_url' value='https://rutasrurales.io/test-success'>";
echo "<input type='hidden' name='cancel_url' value='https://rutasrurales.io/test-cancel'>";
echo "<button type='submit'>Probar API (POST directo)</button>";
echo "</form>";

echo "<script>
document.getElementById('testForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        plan_id: formData.get('plan_id'),
        billing_cycle: formData.get('billing_cycle'),
        success_url: formData.get('success_url'),
        cancel_url: formData.get('cancel_url')
    };
    
    try {
        const response = await fetch('api/create_checkout_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const resultDiv = document.getElementById('result');
        if (response.ok) {
            const data = await response.json();
            resultDiv.innerHTML = '<p style=\"color: green;\">✓ API respondió correctamente:</p><pre>' + JSON.stringify(data, null, 2) + '</pre>';
        } else {
            const errorText = await response.text();
            resultDiv.innerHTML = '<p style=\"color: red;\">✗ Error HTTP ' + response.status + ':</p><pre>' + errorText + '</pre>';
        }
    } catch (error) {
        document.getElementById('result').innerHTML = '<p style=\"color: red;\">✗ Error de conexión: ' + error + '</p>';
    }
});
</script>";

echo "<div id='result'></div>";
?>