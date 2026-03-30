<?php
/**
 * API Endpoint: Gestión de Roles de Usuario
 *
 * GET  /api/roles.php                          → Lista todos los roles (alias: list_roles, todos)
 * GET  /api/roles.php?action=mis_roles         → Roles del usuario autenticado
 * GET  /api/roles.php?action=user_roles&user_id=X → Roles de un usuario (admin)
 * POST /api/roles.php  { action: "assign",    role_slug: "turista" }
 * POST /api/roles.php  { action: "remove",    role_slug: "turista" }
 * POST /api/roles.php  { action: "set_roles", role_slugs: ["turista","alojamiento"] }
 * POST /api/roles.php  { action: "actualizar_mis_roles", roles: ["turista","alojamiento"] }
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list_roles';

try {
    $pdo = getDBConnection();

    // ── GET ──────────────────────────────────────────────────────────────────
    if ($method === 'GET') {

        // Catálogo completo de roles — acepta 'list_roles' y 'todos'
        if ($action === 'list_roles' || $action === 'todos') {
            $stmt = $pdo->query("SELECT id, nombre, slug, descripcion FROM roles ORDER BY id");
            jsonSuccess($stmt->fetchAll());
        }

        // Roles del usuario autenticado
        if ($action === 'mis_roles') {
            if (!isset($_SESSION['user_id'])) {
                jsonError('No autenticado', 401);
            }
            $stmt = $pdo->prepare("
                SELECT r.id, r.nombre, r.slug, r.descripcion, ru.assigned_at
                FROM roles r
                INNER JOIN role_user ru ON ru.role_id = r.id
                WHERE ru.user_id = ?
                ORDER BY r.id
            ");
            $stmt->execute([$_SESSION['user_id']]);
            jsonSuccess($stmt->fetchAll());
        }

        // Admin: roles de cualquier usuario
        if ($action === 'user_roles') {
            if (!isset($_SESSION['user_id'])) {
                jsonError('No autenticado', 401);
            }
            $targetUserId = intval($_GET['user_id'] ?? 0);
            if (!$targetUserId) {
                jsonError('user_id requerido', 400);
            }
            $stmt = $pdo->prepare("
                SELECT r.id, r.nombre, r.slug, ru.assigned_at
                FROM roles r
                INNER JOIN role_user ru ON ru.role_id = r.id
                WHERE ru.user_id = ?
                ORDER BY r.id
            ");
            $stmt->execute([$targetUserId]);
            jsonSuccess($stmt->fetchAll());
        }

        jsonError('Acción no válida', 400);
    }

    // ── POST ─────────────────────────────────────────────────────────────────
    if ($method === 'POST') {
        if (!isset($_SESSION['user_id'])) {
            jsonError('No autenticado', 401);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            jsonError('Datos JSON inválidos', 400);
        }

        $postAction   = sanitizeInput($input['action'] ?? '');
        $roleSlug     = sanitizeInput($input['role_slug'] ?? '');
        // Permite que un admin asigne roles a otro usuario
        $targetUserId = intval($input['user_id'] ?? $_SESSION['user_id']);

        // ── actualizar_mis_roles: reemplaza todos los roles del usuario ──────
        // Usado desde user-dashboard.html (modal de roles)
        // Body: { action: "actualizar_mis_roles", roles: ["turista","alojamiento"] }
        if ($postAction === 'actualizar_mis_roles') {
            $roleSlugs = $input['roles'] ?? [];
            if (!is_array($roleSlugs)) {
                jsonError('roles debe ser un array', 400);
            }

            // Asegurarse de que al menos hay un rol
            if (empty($roleSlugs)) {
                $roleSlugs = ['turista'];
            }

            // Obtener IDs de los roles indicados (solo los que existen en BD)
            $placeholders = implode(',', array_fill(0, count($roleSlugs), '?'));
            $stmtIds = $pdo->prepare("SELECT id FROM roles WHERE slug IN ($placeholders)");
            $stmtIds->execute($roleSlugs);
            $roleIds = array_column($stmtIds->fetchAll(), 'id');

            // Borrar roles actuales del usuario
            $pdo->prepare("DELETE FROM role_user WHERE user_id = ?")->execute([$targetUserId]);

            // Insertar nuevos roles
            if (!empty($roleIds)) {
                $stmtIns = $pdo->prepare("INSERT IGNORE INTO role_user (user_id, role_id) VALUES (?, ?)");
                foreach ($roleIds as $rid) {
                    $stmtIns->execute([$targetUserId, $rid]);
                }
            }

            syncUserType($pdo, $targetUserId);

            jsonSuccess(
                ['user_id' => $targetUserId, 'roles' => $roleSlugs],
                'Roles actualizados correctamente'
            );
        }

        // ── assign: añadir un rol ────────────────────────────────────────────
        if ($postAction === 'assign') {
            if (empty($roleSlug)) jsonError('role_slug es requerido', 400);

            $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE slug = ?");
            $stmtRole->execute([$roleSlug]);
            $role = $stmtRole->fetch();
            if (!$role) jsonError("Rol '$roleSlug' no existe", 404);

            $stmt = $pdo->prepare("INSERT IGNORE INTO role_user (user_id, role_id) VALUES (?, ?)");
            $stmt->execute([$targetUserId, $role['id']]);
            syncUserType($pdo, $targetUserId);

            jsonSuccess(['user_id' => $targetUserId, 'role_slug' => $roleSlug], 'Rol asignado correctamente');
        }

        // ── remove: quitar un rol ────────────────────────────────────────────
        if ($postAction === 'remove') {
            if (empty($roleSlug)) jsonError('role_slug es requerido', 400);

            $stmtRole = $pdo->prepare("SELECT id FROM roles WHERE slug = ?");
            $stmtRole->execute([$roleSlug]);
            $role = $stmtRole->fetch();
            if (!$role) jsonError("Rol '$roleSlug' no existe", 404);

            $stmt = $pdo->prepare("DELETE FROM role_user WHERE user_id = ? AND role_id = ?");
            $stmt->execute([$targetUserId, $role['id']]);
            syncUserType($pdo, $targetUserId);

            jsonSuccess(['user_id' => $targetUserId, 'role_slug' => $roleSlug], 'Rol eliminado correctamente');
        }

        // ── set_roles: reemplaza todos los roles (versión admin) ─────────────
        // Body: { action: "set_roles", role_slugs: ["turista","alojamiento"] }
        if ($postAction === 'set_roles') {
            $roleSlugs = $input['role_slugs'] ?? [];
            if (!is_array($roleSlugs)) {
                jsonError('role_slugs debe ser un array', 400);
            }

            if (!empty($roleSlugs)) {
                $placeholders = implode(',', array_fill(0, count($roleSlugs), '?'));
                $stmtIds = $pdo->prepare("SELECT id FROM roles WHERE slug IN ($placeholders)");
                $stmtIds->execute($roleSlugs);
                $roleIds = array_column($stmtIds->fetchAll(), 'id');
            } else {
                $roleIds = [];
            }

            $pdo->prepare("DELETE FROM role_user WHERE user_id = ?")->execute([$targetUserId]);

            if (!empty($roleIds)) {
                $stmtIns = $pdo->prepare("INSERT IGNORE INTO role_user (user_id, role_id) VALUES (?, ?)");
                foreach ($roleIds as $rid) {
                    $stmtIns->execute([$targetUserId, $rid]);
                }
            }

            syncUserType($pdo, $targetUserId);

            jsonSuccess(['user_id' => $targetUserId, 'roles' => $roleSlugs], 'Roles actualizados correctamente');
        }

        jsonError('Acción no válida. Usa: assign, remove, set_roles, actualizar_mis_roles', 400);
    }

    jsonError('Método no permitido', 405);

} catch (PDOException $e) {
    error_log('roles.php Error: ' . $e->getMessage());
    jsonError('Error de base de datos: ' . $e->getMessage(), 500);
}

/**
 * Mantiene la columna user_type de la tabla users sincronizada
 * con el primer rol asignado en role_user (compatibilidad legacy).
 */
function syncUserType(PDO $pdo, int $userId): void {
    $stmt = $pdo->prepare("
        SELECT r.slug
        FROM roles r
        INNER JOIN role_user ru ON ru.role_id = r.id
        WHERE ru.user_id = ?
        ORDER BY r.id
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    $newType = $row ? $row['slug'] : 'turista';

    $pdo->prepare("UPDATE users SET user_type = ? WHERE id = ?")
        ->execute([$newType, $userId]);
}
