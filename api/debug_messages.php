<?php
/**
 * DIAGNÓSTICO DEL SISTEMA DE MENSAJES
 * Acceder con sesión iniciada: /api/debug_messages.php
 * BORRAR DEL SERVIDOR TRAS DIAGNOSTICAR
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$out = [];
$userId = $_SESSION['user_id'] ?? null;
$out['session_user_id'] = $userId;

try {
    $pdo = getDBConnection();
    $out['db_connection'] = 'OK';

    // 1. ¿Existen las tablas?
    $out['tables'] = [
        'conversations' => $pdo->query("SHOW TABLES LIKE 'conversations'")->rowCount() > 0,
        'messages'      => $pdo->query("SHOW TABLES LIKE 'messages'")->rowCount() > 0,
        'user_resources'=> $pdo->query("SHOW TABLES LIKE 'user_resources'")->rowCount() > 0,
        'accommodations'=> $pdo->query("SHOW TABLES LIKE 'accommodations'")->rowCount() > 0,
    ];

    // 2. Columnas de conversations
    if ($out['tables']['conversations']) {
        $cols = $pdo->query("SHOW COLUMNS FROM conversations")->fetchAll(PDO::FETCH_COLUMN);
        $out['conversations_columns'] = $cols;

        $count = $pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
        $out['conversations_count'] = (int)$count;

        if ($userId) {
            $mine = $pdo->prepare("SELECT * FROM conversations WHERE user_1_id = ? OR user_2_id = ? ORDER BY last_message_at DESC LIMIT 10");
            $mine->execute([$userId, $userId]);
            $out['my_conversations'] = $mine->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // 3. Columnas de messages
    if ($out['tables']['messages']) {
        $cols = $pdo->query("SHOW COLUMNS FROM messages")->fetchAll(PDO::FETCH_COLUMN);
        $out['messages_columns'] = $cols;

        $count = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        $out['messages_count'] = (int)$count;
    }

    // 4. Columnas de accommodations
    if ($out['tables']['accommodations']) {
        $cols = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_COLUMN);
        $out['accommodations_columns'] = $cols;

        $hasOwner = in_array('owner_user_id', $cols);
        $out['has_owner_user_id'] = $hasOwner;

        if ($hasOwner) {
            $withOwner = $pdo->query("SELECT COUNT(*) FROM accommodations WHERE owner_user_id IS NOT NULL")->fetchColumn();
            $out['accommodations_with_owner'] = (int)$withOwner;

            $sample = $pdo->query("SELECT id, name, province, municipality, owner_user_id FROM accommodations WHERE owner_user_id IS NOT NULL LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            $out['sample_accommodations_with_owner'] = $sample;
        }
    }

    // 5. user_resources
    if ($out['tables']['user_resources']) {
        $cols = $pdo->query("SHOW COLUMNS FROM user_resources")->fetchAll(PDO::FETCH_COLUMN);
        $out['user_resources_columns'] = $cols;

        $accOwners = $pdo->query("SELECT COUNT(*) FROM user_resources WHERE resource_type = 'accommodation' AND role = 'owner'")->fetchColumn();
        $out['accommodation_owners_in_user_resources'] = (int)$accOwners;

        $sample = $pdo->query("
            SELECT ur.user_id, ur.resource_id, a.name, a.province
            FROM user_resources ur
            JOIN accommodations a ON a.id = ur.resource_id
            WHERE ur.resource_type = 'accommodation' AND ur.role = 'owner'
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
        $out['sample_ur_owners'] = $sample;
    }

    // 6. Columnas de users
    $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $out['users_columns'] = $userCols;
    if ($userId) {
        $me = $pdo->prepare("SELECT id, first_name, last_name, email, user_type FROM users WHERE id = ?");
        $me->execute([$userId]);
        $out['current_user'] = $me->fetch(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    $out['error'] = $e->getMessage();
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
