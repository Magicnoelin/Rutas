<?php
/**
 * API: Enviar Oferta (para propietarios de alojamientos)
 * POST /api/send_offer.php
 * Body: {
 *   recipient_id: int,
 *   accommodation_id: int,
 *   subject: string,
 *   start_date: 'YYYY-MM-DD',
 *   end_date: 'YYYY-MM-DD',
 *   price: float,
 *   message: string,
 *   currency: string
 * }
 */

session_start();
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión para enviar ofertas', 401);
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

    // Validar campos requeridos
    $requiredFields = ['recipient_id', 'accommodation_id', 'subject', 'start_date', 'end_date', 'price', 'message'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            jsonError("El campo '$field' es requerido", 400);
        }
    }

    $userId = $_SESSION['user_id'];
    $recipientId = (int)$data['recipient_id'];
    $accommodationId = (int)$data['accommodation_id'];
    $subject = sanitizeInput($data['subject']);
    $startDate = sanitizeInput($data['start_date']);
    $endDate = sanitizeInput($data['end_date']);
    $price = (float)$data['price'];
    $message = sanitizeInput($data['message']);
    $currency = isset($data['currency']) ? sanitizeInput($data['currency']) : 'EUR';

    // Validar fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        jsonError('Formato de fecha inválido. Usa YYYY-MM-DD', 400);
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        jsonError('La fecha de inicio no puede ser posterior a la fecha de fin', 400);
    }

    if (strtotime($startDate) < strtotime(date('Y-m-d'))) {
        jsonError('La fecha de inicio no puede ser en el pasado', 400);
    }

    if ($price <= 0) {
        jsonError('El precio debe ser mayor que cero', 400);
    }

    $pdo = getDBConnection();

    // Verificar que el usuario es propietario del alojamiento
    $stmtCheck = $pdo->prepare("
        SELECT a.id, a.name, a.slug
        FROM accommodations a
        JOIN user_resources ur ON a.id = ur.resource_id
        WHERE a.id = ?
        AND ur.user_id = ?
        AND ur.resource_type = 'accommodation'
        AND ur.role = 'owner'
    ");
    $stmtCheck->execute([$accommodationId, $userId]);
    $accommodation = $stmtCheck->fetch();

    if (!$accommodation) {
        jsonError('No tienes permisos para enviar ofertas con este alojamiento', 403);
    }

    // Verificar que el destinatario existe y es turista
    $stmtRecipient = $pdo->prepare("
        SELECT id, first_name, last_name, user_type
        FROM users
        WHERE id = ?
    ");
    $stmtRecipient->execute([$recipientId]);
    $recipient = $stmtRecipient->fetch();

    if (!$recipient) {
        jsonError('Destinatario no encontrado', 404);
    }

    if ($recipient['user_type'] !== 'turista') {
        jsonError('Solo puedes enviar ofertas a usuarios turistas', 400);
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
    $stmtPerm->execute([$userId, $recipientId]);
    $permission = $stmtPerm->fetch();

    if (!$permission || !$permission['can_send_offers']) {
        jsonError('Tu membresía no permite enviar ofertas', 403);
    }

    // Verificar si ya existe una conversación con este usuario
    $stmtConv = $pdo->prepare("
        SELECT id FROM conversations
        WHERE (user_1_id = ? AND user_2_id = ?)
           OR (user_1_id = ? AND user_2_id = ?)
    ");
    $stmtConv->execute([$userId, $recipientId, $recipientId, $userId]);
    $conversation = $stmtConv->fetch();

    if (!$conversation) {
        // Crear nueva conversación
        $stmtNewConv = $pdo->prepare("
            INSERT INTO conversations (user_1_id, user_2_id, last_message_at)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmtNewConv->execute([$userId, $recipientId]);
        $conversationId = $pdo->lastInsertId();
    } else {
        $conversationId = $conversation['id'];
    }

    // Crear mensaje de oferta con formato especial
    $offerMessage = "[OFERTA] " . strtoupper($subject) . "\n\n";
    $offerMessage .= "🏠 **Alojamiento:** " . $accommodation['name'] . "\n";
    $offerMessage .= "📅 **Fechas:** " . date('d/m/Y', strtotime($startDate)) . " - " . date('d/m/Y', strtotime($endDate)) . "\n";
    $offerMessage .= "💰 **Precio:** " . number_format($price, 2) . " " . $currency . "\n\n";
    $offerMessage .= "**Mensaje:**\n" . $message . "\n\n";
    $offerMessage .= "---\n";
    $offerMessage .= "👉 [Ver alojamiento](https://rutasrurales.io/alojamiento-detalle.html?slug=" . $accommodation['slug'] . ")\n";
    $offerMessage .= "📞 Responde a esta oferta para más información";

    // Insertar mensaje en la base de datos
    $stmtMsg = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_id, content, is_read)
        VALUES (?, ?, ?, 0)
    ");
    $stmtMsg->execute([$conversationId, $userId, $offerMessage]);

    // Actualizar timestamp de la conversación
    $stmtUpdate = $pdo->prepare("
        UPDATE conversations SET last_message_at = CURRENT_TIMESTAMP WHERE id = ?
    ");
    $stmtUpdate->execute([$conversationId]);

    // Crear registro de oferta en resource_offers (si existe la tabla)
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'resource_offers'");
        if ($checkTable->rowCount() > 0) {
            $stmtOffer = $pdo->prepare("
                INSERT INTO resource_offers
                (user_id, resource_type, resource_id, recipient_id, subject, start_date, end_date, price, currency, message, status, created_at)
                VALUES (?, 'accommodation', ?, ?, ?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            $stmtOffer->execute([
                $userId, $accommodationId, $recipientId, $subject, $startDate, $endDate, $price, $currency, $message
            ]);
        }
    } catch (PDOException $offerError) {
        error_log("send_offer.php - Error al crear registro de oferta: " . $offerError->getMessage());
    }

    // Respuesta exitosa
    jsonSuccess([
        'conversation_id' => $conversationId,
        'offer_id' => isset($offerId) ? $offerId : null,
        'accommodation' => [
            'id' => $accommodation['id'],
            'name' => $accommodation['name'],
            'slug' => $accommodation['slug']
        ],
        'recipient' => [
            'id' => $recipient['id'],
            'name' => trim($recipient['first_name'] . ' ' . $recipient['last_name'])
        ],
        'offer_details' => [
            'subject' => $subject,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price' => $price,
            'currency' => $currency,
            'message' => $message
        ],
        'message' => 'Oferta enviada correctamente. El turista ha sido notificado.'
    ], 'Oferta enviada con éxito');

} catch (PDOException $e) {
    error_log('send_offer.php Error: ' . $e->getMessage());
    jsonError('Error al enviar la oferta: ' . $e->getMessage(), 500);
}