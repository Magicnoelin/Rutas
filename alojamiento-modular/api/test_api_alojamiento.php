<?php
/**
 * Script de prueba para la API de alojamiento modular
 * Ubicación sugerida: /alojamiento-modular/api/test_api_alojamiento.php
 */

header('Content-Type: text/plain; charset=utf-8');

// Configuración de la prueba
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$apiPath = '/alojamiento-modular/api/alojamiento-data.php';
$testSlug = 'casa-enrique'; // El slug que estamos investigando

// Parámetros de simulación (similares a los que envía el JS)
$params = [
    'slug'   => $testSlug,
    'lat'    => '41.7667', // Coordenadas de ejemplo (Soria)
    'lng'    => '-2.4667',
    'radius' => '50',
    'mode'   => 'nearby'
];

$url = $protocol . "://" . $host . $apiPath . '?' . http_build_query($params);

echo "=== DIAGNÓSTICO DE API: ALOJAMIENTO MODULAR ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
echo "URL objetivo: $url\n\n";

// 1. Verificación de existencia física del archivo
$localPath = __DIR__ . '/alojamiento-data.php';
echo "1. Verificando archivo local...\n";
if (file_exists($localPath)) {
    echo "   [OK] El archivo existe en: $localPath\n";
    echo "   Permisos: " . substr(sprintf('%o', fileperms($localPath)), -3) . "\n";
} else {
    echo "   [ERROR] No se encuentra alojamiento-data.php en este directorio.\n";
}

// 2. Prueba de conectividad vía cURL
echo "\n2. Realizando petición HTTP interna...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   [ERROR] Error de cURL: $error\n";
} else {
    echo "   Código HTTP recibido: $httpCode\n";
    
    if ($httpCode === 200) {
        echo "   [OK] Conexión establecida.\n";
        
        // 3. Verificación de formato JSON
        echo "\n3. Validando formato de respuesta...\n";
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "   [OK] JSON válido.\n";
            echo "   Estado según API: " . (isset($data['success']) && $data['success'] ? 'SUCCESS' : 'FAILURE') . "\n";
            if (isset($data['data'])) {
                echo "   Elementos encontrados en 'data':\n";
                foreach ($data['data'] as $key => $val) {
                    echo "     - $key: " . (is_array($val) ? count($val) : $val) . "\n";
                }
            }
        } else {
            echo "   [ERROR] La respuesta no es un JSON válido. Cuerpo recibido:\n";
            echo "   " . substr($response, 0, 1000) . (strlen($response) > 1000 ? '...' : '') . "\n";
        }
    } else {
        echo "   [ERROR] El servidor respondió con código $httpCode\n";
        echo "   Contenido de la respuesta:\n" . $response . "\n";
    }
}
echo "\n=== FIN DEL DIAGNÓSTICO ===\n";