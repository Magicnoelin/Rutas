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

if (!$input) {
    jsonError('Datos inválidos', 400);
}

try {
    $pdo = getDBConnection();
    
    // Validar campos básicos
    if (empty($input['firstName']) || empty($input['lastName'])) {
        jsonError('Nombre y apellidos son obligatorios', 400);
    }

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, nickname = ?, phone = ? WHERE id = ?");
    $stmt->execute([
        sanitizeInput($input['firstName']),
        sanitizeInput($input['lastName']),
        sanitizeInput($input['nickname'] ?? ''),
        sanitizeInput($input['phone'] ?? ''),
        $_SESSION['user_id']
    ]);

    jsonSuccess(null, 'Perfil actualizado');
} catch (Exception $e) {
    jsonError('Error al actualizar perfil', 500);
}