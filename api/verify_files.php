<?php
/**
 * Verificar versión de archivos
 * Accede a: https://rutasurales.io/api/verify_files.php
 */

header('Content-Type: application/json; charset=utf-8');

$files = [
    'forgot_password.php' => __DIR__ . '/forgot_password.php',
    'reset_password.php' => __DIR__ . '/reset_password.php'
];

$results = [];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        
        // Check for SHA256 usage
        $usesSHA256 = (strpos($content, "hash('sha256'") !== false);
        $usesPasswordHash = (strpos($content, 'password_hash($token') !== false);
        
        $results[$name] = [
            'exists' => true,
            'size' => filesize($path),
            'modified' => date('Y-m-d H:i:s', filemtime($path)),
            'uses_sha256' => $usesSHA256,
            'uses_password_hash_for_token' => $usesPasswordHash,
            'status' => $usesSHA256 ? 'CORRECTO (SHA256)' : 'ANTIGUO (password_hash)'
        ];
    } else {
        $results[$name] = ['exists' => false];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
