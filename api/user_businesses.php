<?php
/**
 * ============================================================
 * API Endpoint: Gestión de Negocios del Usuario
 * POST/GET /api/user_businesses.php
 * ============================================================
 *
 * Permite que UN usuario gestione MÚLTIPLES negocios/alojamientos
 * desde una sola cuenta.
 *
 * GET  ?user_id=X          → Lista todos los negocios del usuario
 * GET  ?id=X               → Detalle de un negocio concreto
 * POST action=create        → Crear nuevo negocio
 * POST action=update        → Actualizar negocio existente
 * POST action=link          → Vincular alojamiento existente a este usuario
 * POST action=set_primary   → Marcar un negocio como principal
 * ============================================================
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    jsonError('No autenticado. Inicia sesión para gestionar tus negocios.', 401);
}

$sessionUserId = (int) $_SESSION['user_id'];
$method        = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDBConnection();

    // ── GET ──────────────────────────────────────────────────
    if ($method === 'GET') {

        // Detalle de un negocio concreto
        if (isset($_GET['id'])) {
            $id = (int) $_GET['id'];
            $stmt = $pdo->prepare("
                SELECT ub.*, a.slug AS accommodation_slug, a.moderation_status
                FROM user_businesses ub
                LEFT JOIN accommodations a ON a.id = ub.accommodation_id
                WHERE ub.id = :id AND ub.user_id = :user_id
                LIMIT 1
            ");
            $stmt->execute([':id' => $id, ':user_id' => $sessionUserId]);
            $business = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$business) {
                jsonError('Negocio no encontrado o no tienes permiso para verlo.', 404);
            }

            jsonSuccess($business);
        }

        // Lista de todos los negocios del usuario
        $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : $sessionUserId;

        // Solo el propio usuario o un admin puede ver la lista
        if ($userId !== $sessionUserId && ($_SESSION['user_type'] ?? '') !== 'admin') {
            jsonError('No tienes permiso para ver los negocios de otro usuario.', 403);
        }

        $stmt = $pdo->prepare("
            SELECT
                ub.id,
                ub.business_name,
                ub.business_type,
                ub.business_email,
                ub.business_phone,
                ub.business_web,
                ub.municipality,
                ub.province,
                ub.accommodation_id,
                ub.is_primary,
                ub.status,
                ub.created_at,
                a.slug           AS accommodation_slug,
                a.moderation_status,
                a.is_active      AS accommodation_active,
                a.photo1         AS accommodation_photo
            FROM user_businesses ub
            LEFT JOIN accommodations a ON a.id = ub.accommodation_id
            WHERE ub.user_id = :user_id
            ORDER BY ub.is_primary DESC, ub.created_at ASC
        ");
        $stmt->execute([':user_id' => $userId]);
        $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'user_id'    => $userId,
            'total'      => count($businesses),
            'businesses' => $businesses,
        ]);
    }

    // ── POST ─────────────────────────────────────────────────
    if ($method === 'POST') {
        $input  = json_decode(file_get_contents('php://input'), true) ?? [];
        $action = sanitizeInput($input['action'] ?? '');

        // ── CREAR nuevo negocio ───────────────────────────────
        if ($action === 'create') {
            $businessName = sanitizeInput($input['business_name'] ?? '');
            if (empty($businessName)) {
                jsonError('El nombre del negocio es obligatorio.', 400);
            }

            $tiposValidos = ['alojamiento','promotor_eventos','actividad_cultural','artesania','restauracion','otro'];
            $businessType = in_array($input['business_type'] ?? '', $tiposValidos, true)
                ? $input['business_type']
                : 'alojamiento';

            // Si ya tiene negocios, el nuevo NO será primario por defecto
            $tieneNegocios = (int) $pdo->prepare("SELECT COUNT(*) FROM user_businesses WHERE user_id = ?")
                ->execute([$sessionUserId]) ? $pdo->query("SELECT COUNT(*) FROM user_businesses WHERE user_id = $sessionUserId")->fetchColumn() : 0;

            $stmt = $pdo->prepare("
                INSERT INTO user_businesses
                    (user_id, business_name, business_type, business_email, business_phone,
                     business_web, nif_cif, municipality, province, accommodation_id, is_primary, status)
                VALUES
                    (:user_id, :name, :type, :email, :phone,
                     :web, :nif, :muni, :prov, :accom_id, :primary, 'active')
            ");
            $stmt->execute([
                ':user_id'  => $sessionUserId,
                ':name'     => $businessName,
                ':type'     => $businessType,
                ':email'    => sanitizeInput($input['business_email'] ?? '') ?: null,
                ':phone'    => normalizePhone($input['business_phone'] ?? null) ?? null,
                ':web'      => sanitizeInput($input['business_web'] ?? '') ?: null,
                ':nif'      => sanitizeInput($input['nif_cif'] ?? '') ?: null,
                ':muni'     => sanitizeInput($input['municipality'] ?? '') ?: null,
                ':prov'     => sanitizeInput($input['province'] ?? '') ?: null,
                ':accom_id' => !empty($input['accommodation_id']) ? (int)$input['accommodation_id'] : null,
                ':primary'  => $tieneNegocios == 0 ? 1 : 0,
            ]);

            $newId = $pdo->lastInsertId();

            $stmtNew = $pdo->prepare("SELECT * FROM user_businesses WHERE id = ?");
            $stmtNew->execute([$newId]);
            $newBusiness = $stmtNew->fetch(PDO::FETCH_ASSOC);

            jsonSuccess($newBusiness, 'Negocio creado correctamente.');
        }

        // ── ACTUALIZAR negocio existente ──────────────────────
        if ($action === 'update') {
            $id = (int) ($input['id'] ?? 0);
            if (!$id) jsonError('ID de negocio requerido.', 400);

            // Verificar propiedad
            $owns = $pdo->prepare("SELECT id FROM user_businesses WHERE id = ? AND user_id = ?");
            $owns->execute([$id, $sessionUserId]);
            if (!$owns->fetch()) {
                jsonError('No tienes permiso para editar este negocio.', 403);
            }

            $tiposValidos = ['alojamiento','promotor_eventos','actividad_cultural','artesania','restauracion','otro'];
            $businessType = in_array($input['business_type'] ?? '', $tiposValidos, true)
                ? $input['business_type']
                : null;

            $campos = [];
            $params = [':id' => $id];

            if (!empty($input['business_name'])) {
                $campos[] = 'business_name = :name';
                $params[':name'] = sanitizeInput($input['business_name']);
            }
            if ($businessType) {
                $campos[] = 'business_type = :type';
                $params[':type'] = $businessType;
            }
            if (array_key_exists('business_email', $input)) {
                $campos[] = 'business_email = :email';
                $params[':email'] = sanitizeInput($input['business_email']) ?: null;
            }
            if (array_key_exists('business_phone', $input)) {
                $campos[] = 'business_phone = :phone';
                $params[':phone'] = normalizePhone($input['business_phone'] ?? null);
            }
            if (array_key_exists('business_web', $input)) {
                $campos[] = 'business_web = :web';
                $params[':web'] = sanitizeInput($input['business_web']) ?: null;
            }
            if (array_key_exists('municipality', $input)) {
                $campos[] = 'municipality = :muni';
                $params[':muni'] = sanitizeInput($input['municipality']) ?: null;
            }
            if (array_key_exists('province', $input)) {
                $campos[] = 'province = :prov';
                $params[':prov'] = sanitizeInput($input['province']) ?: null;
            }

            if (empty($campos)) {
                jsonError('No se proporcionaron campos a actualizar.', 400);
            }

            $sql = "UPDATE user_businesses SET " . implode(', ', $campos) . " WHERE id = :id";
            $pdo->prepare($sql)->execute($params);

            $stmtGet = $pdo->prepare("SELECT * FROM user_businesses WHERE id = ?");
            $stmtGet->execute([$id]);
            jsonSuccess($stmtGet->fetch(PDO::FETCH_ASSOC), 'Negocio actualizado correctamente.');
        }

        // ── VINCULAR alojamiento existente a este usuario ──────
        // Útil para fusionar usuarios (como Raúl Gradillas):
        // asociar accommodations.id al user correcto.
        if ($action === 'link') {
            $accommodationId = (int) ($input['accommodation_id'] ?? 0);
            if (!$accommodationId) jsonError('accommodation_id requerido.', 400);

            // Solo admin puede vincular alojamientos arbitrarios
            if (($_SESSION['user_type'] ?? '') !== 'admin') {
                jsonError('Solo un administrador puede vincular alojamientos.', 403);
            }

            // Obtener datos del alojamiento
            $stmtA = $pdo->prepare("SELECT id, name, email, phone, municipality, province FROM accommodations WHERE id = ?");
            $stmtA->execute([$accommodationId]);
            $accom = $stmtA->fetch(PDO::FETCH_ASSOC);
            if (!$accom) jsonError('Alojamiento no encontrado.', 404);

            $targetUserId = (int) ($input['user_id'] ?? $sessionUserId);

            // Actualizar created_by en accommodations
            $pdo->prepare("UPDATE accommodations SET created_by = ? WHERE id = ?")
                ->execute([$targetUserId, $accommodationId]);

            // Insertar o actualizar en user_businesses
            $existing = $pdo->prepare("SELECT id FROM user_businesses WHERE accommodation_id = ?");
            $existing->execute([$accommodationId]);
            $existingRow = $existing->fetch();

            if ($existingRow) {
                $pdo->prepare("UPDATE user_businesses SET user_id = ? WHERE accommodation_id = ?")
                    ->execute([$targetUserId, $accommodationId]);
            } else {
                $pdo->prepare("
                    INSERT INTO user_businesses (user_id, business_name, business_type, business_email, business_phone, municipality, province, accommodation_id, is_primary, status)
                    VALUES (?, ?, 'alojamiento', ?, ?, ?, ?, ?, 0, 'active')
                ")->execute([
                    $targetUserId,
                    $accom['name'],
                    $accom['email'],
                    $accom['phone'],
                    $accom['municipality'],
                    $accom['province'],
                    $accommodationId,
                ]);
            }

            jsonSuccess([
                'user_id'          => $targetUserId,
                'accommodation_id' => $accommodationId,
                'business_name'    => $accom['name'],
            ], 'Alojamiento vinculado al usuario correctamente.');
        }

        // ── MARCAR NEGOCIO COMO PRINCIPAL ─────────────────────
        if ($action === 'set_primary') {
            $id = (int) ($input['id'] ?? 0);
            if (!$id) jsonError('ID de negocio requerido.', 400);

            $owns = $pdo->prepare("SELECT id FROM user_businesses WHERE id = ? AND user_id = ?");
            $owns->execute([$id, $sessionUserId]);
            if (!$owns->fetch()) {
                jsonError('No tienes permiso sobre este negocio.', 403);
            }

            // Quitar is_primary de todos los negocios del usuario
            $pdo->prepare("UPDATE user_businesses SET is_primary = 0 WHERE user_id = ?")
                ->execute([$sessionUserId]);

            // Marcar el seleccionado como principal
            $pdo->prepare("UPDATE user_businesses SET is_primary = 1 WHERE id = ?")
                ->execute([$id]);

            jsonSuccess(['id' => $id], 'Negocio marcado como principal.');
        }

        jsonError('Acción no reconocida. Usa: create, update, link, set_primary.', 400);
    }

    jsonError('Método no permitido.', 405);

} catch (PDOException $e) {
    error_log('[user_businesses.php] Error: ' . $e->getMessage());
    jsonError('Error de base de datos. Inténtalo de nuevo.', 500);
}

// Importar normalizePhone desde user_normalizer si no está cargado
if (!function_exists('normalizePhone')) {
    require_once 'user_normalizer.php';
}
