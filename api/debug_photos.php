<?php
/**
 * DEBUG: Muestra las primeras filas de entity_photos para ver qué columnas tienen datos
 * BORRAR DESPUÉS DE USAR
 */
require_once 'config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = getDBConnection();

    // Estructura de la tabla
    $cols = $pdo->query("DESCRIBE entity_photos")->fetchAll();

    // Primeras 10 filas
    $rows = $pdo->query("SELECT * FROM entity_photos ORDER BY id DESC LIMIT 10")->fetchAll();

    // Columnas que tienen datos (no nulas)
    $colsWithData = [];
    foreach ($rows as $row) {
        foreach ($row as $k => $v) {
            if (!empty($v)) $colsWithData[$k] = $v;
        }
    }

    echo json_encode([
        'table_columns' => array_column($cols, 'Field'),
        'total_rows'    => $pdo->query("SELECT COUNT(*) FROM entity_photos")->fetchColumn(),
        'sample_rows'   => $rows,
        'cols_with_data'=> array_keys($colsWithData),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
