<?php
/**
 * Diagnóstico de Stripe en Producción
 * SUBIR AL SERVIDOR y acceder via: https://rutasrurales.io/test_stripe_live.php
 * BORRAR después de verificar que funciona
 */

// Seguridad básica: solo accesible con clave
$accessKey = $_GET['key'] ?? '';
if ($accessKey !== 'rutasrurales2026') {
    die('Acceso denegado. Usa ?key=rutasrurales2026');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico Stripe - Rutas Rurales</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #eee; }
        .ok { color: #4ade80; }
        .error { color: #f87171; }
        .warn { color: #fbbf24; }
        .section { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #0f3460; }
        h2 { color: #e94560; }
        pre { background: #0f3460; padding: 10px; border-radius: 5px; overflow-x: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
<h1>🔍 Diagnóstico Stripe - Rutas Rurales</h1>

<?php

// ============================================================
// 1. VERIFICAR ARCHIVOS
// ============================================================
echo '<div class="section"><h2>1. Archivos PHP</h2>';

$files = [
    'api/config.php',
    'api/stripe_config.php',
    'api/create_checkout_session.php',
    'api/stripe_webhook.php',
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $mtime = date('d/m/Y H:i:s', filemtime($path));
        echo "<span class='ok'>✅ $file</span> ($size bytes, modificado: $mtime)<br>";
    } else {
        echo "<span class='error'>❌ $file — NO EXISTE</span><br>";
    }
}
echo '</div>';

// ============================================================
// 2. VERIFICAR EXTENSIONES PHP
// ============================================================
echo '<div class="section"><h2>2. Extensiones PHP</h2>';
$extensions = ['curl', 'json', 'pdo', 'pdo_mysql', 'openssl'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='ok'>✅ $ext</span><br>";
    } else {
        echo "<span class='error'>❌ $ext — NO DISPONIBLE</span><br>";
    }
}
echo 'PHP Version: ' . phpversion() . '<br>';
echo '</div>';

// ============================================================
// 3. CARGAR CONFIGURACIÓN DE STRIPE
// ============================================================
echo '<div class="section"><h2>3. Configuración de Stripe</h2>';

try {
    require_once __DIR__ . '/api/stripe_config.php';
    
    $sk = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : 'NO DEFINIDA';
    $pk = defined('STRIPE_PUBLISHABLE_KEY') ? STRIPE_PUBLISHABLE_KEY : 'NO DEFINIDA';
    $wh = defined('STRIPE_WEBHOOK_SECRET') ? STRIPE_WEBHOOK_SECRET : 'NO DEFINIDA';
    
    // Mostrar solo primeros/últimos caracteres por seguridad
    $skDisplay = strlen($sk) > 10 ? substr($sk, 0, 12) . '...' . substr($sk, -4) : $sk;
    $pkDisplay = strlen($pk) > 10 ? substr($pk, 0, 12) . '...' . substr($pk, -4) : $pk;
    
    echo "Secret Key: <span class='" . (strpos($sk, 'sk_live_') === 0 ? 'ok' : 'error') . "'>$skDisplay</span><br>";
    echo "Publishable Key: <span class='" . (strpos($pk, 'pk_live_') === 0 ? 'ok' : 'error') . "'>$pkDisplay</span><br>";
    
    if ($wh === 'whsec_PENDIENTE_CONFIGURAR_EN_STRIPE_DASHBOARD') {
        echo "Webhook Secret: <span class='warn'>⚠️ PENDIENTE DE CONFIGURAR</span><br>";
    } else {
        echo "Webhook Secret: <span class='ok'>✅ Configurado</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error cargando stripe_config.php: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}
echo '</div>';

// ============================================================
// 4. TEST DE CONEXIÓN A STRIPE API
// ============================================================
echo '<div class="section"><h2>4. Test de Conexión a Stripe API</h2>';

if (!extension_loaded('curl')) {
    echo "<span class='error'>❌ cURL no disponible — no se puede conectar a Stripe</span><br>";
} elseif (!defined('STRIPE_SECRET_KEY') || strpos(STRIPE_SECRET_KEY, 'sk_live_') !== 0) {
    echo "<span class='error'>❌ Secret Key no configurada correctamente</span><br>";
} else {
    // Test: obtener información de la cuenta
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/account');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Stripe-Version: 2023-10-16']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "<span class='error'>❌ Error cURL: " . htmlspecialchars($curlError) . "</span><br>";
    } elseif ($httpCode === 200) {
        $account = json_decode($response, true);
        echo "<span class='ok'>✅ Conexión exitosa a Stripe API</span><br>";
        echo "Cuenta: " . htmlspecialchars($account['email'] ?? 'N/A') . "<br>";
        echo "País: " . htmlspecialchars($account['country'] ?? 'N/A') . "<br>";
        echo "Modo: <span class='" . ($account['livemode'] ? 'ok' : 'warn') . "'>" . ($account['livemode'] ? '🟢 PRODUCCIÓN (live)' : '🟡 TEST') . "</span><br>";
    } else {
        $error = json_decode($response, true);
        echo "<span class='error'>❌ Error HTTP $httpCode: " . htmlspecialchars($error['error']['message'] ?? $response) . "</span><br>";
    }
}
echo '</div>';

// ============================================================
// 5. TEST DE CREACIÓN DE SESIÓN DE CHECKOUT
// ============================================================
echo '<div class="section"><h2>5. Test de Sesión de Checkout (0.50€)</h2>';

if (defined('STRIPE_SECRET_KEY') && strpos(STRIPE_SECRET_KEY, 'sk_live_') === 0 && extension_loaded('curl')) {
    
    $testData = [
        'payment_method_types[]' => 'card',
        'mode' => 'payment',
        'success_url' => 'https://rutasrurales.io/user-dashboard.html?payment=success&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://rutasrurales.io/user-dashboard.html?payment=canceled',
        'customer_email' => 'test@rutasrurales.io',
        'locale' => 'es',
        'line_items[0][price_data][currency]' => 'eur',
        'line_items[0][price_data][product_data][name]' => 'Test Plan (0.50€)',
        'line_items[0][price_data][unit_amount]' => 50, // 0.50€ en céntimos
        'line_items[0][quantity]' => 1,
        'metadata[test]' => 'true',
        'metadata[user_id]' => '0',
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($testData));
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Stripe-Version: 2023-10-16'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "<span class='error'>❌ Error cURL: " . htmlspecialchars($curlError) . "</span><br>";
    } elseif ($httpCode === 200) {
        $session = json_decode($response, true);
        echo "<span class='ok'>✅ Sesión de checkout creada correctamente</span><br>";
        echo "Session ID: " . htmlspecialchars($session['id'] ?? 'N/A') . "<br>";
        echo "URL: <a href='" . htmlspecialchars($session['url'] ?? '#') . "' target='_blank' style='color:#60a5fa;'>" . htmlspecialchars(substr($session['url'] ?? '', 0, 60)) . "...</a><br>";
        echo "<br><span class='ok'>🎉 ¡Stripe está funcionando correctamente en producción!</span><br>";
    } else {
        $error = json_decode($response, true);
        echo "<span class='error'>❌ Error HTTP $httpCode</span><br>";
        echo "<pre>" . htmlspecialchars(json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    }
} else {
    echo "<span class='warn'>⚠️ Saltando test — Stripe no configurado o cURL no disponible</span><br>";
}
echo '</div>';

// ============================================================
// 6. VERIFICAR BASE DE DATOS - TABLAS NECESARIAS
// ============================================================
echo '<div class="section"><h2>6. Base de Datos — Tablas del Sistema de Membresías</h2>';

try {
    require_once __DIR__ . '/api/config.php';
    $pdo = getDBConnection();
    
    $tables = [
        'membership_plans'   => 'Planes de membresía',
        'user_subscriptions' => 'Suscripciones de usuarios',
        'payment_intents'    => 'Intenciones de pago',
        'invoices'           => 'Facturas',
        'payment_failures'   => 'Fallos de pago (opcional)',
    ];
    
    foreach ($tables as $table => $desc) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM `$table`");
            $row = $stmt->fetch();
            echo "<span class='ok'>✅ $table</span> — $desc ({$row['cnt']} registros)<br>";
        } catch (PDOException $e) {
            echo "<span class='error'>❌ $table — NO EXISTE</span> ($desc)<br>";
        }
    }
    
    // Verificar columnas de users necesarias
    echo "<br><strong>Columnas en tabla 'users':</strong><br>";
    $userCols = ['stripe_customer_id', 'stripe_subscription_id', 'membership_type', 'membership_status', 'membership_start_date', 'membership_end_date'];
    $stmt = $pdo->query("DESCRIBE users");
    $existingCols = array_column($stmt->fetchAll(), 'Field');
    
    foreach ($userCols as $col) {
        if (in_array($col, $existingCols)) {
            echo "<span class='ok'>✅ users.$col</span><br>";
        } else {
            echo "<span class='error'>❌ users.$col — FALTA ESTA COLUMNA</span><br>";
        }
    }
    
    // Mostrar planes existentes
    echo "<br><strong>Planes en membership_plans:</strong><br>";
    try {
        $stmt = $pdo->query("SELECT id, name, price_monthly, price_yearly, plan_type, status FROM membership_plans ORDER BY id");
        $plans = $stmt->fetchAll();
        if ($plans) {
            echo "<pre>";
            foreach ($plans as $p) {
                $status = $p['status'] === 'active' ? '✅' : '⚠️';
                echo "$status ID:{$p['id']} | {$p['name']} | {$p['price_monthly']}€/mes | {$p['price_yearly']}€/año | {$p['plan_type']} | {$p['status']}\n";
            }
            echo "</pre>";
        } else {
            echo "<span class='warn'>⚠️ No hay planes en membership_plans</span><br>";
        }
    } catch (PDOException $e) {
        echo "<span class='error'>❌ Error leyendo planes: " . htmlspecialchars($e->getMessage()) . "</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Error de BD: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}
echo '</div>';

// ============================================================
// 7. RESUMEN Y PRÓXIMOS PASOS
// ============================================================
echo '<div class="section"><h2>7. Resumen</h2>';
echo '<p>Si todos los checks son ✅, el sistema está listo para procesar pagos reales.</p>';
echo '<p><strong>Mínimo de Stripe:</strong> 0.50€ por transacción (no 0.01€)</p>';
echo '<p><strong>Para suscripciones:</strong> mínimo 0.50€/mes o 0.50€/año</p>';
echo '<p style="color:#f87171;"><strong>⚠️ IMPORTANTE: Borra este archivo del servidor después de verificar.</strong></p>';
echo '</div>';
?>

</body>
</html>
