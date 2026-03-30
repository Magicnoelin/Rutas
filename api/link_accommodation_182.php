<?php
/**
 * API to link accommodation ID 182 to user nuevoaloja1@gmail.com
 * This can be called via web browser
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();

    // 1. Find user ID for nuevoaloja1@gmail.com
    $stmtUser = $pdo->prepare("SELECT id, first_name, last_name, user_type FROM users WHERE email = ?");
    $stmtUser->execute(['nuevoaloja1@gmail.com']);
    $user = $stmtUser->fetch();

    if (!$user) {
        jsonError('Usuario nuevoaloja1@gmail.com no encontrado', 404);
    }

    $userId = $user['id'];

    // 2. Check if accommodation ID 182 exists
    $stmtAccom = $pdo->prepare("SELECT id, name, created_by FROM accommodations WHERE id = ?");
    $stmtAccom->execute([182]);
    $accommodation = $stmtAccom->fetch();

    if (!$accommodation) {
        jsonError('Alojamiento con ID 182 no encontrado', 404);
    }

    // 3. Check if already linked in user_resources
    $stmtCheck = $pdo->prepare("
        SELECT id, status FROM user_resources
        WHERE user_id = ? AND resource_type = 'accommodation' AND resource_id = ?
    ");
    $stmtCheck->execute([$userId, 182]);
    $existingLink = $stmtCheck->fetch();

    if ($existingLink) {
        jsonSuccess([
            'message' => 'Ya existe vinculación',
            'link_id' => $existingLink['id'],
            'status' => $existingLink['status'],
            'user_id' => $userId,
            'user_name' => $user['first_name'] . ' ' . $user['last_name'],
            'user_email' => 'nuevoaloja1@gmail.com',
            'accommodation_id' => 182,
            'accommodation_name' => $accommodation['name'],
            'steps_completed' => [
                'user_found' => true,
                'accommodation_found' => true,
                'created_by_updated' => false,
                'user_resources_link_created' => false,
                'resource_stats_initialized' => false,
                'verification_successful' => true
            ]
        ], 'El alojamiento ya está vinculado al usuario');
    }

    // 4. Update created_by field if not set
    if (empty($accommodation['created_by'])) {
        $stmtUpdate = $pdo->prepare("UPDATE accommodations SET created_by = ? WHERE id = ?");
        $stmtUpdate->execute([$userId, 182]);
    }

    // 5. Create link in user_resources table
    $stmtInsert = $pdo->prepare("
        INSERT INTO user_resources (user_id, resource_type, resource_id, role, status)
        VALUES (?, 'accommodation', ?, 'owner', 'active')
    ");
    $stmtInsert->execute([$userId, 182]);
    $linkId = $pdo->lastInsertId();

    // 6. Create resource stats if not exists
    $stmtStats = $pdo->prepare("
        INSERT IGNORE INTO resource_stats (resource_type, resource_id, views_count, interests_count, messages_count, favorites_count)
        VALUES ('accommodation', ?, 0, 0, 0, 0)
    ");
    $stmtStats->execute([182]);

    // 7. Verify the link
    $stmtVerify = $pdo->prepare("
        SELECT ur.id, ur.status, a.name as accommodation_name
        FROM user_resources ur
        JOIN accommodations a ON ur.resource_id = a.id
        WHERE ur.user_id = ? AND ur.resource_type = 'accommodation' AND ur.resource_id = ?
    ");
    $stmtVerify->execute([$userId, 182]);
    $verification = $stmtVerify->fetch();

    if (!$verification) {
        jsonError('No se pudo verificar la vinculación', 500);
    }

    jsonSuccess([
        'message' => 'Vinculación completada con éxito',
        'link_id' => $linkId,
        'user_id' => $userId,
        'user_name' => $user['first_name'] . ' ' . $user['last_name'],
        'user_email' => 'nuevoaloja1@gmail.com',
        'accommodation_id' => 182,
        'accommodation_name' => $verification['accommodation_name'],
        'status' => $verification['status'],
        'steps_completed' => [
            'user_found' => true,
            'accommodation_found' => true,
            'created_by_updated' => empty($accommodation['created_by']),
            'user_resources_link_created' => true,
            'resource_stats_initialized' => true,
            'verification_successful' => true
        ]
    ], 'Alojamiento vinculado correctamente');

} catch (PDOException $e) {
    error_log('link_accommodation_182.php Error: ' . $e->getMessage());
    jsonError('Error al vincular alojamiento: ' . $e->getMessage(), 500);
}