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

    $user = [];
    $roles = [];
    $resources = [
        'accommodation' => [],
        'activity' => [],
        'place' => [],
        'event' => []
    ];
    $summary = [
        'total_resources' => 0,
        'accommodations' => 0,
        'activities' => 0,
        'places' => 0,
        'events' => 0,
        'active_offers' => 0, // Placeholder, implementar si hay tabla de ofertas
        'unread_messages' => 0 // Placeholder, implementar si hay tabla de mensajes
    ];

    // 1. Fetch User Details
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, user_type, membership_type, membership_status FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        jsonError('Usuario no encontrado', 404);
    }

    // 2. Fetch User Roles
    $stmt = $pdo->prepare("SELECT r.slug FROM roles r JOIN role_user ru ON r.id = ru.role_id WHERE ru.user_id = ?");
    $stmt->execute([$userId]);
    $userRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($userRoles as $roleSlug) {
        $roles[$roleSlug] = true;
    }

    // 3. Fetch Resources

    // --- Accommodations ---
    // Nota: 'status' no existe como columna en accommodations, se omite
    // Se incluyen token_publico (para checking) e is_premium (para mostrar enlace de check-in)
    $accommodationColumns = ['id', 'name', 'slug', 'municipality', 'province', 'accommodation_type', 'price_per_night', 'photo1 AS photo', 'is_active', 'token_publico', 'is_premium'];
    $colsSql = implode(', ', array_map(fn($col) => "a.$col", $accommodationColumns));
    
    $tempAccommodations = [];
    // Strategy 1: user_resources table
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'user_resources'");
        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT $colsSql
                FROM accommodations a
                JOIN user_resources ur ON a.id = ur.resource_id
                    AND ur.resource_type = 'accommodation'
                    AND ur.user_id = ?
                ORDER BY a.name ASC
            ");
            $stmt->execute([$userId]);
            $tempAccommodations = array_merge($tempAccommodations, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    } catch (Exception $e) { /* ignorar */ }

    // Estrategia 2: columna owner_user_id
    if (empty($tempAccommodations)) { // Only try other strategies if user_resources didn't yield results
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('owner_user_id', $cols)) {
                $colsSql = implode(', ', $accommodationColumns); // No alias 'a' needed here
                $stmt = $pdo->prepare("
                    SELECT $colsSql
                    FROM accommodations
                    WHERE owner_user_id = ?
                    ORDER BY name ASC
                ");
                $stmt->execute([$userId]);
                $tempAccommodations = array_merge($tempAccommodations, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (Exception $e) { /* ignorar */ }
    }

    // Estrategia 3: columna user_id
    if (empty($tempAccommodations)) {
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('user_id', $cols)) {
                $colsSql = implode(', ', $accommodationColumns);
                $stmt = $pdo->prepare("
                    SELECT $colsSql
                    FROM accommodations
                    WHERE user_id = ?
                    ORDER BY name ASC
                ");
                $stmt->execute([$userId]);
                $tempAccommodations = array_merge($tempAccommodations, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (Exception $e) { /* ignorar */ }
    }
    
    // Obtener estadísticas reales desde resource_stats para cada alojamiento
    foreach ($tempAccommodations as &$res) {
        $views = 0; $favorites = 0; $messages = 0;
        try {
            $stmtS = $pdo->prepare("
                SELECT views_count, favorites_count, interests_count, messages_count
                FROM resource_stats
                WHERE resource_type = 'accommodation' AND resource_id = ?
                LIMIT 1
            ");
            $stmtS->execute([$res['id']]);
            $rowS = $stmtS->fetch(PDO::FETCH_ASSOC);
            if ($rowS) {
                $views     = (int)($rowS['views_count']     ?? 0);
                $favorites = (int)($rowS['favorites_count'] ?? 0);
                // interests_count = emails/consultas recibidas; messages_count = mensajes internos
                $messages  = (int)($rowS['interests_count'] ?? 0) + (int)($rowS['messages_count'] ?? 0);
            }
        } catch (Exception $e) { /* tabla no existe, dejar en 0 */ }
        $res['stats'] = [
            'views'     => $views,
            'favorites' => $favorites,
            'messages'  => $messages
        ];
    }
    $resources['accommodation'] = $tempAccommodations;
    $summary['accommodations'] = count($resources['accommodation']);
    $summary['total_resources'] += $summary['accommodations'];

    // --- Activities ---
    $activityColumns = ['id', 'name', 'slug', 'municipality', 'province', 'difficulty_level', 'price_adult', 'photo1 AS photo', 'is_active'];
    $colsSql = implode(', ', array_map(fn($col) => "a.$col", $activityColumns));
    
    $tempActivities = [];
    // Strategy 1: user_resources table
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'user_resources'");
        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT $colsSql
                FROM tourist_activities a
                JOIN user_resources ur ON a.id = ur.resource_id
                    AND ur.resource_type = 'activity'
                    AND ur.user_id = ?
                ORDER BY a.name ASC
            ");
            $stmt->execute([$userId]);
            $tempActivities = array_merge($tempActivities, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    } catch (Exception $e) { /* ignorar */ }

    // Strategy 2: created_by column (common for many resources)
    if (empty($tempActivities)) {
        try {
            $tableCols = $pdo->query("SHOW COLUMNS FROM tourist_activities")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('created_by', $tableCols)) {
                $colsSql = implode(', ', $activityColumns);
                $stmt = $pdo->prepare("
                    SELECT $colsSql
                    FROM tourist_activities
                    WHERE created_by = ?
                    ORDER BY name ASC
                ");
                $stmt->execute([$userId]);
                $tempActivities = array_merge($tempActivities, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (Exception $e) { /* ignorar */ }
    }
    
    // Obtener estadísticas reales desde resource_stats para cada actividad
    foreach ($tempActivities as &$res) {
        $views = 0; $favorites = 0; $messages = 0;
        try {
            $stmtS = $pdo->prepare("
                SELECT views_count, favorites_count, interests_count, messages_count
                FROM resource_stats
                WHERE resource_type = 'activity' AND resource_id = ?
                LIMIT 1
            ");
            $stmtS->execute([$res['id']]);
            $rowS = $stmtS->fetch(PDO::FETCH_ASSOC);
            if ($rowS) {
                $views     = (int)($rowS['views_count']     ?? 0);
                $favorites = (int)($rowS['favorites_count'] ?? 0);
                $messages  = (int)($rowS['interests_count'] ?? 0) + (int)($rowS['messages_count'] ?? 0);
            }
        } catch (Exception $e) { /* tabla no existe, dejar en 0 */ }
        $res['stats'] = [
            'views'     => $views,
            'favorites' => $favorites,
            'messages'  => $messages
        ];
    }
    $resources['activity'] = $tempActivities;
    $summary['activities'] = count($resources['activity']);
    $summary['total_resources'] += $summary['activities'];


    // --- Places of Interest ---
    $placeColumns = ['id', 'name', 'slug', 'municipality', 'province', 'category_id', 'photo1 AS photo', 'is_active'];
    $colsSql = implode(', ', array_map(fn($col) => "a.$col", $placeColumns));

    $tempPlaces = [];
    // Strategy 1: user_resources table
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'user_resources'");
        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT $colsSql
                FROM places_of_interest a
                JOIN user_resources ur ON a.id = ur.resource_id
                    AND ur.resource_type = 'place'
                    AND ur.user_id = ?
                ORDER BY a.name ASC
            ");
            $stmt->execute([$userId]);
            $tempPlaces = array_merge($tempPlaces, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    } catch (Exception $e) { /* ignorar */ }

    // Strategy 2: created_by column
    if (empty($tempPlaces)) {
        try {
            $tableCols = $pdo->query("SHOW COLUMNS FROM places_of_interest")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('created_by', $tableCols)) {
                $colsSql = implode(', ', $placeColumns);
                $stmt = $pdo->prepare("
                    SELECT $colsSql
                    FROM places_of_interest
                    WHERE created_by = ?
                    ORDER BY name ASC
                ");
                $stmt->execute([$userId]);
                $tempPlaces = array_merge($tempPlaces, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (Exception $e) { /* ignorar */ }
    }

    // Obtener estadísticas reales desde resource_stats para cada lugar
    foreach ($tempPlaces as &$res) {
        $views = 0; $favorites = 0; $messages = 0;
        try {
            $stmtS = $pdo->prepare("
                SELECT views_count, favorites_count, interests_count, messages_count
                FROM resource_stats
                WHERE resource_type = 'place' AND resource_id = ?
                LIMIT 1
            ");
            $stmtS->execute([$res['id']]);
            $rowS = $stmtS->fetch(PDO::FETCH_ASSOC);
            if ($rowS) {
                $views     = (int)($rowS['views_count']     ?? 0);
                $favorites = (int)($rowS['favorites_count'] ?? 0);
                $messages  = (int)($rowS['interests_count'] ?? 0) + (int)($rowS['messages_count'] ?? 0);
            }
        } catch (Exception $e) { /* tabla no existe, dejar en 0 */ }
        $res['stats'] = [
            'views'     => $views,
            'favorites' => $favorites,
            'messages'  => $messages
        ];
    }
    $resources['place'] = $tempPlaces;
    $summary['places'] = count($resources['place']);
    $summary['total_resources'] += $summary['places'];


    // --- Events ---
    $eventColumns = ['id', 'name', 'slug', 'municipality', 'province', 'start_date', 'end_date', 'photo1 AS photo', 'is_active'];
    $colsSql = implode(', ', array_map(fn($col) => "a.$col", $eventColumns));

    $tempEvents = [];
    // Strategy 1: user_resources table
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'user_resources'");
        if ($check->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT $colsSql
                FROM cultural_events a
                JOIN user_resources ur ON a.id = ur.resource_id
                    AND ur.resource_type = 'event'
                    AND ur.user_id = ?
                ORDER BY a.name ASC
            ");
            $stmt->execute([$userId]);
            $tempEvents = array_merge($tempEvents, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
    } catch (Exception $e) { /* ignorar */ }

    // Strategy 2: created_by column
    if (empty($tempEvents)) {
        try {
            $tableCols = $pdo->query("SHOW COLUMNS FROM cultural_events")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('created_by', $tableCols)) {
                $colsSql = implode(', ', $eventColumns);
                $stmt = $pdo->prepare("
                    SELECT $colsSql
                    FROM cultural_events
                    WHERE created_by = ?
                    ORDER BY name ASC
                ");
                $stmt->execute([$userId]);
                $tempEvents = array_merge($tempEvents, $stmt->fetchAll(PDO::FETCH_ASSOC));
            }
        } catch (Exception $e) { /* ignorar */ }
    }

    // Obtener estadísticas reales desde resource_stats para cada evento
    foreach ($tempEvents as &$res) {
        $views = 0; $favorites = 0; $messages = 0;
        try {
            $stmtS = $pdo->prepare("
                SELECT views_count, favorites_count, interests_count, messages_count
                FROM resource_stats
                WHERE resource_type = 'event' AND resource_id = ?
                LIMIT 1
            ");
            $stmtS->execute([$res['id']]);
            $rowS = $stmtS->fetch(PDO::FETCH_ASSOC);
            if ($rowS) {
                $views     = (int)($rowS['views_count']     ?? 0);
                $favorites = (int)($rowS['favorites_count'] ?? 0);
                $messages  = (int)($rowS['interests_count'] ?? 0) + (int)($rowS['messages_count'] ?? 0);
            }
        } catch (Exception $e) { /* tabla no existe, dejar en 0 */ }
        $res['stats'] = [
            'views'     => $views,
            'favorites' => $favorites,
            'messages'  => $messages
        ];
    }
    $resources['event'] = $tempEvents;
    $summary['events'] = count($resources['event']);
    $summary['total_resources'] += $summary['events'];


    // 4. Return the full structured data
    jsonSuccess([
        'user' => $user,
        'roles' => $roles,
        'resources' => $resources,
        'summary' => $summary
    ]);

} catch (PDOException $e) {
    error_log('get_user_resources.php Error: ' . $e->getMessage());
    jsonError('Error al obtener recursos', 500);
}
?>
