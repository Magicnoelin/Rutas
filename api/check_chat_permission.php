<?php
/**
 * API: Verificar permisos de chat
 * POST /api/check_chat_permission.php
 * Body: { action, recipient_id }
 * Actions: 'initiate', 'send_message', 'send_offer'
 */

session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado', 401);
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        jsonError('Datos JSON inválidos', 400);
    }
    
    $userId = $_SESSION['user_id'];
    $recipientId = isset($data['recipient_id']) ? (int)$data['recipient_id'] : 0;
    $action = isset($data['action']) ? sanitizeInput($data['action']) : '';
    
    if (!$recipientId || !$action) {
        jsonError('Faltan parámetros: recipient_id, action', 400);
    }
    
    $pdo = getDBConnection();
    
    // Obtener información de ambos usuarios
    $stmt = $pdo->prepare("
        SELECT id, user_type, membership_type, first_name, last_name
        FROM users
        WHERE id IN (?, ?)
    ");
    $stmt->execute([$userId, $recipientId]);
    $users = [];
    while ($row = $stmt->fetch()) {
        $users[$row['id']] = $row;
    }
    
    if (!isset($users[$userId]) || !isset($users[$recipientId])) {
        jsonError('Usuario no encontrado', 404);
    }
    
    $initiator = $users[$userId];
    $recipient = $users[$recipientId];
    
    // Determinar tipos (turista o gestor)
    $initiatorType = $initiator['user_type'] === 'turista' ? 'turista' : 'gestor';
    $recipientType = $recipient['user_type'] === 'turista' ? 'turista' : 'gestor';
    
    // Buscar permisos
    $stmtPerm = $pdo->prepare("
        SELECT 
            can_initiate_conversation,
            can_send_messages,
            can_send_offers,
            max_messages_per_day,
            description
        FROM chat_permissions
        WHERE initiator_type = ?
        AND initiator_membership = ?
        AND recipient_type = ?
        AND (recipient_membership = ? OR recipient_membership = 'any')
        AND is_active = TRUE
        LIMIT 1
    ");
    $stmtPerm->execute([
        $initiatorType,
        $initiator['membership_type'],
        $recipientType,
        $recipient['membership_type']
    ]);
    $permission = $stmtPerm->fetch();
    
    if (!$permission) {
        jsonError('No hay permisos definidos para esta combinación de usuarios', 403);
    }
    
    // Verificar según la acción
    $allowed = false;
    $message = '';
    $upgrade_required = false;
    
    switch ($action) {
        case 'initiate':
            $allowed = (bool)$permission['can_initiate_conversation'];
            if (!$allowed) {
                if ($initiatorType === 'gestor' && $initiator['membership_type'] === 'free') {
                    $message = 'Tu membresía gratuita no permite iniciar conversaciones. Los gestores solo pueden responder cuando un turista les contacta.';
                    $upgrade_required = true;
                } elseif ($initiatorType === 'turista' && $recipientType === 'turista') {
                    $message = 'No puedes iniciar conversaciones con otros turistas.';
                } else {
                    $message = 'No tienes permisos para iniciar esta conversación.';
                }
            }
            break;
            
        case 'send_message':
            $allowed = (bool)$permission['can_send_messages'];
            
            if ($allowed && $permission['max_messages_per_day'] !== null) {
                // Verificar límite diario
                $stmtLimit = $pdo->prepare("
                    SELECT COALESCE(messages_sent, 0) as sent
                    FROM chat_daily_limits
                    WHERE user_id = ? AND date = CURDATE()
                ");
                $stmtLimit->execute([$userId]);
                $limit = $stmtLimit->fetch();
                $messagesSent = $limit ? (int)$limit['sent'] : 0;
                
                if ($messagesSent >= $permission['max_messages_per_day']) {
                    $allowed = false;
                    $message = "Has alcanzado tu límite diario de {$permission['max_messages_per_day']} mensajes.";
                    if ($initiator['membership_type'] === 'free') {
                        $message .= ' Actualiza a Premium para enviar mensajes ilimitados.';
                        $upgrade_required = true;
                    }
                }
            }
            
            if (!$allowed && empty($message)) {
                $message = 'No tienes permisos para enviar mensajes a este usuario.';
            }
            break;
            
        case 'send_offer':
            $allowed = (bool)$permission['can_send_offers'];
            if (!$allowed) {
                if ($initiatorType === 'gestor' && $initiator['membership_type'] === 'free') {
                    $message = 'Tu membresía gratuita no permite enviar ofertas. Actualiza a Premium para esta funcionalidad.';
                    $upgrade_required = true;
                } else {
                    $message = 'No tienes permisos para enviar ofertas.';
                }
            }
            break;
            
        default:
            jsonError('Acción no válida', 400);
    }
    
    $response = [
        'allowed' => $allowed,
        'message' => $message,
        'upgrade_required' => $upgrade_required,
        'permission_details' => [
            'can_initiate' => (bool)$permission['can_initiate_conversation'],
            'can_send_messages' => (bool)$permission['can_send_messages'],
            'can_send_offers' => (bool)$permission['can_send_offers'],
            'daily_limit' => $permission['max_messages_per_day'],
            'description' => $permission['description']
        ],
        'initiator' => [
            'type' => $initiatorType,
            'membership' => $initiator['membership_type']
        ],
        'recipient' => [
            'type' => $recipientType,
            'membership' => $recipient['membership_type'],
            'name' => trim($recipient['first_name'] . ' ' . $recipient['last_name'])
        ]
    ];
    
    jsonSuccess($response);
    
} catch (PDOException $e) {
    error_log('check_chat_permission.php Error: ' . $e->getMessage());
    jsonError('Error al verificar permisos: ' . $e->getMessage(), 500);
}
