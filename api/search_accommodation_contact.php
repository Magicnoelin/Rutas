<?php
/**
 * API: Buscar Alojamiento para Contacto
 * Devuelve alojamientos con su owner_user_id para iniciar chat
 * GET /api/search_accommodation_contact.php?query=...&province=...&limit=...
 */

// Sesión ANTES de require_once (que ya envía headers de Content-Type)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Requiere autenticación
if (!isset($_SESSION['user_id'])) {
    jsonError('Debes iniciar sesión', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Método no permitido', 405);
}

$query    = isset($_GET['query'])    ? sanitizeInput($_GET['query'])    : '';
$province = isset($_GET['province']) ? sanitizeInput($_GET['province']) : '';
// Limit ya validado como entero — se inserta directamente en el SQL
$limit    = isset($_GET['limit'])    ? max(1, min(20, (int)$_GET['limit'])) : 10;

try {
    $pdo = getDBConnection();

    // ── 1. ¿Existe tabla user_resources? ──────────────────────────────────
    $hasUserResources = false;
    try {
        $hasUserResources = $pdo->query("SHOW TABLES LIKE 'user_resources'")->rowCount() > 0;
    } catch (Exception $e) { /* ignorar */ }

    // ── 2. ¿Existe columna status en accommodations? ───────────────────────
    $hasStatusColumn = false;
    try {
        $hasStatusColumn = $pdo->query("SHOW COLUMNS FROM accommodations LIKE 'status'")->rowCount() > 0;
    } catch (Exception $e) { /* ignorar */ }

    // ── 3. Construir WHERE ─────────────────────────────────────────────────
    $conditions = [];
    $params     = [];

    if ($hasStatusColumn) {
        $conditions[] = "(a.status NOT IN ('deleted', 'spam') OR a.status IS NULL)";
    }

    if ($query !== '') {
        $conditions[] = "(a.name LIKE :query OR a.municipality LIKE :query OR a.province LIKE :query OR a.slug LIKE :query)";
        $params[':query'] = '%' . $query . '%';
    }

    if ($province !== '') {
        $conditions[] = "a.province LIKE :province";
        $params[':province'] = '%' . $province . '%';
    }

    $whereClause = empty($conditions) ? '1=1' : implode(' AND ', $conditions);

    // ── 4. Elegir SQL según estructura de BD ───────────────────────────────
    // $limit ya es un entero seguro → se puede incrustar directamente
    if ($hasUserResources) {
        $sql = "
            SELECT
                a.id,
                a.name,
                a.slug,
                a.municipality,
                a.province,
                a.accommodation_type,
                a.price_per_night,
                a.max_guests,
                a.description,
                ur.user_id        AS owner_user_id,
                u.first_name      AS owner_first_name,
                u.last_name       AS owner_last_name
            FROM accommodations a
            LEFT JOIN user_resources ur
                   ON a.id = ur.resource_id
                  AND ur.resource_type = 'accommodation'
                  AND ur.role = 'owner'
            LEFT JOIN users u ON ur.user_id = u.id
            WHERE {$whereClause}
            ORDER BY a.name ASC
            LIMIT {$limit}
        ";
    } else {
        // ¿Tiene columna owner_user_id directa?
        $colsRaw   = $pdo->query("SHOW COLUMNS FROM accommodations")->fetchAll(PDO::FETCH_ASSOC);
        $colNames  = array_column($colsRaw, 'Field');

        if (in_array('owner_user_id', $colNames)) {
            $sql = "
                SELECT
                    a.id,
                    a.name,
                    a.slug,
                    a.municipality,
                    a.province,
                    a.accommodation_type,
                    a.price_per_night,
                    a.max_guests,
                    a.description,
                    a.owner_user_id,
                    u.first_name AS owner_first_name,
                    u.last_name  AS owner_last_name
                FROM accommodations a
                LEFT JOIN users u ON a.owner_user_id = u.id
                WHERE {$whereClause}
                ORDER BY a.name ASC
                LIMIT {$limit}
            ";
        } else {
            // Fallback sin propietario
            $sql = "
                SELECT
                    a.id,
                    a.name,
                    a.slug,
                    a.municipality,
                    a.province,
                    a.accommodation_type,
                    a.price_per_night,
                    a.max_guests,
                    a.description,
                    NULL AS owner_user_id,
                    NULL AS owner_first_name,
                    NULL AS owner_last_name
                FROM accommodations a
                WHERE {$whereClause}
                ORDER BY a.name ASC
                LIMIT {$limit}
            ";
        }
    }

    // ── 5. Ejecutar ────────────────────────────────────────────────────────
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ── 6. Formatear ───────────────────────────────────────────────────────
    $formatted = array_map(function ($row) {
        return [
            'id'                 => (int)$row['id'],
            'name'               => $row['name'],
            'slug'               => $row['slug'],
            'municipality'       => $row['municipality'] ?? '',
            'province'           => $row['province']     ?? '',
            'accommodation_type' => $row['accommodation_type'] ?? '',
            'price_per_night'    => !empty($row['price_per_night']) ? (float)$row['price_per_night'] : null,
            'max_guests'         => !empty($row['max_guests'])      ? (int)$row['max_guests']        : null,
            'description'        => !empty($row['description'])
                                        ? mb_substr($row['description'], 0, 120) . '...'
                                        : '',
            'owner_user_id'      => !empty($row['owner_user_id']) ? (int)$row['owner_user_id'] : null,
            'owner_name'         => trim(($row['owner_first_name'] ?? '') . ' ' . ($row['owner_last_name'] ?? '')) ?: null,
            'has_owner'          => !empty($row['owner_user_id']),
        ];
    }, $results);

    $msg = count($formatted) > 0 ? 'Alojamientos encontrados' : 'Sin resultados';
    jsonSuccess([
        'results' => $formatted,
        'count'   => count($formatted),
        'query'   => $query,
    ], $msg);

} catch (PDOException $e) {
    error_log('search_accommodation_contact.php PDO Error: ' . $e->getMessage());
    jsonError('Error en la búsqueda: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log('search_accommodation_contact.php Error: ' . $e->getMessage());
    jsonError('Error inesperado: ' . $e->getMessage(), 500);
}
