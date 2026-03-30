<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado', 401);
}

$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo = getDBConnection();
    
    // 1. Obtener preferencias actuales para no borrar datos extra que no vengan en el formulario
    $stmt = $pdo->prepare("SELECT preferences_json FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current = $stmt->fetch(PDO::FETCH_ASSOC);
    $currentPrefs = $current['preferences_json'] ? json_decode($current['preferences_json'], true) : [];
    
    // 2. Fusionar con las nuevas (las nuevas sobrescriben a las viejas)
    $newPrefs = array_merge($currentPrefs, $input);
    
    // 3. Guardar
    $stmt = $pdo->prepare("UPDATE users SET preferences_json = ? WHERE id = ?");
    $stmt->execute([json_encode($newPrefs), $_SESSION['user_id']]);
    
    jsonSuccess(null, 'Preferencias actualizadas');
} catch (Exception $e) {
    jsonError('Error al guardar preferencias', 500);
}