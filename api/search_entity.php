<?php
/**
 * API: Búsqueda de entidades para el gestor de fotos
 * GET /api/search_entity.php?q=texto&type=places_of_interest
 *
 * Busca en las 4 tablas principales.
 * Robusto: si una tabla falla o no tiene la columna esperada, continúa con las demás.
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$q    = trim($_GET['q'] ?? '');
$type = trim($_GET['type'] ?? 'all');

if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'data' => [], 'message' => 'Escribe al menos 2 caracteres']);
    exit;
}

$validTypes = ['accommodations', 'places_of_interest', 'cultural_events', 'activities', 'all'];
if (!in_array($type, $validTypes)) {
    $type = 'all';
}

try {
    $pdo = getDBConnection();
    $results = [];
    $searchTerm = '%' . $q . '%';

    // ── Helper: detectar columnas disponibles en una tabla ────────────────────
    function getTableColumns(PDO $pdo, string $table): array {
        static $cache = [];
        if (isset($cache[$table])) return $cache[$table];
        try {
            $rows = $pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
            $cache[$table] = $rows;
            return $rows;
        } catch (Exception $e) {
            return [];
        }
    }

    // ── Helper: construir SELECT seguro según columnas disponibles ────────────
    function buildSelect(array $cols, string $entityType, string $typeLabel, string $icon): string {
        $municipality = in_array('municipality', $cols) ? 'municipality' : "'' AS municipality_raw";
        $province     = in_array('province', $cols)     ? 'province'     : "'' AS province_raw";
        $slug         = in_array('slug', $cols)         ? 'slug'         : "'' AS slug_raw";

        // Foto de miniatura (intentar varias columnas comunes)
        $thumb = "'' AS thumb";
        foreach (['photo1','main_photo','photo','image','cover_photo','thumbnail'] as $col) {
            if (in_array($col, $cols)) {
                $thumb = "COALESCE($col, '') AS thumb";
                break;
            }
        }

        return "id, name, $municipality AS municipality, $province AS province, $slug AS slug,
                '$entityType' AS entity_type,
                '$typeLabel' AS type_label,
                '$icon' AS icon,
                $thumb";
    }

    // ── Helper: construir WHERE seguro ────────────────────────────────────────
    function buildWhere(array $cols): string {
        $conditions = ['name LIKE ?'];
        if (in_array('municipality', $cols)) $conditions[] = 'municipality LIKE ?';
        if (in_array('slug', $cols))         $conditions[] = 'slug LIKE ?';
        return '(' . implode(' OR ', $conditions) . ')';
    }

    // ── Helper: construir filtro de activos ───────────────────────────────────
    function buildActiveFilter(array $cols): string {
        if (in_array('is_active', $cols))  return 'AND is_active = 1';
        if (in_array('status', $cols))     return "AND status IN ('active','published','activo','publicado')";
        if (in_array('active', $cols))     return 'AND active = 1';
        return ''; // Sin filtro si no hay columna de estado
    }

    // ── Configuración de tablas ───────────────────────────────────────────────
    $tableConfig = [
        'accommodations'     => ['label' => 'Alojamiento',      'icon' => 'fa-bed'],
        'places_of_interest' => ['label' => 'Lugar de Interés', 'icon' => 'fa-map-marker-alt'],
        'cultural_events'    => ['label' => 'Evento Cultural',  'icon' => 'fa-calendar-alt'],
        'activities'         => ['table' => 'tourist_activities', 'label' => 'Actividad', 'icon' => 'fa-hiking'],
    ];

    foreach ($tableConfig as $entityType => $config) {
        // Filtrar por tipo si se especificó
        if ($type !== 'all' && $type !== $entityType) continue;
        
        // Usar el nombre real de la tabla (para 'activities' usa 'tourist_activities')
        $tableName = $config['table'] ?? $entityType;

        try {
            $cols         = getTableColumns($pdo, $tableName);
            if (empty($cols)) continue; // Tabla no existe

            $selectPart   = buildSelect($cols, $entityType, $config['label'], $config['icon']);
            $wherePart    = buildWhere($cols);
            $activePart   = buildActiveFilter($cols);

            // Construir parámetros según columnas disponibles
            $params = [$searchTerm]; // siempre name LIKE ?
            if (in_array('municipality', $cols)) $params[] = $searchTerm;
            if (in_array('slug', $cols))         $params[] = $searchTerm;

            $sql = "SELECT $selectPart
                    FROM `$tableName`
                    WHERE $wherePart $activePart
                    ORDER BY name ASC
                    LIMIT 10";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            $results = array_merge($results, $rows);

        } catch (Exception $e) {
            // Si una tabla falla, continuar con las demás
            error_log("search_entity.php error en tabla $tableName: " . $e->getMessage());
        }
    }

    // Ordenar por nombre y limitar
    usort($results, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
    $results = array_slice($results, 0, 20);

    echo json_encode([
        'success' => true,
        'data'    => array_values($results),
        'total'   => count($results),
        'query'   => $q,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error de búsqueda: ' . $e->getMessage()]);
}
