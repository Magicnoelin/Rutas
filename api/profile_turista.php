<?php
/**
 * API Endpoint: Perfil de Turista
 * 
 * GET  /api/profile_turista.php              → Obtener perfil del usuario autenticado
 * GET  /api/profile_turista.php?user_id=X    → Obtener perfil de un usuario concreto
 * POST /api/profile_turista.php              → Crear o actualizar perfil
 *      Body: JSON con los campos del perfil
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado', 401);
}

$method = $_SERVER['REQUEST_METHOD'];

// Valores ENUM válidos
$presupuestosValidos   = ['bajo', 'medio', 'alto', 'sin_limite'];
$duracionesValidas     = ['fin_semana', 'puente', 'semana', 'mas_semana'];
$viajaConValidos       = ['solo', 'pareja', 'familia', 'amigos', 'grupo'];

try {
    $pdo = getDBConnection();

    // ── GET ──────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $userId = intval($_GET['user_id'] ?? $_SESSION['user_id']);

        $stmt = $pdo->prepare("
            SELECT 
                pt.*, 
                u.first_name, u.last_name, u.email, u.phone, u.user_type
            FROM profile_turistas pt
            INNER JOIN users u ON u.id = pt.user_id
            WHERE pt.user_id = ?
        ");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            // Intentar obtener preferencias legacy desde users.preferences_json
            $stmtUser = $pdo->prepare("
                SELECT id, first_name, last_name, email, phone, preferences_json
                FROM users WHERE id = ?
            ");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch();

            $legacyPrefs = [];
            if (!empty($user['preferences_json'])) {
                $legacyPrefs = json_decode($user['preferences_json'], true) ?? [];
            }

            jsonSuccess([
                'exists'          => false,
                'user_id'         => $userId,
                'avatar_url'      => null, // Añadido para consistencia
                'first_name'      => $user['first_name']  ?? '',
                'last_name'       => $user['last_name']   ?? '',
                'email'           => $user['email']        ?? '',
                'phone'           => $user['phone']        ?? '',
                'intereses_json'  => $legacyPrefs['interests'] ?? null,
                'presupuesto'     => $legacyPrefs['budget']    ?? null,
                'duracion_viaje'  => $legacyPrefs['duration']  ?? null,
            ], 'Perfil de turista no creado aún');
        }

        // Decodificar intereses_json si viene como string
        if (isset($profile['intereses_json']) && is_string($profile['intereses_json'])) {
            $profile['intereses_json'] = json_decode($profile['intereses_json'], true);
        }

        $profile['exists'] = true;
        jsonSuccess($profile);
    }

    // ── POST ─────────────────────────────────────────────────────────────────
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            jsonError('Datos JSON inválidos', 400);
        }

        $userId = intval($input['user_id'] ?? $_SESSION['user_id']);

        // Validar y preparar intereses_json
        $intereses = $input['intereses_json'] ?? $input['interests'] ?? null;
        if (is_array($intereses)) {
            $interesesJson = json_encode($intereses, JSON_UNESCAPED_UNICODE);
        } elseif (is_string($intereses) && !empty($intereses)) {
            // Verificar que es JSON válido
            json_decode($intereses);
            $interesesJson = (json_last_error() === JSON_ERROR_NONE) ? $intereses : null;
        } else {
            $interesesJson = null;
        }

        // Validar ENUMs
        $presupuesto  = sanitizeInput($input['presupuesto']  ?? $input['budget']   ?? '');
        $duracion     = sanitizeInput($input['duracion_viaje'] ?? $input['duration'] ?? '');
        $viajaConVal  = sanitizeInput($input['viaja_con']    ?? '');

        if (!empty($presupuesto) && !in_array($presupuesto, $presupuestosValidos)) {
            jsonError("Presupuesto inválido. Valores: " . implode(', ', $presupuestosValidos), 400);
        }
        if (!empty($duracion) && !in_array($duracion, $duracionesValidas)) {
            jsonError("Duración inválida. Valores: " . implode(', ', $duracionesValidas), 400);
        }
        if (!empty($viajaConVal) && !in_array($viajaConVal, $viajaConValidos)) {
            jsonError("viaja_con inválido. Valores: " . implode(', ', $viajaConValidos), 400);
        }

        $campos = [
            'avatar_url'       => sanitizeInput($input['avatar_url']       ?? ''),
            'intereses_json'   => $interesesJson,
            'presupuesto'      => $presupuesto  ?: null,
            'duracion_viaje'   => $duracion     ?: null,
            'viaja_con'        => $viajaConVal  ?: null,
            'provincia_origen' => sanitizeInput($input['provincia_origen'] ?? ''),
            'pais_origen'      => sanitizeInput($input['pais_origen']      ?? 'España'),
            'idioma_preferido' => sanitizeInput($input['idioma_preferido'] ?? 'es'),
            'notas'            => sanitizeInput($input['notas']            ?? ''),
        ];

        // Comprobar si ya existe el perfil
        $stmtCheck = $pdo->prepare("SELECT id FROM profile_turistas WHERE user_id = ?");
        $stmtCheck->execute([$userId]);
        $exists = $stmtCheck->fetch();

        if ($exists) {
            // UPDATE
            $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($campos)));
            $sql  = "UPDATE profile_turistas SET $sets WHERE user_id = :user_id";
            $campos['user_id'] = $userId;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($campos);
            $msg = 'Perfil de turista actualizado correctamente';
        } else {
            // INSERT
            $campos['user_id'] = $userId;
            $cols = implode(', ', array_keys($campos));
            $vals = implode(', ', array_map(fn($k) => ":$k", array_keys($campos)));
            $sql  = "INSERT INTO profile_turistas ($cols) VALUES ($vals)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($campos);

            // Asegurarse de que el usuario tiene el rol 'turista'
            assignRoleIfMissing($pdo, $userId, 'turista');
            $msg = 'Perfil de turista creado correctamente';
        }

        // Sincronizar también en users.preferences_json (compatibilidad legacy)
        if ($interesesJson !== null) {
            $legacyPrefs = [
                'interests' => json_decode($interesesJson, true),
                'budget'    => $presupuesto ?: null,
                'duration'  => $duracion    ?: null,
            ];
            $pdo->prepare("UPDATE users SET preferences_json = ? WHERE id = ?")
                ->execute([json_encode($legacyPrefs, JSON_UNESCAPED_UNICODE), $userId]);
        }

        // Devolver el perfil actualizado
        $stmtGet = $pdo->prepare("SELECT * FROM profile_turistas WHERE user_id = ?");
        $stmtGet->execute([$userId]);
        $result = $stmtGet->fetch();
        if ($result && is_string($result['intereses_json'])) {
            $result['intereses_json'] = json_decode($result['intereses_json'], true);
        }

        jsonSuccess($result, $msg);
    }

    jsonError('Método no permitido', 405);

} catch (PDOException $e) {
    error_log('profile_turista.php Error: ' . $e->getMessage());
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
}

/**
 * Asigna un rol al usuario si no lo tiene ya.
 */
function assignRoleIfMissing(PDO $pdo, int $userId, string $slug): void {
    $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE slug = ?");
    $stmtRole->execute([$slug]);
    $role = $stmtRole->fetch();
    if (!$role) return;

    $pdo->prepare("INSERT IGNORE INTO role_user (user_id, role_id) VALUES (?, ?)")
        ->execute([$userId, $role['id']]);

    // Sincronizar user_type (compatibilidad legacy)
    $stmtFirst = $pdo->prepare("
        SELECT r.slug FROM roles r
        INNER JOIN role_user ru ON ru.role_id = r.id
        WHERE ru.user_id = ? ORDER BY r.id LIMIT 1
    ");
    $stmtFirst->execute([$userId]);
    $first = $stmtFirst->fetch();
    if ($first) {
        $pdo->prepare("UPDATE users SET user_type = ? WHERE id = ?")
            ->execute([$first['slug'], $userId]);
    }
}
