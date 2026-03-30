<?php
/**
 * API Endpoint: Perfil de Alojamiento
 * 
 * GET  /api/profile_alojamiento.php              → Obtener perfil del usuario autenticado
 * GET  /api/profile_alojamiento.php?user_id=X    → Obtener perfil de un usuario concreto
 * POST /api/profile_alojamiento.php              → Crear o actualizar perfil
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

try {
    $pdo = getDBConnection();

    // ── GET ──────────────────────────────────────────────────────────────────
    if ($method === 'GET') {
        $userId = intval($_GET['user_id'] ?? $_SESSION['user_id']);

        $stmt = $pdo->prepare("
            SELECT 
                pa.*,
                u.first_name, u.last_name, u.email, u.phone, u.user_type
            FROM profile_alojamientos pa
            INNER JOIN users u ON u.id = pa.user_id
            WHERE pa.user_id = ?
        ");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();

        if (!$profile) {
            // Devolver perfil vacío con datos básicos del usuario
            $stmtUser = $pdo->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch();

            jsonSuccess([
                'exists'     => false,
                'user_id'    => $userId,
                'first_name' => $user['first_name'] ?? '',
                'last_name'  => $user['last_name']  ?? '',
                'email'      => $user['email']       ?? '',
                'phone'      => $user['phone']       ?? '',
            ], 'Perfil de alojamiento no creado aún');
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

        // Campos permitidos para el perfil de alojamiento
        $campos = [
            'nif'                 => sanitizeInput($input['nif']                 ?? ''),
            'razon_social'        => sanitizeInput($input['razon_social']        ?? ''),
            'direccion'           => sanitizeInput($input['direccion']           ?? ''),
            'municipio'           => sanitizeInput($input['municipio']           ?? ''),
            'provincia'           => sanitizeInput($input['provincia']           ?? ''),
            'codigo_postal'       => sanitizeInput($input['codigo_postal']       ?? ''),
            'telefono_negocio'    => sanitizeInput($input['telefono_negocio']    ?? ''),
            'web'                 => sanitizeInput($input['web']                 ?? ''),
            'capacidad_total'     => intval($input['capacidad_total']            ?? 0),
            'num_alojamientos'    => intval($input['num_alojamientos']           ?? 0),
            'descripcion_negocio' => sanitizeInput($input['descripcion_negocio'] ?? ''),
            'logo_url'            => sanitizeInput($input['logo_url']            ?? ''),
        ];

        // Comprobar si ya existe el perfil
        $stmtCheck = $pdo->prepare("SELECT id FROM profile_alojamientos WHERE user_id = ?");
        $stmtCheck->execute([$userId]);
        $exists = $stmtCheck->fetch();

        if ($exists) {
            // UPDATE
            $sets = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($campos)));
            $sql  = "UPDATE profile_alojamientos SET $sets WHERE user_id = :user_id";
            $campos['user_id'] = $userId;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($campos);
            $msg = 'Perfil de alojamiento actualizado correctamente';
        } else {
            // INSERT
            $campos['user_id'] = $userId;
            $cols  = implode(', ', array_keys($campos));
            $vals  = implode(', ', array_map(fn($k) => ":$k", array_keys($campos)));
            $sql   = "INSERT INTO profile_alojamientos ($cols) VALUES ($vals)";
            $stmt  = $pdo->prepare($sql);
            $stmt->execute($campos);

            // Asegurarse de que el usuario tiene el rol 'alojamiento'
            assignRoleIfMissing($pdo, $userId, 'alojamiento');
            $msg = 'Perfil de alojamiento creado correctamente';
        }

        // Devolver el perfil actualizado
        $stmtGet = $pdo->prepare("SELECT * FROM profile_alojamientos WHERE user_id = ?");
        $stmtGet->execute([$userId]);
        jsonSuccess($stmtGet->fetch(), $msg);
    }

    jsonError('Método no permitido', 405);

} catch (PDOException $e) {
    error_log('profile_alojamiento.php Error: ' . $e->getMessage());
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
