<?php
/**
 * API: Enviar Consulta de Alojamiento
 * Usa la estructura REAL de la BD: conversations con provider_id, entity_type, entity_id
 * POST /api/submit_inquiry.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión', 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

$touristId = (int)$_SESSION['user_id'];

$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!$body) {
    jsonError('Datos inválidos: ' . json_last_error_msg(), 400);
}

$message  = isset($body['message'])   ? trim($body['message'])   : '';
$zone     = isset($body['zone'])      ? trim($body['zone'])      : '';
$persons  = isset($body['persons'])   ? (int)$body['persons']    : 2;
$checkIn  = isset($body['check_in'])  ? trim($body['check_in'])  : '';
$checkOut = isset($body['check_out']) ? trim($body['check_out']) : '';

if (empty($message)) {
    jsonError('El mensaje no puede estar vacío', 400);
}

try {
    $pdo = getDBConnection();

    // ── Estructura real detectada del diagnóstico ─────────────────────────
    // conversations: id, user_1_id, entity_type, entity_id, provider_id,
    //               status, last_message_at, created_at, updated_at,
    //               resource_type, resource_id
    // messages: detectar columnas reales
    $msgCols    = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN);
    $contentCol = in_array('content', $msgCols) ? 'content' :
                  (in_array('message_text', $msgCols) ? 'message_text' : 'content');

    // ── Detectar estructura de accommodations ─────────────────────────────
    $accCols     = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_COLUMN);
    $hasStatus   = in_array('status', $accCols);
    $hasOwnerCol = in_array('owner_user_id', $accCols);

    // Verificar si user_resources tiene registros de tipo accommodation+owner
    $urCols = $pdo->query("SHOW COLUMNS FROM user_resources")->fetchAll(PDO::FETCH_COLUMN);
    $urHasRole = in_array('role', $urCols);
    $urHasResourceType = in_array('resource_type', $urCols);

    $statusFilter = $hasStatus ? "AND (a.status NOT IN ('deleted','spam') OR a.status IS NULL)" : '';

    // ── Buscar alojamientos con propietario ───────────────────────────────
    // Estrategia 1: user_resources
    $owners = []; // array de ['owner_id' => X, 'acc_id' => Y, 'acc_name' => Z]

    if ($urHasRole && $urHasResourceType) {
        $zoneWhere = $zone !== '' ? "AND (a.province LIKE :zone OR a.municipality LIKE :zone)" : '';
        $sql = "
            SELECT DISTINCT ur.user_id AS owner_id
            FROM accommodations a
            JOIN user_resources ur
                ON a.id = ur.resource_id
               AND ur.resource_type = 'accommodation'
               AND ur.role = 'owner'
            WHERE ur.user_id IS NOT NULL
              AND ur.user_id != :tid
              {$statusFilter}
              {$zoneWhere}
        ";
        $params = [':tid' => $touristId];
        if ($zone !== '') $params[':zone'] = '%' . $zone . '%';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $owners = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Si no hay con zona, buscar sin zona
        if (empty($owners) && $zone !== '') {
            $stmt2 = $pdo->prepare("
                SELECT DISTINCT ur.user_id AS owner_id
                FROM user_resources ur
                WHERE ur.user_id IS NOT NULL AND ur.user_id != :tid {$statusFilter}
                LIMIT 5
            ");
            $stmt2->execute([':tid' => $touristId]);
            $owners = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    // Estrategia 2: owner_user_id directo en accommodations
    if (empty($owners) && $hasOwnerCol) {
        $zoneWhere = $zone !== '' ? "AND (a.province LIKE :zone OR a.municipality LIKE :zone)" : '';
        $sql = "
            SELECT DISTINCT a.owner_user_id AS owner_id
            FROM accommodations a
            WHERE a.owner_user_id IS NOT NULL AND a.owner_user_id != :tid
              {$statusFilter} {$zoneWhere}
        ";
        $params = [':tid' => $touristId];
        if ($zone !== '') $params[':zone'] = '%' . $zone . '%';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $owners = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($owners) && $zone !== '') {
            $stmt2 = $pdo->prepare("
                SELECT DISTINCT a.owner_user_id AS owner_id
                FROM accommodations a
                WHERE a.owner_user_id IS NOT NULL AND a.owner_user_id != :tid {$statusFilter}
                LIMIT 5
            ");
            $stmt2->execute([':tid' => $touristId]);
            $owners = $stmt2->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    // ── Sin propietarios: guardar pendiente ───────────────────────────────
    if (empty($owners)) {
        // Guardar como conversación "sin destinatario" en la tabla conversations
        // usando provider_id = NULL y entity_type = 'inquiry'
        try {
            $pdo->prepare("
                INSERT INTO conversations
                    (user_1_id, entity_type, entity_id, provider_id, status, last_message_at, created_at, updated_at, resource_type, resource_id)
                VALUES
                    ($touristId, 'inquiry', 0, NULL, 'open', NOW(), NOW(), NOW(), 'accommodation', 0)
            ")->execute();
            $convId = (int)$pdo->lastInsertId();

            $pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, {$contentCol}, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ")->execute([$convId, $touristId, $message]);
        } catch (PDOException $ex) {
            error_log("submit_inquiry fallback conv: " . $ex->getMessage());
        }

        jsonSuccess([
            'conversations_created' => 0,
            'pending_inquiry' => true,
            'zone' => $zone,
            'message' => $zone
                ? "No encontramos alojamientos con propietario registrado en '{$zone}'. Tu consulta se ha guardado y te contactaremos."
                : "Tu consulta se ha guardado. En breve te contactaremos.",
        ], 'Consulta guardada');
    }

    // ── Crear conversación con cada propietario ───────────────────────────
    $conversationsCreated = [];
    $errors = [];

    foreach ($owners as $ownerId) {
        $ownerId = (int)$ownerId;

        if ($ownerId <= 0 || $ownerId === $touristId) continue;

        try {
            // ¿Ya existe una conversación genérica (sin entity_id) entre este turista y este propietario?
            $checkStmt = $pdo->prepare("
                SELECT id FROM conversations
                WHERE user_1_id = ?
                  AND provider_id = ?
                  AND entity_type = 'inquiry'
                LIMIT 1
            ");
            $checkStmt->execute([$touristId, $ownerId]);
            $existingConvId = $checkStmt->fetchColumn();

            if ($existingConvId) {
                $convId = (int)$existingConvId;
                $pdo->prepare("UPDATE conversations SET last_message_at = NOW(), status = 'open', updated_at = NOW() WHERE id = ?")->execute([$convId]);
            } else {
                $pdo->prepare("
                    INSERT INTO conversations
                        (user_1_id, entity_type, entity_id, provider_id, status, last_message_at, created_at, updated_at)
                    VALUES (?, 'inquiry', 0, ?, 'open', NOW(), NOW(), NOW())
                ")->execute([$touristId, $ownerId]);
                $convId = (int)$pdo->lastInsertId();
            }

            // Insertar mensaje
            $pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, {$contentCol}, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ")->execute([$convId, $touristId, $message]);
            
            $conversationsCreated[] = ['conv_id' => $convId, 'owner_id' => $ownerId];

        } catch (PDOException $ex) {
            $errors[] = "Owner {$ownerId}: " . $ex->getMessage();
            error_log("submit_inquiry owner {$ownerId}: " . $ex->getMessage());
        }
    }

    $n = count($conversationsCreated);
    jsonSuccess([
        'conversations_created' => $n,
        'conversations'         => $conversationsCreated,
        'owners_notified'       => $n,
        'zone'                  => $zone,
        'errors'                => $errors,
    ], $n === 1
        ? 'Mensaje enviado a 1 alojamiento'
        : "Mensaje enviado a {$n} alojamientos");

} catch (PDOException $e) {
    error_log('submit_inquiry.php PDO fatal: ' . $e->getMessage());
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('submit_inquiry.php fatal: ' . $e->getMessage());
    jsonError('Error inesperado: ' . $e->getMessage(), 500);
}
