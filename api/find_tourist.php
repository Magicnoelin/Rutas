<?php
/**
 * API: Buscar Turista por Email
 * POST /api/find_tourist.php
 * Body: { email: string }
 * Retorna información del turista si existe y es de tipo turista
 */

session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión', 401);
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data || empty($data['email'])) {
        jsonError('Email requerido', 400);
    }

    $email = sanitizeInput($data['email']);
    $currentUserId = $_SESSION['user_id'];

    // Validar formato de email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonError('Formato de email inválido', 400);
    }

    $pdo = getDBConnection();

    // Buscar usuario por email
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, user_type, email, avatar_url
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonError('No se encontró ningún usuario con ese correo electrónico', 404);
    }

    // Verificar que el usuario encontrado sea un turista
    if ($user['user_type'] !== 'turista') {
        jsonError('Solo puedes enviar ofertas a usuarios con rol de turista', 400);
    }

    // Verificar que no sea el mismo usuario
    if ($user['id'] == $currentUserId) {
        jsonError('No puedes enviar una oferta a ti mismo', 400);
    }

    // Verificar permisos para enviar ofertas
    $stmtPerm = $pdo->prepare("
        SELECT can_send_offers
        FROM chat_permissions
        WHERE initiator_type = 'gestor'
        AND initiator_membership = (SELECT membership_type FROM users WHERE id = ?)
        AND recipient_type = 'turista'
        AND (recipient_membership = (SELECT membership_type FROM users WHERE id = ?) OR recipient_membership = 'any')
        AND is_active = TRUE
        LIMIT 1
    ");
    $stmtPerm->execute([$currentUserId, $user['id']]);
    $permission = $stmtPerm->fetch();

    if (!$permission || !$permission['can_send_offers']) {
        jsonError('Tu membresía no permite enviar ofertas', 403);
    }

    // Respuesta exitosa
    jsonSuccess([
        'user' => [
            'id' => $user['id'],
            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
            'email' => $user['email'],
            'avatar_url' => $user['avatar_url'] ?? null
        ],
        'message' => 'Turista encontrado. Puedes proceder a enviar tu oferta.'
    ], 'Turista encontrado');

} catch (PDOException $e) {
    error_log('find_tourist.php Error: ' . $e->getMessage());
    jsonError('Error al buscar turista: ' . $e->getMessage(), 500);
}