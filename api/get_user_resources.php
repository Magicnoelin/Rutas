<?php
/**
 * API: Obtener recursos del usuario (alojamientos del gestor)
 * GET /api/get_user_resources.php
 * Requiere sesión activa
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado', 401);
}

try {
    $pdo = getDBConnection();
    $userId = (int)$_SESSION['user_id'];
    $accommodations = [];

    // Estrategia 1: tabla user_resources
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'user_resources'");
        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT a.id, a.name, a.municipality, a.province, 
                       a.accommodation_type, a.price_per_night, a.status
                FROM accommodations a
                JOIN user_resources ur ON a.id = ur.resource_id
                    AND ur.resource_type = 'accommodation'
                    AND ur.user_id = ?
                ORDER BY a.name ASC
            ");
            $stmt->execute([$userId]);
            $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) { /* ignorar */ }

    // Estrategia 2: columna owner_user_id
    if (empty($accommodations)) {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('owner_user_id', $cols)) {
                $stmt = $pdo->prepare("
                    SELECT id, name, municipality, province, accommodation_type, 
                           price_per_night, status
                    FROM accommodations
                    WHERE owner_user_id = ?
                    ORDER BY name ASC
                ");
                $stmt->execute([$userId]);
                $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) { /* ignorar */ }
    }

    // Estrategia 3: columna user_id
    if (empty($accommodations)) {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('user_id', $cols)) {
                $stmt = $pdo->prepare("
                    SELECT id, name, municipality, province, accommodation_type, 
                           price_per_night, status
                    FROM accommodations
                    WHERE user_id = ?
                    ORDER BY name ASC
                ");
                $stmt->execute([$userId]);
                $accommodations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) { /* ignorar */ }
    }

    jsonSuccess([
        'accommodations' => $accommodations,
        'count' => count($accommodations)
    ]);

} catch (PDOException $e) {
    error_log('get_user_resources.php Error: ' . $e->getMessage());
    jsonError('Error al obtener recursos', 500);
}
?>
