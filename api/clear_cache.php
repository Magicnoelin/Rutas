<?php
/**
 * Limpiar cache de PHP/OPcache
 * Accede a: https://rutasrurales.io/api/clear_cache.php
 */

header('Content-Type: application/json; charset=utf-8');

$results = [];

// Clear OPcache if available
if (function_exists('opcache_reset')) {
    $results['opcache_reset'] = opcache_reset();
    $results['opcache_status'] = 'OPcache limpiado';
} else {
    $results['opcache_status'] = 'OPcache no disponible';
}

// Clear realpath cache
clearstatcache(true);
$results['clearstatcache'] = 'Cache de archivos limpiado';

// Touch the files to force reload
$files = ['forgot_password.php', 'reset_password.php'];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        touch($path);
        $results['touched'][] = $file;
    }
}

$results['message'] = 'Cache limpiado. Ahora solicita un nuevo enlace de recuperación.';
$results['next_step'] = 'Ve a https://rutasrurales.io/login.html y solicita nuevo enlace';

echo json_encode($results, JSON_PRETTY_PRINT);
