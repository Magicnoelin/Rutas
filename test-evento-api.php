<?php
/**
 * DIAGNÓSTICO: Test de la API evento-data.php
 * Acceder a: https://rutasrurales.io/test-evento-api.php
 * BORRAR después de usar
 */
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Test API Evento</title>
<style>
body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
.ok { color: #0f0; } .err { color: #f00; } .warn { color: #ff0; }
pre { background: #000; padding: 10px; border-radius: 4px; overflow: auto; font-size: 12px; }
h2 { color: #0af; border-bottom: 1px solid #333; padding-bottom: 5px; }
</style>
</head>
<body>
<h1>🔍 Diagnóstico API evento-data.php</h1>

<?php
// Test 1: ¿Existe el archivo?
$apiFile = __DIR__ . '/api/evento-data.php';
echo "<h2>1. Archivo api/evento-data.php</h2>";
if (file_exists($apiFile)) {
    echo "<p class='ok'>✅ Existe</p>";
    $content = file_get_contents($apiFile);
    // Buscar require_once
    preg_match('/require_once\s+[\'"]([^\'"]+)[\'"]/', $content, $m);
    echo "<p>require_once: <strong>" . htmlspecialchars($m[1] ?? 'no encontrado') . "</strong></p>";
    if (strpos($content, '../../api/config.php') !== false) {
        echo "<p class='err'>❌ FALLO: Tiene ruta incorrecta '../../api/config.php'</p>";
    } else {
        echo "<p class='ok'>✅ Ruta require_once correcta</p>";
    }
} else {
    echo "<p class='err'>❌ NO EXISTE - Necesitas subir api/evento-data.php por FileZilla</p>";
}

// Test 2: ¿Existe config.php?
echo "<h2>2. Archivo api/config.php</h2>";
$configFile = __DIR__ . '/api/config.php';
if (file_exists($configFile)) {
    echo "<p class='ok'>✅ Existe</p>";
} else {
    echo "<p class='err'>❌ NO EXISTE</p>";
}

// Test 3: Test directo de la API
echo "<h2>3. Test llamada API (modo nearby)</h2>";
$testSlug = 'visita-catedral-tarazona-2026-patrimonio';
$apiUrl = "https://rutasrurales.io/api/evento-data.php?slug={$testSlug}&mode=nearby&prov=Zaragoza";
echo "<p>URL: <a href='$apiUrl' target='_blank' style='color:#0af;'>$apiUrl</a></p>";

// Hacer llamada local
$localUrl = "http://localhost/api/evento-data.php?slug={$testSlug}&mode=nearby&prov=Zaragoza";
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Status: <strong class='" . ($httpCode == 200 ? 'ok' : 'err') . "'>{$httpCode}</strong></p>";
if ($error) echo "<p class='err'>cURL Error: $error</p>";

if ($response) {
    $json = json_decode($response, true);
    if ($json) {
        echo "<p class='ok'>✅ JSON válido</p>";
        if (isset($json['success'])) {
            echo "<p class='ok'>✅ success: true</p>";
            $aloj = count($json['data']['alojamientos'] ?? []);
            $lug = count($json['data']['lugares'] ?? []);
            $act = count($json['data']['actividades'] ?? []);
            echo "<p>Alojamientos: <strong>$aloj</strong></p>";
            echo "<p>Lugares: <strong>$lug</strong></p>";
            echo "<p>Actividades: <strong>$act</strong></p>";
            
            // Mostrar primer alojamiento para ver si tiene lat/lng
            if ($aloj > 0) {
                $first = $json['data']['alojamientos'][0];
                echo "<p>Primer alojamiento: <strong>{$first['name']}</strong></p>";
                echo "<p>latitude: <strong>" . ($first['latitude'] ?? 'NULL') . "</strong></p>";
                echo "<p>longitude: <strong>" . ($first['longitude'] ?? 'NULL') . "</strong></p>";
            }
        } elseif (isset($json['error'])) {
            echo "<p class='err'>❌ Error API: " . htmlspecialchars($json['error']) . "</p>";
        }
        echo "<pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    } else {
        echo "<p class='err'>❌ Respuesta no es JSON válido</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    }
}

// Test 4: ¿Existe js/evento-modular.js?
echo "<h2>4. Archivo js/evento-modular.js</h2>";
$jsFile = __DIR__ . '/js/evento-modular.js';
if (file_exists($jsFile)) {
    echo "<p class='ok'>✅ Existe (" . number_format(filesize($jsFile)) . " bytes)</p>";
    $jsContent = file_get_contents($jsFile);
    if (strpos($jsContent, '_initMapAndThen') !== false) {
        echo "<p class='ok'>✅ Tiene _initMapAndThen (fix botones mapa)</p>";
    } else {
        echo "<p class='err'>❌ NO tiene _initMapAndThen - Necesitas subir js/evento-modular.js</p>";
    }
    if (strpos($jsContent, '/api/evento-data.php') !== false) {
        echo "<p class='ok'>✅ URL API correcta: /api/evento-data.php</p>";
    } elseif (strpos($jsContent, '/evento-modular/api/') !== false) {
        echo "<p class='err'>❌ URL API incorrecta: apunta a /evento-modular/api/ - Necesitas subir js/evento-modular.js</p>";
    }
} else {
    echo "<p class='err'>❌ NO EXISTE - Necesitas subir js/evento-modular.js por FileZilla</p>";
}

echo "<h2>5. Resumen: Archivos a subir por FileZilla</h2>";
echo "<ul>";
echo "<li><strong>api/evento-data.php</strong> → /public_html/api/evento-data.php</li>";
echo "<li><strong>js/evento-modular.js</strong> → /public_html/js/evento-modular.js</li>";
echo "<li><strong>evento-detalle.php</strong> → /public_html/evento-detalle.php</li>";
echo "</ul>";
?>

</body>
</html>
