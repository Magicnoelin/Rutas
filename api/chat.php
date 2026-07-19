<?php
/**
 * API Endpoint: Sistema de Chat y Mensajería
 * Adaptado a la estructura REAL de la BD del servidor:
 *   conversations: id, user_1_id, entity_type, entity_id, provider_id,
 *                  status, last_message_at, created_at, updated_at,
 *                  resource_type, resource_id
 *   messages: id, conversation_id, sender_id, content, is_read, created_at
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión para usar el chat', 401);
}

$userId   = (int)$_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? 'turista';
$pdo      = getDBConnection();

// --------------------------------------------------------------------------
// 1. DETECTAR ESTRUCTURA REAL (sin intentar migrar)
// --------------------------------------------------------------------------
$convColumns = $pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_COLUMN);
$msgColumns  = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN);
$userColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

// Columna del "segundo usuario": puede ser user_2_id (estructura nueva) o provider_id (estructura real del servidor)
$user2Col   = in_array('user_2_id', $convColumns)   ? 'user_2_id'   :
              (in_array('provider_id', $convColumns) ? 'provider_id' : null);

// Columna de contenido del mensaje
$contentCol = in_array('content', $msgColumns) ? 'content' :
              (in_array('message_text', $msgColumns) ? 'message_text' : 'content');

// Columnas opcionales de users
$avatarSQL   = in_array('avatar_url', $userColumns) ? 'u.avatar_url' : 'NULL as avatar_url';
$userTypeSQL = in_array('user_type', $userColumns)  ? 'u.user_type'  : "'turista' as user_type";

// La tabla conversations en el servidor tiene entity_type, entity_id, status
$hasEntityType  = in_array('entity_type', $convColumns);
$hasStatus      = in_array('status', $convColumns);

// --------------------------------------------------------------------------
// 2. HELPER: obtener other_user_id de una conversación
//    En la estructura nueva: CASE WHEN user_1_id = me THEN user_2_id ELSE user_1_id
//    En la estructura real (provider_id): si user_1_id = me → provider_id, sino user_1_id
// --------------------------------------------------------------------------

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // ── Listar conversaciones del usuario ──────────────────────────────
        case 'list_conversations':
            if (!$user2Col) jsonError('Estructura de tabla conversations no compatible', 500);

            // WHERE: el usuario es user_1_id O es el provider_id
            $whereClause = "c.user_1_id = :userId1 OR c.{$user2Col} = :userId2";

            // Calcular other_user_id según estructura
            $otherUserExpr = "CASE WHEN c.user_1_id = :userId3 THEN c.{$user2Col} ELSE c.user_1_id END";

            // Para conversations con provider_id = NULL (consultas pendientes sin destinatario)
            // las incluimos pero mostramos datos del admin o sin nombre
            $sql = "
                SELECT
                    c.id AS conversation_id,
                    c.last_message_at,
                    ({$otherUserExpr}) AS other_user_id,
                    COALESCE(u.first_name, 'Administración') AS first_name,
                    COALESCE(u.last_name, 'Rutas Rurales') AS last_name,
                    {$avatarSQL},
                    {$userTypeSQL},
                    " . ($hasEntityType ? "c.entity_type," : "'chat' AS entity_type,") . "
                    " . ($hasStatus ? "c.status," : "'open' AS status,") . "
                    (SELECT m.{$contentCol} FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_id != :userId4 AND m.sender_id IS NOT NULL) AS unread_count
                FROM conversations c
                LEFT JOIN users u ON ({$otherUserExpr}) = u.id
                WHERE {$whereClause}
                ORDER BY c.last_message_at DESC
                LIMIT 50
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':userId1' => $userId,
                ':userId2' => $userId,
                ':userId3' => $userId,
                ':userId4' => $userId,
            ]);
            $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fallback para conversaciones huérfanas (provider_id = NULL)
            // Si el JOIN con `users` falla, other_user_id puede ser NULL.
            // Asignamos datos de admin para que se muestre correctamente.
            foreach ($conversations as &$conv) {
                if ($conv['other_user_id'] === null) {
                    $conv['first_name'] = 'Administración';
                    $conv['last_name'] = 'Rutas Rurales';
                    $conv['user_type'] = 'admin';
                    $conv['avatar_url'] = null;
                }
            }

            jsonSuccess($conversations);
            break;

        // ── Obtener mensajes de una conversación ───────────────────────────
        case 'get_messages':
            if (empty($_GET['conversation_id'])) jsonError('ID de conversación requerido', 400);
            $convId = (int)$_GET['conversation_id'];

            if (!$user2Col) jsonError('Estructura de tabla conversations no compatible', 500);

            // Verificar acceso: el usuario debe ser user_1_id o provider_id
            $check = $pdo->prepare("
                SELECT id FROM conversations
                WHERE id = ? AND (user_1_id = ? OR {$user2Col} = ?)
                LIMIT 1
            ");
            $check->execute([$convId, $userId, $userId]);
            if (!$check->fetch()) jsonError('No tienes permiso para ver esta conversación', 403);

            // Marcar como leídos
            $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?")->execute([$convId, $userId]);

            // Obtener mensajes
            $stmt = $pdo->prepare("
                SELECT m.id, m.conversation_id, m.sender_id, m.{$contentCol} AS content,
                       m.is_read, m.created_at,
                       COALESCE(u.first_name, 'Usuario') AS first_name,
                       {$avatarSQL}
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.conversation_id = ?
                ORDER BY m.created_at ASC
            ");
            $stmt->execute([$convId]);
            jsonSuccess($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        // ── Iniciar conversación (desde ficha de alojamiento) ──────────────
        case 'start_conversation':
            $data        = json_decode(file_get_contents('php://input'), true);
            $recipientId = (int)($data['recipient_id']   ?? 0);
            $entityType  = $data['entity_type']           ?? 'accommodation';
            $entityId    = (int)($data['entity_id']       ?? 0);
            $initiatorRole = $data['initiator_role']      ?? '';

            if (!$recipientId) jsonError('Destinatario requerido', 400);
            if ($recipientId === $userId) jsonError('No puedes hablar contigo mismo', 400);

            // Verificar que el destinatario existe
            $stmtU = $pdo->prepare("SELECT id, user_type, LOWER(COALESCE(membership_type,'free')) as membership_type,
                (SELECT GROUP_CONCAT(r.slug) FROM roles r JOIN role_user ru ON ru.role_id = r.id WHERE ru.user_id = users.id) as all_roles
                FROM users WHERE id IN (?,?)");
            $stmtU->execute([$userId, $recipientId]);
            $usersData = [];
            while ($row = $stmtU->fetch()) { $usersData[$row['id']] = $row; }

            if (!isset($usersData[$userId]) || !isset($usersData[$recipientId])) {
                jsonError('Usuario no encontrado', 404);
            }

            $initiator    = $usersData[$userId];
            $myRoles      = array_filter(explode(',', $initiator['all_roles'] ?? ''));
            $senderIsGuest = in_array('turista', $myRoles)
                || $initiator['user_type'] === 'turista'
                || $userType === 'turista'
                || empty($myRoles);

            if ($initiatorRole === 'turista') $senderIsGuest = true;

            $recipient    = $usersData[$recipientId];
            $recipRoles   = array_filter(explode(',', $recipient['all_roles'] ?? ''));
            $recipIsGuest = in_array('turista', $recipRoles) && empty(array_diff($recipRoles, ['turista']));
            if ($senderIsGuest && $recipIsGuest) {
                jsonError('No puedes iniciar conversaciones con otros turistas.', 403);
            }

            if (!$user2Col) jsonError('Estructura de tabla no detectada', 500);

            // Buscar conversación existente
            if ($hasEntityType && $entityId > 0) {
                $checkSql = "SELECT id FROM conversations
                             WHERE user_1_id = ? AND {$user2Col} = ? AND entity_type = ? AND entity_id = ?
                             LIMIT 1";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([$userId, $recipientId, $entityType, $entityId]);
            } else {
                $checkSql = "SELECT id FROM conversations
                             WHERE (user_1_id = ? AND {$user2Col} = ?)
                                OR (user_1_id = ? AND {$user2Col} = ?)
                             LIMIT 1";
                $checkStmt = $pdo->prepare($checkSql);
                $checkStmt->execute([$userId, $recipientId, $recipientId, $userId]);
            }
            $existing = $checkStmt->fetch();

            if ($existing) {
                jsonSuccess(['conversation_id' => (int)$existing['id'], 'is_new' => false]);
            } else {
                if ($hasEntityType) {
                    $ins = $pdo->prepare("
                        INSERT INTO conversations (user_1_id, {$user2Col}, entity_type, entity_id, status, last_message_at, created_at, updated_at)
                        VALUES (?, ?, ?, ?, 'open', NOW(), NOW(), NOW())
                    ");
                    $ins->execute([$userId, $recipientId, $entityType, $entityId ?: 0]);
                } else {
                    $ins = $pdo->prepare("INSERT INTO conversations (user_1_id, {$user2Col}) VALUES (?, ?)");
                    $ins->execute([$userId, $recipientId]);
                }
                jsonSuccess(['conversation_id' => (int)$pdo->lastInsertId(), 'is_new' => true]);
            }
            break;

        // ── Enviar un mensaje ──────────────────────────────────────────────
        case 'send_message':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método POST requerido', 405);

            $data    = json_decode(file_get_contents('php://input'), true);
            $convId  = (int)($data['conversation_id'] ?? 0);
            $content = trim($data['content'] ?? '');

            if (!$convId || !$content) jsonError('Datos incompletos', 400);
            if (!$user2Col) jsonError('Estructura de tabla no detectada', 500);

            // Verificar acceso
            $checkConv = $pdo->prepare("
                SELECT user_1_id, {$user2Col} AS other_id
                FROM conversations
                WHERE id = ? AND (user_1_id = ? OR {$user2Col} = ?)
            ");
            $checkConv->execute([$convId, $userId, $userId]);
            $conversation = $checkConv->fetch();
            if (!$conversation) jsonError('Conversación no válida o sin acceso', 403);

            // Insertar mensaje
            $pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, {$contentCol}, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ")->execute([$convId, $userId, $content]);

            // Actualizar timestamp conversación
            $updCols = $hasStatus ? "last_message_at = NOW(), updated_at = NOW()" : "last_message_at = NOW()";
            $pdo->prepare("UPDATE conversations SET {$updCols} WHERE id = ?")->execute([$convId]);

            // Contador diario (best-effort, sin error si falla)
            try {
                $pdo->prepare("
                    INSERT INTO chat_daily_limits (user_id, date, messages_sent)
                    VALUES (?, CURDATE(), 1)
                    ON DUPLICATE KEY UPDATE messages_sent = messages_sent + 1, updated_at = CURRENT_TIMESTAMP
                ")->execute([$userId]);
            } catch (PDOException $ex) { /* silencioso */ }

            jsonSuccess(['status' => 'sent', 'timestamp' => date('Y-m-d H:i:s')]);
            break;

        default:
            jsonError('Acción no válida: ' . $action, 400);
    }
} catch (PDOException $e) {
    error_log('chat.php PDO: ' . $e->getMessage());
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('chat.php: ' . $e->getMessage());
    jsonError('Error en el sistema de chat: ' . $e->getMessage(), 500);
}
