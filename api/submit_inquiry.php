<?php
/**
 * API: Enviar Consulta de Alojamiento
 * El turista envía una consulta libre → se crea una conversación real
 * con cada alojamiento relevante de la zona que tenga propietario vinculado.
 * POST /api/submit_inquiry.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Requiere autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

$touristId = (int)$_SESSION['user_id'];

// Leer JSON body
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
$extra    = isset($body['extra'])     ? trim($body['extra'])     : '';

if (empty($message)) {
    jsonError('El mensaje no puede estar vacío', 400);
}

try {
    $pdo = getDBConnection();

    // ── 1. Detectar estructura real de las tablas (NO las creamos aquí, las crea chat.php) ──
    $convExists = $pdo->query("SHOW TABLES LIKE 'conversations'")->rowCount() > 0;
    $msgExists  = $pdo->query("SHOW TABLES LIKE 'messages'")->rowCount() > 0;

    if (!$convExists || !$msgExists) {
        // Si no existen, inicializarlas mediante una llamada interna a chat.php
        // o crearlas con la estructura correcta (con FK)
        $pdo->exec("CREATE TABLE IF NOT EXISTS conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_1_id INT NOT NULL,
            user_2_id INT NOT NULL,
            last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_1_id), INDEX (user_2_id), INDEX (last_message_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            sender_id INT NOT NULL,
            content TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (conversation_id), INDEX (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // ── 2. Detectar nombre real de columnas ─────────────────────────────────
    $convCols = $pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_COLUMN);
    $user2Col = in_array('user_2_id', $convCols) ? 'user_2_id' :
                (in_array('provider_id', $convCols) ? 'provider_id' : null);

    if (!$user2Col) {
        jsonError('Tabla conversations sin columna user_2_id ni provider_id', 500);
    }

    $msgCols    = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN);
    $contentCol = in_array('content', $msgCols) ? 'content' :
                  (in_array('message_text', $msgCols) ? 'message_text' : 'content');

    // ── 3. Detectar estructura de accommodations ────────────────────────────
    $accCols   = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_COLUMN);
    $hasStatus = in_array('status', $accCols);
    $hasOwnerCol = in_array('owner_user_id', $accCols);
    $hasUserResources = $pdo->query("SHOW TABLES LIKE 'user_resources'")->rowCount() > 0;

    // ── 4. Construir filtro de zona ─────────────────────────────────────────
    $statusFilter = $hasStatus
        ? "AND (a.status NOT IN ('deleted','spam') OR a.status IS NULL)"
        : '';

    $owners = [];

    if ($zone !== '') {
        // Buscar con zona
        if ($hasUserResources) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT ur.user_id AS owner_id
                FROM accommodations a
                JOIN user_resources ur
                    ON a.id = ur.resource_id
                   AND ur.resource_type = 'accommodation'
                   AND ur.role = 'owner'
                WHERE ur.user_id IS NOT NULL
                  AND ur.user_id != :tid
                  {$statusFilter}
                  AND (a.province LIKE :zone OR a.municipality LIKE :zone OR a.name LIKE :zone)
                LIMIT 10
            ");
            $stmt->execute([':tid' => $touristId, ':zone' => '%' . $zone . '%']);
        } elseif ($hasOwnerCol) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT a.owner_user_id AS owner_id
                FROM accommodations a
                WHERE a.owner_user_id IS NOT NULL
                  AND a.owner_user_id != :tid
                  {$statusFilter}
                  AND (a.province LIKE :zone OR a.municipality LIKE :zone OR a.name LIKE :zone)
                LIMIT 10
            ");
            $stmt->execute([':tid' => $touristId, ':zone' => '%' . $zone . '%']);
        }

        if (isset($stmt)) {
            $owners = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    // Si no hay resultados con zona (o no se indicó zona), buscar sin filtro de zona
    if (empty($owners)) {
        if ($hasUserResources) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT ur.user_id AS owner_id
                FROM accommodations a
                JOIN user_resources ur
                    ON a.id = ur.resource_id
                   AND ur.resource_type = 'accommodation'
                   AND ur.role = 'owner'
                WHERE ur.user_id IS NOT NULL
                  AND ur.user_id != :tid
                  {$statusFilter}
                LIMIT 5
            ");
            $stmt->execute([':tid' => $touristId]);
        } elseif ($hasOwnerCol) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT a.owner_user_id AS owner_id
                FROM accommodations a
                WHERE a.owner_user_id IS NOT NULL
                  AND a.owner_user_id != :tid
                  {$statusFilter}
                LIMIT 5
            ");
            $stmt->execute([':tid' => $touristId]);
        }

        if (isset($stmt)) {
            $owners = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    // ── 5. Crear conversación + mensaje por cada propietario ────────────────
    $conversationsCreated = [];
    $errors = [];

    foreach ($owners as $ownerId) {
        $ownerId = (int)$ownerId;
        if ($ownerId <= 0 || $ownerId === $touristId) continue;

        try {
            // ¿Ya existe conversación entre estos dos?
            $checkStmt = $pdo->prepare("
                SELECT id FROM conversations
                WHERE (user_1_id = ? AND {$user2Col} = ?)
                   OR (user_1_id = ? AND {$user2Col} = ?)
                LIMIT 1
            ");
            $checkStmt->execute([$touristId, $ownerId, $ownerId, $touristId]);
            $existingConvId = $checkStmt->fetchColumn();

            if ($existingConvId) {
                $convId = (int)$existingConvId;
                $pdo->exec("UPDATE conversations SET last_message_at = NOW() WHERE id = {$convId}");
            } else {
                $pdo->exec("INSERT INTO conversations (user_1_id, {$user2Col}, last_message_at, created_at)
                            VALUES ({$touristId}, {$ownerId}, NOW(), NOW())");
                $convId = (int)$pdo->lastInsertId();
            }

            // Insertar mensaje
            $msgStmt = $pdo->prepare("
                INSERT INTO messages (conversation_id, sender_id, {$contentCol}, is_read, created_at)
                VALUES (?, ?, ?, 0, NOW())
            ");
            $msgStmt->execute([$convId, $touristId, $message]);

            $conversationsCreated[] = $convId;

        } catch (PDOException $ex) {
            $errors[] = "Owner {$ownerId}: " . $ex->getMessage();
            error_log("submit_inquiry.php - owner {$ownerId}: " . $ex->getMessage());
        }
    }

    // ── 6. Si no había propietarios, guardar como consulta pendiente ─────────
    if (empty($owners)) {
        // Intentar crear tabla pending_inquiries si no existe
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS pending_inquiries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tourist_id INT NOT NULL,
                zone VARCHAR(255),
                persons INT DEFAULT 2,
                check_in DATE,
                check_out DATE,
                message TEXT NOT NULL,
                extra TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $inqStmt = $pdo->prepare("INSERT INTO pending_inquiries
                (tourist_id, zone, persons, check_in, check_out, message, extra, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $inqStmt->execute([
                $touristId,
                $zone ?: null,
                $persons,
                $checkIn  ?: null,
                $checkOut ?: null,
                $message,
                $extra ?: null,
            ]);
        } catch (PDOException $ex) {
            error_log("submit_inquiry pending_inquiries: " . $ex->getMessage());
        }

        jsonSuccess([
            'conversations_created' => 0,
            'pending_inquiry' => true,
            'zone' => $zone,
            'debug' => [
                'has_user_resources' => $hasUserResources,
                'has_owner_col'      => $hasOwnerCol,
                'user2_col'          => $user2Col,
            ],
            'message' => $zone
                ? "No encontramos alojamientos con propietario registrado en '{$zone}'. Tu consulta se ha guardado."
                : "No hay alojamientos con propietario registrado aún. Tu consulta se ha guardado.",
        ], 'Consulta guardada');
    }

    // ── 7. Respuesta exitosa ─────────────────────────────────────────────────
    jsonSuccess([
        'conversations_created' => count($conversationsCreated),
        'conversation_ids'      => $conversationsCreated,
        'owners_notified'       => count($conversationsCreated),
        'zone'                  => $zone,
        'errors'                => $errors,
    ], count($conversationsCreated) === 1
        ? 'Mensaje enviado a 1 alojamiento'
        : 'Mensaje enviado a ' . count($conversationsCreated) . ' alojamientos');

} catch (PDOException $e) {
    error_log('submit_inquiry.php PDO fatal: ' . $e->getMessage());
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('submit_inquiry.php fatal: ' . $e->getMessage());
    jsonError('Error inesperado: ' . $e->getMessage(), 500);
}
