<?php
/**
 * API Endpoint: Sistema de Chat y Mensajería
 * Gestiona la comunicación entre Turistas y Servicios
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión para usar el chat', 401);
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? 'turista';
$pdo = getDBConnection();

// --------------------------------------------------------------------------
// 1. AUTO-MIGRACIÓN: Rescatar/Crear tablas si no existen
// --------------------------------------------------------------------------
try {
    // Migración de esquema antiguo (tourist_id/provider_id) a nuevo (user_1_id/user_2_id)
    $checkTable = $pdo->query("SHOW TABLES LIKE 'conversations'");
    if ($checkTable->rowCount() > 0) {
        $columns = $pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_COLUMN);
        
        // Si existe tourist_id pero no user_1_id, renombramos
        if (in_array('tourist_id', $columns) && !in_array('user_1_id', $columns)) {
            $pdo->exec("ALTER TABLE conversations CHANGE tourist_id user_1_id INT NOT NULL");
        }
        // Si existe provider_id pero no user_2_id, renombramos
        if (in_array('provider_id', $columns) && !in_array('user_2_id', $columns)) {
            $pdo->exec("ALTER TABLE conversations CHANGE provider_id user_2_id INT NOT NULL");
        }
    }
    
    $checkMsgTable = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($checkMsgTable->rowCount() > 0) {
        $msgColumns = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('message_text', $msgColumns) && !in_array('content', $msgColumns)) {
            $pdo->exec("ALTER TABLE messages CHANGE message_text content TEXT NOT NULL");
        }
    }

    // Tabla de Conversaciones: Vincula a dos usuarios
    $pdo->exec("CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_1_id INT NOT NULL,
        user_2_id INT NOT NULL,
        last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_1_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (user_2_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (user_1_id),
        INDEX (user_2_id),
        INDEX (last_message_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Tabla de Mensajes: Contenido del chat
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_id INT NOT NULL,
        content TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX (conversation_id),
        INDEX (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    error_log("Chat Tables Init Error: " . $e->getMessage());
}

// --------------------------------------------------------------------------
// 2. LÓGICA DE NEGOCIO
// --------------------------------------------------------------------------

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // Listar todas las conversaciones del usuario (para el Dashboard)
        case 'list_conversations':
            try {
                // Verificar estructura de la tabla conversations
                $convColumns = $pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_COLUMN);
                $hasUser2 = in_array('user_2_id', $convColumns);
                $hasProvider = in_array('provider_id', $convColumns);
                
                // Comprobar columnas en users
                $userColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
                $avatarColumnSQL = in_array('avatar_url', $userColumns) ? 'u.avatar_url' : 'NULL as avatar_url';
                $userTypeSQL = in_array('user_type', $userColumns) ? 'u.user_type' : "'turista' as user_type";

                // Adaptar consulta según estructura de tabla
                if ($hasUser2) {
                    // Estructura nueva (user_1_id, user_2_id)
                    $sql = "
                        SELECT 
                            c.id as conversation_id,
                            c.last_message_at,
                            CASE 
                                WHEN c.user_1_id = :me THEN c.user_2_id
                                ELSE c.user_1_id
                            END as other_user_id,
                            u.first_name,
                            u.last_name,
                            $avatarColumnSQL,
                            $userTypeSQL,
                            (SELECT content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
                            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_id != :me) as unread_count
                        FROM conversations c
                        JOIN users u ON (CASE WHEN c.user_1_id = :me THEN c.user_2_id ELSE c.user_1_id END) = u.id
                        WHERE c.user_1_id = :me OR c.user_2_id = :me
                        ORDER BY c.last_message_at DESC
                    ";
                } else if ($hasProvider) {
                    // Estructura antigua (user_1_id, provider_id)
                    $sql = "
                        SELECT 
                            c.id as conversation_id,
                            c.last_message_at,
                            CASE 
                                WHEN c.user_1_id = :me THEN c.provider_id
                                ELSE c.user_1_id
                            END as other_user_id,
                            u.first_name,
                            u.last_name,
                            $avatarColumnSQL,
                            $userTypeSQL,
                            (SELECT content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) as last_message,
                            (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_id != :me) as unread_count
                        FROM conversations c
                        JOIN users u ON (CASE WHEN c.user_1_id = :me THEN c.provider_id ELSE c.user_1_id END) = u.id
                        WHERE c.user_1_id = :me OR c.provider_id = :me
                        ORDER BY c.last_message_at DESC
                    ";
                } else {
                    jsonError('Estructura de tabla conversations no compatible', 500);
                    break;
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':me' => $userId]);
                
                $conversations = $stmt->fetchAll();
                
                jsonSuccess($conversations);
            } catch (PDOException $e) {
                error_log("list_conversations Error: " . $e->getMessage());
                jsonError('Error al cargar conversaciones: ' . $e->getMessage(), 500);
            }
            break;

        // Obtener historial de mensajes de una conversación
        case 'get_messages':
            if (empty($_GET['conversation_id'])) jsonError('ID de conversación requerido', 400);
            
            $convId = sanitizeInput($_GET['conversation_id']);
            
            // Verificar estructura de conversations
            $convColumns = $pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_COLUMN);
            $hasUser2 = in_array('user_2_id', $convColumns);
            $hasProvider = in_array('provider_id', $convColumns);
            
            // Verificar pertenencia según estructura
            if ($hasUser2) {
                $check = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND (user_1_id = ? OR user_2_id = ?)");
                $check->execute([$convId, $userId, $userId]);
            } else if ($hasProvider) {
                $check = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND (user_1_id = ? OR provider_id = ?)");
                $check->execute([$convId, $userId, $userId]);
            }
            
            if (!$check->fetch()) jsonError('No tienes permiso para ver esta conversación', 403);

            // Marcar como leídos los mensajes recibidos
            $markRead = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?");
            $markRead->execute([$convId, $userId]);

            // Verificar columnas en users
            $userColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            $avatarSQL = in_array('avatar_url', $userColumns) ? 'u.avatar_url' : 'NULL as avatar_url';
            
            // Obtener mensajes
            $sql = "
                SELECT m.*, u.first_name, $avatarSQL 
                FROM messages m 
                JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ? 
                ORDER BY m.created_at ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$convId]);
            
            jsonSuccess($stmt->fetchAll());
            break;

        // Iniciar conversación (ej. desde botón "Contactar" en ficha de alojamiento)
        case 'start_conversation':
            $data = json_decode(file_get_contents('php://input'), true);
            $recipientId = (int)($data['recipient_id'] ?? 0);
            
            if (!$recipientId) jsonError('Destinatario requerido', 400);
            if ($recipientId == $userId) jsonError('No puedes hablar contigo mismo', 400);

            // VALIDAR PERMISOS DE MEMBRESÍA
            // Obtener información de ambos usuarios
            $stmt = $pdo->prepare("
                SELECT id, user_type, LOWER(COALESCE(membership_type, 'free')) as membership_type
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
            
            // Determinar tipos
            $initiatorType = $initiator['user_type'] === 'turista' ? 'turista' : 'gestor';
            $recipientType = $recipient['user_type'] === 'turista' ? 'turista' : 'gestor';
            
            // Verificar permisos
            $stmtPerm = $pdo->prepare("
                SELECT can_initiate_conversation, description
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
            
            if (!$permission || !$permission['can_initiate_conversation']) {
                // Mensaje personalizado según el caso
                if ($initiatorType === 'gestor' && $initiator['membership_type'] === 'free') {
                    jsonError('Tu membresía gratuita no permite iniciar conversaciones. Los gestores solo pueden responder cuando un turista les contacta. Actualiza a Premium para esta funcionalidad.', 403);
                } elseif ($initiatorType === 'turista' && $recipientType === 'turista') {
                    jsonError('No puedes iniciar conversaciones con otros turistas.', 403);
                } else {
                    jsonError('No tienes permisos para iniciar esta conversación.', 403);
                }
            }

            // Verificar si ya existe conversación
            $sql = "SELECT id FROM conversations 
                    WHERE (user_1_id = :me AND user_2_id = :other) 
                       OR (user_1_id = :other AND user_2_id = :me) 
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':me' => $userId, ':other' => $recipientId]);
            $existing = $stmt->fetch();

            if ($existing) {
                jsonSuccess(['conversation_id' => $existing['id'], 'is_new' => false]);
            } else {
                // Crear nueva conversación
                $stmt = $pdo->prepare("INSERT INTO conversations (user_1_id, user_2_id) VALUES (?, ?)");
                $stmt->execute([$userId, $recipientId]);
                jsonSuccess(['conversation_id' => $pdo->lastInsertId(), 'is_new' => true]);
            }
            break;

        // Enviar un mensaje
        case 'send_message':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método POST requerido', 405);
            
            $data = json_decode(file_get_contents('php://input'), true);
            $convId = (int)($data['conversation_id'] ?? 0);
            $content = sanitizeInput($data['content'] ?? '');
            
            if (!$convId || !$content) jsonError('Datos incompletos', 400);

            // Verificar que la conversación existe y obtener el destinatario
            $checkConv = $pdo->prepare("
                SELECT user_1_id, user_2_id 
                FROM conversations 
                WHERE id = ? AND (user_1_id = ? OR user_2_id = ?)
            ");
            $checkConv->execute([$convId, $userId, $userId]);
            $conversation = $checkConv->fetch();
            
            if (!$conversation) jsonError('Conversación no válida', 403);
            
            // Determinar el destinatario
            $recipientId = ($conversation['user_1_id'] == $userId) 
                ? $conversation['user_2_id'] 
                : $conversation['user_1_id'];

            // VALIDAR PERMISOS Y LÍMITES
            // Obtener información de ambos usuarios
            $stmt = $pdo->prepare("
                SELECT id, user_type, membership_type
                FROM users
                WHERE id IN (?, ?)
            ");
            $stmt->execute([$userId, $recipientId]);
            $users = [];
            while ($row = $stmt->fetch()) {
                $users[$row['id']] = $row;
            }
            
            $initiator = $users[$userId];
            $recipient = $users[$recipientId];
            
            // Determinar tipos
            $initiatorType = $initiator['user_type'] === 'turista' ? 'turista' : 'gestor';
            $recipientType = $recipient['user_type'] === 'turista' ? 'turista' : 'gestor';
            
            // Verificar permisos
            $stmtPerm = $pdo->prepare("
                SELECT can_send_messages, max_messages_per_day
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
            
            if (!$permission || !$permission['can_send_messages']) {
                jsonError('No tienes permisos para enviar mensajes a este usuario.', 403);
            }
            
            // Verificar límite diario si existe
            if ($permission['max_messages_per_day'] !== null) {
                $stmtLimit = $pdo->prepare("
                    SELECT COALESCE(messages_sent, 0) as sent
                    FROM chat_daily_limits
                    WHERE user_id = ? AND date = CURDATE()
                ");
                $stmtLimit->execute([$userId]);
                $limit = $stmtLimit->fetch();
                $messagesSent = $limit ? (int)$limit['sent'] : 0;
                
                if ($messagesSent >= $permission['max_messages_per_day']) {
                    $upgradeMsg = ($initiator['membership_type'] === 'free') 
                        ? ' Actualiza a Premium para enviar mensajes ilimitados.' 
                        : '';
                    jsonError("Has alcanzado tu límite diario de {$permission['max_messages_per_day']} mensajes.$upgradeMsg", 429);
                }
            }

            // Insertar mensaje
            $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$convId, $userId, $content]);
            
            // Incrementar contador diario
            $pdo->prepare("
                INSERT INTO chat_daily_limits (user_id, date, messages_sent)
                VALUES (?, CURDATE(), 1)
                ON DUPLICATE KEY UPDATE 
                    messages_sent = messages_sent + 1,
                    updated_at = CURRENT_TIMESTAMP
            ")->execute([$userId]);
            
            // Actualizar timestamp de la conversación
            $pdo->prepare("UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$convId]);

            // Calcular mensajes restantes si hay límite
            $messagesRemaining = null;
            if ($permission['max_messages_per_day'] !== null) {
                $messagesRemaining = $permission['max_messages_per_day'] - ($messagesSent + 1);
            }

            jsonSuccess([
                'status' => 'sent', 
                'timestamp' => date('Y-m-d H:i:s'),
                'messages_remaining' => $messagesRemaining,
                'daily_limit' => $permission['max_messages_per_day']
            ]);
            break;

        default:
            jsonError('Acción no válida', 400);
    }
} catch (Exception $e) {
    jsonError('Error en el sistema de chat: ' . $e->getMessage(), 500);
}

?>