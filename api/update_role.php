<?php
// api/update_role.php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    jsonError('No autorizado', 401);
}

try {
    $pdo = getDBConnection();
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $_SESSION['user_id'];
    $newRoles = $data['roles'] ?? [];

    $pdo->beginTransaction();

    // 1. Mapeo de tablas para proteger datos (Integridad)
    $proteccion = [
        'alojamiento' => 'accommodations',
        'actividad_cultural' => 'activities',
        'promotor_eventos' => 'cultural_events'
    ];

    // 2. Verificar si el usuario está intentando quitar un rol con datos creados
    $stmt = $pdo->prepare("SELECT r.slug FROM user_role_subscriptions urs JOIN roles r ON urs.role_id = r.id WHERE urs.user_id = ? AND urs.status = 'active'");
    $stmt->execute([$userId]);
    $rolesActuales = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($rolesActuales as $rolAntiguo) {
        if (!in_array($rolAntiguo, $newRoles) && isset($proteccion[$rolAntiguo])) {
            $tabla = $proteccion[$rolAntiguo];
            $check = $pdo->prepare("SELECT COUNT(*) FROM $tabla WHERE user_id = ?");
            $check->execute([$userId]);
            if ($check->fetchColumn() > 0) {
                $pdo->rollBack();
                jsonError("No puedes quitar el rol '$rolAntiguo' porque tienes registros en la tabla $tabla.", 403);
            }
        }
    }

    // 3. Límite de 2 gratuitos
    if (count($newRoles) > 2) {
        $pdo->rollBack();
        jsonError("El plan gratuito solo permite 2 roles activos.", 403);
    }

    // 4. Actualización limpia
    $pdo->prepare("DELETE FROM user_role_subscriptions WHERE user_id = ?")->execute([$userId]);
    $stmtInsert = $pdo->prepare("INSERT INTO user_role_subscriptions (user_id, role_id, plan_id, status) VALUES (?, (SELECT id FROM roles WHERE slug = ?), (SELECT id FROM membership_plans WHERE price_monthly = 0 LIMIT 1), 'active')");
    
    foreach ($newRoles as $slug) {
        $stmtInsert->execute([$userId, $slug]);
    }

    $pdo->commit();
    jsonSuccess([], "Roles actualizados");

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonError($e->getMessage(), 500);
}