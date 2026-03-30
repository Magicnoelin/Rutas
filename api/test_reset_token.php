<?php
/**
 * Script de diagnóstico para tokens de recuperación
 * Accede a: https://www.rutasrurales.io/api/test_reset_token.php?email=TU_EMAIL
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$email = $_GET['email'] ?? '';

if (empty($email)) {
    die(json_encode(['error' => 'Proporciona ?email=tu@email.com']));
}

try {
    $pdo = getDBConnection();
    
    // Get user
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die(json_encode(['error' => 'Usuario no encontrado']));
    }
    
    // Get latest token
    $stmt = $pdo->prepare("
        SELECT id, token_hash, expires_at, is_used, created_at
        FROM password_reset_tokens
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user['id']]);
    $token = $stmt->fetch();
    
    if (!$token) {
        die(json_encode(['error' => 'No hay tokens para este usuario']));
    }
    
    // Test with a sample token
    $sampleToken = 'abc123';
    $sampleHash = hash('sha256', $sampleToken);
    
    echo json_encode([
        'user_id' => $user['id'],
        'user_email' => $user['email'],
        'token_info' => [
            'id' => $token['id'],
            'hash_length' => strlen($token['token_hash']),
            'hash_prefix' => substr($token['token_hash'], 0, 20),
            'is_password_hash' => (password_get_info($token['token_hash'])['algo'] !== 0),
            'expires_at' => $token['expires_at'],
            'is_used' => $token['is_used'],
            'created_at' => $token['created_at']
        ],
        'test' => [
            'sample_token' => $sampleToken,
            'sample_sha256' => $sampleHash,
            'sha256_length' => strlen($sampleHash)
        ],
        'server_time' => date('Y-m-d H:i:s'),
        'php_version' => phpversion()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
