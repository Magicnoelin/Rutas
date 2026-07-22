<?php
/**
 * API Endpoint: Actualizar fotos de un alojamiento existente
 * POST /api/actualizar_fotos_alojamiento.php
 * Body: JSON con { accommodation_id, photo1, photo2, photo3, photo4 }
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Método no permitido', 405);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';

    if (strpos($contentType, 'application/json') !== false) {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            jsonError('Error al decodificar JSON: ' . json_last_error_msg(), 400);
        }
    } else {
        $data = $_POST;
    }

    if (empty($data) || empty($data['accommodation_id'])) {
        jsonError('Se requiere accommodation_id', 400);
    }

    $accommodationId = intval($data['accommodation_id']);

    $pdo = getDBConnection();

    // Verificar que el alojamiento existe y pertenece al usuario (si está autenticado)
    if (isset($_SESSION['user_id'])) {
        $stmtCheck = $pdo->prepare("
            SELECT a.id FROM accommodations a
            INNER JOIN user_resources ur ON ur.resource_id = a.id AND ur.resource_type = 'accommodation'
            WHERE a.id = ? AND ur.user_id = ? AND ur.role = 'owner'
            LIMIT 1
        ");
        $stmtCheck->execute([$accommodationId, $_SESSION['user_id']]);
        if (!$stmtCheck->fetch()) {
            jsonError('Alojamiento no encontrado o sin permisos', 403);
        }
    } else {
        // Sin sesión, verificar solo que existe
        $stmtCheck = $pdo->prepare("SELECT id FROM accommodations WHERE id = ? LIMIT 1");
        $stmtCheck->execute([$accommodationId]);
        if (!$stmtCheck->fetch()) {
            jsonError('Alojamiento no encontrado', 404);
        }
    }

    // Preparar campos de fotos a actualizar
    $updates = [];
    $params = [];

    for ($i = 1; $i <= 4; $i++) {
        $key = "photo$i";
        $dataKey = "Foto$i";
        if (!empty($data[$dataKey])) {
            $updates[] = "$key = ?";
            $params[] = trim($data[$dataKey]);
        }
    }

    if (empty($updates)) {
        jsonError('No se proporcionaron URLs de fotos para actualizar', 400);
    }

    $params[] = $accommodationId;
    $sql = "UPDATE accommodations SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    error_log("actualizar_fotos_alojamiento.php - Fotos actualizadas para accommodation_id=$accommodationId");

    jsonSuccess(['accommodation_id' => $accommodationId, 'updated' => count($updates)], 'Fotos actualizadas correctamente');

} catch (PDOException $e) {
    error_log('actualizar_fotos_alojamiento.php - Error: ' . $e->getMessage());
    jsonError('Error al actualizar fotos: ' . $e->getMessage(), 500);
}
?>
