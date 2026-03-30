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
    
    // Obtener información del usuario incluyendo datos de membresía
    try {
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, nickname, email, phone, user_type, avatar_url,
                   membership_type, membership_start_date, membership_end_date,
                   stripe_customer_id, stripe_subscription_id
            FROM users 
            WHERE id = ?
        ");
    } catch (Exception $e) {
        // Fallback si las columnas aún no existen
        try {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, nickname, email, phone, user_type, avatar_url FROM users WHERE id = ?");
        } catch (Exception $e2) {
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, user_type FROM users WHERE id = ?");
        }
    }
    
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        jsonSuccess($user);
    } else {
        jsonError('Usuario no encontrado', 404);
    }
} catch (Exception $e) {
    jsonError('Error del servidor', 500);
}