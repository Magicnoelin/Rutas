<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado', 401);
}

try {
    $pdo = getDBConnection();
    // Obtenemos el JSON de preferencias
    $stmt = $pdo->prepare("SELECT preferences_json FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Decodificamos para enviar como objeto JSON limpio
    $prefs = $result['preferences_json'] ? json_decode($result['preferences_json'], true) : [];
    
    jsonSuccess($prefs);
} catch (Exception $e) {
    jsonError('Error al obtener preferencias', 500);
}